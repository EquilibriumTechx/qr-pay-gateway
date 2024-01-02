<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * QR Payment Gateway
 *
 * Provides an QR Payment Gateway.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		QrPayGateway
 * @extends		WC_Payment_Gateway
 * @since		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Equilibrium
 */

 add_action('plugins_loaded', 'qr_pay_gateway_init', 11);

 function qr_pay_gateway_init() {
    if ( class_exists( 'WC_Payment_Gateway' ) ) {
        class QrPayGateway extends WC_Payment_Gateway {
            /**
             * Constructor for the gateway.
             */
            public function __construct() {

                // Logo path, from plugin dir images
				$default_image_path = esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/images/duitnow-ewallet-logo.png');

				$this->id = 'qr_pay_gateway';

				// Get the value of the 'custom_selector' field
				$qr_type_selectors = $this->get_option('qr_type_selector');

				// Update $image_path based on the selected option
				if ($qr_type_selectors === 'option_1') {
					$this->icon = esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/images/duitnow-ewallet-logo.png');
				} else if ($qr_type_selectors === 'option_2') {
					$this->icon = esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/images/tng-ewallet-logo.png');
				} else if ($qr_type_selectors === 'option_3') {
					$this->icon = esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/images/grabpay-ewallet-logo.png');
				} else if ($qr_type_selectors === 'option_4') {
					$this->icon = esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/images/boost-ewallet-logo.png');
				} else if ($qr_type_selectors === 'option_5') {
					$this->icon = esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/images/shopeepay-ewallet-logo.png');
				} else {
					$this->icon = $default_image_path; // Set the default image path
				}
        
                $this->has_fields         = true;
                $this->method_title       = esc_html__( 'QR Payment', 'qr-pay-gateway' );
                $this->title              = esc_html__( 'QR Payment', 'qr-pay-gateway' );
                $this->method_description = esc_html__( 'This is a QR Payment', 'qr-pay-gateway' );
                $this->order_button_text  = esc_html__( 'Pay via QR Pay', 'qr-pay-gateway' );
                $this->supports = array(
					'products', // Simple products
					'subscriptions', // Subscriptions
					'subscription_amount_changes', // Subscription amount changes
					'subscription_date_changes', // Subscription date changes
					'subscription_cancellation', // Subscription cancellation
					'subscription_suspension', // Subscription suspension
					'subscription_reactivation', // Subscription reactivation
					'multiple_subscriptions', // Multiple subscriptions
					'variation_products', // Product variations
					'subscription_payment_method_change', // Subscription payment method change
					'subscription_switch', // Subscription switch
					'subscription_trash', // Subscription trash
					'subscription_uncancel', // Subscription uncancel
					'subscription_resubscribe', // Subscription resubscribe
					'subscription_renewal', // Subscription renewal
					'subscription_renewal_payment', // Subscription renewal payment
					'products_export', // Product export
					'products_import', // Product import
					'downloadable_products', // Downloadable products
				);
            
                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();
            
                // Define user set variables
                $this->title                   = esc_html( $this->get_option( 'title' ) );
                $this->description             = esc_html( $this->get_option( 'description' ) );
                $this->instructions            = esc_html( $this->get_option( 'instructions', $this->description ) );
                $this->order_status            = esc_html( $this->get_option( 'order_status', 'on-hold' ) );
                $this->upload_qr               = esc_html( $this->get_option( 'upload_qr'));
                $this->media                   = esc_html( $this->get_option( 'media', '' ) );
                $this->preview_qr              = esc_html( $this->get_option( 'preview_qr'));
                $this->account_name            = esc_html( $this->get_option( 'account_name'));
                $this->required_types          = esc_html( $this->get_option( 'required_types'));
                $this->qr_type_selector        = esc_html( $this->get_option( 'qr_type_selector', 'option_1' ) );
            
                // Actions
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            
                // Customer Emails
                add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            }
            
            /**
             * Initialize Gateway Settings Form Fields
             */
            public function init_form_fields() {
                $order_statuses = array();
                $wc_order_statuses = wc_get_order_statuses();
                foreach ($wc_order_statuses as $key => $value) {
                    $order_statuses[$key] = $value;
                }
            
                $this->form_fields = apply_filters( 'qr_pay_gateway_form_fields', array(
                    'enabled' => array(
                        'title'   => esc_html__( 'Enable/Disable', 'qr-pay-gateway' ),
                        'type'    => 'checkbox',
                        'label'   => esc_html__( 'Enable QR Payment method', 'qr-pay-gateway' ),
                        'default' => 'no'
                    ),
            
                    'title' => array(
                        'title'       => esc_html__( 'Title', 'qr-pay-gateway' ),
                        'type'        => 'text',
                        'description' => esc_html__( 'This controls the title for the payment method the customer sees during checkout.', 'qr-pay-gateway' ),
                        'default'     => esc_html__( 'QR Payment', 'qr-pay-gateway' ),
                        'desc_tip'    => true,
                    ),
            
                    'order_status' => array(
                        'title'       => esc_html__( 'Order Status', 'qr-pay-gateway' ),
                        'type'        => 'select',
                        'class'       => 'wc-enhanced-select',
                        'description' => esc_html__( 'Choose the status you wish after checkout.', 'qr-pay-gateway' ),
                        'default'     => 'wc-on-hold',
                        'desc_tip'    => true,
                        'options'     => $order_statuses,
                    ),
            
                    'description' => array(
                        'title'       => esc_html__( 'Description', 'qr-pay-gateway' ),
                        'type'        => 'textarea',
                        'description' => esc_html__( 'Payment method description that the customer will see on your checkout.', 'qr-pay-gateway' ),
                        'default'     => esc_html__( 'Please fill in the required fields and upload your proof of payment', 'qr-pay-gateway' ),
                        'desc_tip'    => true,
                    ),
            
                    'instructions' => array(
                        'title'       => esc_html__( 'Instructions', 'qr-pay-gateway' ),
                        'type'        => 'textarea',
                        'description' => esc_html__( 'Instructions that will be added to the thank you page and emails.', 'qr-pay-gateway' ),
                        'default'     => '',
                        'desc_tip'    => true,
                    ),
            
                    'upload_qr' => array(
						'title'       => esc_html__( 'Select (QR) Image', 'qr-pay-gateway' ),
						'type'        => 'button',
						'class'		  => 'qr_pay_upload_image_button button-secondary',
						'label'		  => 'Select (QR) Image',		
						'description' => esc_html__( 'Upload your QR Code image.', 'qr-pay-gateway' ),
						'desc_tip'    => true,
					),

                    'media' => array(
                        'title'       => esc_html__( 'Media (URL)', 'qr-pay-gateway' ),
                        'type'        => 'media',
                        'description' => esc_html__( 'Enter Path or use selector for automation', 'qr-pay-gateway' ),
                        'default'     => '',
						'class'		  => 'qr_media_upload_url',
                        'desc_tip'    => true,
                    ),

					'preview_qr' => array(
						'title'       => '',
						'type'        => 'hidden',
						'class'		  => 'qr_pay_preview_qr',
					),
					
					'account_name' => array(
                        'title'       => esc_html__( 'Account Name', 'qr-pay-gateway' ),
                        'type'        => 'text',
                        'description' => esc_html__( 'Enter the QR account holders name', 'qr-pay-gateway' ),
                        'default'     => esc_html__( 'ABC Sdn Bhd', 'qr-pay-gateway' ),
                        'desc_tip'    => true,
                    ),
					
					'required_types' => array(
						'title'       => esc_html__( 'Required Transaction ID', 'qr-pay-gateway' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Enable Required Transaction ID', 'qr-pay-gateway' ),
						'description' => esc_html__( 'Enter the QR account holder\'s name', 'qr-pay-gateway' ),
						'default'     => 'no',
						'desc_tip'    => true,
					),
					
                    'qr_type_selector' => array(
                        'title'       => esc_html__( 'QR Payment Type', 'qr-pay-gateway' ),
                        'type'        => 'select',
                        'class'       => 'wc-enhanced-select',
                        'description' => esc_html__( 'Select a QR Payment Type will show which icon on the front end.', 'qr-pay-gateway' ),
                        'default'     => 'option_1',
                        'desc_tip'    => true,
                        'options'     => array(
                            'option_1' => __( 'DuitNow E-Wallet', 'qr-pay-gateway' ),
                            'option_2' => __( 'Touch N Go E-Wallet', 'qr-pay-gateway' ),
                            'option_3' => __( 'Grab E-Wallet', 'qr-pay-gateway' ),
                            'option_4' => __( 'Boost E-Wallet', 'qr-pay-gateway' ),
                            'option_5' => __( 'ShopeePay E-Wallet', 'qr-pay-gateway' ),
                            ),
                    ),
                ) );
            }

            /**
             * Upload Qr Code Button
             */
			public function generate_button_html( $key, $data ) {
				$field    = $this->plugin_id . $this->id . '_' . $key;
				$defaults = array(
					'class'             => 'button-secondary',
					'css'               => '',
					'custom_attributes' => array(),
					'desc_tip'          => false,
					'description'       => '',
					'title'             => '',
				);

				$data = wp_parse_args( $data, $defaults );

				ob_start();
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
						<?php echo $this->get_tooltip_html( $data ); ?>
					</th>
					<td class="forminp">
						<fieldset>
							<div class="upload_area woocommerce-qr-pay-gateway-upload-wrapper">
								<span><?php echo __( 'Image Selector', 'qr-pay-gateway' ); ?></span>
								<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $field ); ?>"><?php echo __( 'QR Image Preview', 'qr-pay-gateway' ); ?></label>
						<?php echo $this->get_tooltip_html( $data ); ?>
					</th>
					<td class="forminp qr-pay-preview-area">
						<fieldset>
							<div class="preview_area">
								<?php
								$options = get_option( 'woocommerce_qr_pay_gateway_settings' );
								if( isset( $options['preview_qr'] ) && !empty( $options['preview_qr'] ) ){ ?>
								<?php $media_path_url = $options['preview_qr'] ?>
								
									<img src="<?php echo $options['preview_qr'] ?>" class="upload_qr">
									<button class="remove_qr button-secondary" type="button"><?php echo __( 'Delete (QR) Image', 'qr-pay-gateway' ); ?></button>
								<?php echo $this->get_description_html( $data );
								 }	?>
							</div>
						</fieldset>
					</td>
				</tr>
				<?php
				return ob_get_clean();
			}


            /**
             * Output for the order received page.
             */
            public function thankyou_page() {
                if ( $this->instructions ) {
                    echo wpautop( wptexturize( esc_html( $this->instructions ) ) );
                }
                echo '<p>' . esc_html__('Thank you for choosing QR Payment.', 'qr-pay-gateway') . '</p>';
            }
            /**
             * Add content to the WC emails.
             *
             * @access public
             * @param WC_Order $order
             * @param bool $sent_to_admin
             * @param bool $plain_text
             */
            public function email_instructions($order, $sent_to_admin, $plain_text = false) {
				if ($this->instructions && !$sent_to_admin && $order->get_payment_method() === 'qr_pay_gateway') {
					$extra_text = esc_html__('Thank you for choosing QR Payment.', 'qr-pay-gateway');
					$separator = $plain_text ? "\n" : '<br>';
					$email_instructions = wpautop(wptexturize($this->instructions)) . $separator . wpautop(wptexturize($extra_text));

					if ($plain_text) {
						echo wp_strip_all_tags($email_instructions) . PHP_EOL;
					} else {
						echo $email_instructions;
					}
				}
			}
                       
            //Display the payment field sections at checkout 
            public function payment_fields(){
                if ( $description = $this->get_description() ) {
                    echo wpautop( wptexturize( esc_html( $description ) ) );
                }
            
                $media_url = $this->get_option('media');
				if ($media_url) {
					$media_id = attachment_url_to_postid(esc_url($media_url));
					$media = wp_get_attachment_image_src($media_id, 'full');
					if ($media) {
							$alt_text = esc_attr(get_post_meta($media_id, '_wp_attachment_image_alt', true));

							// Set the timezone to GMT+8 (Asia/Kuala_Lumpur)
							date_default_timezone_set('Asia/Kuala_Lumpur');

							// Get the Qr-Type value for the order
							$this->id = 'qr_pay_gateway';	
							$qr_type_value = $this->get_option('qr_type_selector');
						
							// Define the options and corresponding labels
							$qr_type_options = array(
								'option_1' => __( 'DuitNow E-Wallet', 'qr-pay-gateway' ),
								'option_2' => __( 'Touch N Go E-Wallet', 'qr-pay-gateway' ),
								'option_3' => __( 'Grab E-Wallet', 'qr-pay-gateway' ),
								'option_4' => __( 'Boost E-Wallet', 'qr-pay-gateway' ),
								'option_5' => __( 'ShopeePay E-Wallet', 'qr-pay-gateway' ),
							);
							
							// Get the corresponding label
							$qr_type_label = isset($qr_type_options[$qr_type_value]) ? $qr_type_options[$qr_type_value] : '';

							// Replace spaces with dashes in the label
							$qr_type_label_slug = str_replace(' ', '-', $qr_type_label);

							// Generate a random 8-digit number
							$random_number = str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
							// Generate a filename based on today's date, time, Qr-Type, and order ID
							$filename = date('Ymd-') . $random_number  . '-TYPE-' . sanitize_file_name($qr_type_label_slug) . '-QR.jpg';
							$account_holder_name = $this->get_option('account_name');
							// Output the image with a hidden download link
							echo '<div class="custom-payment-image-container">';
							echo '<h3>' . $account_holder_name . '</h3>';
							echo '<img class="custom-payment-image" src="' . esc_url($media[0]) . '" alt="' . $alt_text . '">';
							echo '<a class="qr-image-download-button" href="' . esc_url($media[0]) . '" download="' . esc_attr($filename) . '">Download '. $qr_type_label .' QR </a>';
							echo '</div>';
						}
					}
                ?>
                <div id="custom_input" class="custom-payment-input">
                    <p class="form-row form-row-wide">
                        <label for="full_pay_name" class="custom-pay-name"><?php esc_html_e('Payment Full Name', 'qr-pay-gateway'); ?><span class="requiredqr">*</span></label>
                        <input type="text" class="custom-pay-name-input" name="essb_full_pay_name" id="essb_full_pay_name" placeholder=" Veronica Lee" value="" required/>
                    </p>
                    <p class="form-row form-row-wide">
                        <label for="mobile" class="custom-mobile-number"><?php esc_html_e('Mobile Number', 'qr-pay-gateway'); ?><span class="requiredqr">*</span></label>
                        <input type="text" class="custom-mobile-number-input" name="essb_mobile" id="essb_mobile" placeholder="+60-14-315-4949" value="" required/>
                    </p>

					
					<?php
						$required_types_cons = $this->get_option('required_types');
				
						if ($required_types_cons == 'yes') {
							// If $required_types_cons is 'Yes'
							?>
							<p class="form-row form-row-wide">
								<label for="transaction" class="custom-mobile-number"><?php esc_html_e('Transaction ID', 'qr-pay-gateway'); ?><span class="requiredqr">*</span></label>
								<input type="text" class="custom-mobile-number-input" name="essb_transaction" id="essb_transaction" placeholder="ABCD1234567EFGH789" value=""/>
							</p>
							<?php
						} else {
							// If $required_types_cons is not 'Yes'
							?>
							<p class="form-row form-row-wide">
								<label for="transaction" class="custom-mobile-number"><?php esc_html_e('Transaction ID', 'qr-pay-gateway'); ?><span class="optionalqr"> (Optional)</span></label>
								<input type="text" class="custom-mobile-number-input" name="essb_transaction" id="essb_transaction" placeholder="ABCD1234567EFGH789" value=""/>
							</p>
							<?php
						}
					?>
					<!-- Hidden input for transaction_type -->
					<input type="hidden" name="essb_transaction_type" value="<?php echo esc_attr($qr_type_label); ?>"/>
                </div>
                <?php
            }
            
            /**
             * Process the payment and return the result
             *
             * @param int $order_id
             * @return array
             */
            public function process_payment( $order_id ) {
                $order = wc_get_order( $order_id );
            
                // Sanitize the order status
                $status = 'wc-' === substr( sanitize_text_field( $this->order_status ), 0, 3 ) ? substr( sanitize_text_field( $this->order_status ), 3 ) : sanitize_text_field( $this->order_status );
            
                // Set order status
                $order->update_status( $status, esc_html__( 'Checkout with QR Payment. ', 'qr-pay-gateway' ) );
            
                // Reduce stock levels
                //$order->reduce_order_stock();
                wc_reduce_stock_levels($order->get_id());
            
                // Remove cart
                WC()->cart->empty_cart();
            
                // Return thankyou redirect
                return array(
                    'result'    => 'success',
                    'redirect'  => esc_url( $this->get_return_url( $order ) )
                );
            }

        } // end \WC_QR_PAY class

        if( OrderUtil::custom_orders_table_usage_is_enabled() ) {
            //HPOS Enabled
            require_once(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/php/essb-payment-validation.php');

            // require order meta updates
            require_once(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/php/essb-order-meta-update-hpos.php');

            // require admin order edits display
            require_once(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/php/essb-admin-display-hpos.php'); 
        } else {
            //CPT Enabled
            // require the payment validation process
            require_once(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/php/essb-payment-validation.php');

            // require order meta updates
            require_once(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/php/essb-order-meta-update.php');

            // require admin order edits display
            require_once(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/php/essb-admin-display.php');
        }
    }
}
?>