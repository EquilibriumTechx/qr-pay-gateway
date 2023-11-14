<?php
/*
 * Plugin Name: QR Payments Gateway
 * Plugin URI: https://www.equilibrium.my/qr-pay-gateway/
 * Description: QR Payments For Woocommerce Payment Gateway For Touch N Go, DuitNow, Grab, Shopee Pay, Boost
 * Version: 1.1.4
 * Requires at least: 6.3
 * Requires PHP: 8.0
 * Author: Equilibrium Solution M Sdn. Bhd.
 * Author URI: https://www.equilibrium.my
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: qr-pay-gateway
 * Domain Path: /languages
 *
 * Copyright: c 2018-2023 Equilibrium Solution M Sdn. Bhd.
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   qr_pay_gateway
 * @author    Equilibrium
 * @category  Admin
 * @copyright Copyright 2018-2023 Equilibrium Solution M Sdn. Bhd.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * 
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */

function qr_pay_gateway_add_to_gateways( $gateways ) {
    $s_core_gateway = 'QrPayGateway';
    $gateways[] = esc_attr( $s_core_gateway );
    return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'qr_pay_gateway_add_to_gateways' );

/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function qr_pay_gateway_plugin_links( $links ) {
    $plugin_links = array(
        '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=qrpaygateway' ) ) . '">' . esc_html__( 'Configure', 'qr-pay-gateway' ) . '</a>'
    );

    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( esc_attr( __FILE__ ) ), 'qr_pay_gateway_plugin_links' );

/**
 * QR Payment Gateway
 *
 * Provides an QR Payment Gateway.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		QrPayGateway
 * @extends		WC_Payment_Gateway
 * @version		1.1.4
 * @package		WooCommerce/Classes/Payment
 * @author 		Equilibrium
 */
add_action( 'plugins_loaded', 'qr_pay_gateway_init', 11 );

function qr_pay_gateway_init() {

	class QrPayGateway extends WC_Payment_Gateway {
		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			// Logo path, from plugin dir images
			$default_image_path = esc_url( plugin_dir_url( __FILE__ ) . 'images/duitnow-ewallet-logo.png' );
		
			$this->id				 = 'qr_pay_gateway';
			
			// Get the value of the 'custom_selector' field
			$qr_type_selectors = $this->get_option('qr_type_selector');

			// Update $image_path based on the selected option
			if ($qr_type_selectors === 'option_1') {
				$this->icon = esc_url(plugin_dir_url(__FILE__) . 'images/duitnow-ewallet-logo.png');
			} else if ($qr_type_selectors === 'option_2') {
				$this->icon = esc_url(plugin_dir_url(__FILE__) . 'images/tng-ewallet-logo.png');
			} else if ($qr_type_selectors === 'option_3') {
				$this->icon = esc_url(plugin_dir_url(__FILE__) . 'images/grabpay-ewallet-logo.png');
			} else if ($qr_type_selectors === 'option_4') {
				$this->icon = esc_url(plugin_dir_url(__FILE__) . 'images/boost-ewallet-logo.png');
			} else if ($qr_type_selectors === 'option_5') {
				$this->icon = esc_url(plugin_dir_url(__FILE__) . 'images/shopeepay-ewallet-logo.png');
			} else {
				$this->icon = $default_image_path; // Set the default image path
			}

			$this->has_fields         = true;
			$this->method_title       = esc_html__( 'QR Payment', 'qr-pay-gateway' );
			$this->title              = esc_html__( 'QR Payment', 'qr-pay-gateway' );
			$this->method_description = esc_html__( 'This is a QR Payment', 'qr-pay-gateway' );
			$this->order_button_text  = esc_html( 'Pay via QR Pay', 'qr-pay-gateway' );
			$this->supports = array(
				'products',
				'subscriptions',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'multiple_subscriptions',
			);
		
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		
			// Define user set variables
			$this->title        = esc_html( $this->get_option( 'title' ) );
			$this->description  = esc_html( $this->get_option( 'description' ) );
			$this->instructions = esc_html( $this->get_option( 'instructions', $this->description ) );
			$this->order_status = esc_html( $this->get_option( 'order_status', 'on-hold' ) );
			$this->media        = esc_html( $this->get_option( 'media', '' ) );
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
					'default'     => esc_html__( 'Please remit payment to Store Name upon pickup or delivery.', 'qr-pay-gateway' ),
					'desc_tip'    => true,
				),
		
				'instructions' => array(
					'title'       => esc_html__( 'Instructions', 'qr-pay-gateway' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'Instructions that will be added to the thank you page and emails.', 'qr-pay-gateway' ),
					'default'     => '',
					'desc_tip'    => true,
				),
		
				'media' => array(
					'title'       => esc_html__( 'Media(URL)', 'qr-pay-gateway' ),
					'type'        => 'media',
					'description' => esc_html__( 'Add an image URL related to this payment method.', 'qr-pay-gateway' ),
					'default'     => '',
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
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( esc_html( $this->instructions ) ) );
			}
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
			if ($this->instructions && ! $sent_to_admin && $order->get_payment_method() === 'qr_pay_gateway' && $order->has_status('on-hold')) {
				$email_instructions = wpautop(wptexturize( esc_html( $this->instructions ) ));
				
				if ($plain_text) {
					echo wp_strip_all_tags( $email_instructions ) . PHP_EOL;
				} else {
					echo $email_instructions;
				}
			}
		}
		public function payment_fields(){
			if ( $description = $this->get_description() ) {
				echo wpautop( wptexturize( esc_html( $description ) ) );
			}
		
			$media_url = $this->get_option( 'media' );
			if ( $media_url ) {
				$media_id = attachment_url_to_postid( esc_url( $media_url ) );
				$media = wp_get_attachment_image_src( $media_id, 'full' );
				if ( $media ) {
					$alt_text = esc_attr( get_post_meta( $media_id, '_wp_attachment_image_alt', true ) );
					echo '<img width="200" height="200" style="margin-left: 25%; margin-right: auto;" src="' . esc_url( $media[0] ) . '" alt="' . $alt_text . '">';
				}
			}
			?>
			<div id="custom_input">
				<p class="form-row form-row-wide">
					<label for="full_pay_name" class=""><?php esc_html_e('Payment Full Name', 'qr-pay-gateway'); ?></label>
					<input type="text" class="" name="full_pay_name" id="full_pay_name" placeholder=" Veronica Lee" value="" required/>
				</p>
				<p class="form-row form-row-wide">
					<label for="mobile" class=""><?php esc_html_e('Mobile Number', 'qr-pay-gateway'); ?></label>
					<input type="text" class="" name="mobile" id="mobile" placeholder="+60-14-315-4949" value="" required/>
				</p>
				<p class="form-row form-row-wide">
					<label for="transaction" class=""><?php esc_html_e('Transaction ID', 'qr-pay-gateway'); ?></label>
					<input type="text" class="" name="transaction" id="transaction" placeholder="ABCD1234567EFGH789" value="" required/>
				</p>
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
			$order->reduce_order_stock();
		
			// Remove cart
			WC()->cart->empty_cart();
		
			// Return thankyou redirect
			return array(
				'result'    => 'success',
				'redirect'  => esc_url( $this->get_return_url( $order ) )
			);
		}
  } // end \WC_QR_PAY class
}

// Trigger Woocommerce Default Error if the fields are empty and validate the mobile field to only have digits, dash and plus symbols
add_action('woocommerce_checkout_process', 'qr_pay_gateway_process_custom_payment');

function qr_pay_gateway_process_custom_payment() {
    if ($_POST['payment_method'] !== 'qr_pay_gateway') {
        return;
    }

    if (!isset($_POST['full_pay_name']) || empty($_POST['full_pay_name'])) {
        wc_add_notice(esc_html__('Payment Full Name is a required field.', 'qr-pay-gateway'), 'error');
    }

    if (!isset($_POST['mobile']) || empty($_POST['mobile'])) {
        wc_add_notice(esc_html__('Mobile Number is a required field.', 'qr-pay-gateway'), 'error');
    } else {
        $mobile = sanitize_text_field($_POST['mobile']);
        if (!preg_match('/^[\d\+\-]+$/', $mobile)) {
            wc_add_notice(esc_html__('Please enter a valid mobile number. Only digits, plus, and dash are allowed.', 'qr-pay-gateway'), 'error');
        }
    }

    if (!isset($_POST['transaction']) || empty($_POST['transaction'])) {
        wc_add_notice(esc_html__('Transaction ID is a required field.', 'qr-pay-gateway'), 'error');
    }
}

/**
 * Sanitized, Escaped, and Validated and Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'qr_pay_gateway_update_order_meta' );
function qr_pay_gateway_update_order_meta( $order_id ) {
    if ( isset( $_POST['payment_method'] ) && $_POST['payment_method'] !== 'qr_pay_gateway' ) {
        return;
    }

    // Sanitize and save the data
    if ( isset( $_POST['full_pay_name'] ) ) {
        $full_pay_name = wc_clean( wp_unslash( $_POST['full_pay_name'] ) );
        update_post_meta( $order_id, 'full_pay_name', $full_pay_name );
    }

    // Validate and sanitize the mobile number
    if ( isset( $_POST['mobile'] ) ) {
        $mobile = wc_clean( wp_unslash( $_POST['mobile'] ) );
        $mobile = preg_replace('/[^\d\-\+]/', '', $mobile); // Retains only digits, dash, and plus
        update_post_meta( $order_id, 'mobile', $mobile );
    }

    if ( isset( $_POST['transaction'] ) ) {
        $transaction = wc_clean( wp_unslash( $_POST['transaction'] ) );
        update_post_meta( $order_id, 'transaction', $transaction );
    }
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'qr_pay_gateway_checkout_field_display_admin_order_meta', 10, 1 );
function qr_pay_gateway_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->get_id(), '_payment_method', true );
    if($method !== 'qr_pay_gateway') {
        return;
    }

    $full_pay_name = get_post_meta( $order->get_id(), 'full_pay_name', true );
    $mobile = get_post_meta( $order->get_id(), 'mobile', true );
    $transaction = get_post_meta( $order->get_id(), 'transaction', true );

    echo '<h4>' . esc_html__( 'Payment By QR Payment Details' ) . '</h4>';
    echo '<p><strong>' . esc_html__( 'Payment Full Name', 'qr-pay-gateway' ) . ':</strong> ' . esc_html($full_pay_name) . '</p>';
    echo '<p><strong>' . esc_html__( 'Mobile Number', 'qr-pay-gateway' ) . ':</strong> ' . esc_html($mobile) . '</p>';
    echo '<p><strong>' . esc_html__( 'Transaction ID', 'qr-pay-gateway' ) . ':</strong> ' . esc_html($transaction) . '</p>';
}

//Triggers Uninstall action
function qr_pay_gateway_uninstall_plugin( $plugin ) {
	$file = plugin_basename( $plugin );

	$qr_pay_gateway_uninstallable_plugins = (array) get_option( 'uninstall_plugins' );

	/**
	 * Fires in uninstall_plugin() immediately before the plugin is uninstalled.
	 *
	 * @since 1.0.0
	 * @param string $plugin                Path to the plugin file relative to the plugins directory.
	 * @param array  $qr_pay_gateway_uninstallable_plugins Uninstallable plugins.
	 */
	do_action( 'pre_uninstall_plugin', $plugin, $qr_pay_gateway_uninstallable_plugins );

	if ( file_exists( WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php' ) ) {
		if ( isset( $qr_pay_gateway_uninstallable_plugins[ $file ] ) ) {
			unset( $qr_pay_gateway_uninstallable_plugins[ $file ] );
			update_option( 'uninstall_plugins', $qr_pay_gateway_uninstallable_plugins );
		}
		unset( $qr_pay_gateway_uninstallable_plugins );

		define( 'WP_UNINSTALL_PLUGIN', $file );

		wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $file );
		include_once WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php';

		return true;
	}

	if ( isset( $qr_pay_gateway_uninstallable_plugins[ $file ] ) ) {
		$callable = $qr_pay_gateway_uninstallable_plugins[ $file ];
		unset( $qr_pay_gateway_uninstallable_plugins[ $file ] );
		update_option( 'uninstall_plugins', $qr_pay_gateway_uninstallable_plugins );
		unset( $qr_pay_gateway_uninstallable_plugins );

		wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $file );
		include_once WP_PLUGIN_DIR . '/' . $file;

		add_action( "uninstall_{$file}", $callable );

		/**
		 * Fires in uninstall_plugin() once the plugin has been uninstalled.
		 *
		 * The action concatenates the 'uninstall_' prefix with the basename of the
		 * plugin passed to uninstall_plugin() to create a dynamically-named action.
		 *
		 * @since 2.7.0
		 */
		do_action( "uninstall_{$file}" );
	}
}
