<?php
/*
 * Plugin Name: Malaysian Touch N Go QR Pay
 * Plugin URI: https://www.equilibrium.my/malaysian-touch-n-go-qr-pay/
 * Description: Malaysian Touch N Go QR Pay
 * Version: 1.0.9
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Equilibrium Solution M Sdn. Bhd.
 * Author URI: https://www.equilibrium.my
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-tng-qr-pay
 * Domain Path: /languages
 *
 * Copyright: c 2018-2022 Equilibrium Solution M Sdn. Bhd.
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC_TNG_QR_PAY
 * @author    Equilibrium
 * @category  Admin
 * @copyright Copyright 2018-2022 Equilibrium Solution M Sdn. Bhd.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This is a Malaysian Touch N Go Payment Qr Code.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
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
function wc_tng_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_TNG_QR_PAY';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_tng_add_to_gateways' );


/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_tng_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=tng_gateway' ) . '">' . __( 'Configure', 'wc-tng-gateway' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_tng_gateway_plugin_links' );


/**
 * TNG Payment Gateway
 *
 * Provides an Tng Payment Gateway;
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_TNG_QR_PAY
 * @extends		WC_Payment_Gateway
 * @version		1.0.8
 * @package		WooCommerce/Classes/Payment
 * @author 		Equilibrium
 */
add_action( 'plugins_loaded', 'wc_tng_gateway_init', 11 );

function wc_tng_gateway_init() {

	class WC_TNG_QR_PAY extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'tng_gateway';
			$this->icon               = 'https://www.equilibrium.my/wp-content/uploads/2023/04/Touch_n_Go_eWallet_logo-e1681742544757.png';
			$this->has_fields         = true;
			$this->method_title       = __( 'Touch N Go', 'wc-tng-gateway' );
			$this->title              = __( 'Touch N Go', 'wc-tng-gateway' );
			$this->method_description = __( 'This is a Malaysia Touch N Go Qr Payment Method', 'wc-tng-gateway' );
			$this->order_button_text = 'Pay via Touch N Go';
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
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			$this->order_status = $this->get_option( 'order_status', 'on-hold' );
			$this->media        = $this->get_option( 'media', '' );
		  
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
	  
			$this->form_fields = apply_filters( 'wc_tng_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'wc-tng-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Touch N Go Payment method', 'wc-tng-gateway' ),
					'default' => 'no'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'wc-tng-gateway' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-tng-gateway' ),
					'default'     => __( 'Touch N Go Payment mode', 'wc-tng-gateway' ),
					'desc_tip'    => true,
				),
				'order_status' => array(
                    'title'       => __( 'Order Status', 'wc-tng-gateway' ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', 'wc-tng-gateway' ),
                    'default'     => 'wc-on-hold',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
				
				'description' => array(
					'title'       => __( 'Description', 'wc-tng-gateway' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-tng-gateway' ),
					'default'     => __( 'Please remit payment to Store Name upon pickup or delivery.', 'wc-tng-gateway' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-tng-gateway' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-tng-gateway' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'media' => array(
					'title'       => __( 'Media(URL)', 'wc-tng-gateway' ),
					'type'        => 'media',
					'description' => __( 'Add an image URL related to this payment method.', 'wc-tng-gateway' ),
					'default'     => '',
					'desc_tip'    => true,
				),
			) );
		}
	
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
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
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && 'tng_gateway' && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
		
		public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

			$media_url = $this->get_option( 'media' );
			if ( $media_url ) {
				$media_id = attachment_url_to_postid( $media_url );
				$media = wp_get_attachment_image_src( $media_id, 'full' );
				if ( $media ) {
					echo '<img width="200" height="200" style="margin-left: 25%; margin-right: auto;" src="' . $media[0] . '" alt="' . get_post_meta( $media_id, '_wp_attachment_image_alt', true ) . '">';
				}
			}

            ?>
            <div id="custom_input">
				<p class="form-row form-row-wide">
                    <label for="full_pay_name" class=""><?php _e('Payment Full Name', 'wc-tng-gateway'); ?></label>
                    <input type="text" class="" name="full_pay_name" id="full_pay_name" placeholder="" value="" required/>
                </p>
                <p class="form-row form-row-wide">
                    <label for="mobile" class=""><?php _e('Mobile Number', 'wc-tng-gateway'); ?></label>
                    <input type="text" class="" name="mobile" id="mobile" placeholder="" value="" required/>
                </p>
                <p class="form-row form-row-wide">
                    <label for="transaction" class=""><?php _e('Transaction ID', 'wc-tng-gateway'); ?></label>
                    <input type="text" class="" name="transaction" id="transaction" placeholder="" value="" required/>
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

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            // Set order status
            $order->update_status( $status, __( 'Checkout with TNG payment. ', 'wc-tng-gateway' ) );

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
  } // end \WC_TNG_QR_PAY class
}

add_action('woocommerce_checkout_process', 'tng_process_custom_payment');
function tng_process_custom_payment(){
	$domain = $_SERVER['HTTP_HOST'];

    if($_POST['payment_method'] != 'tng_gateway')
        return;
	if( !isset($_POST['full_pay_name']) || empty($_POST['full_pay_name']) )
        wc_add_notice( __( '<strong>Please add your Payment Full Name</strong> is a required field.', $domain ), 'error' );
	
    if( !isset($_POST['mobile']) || empty($_POST['mobile']) )
        wc_add_notice( __( '<strong>Please add your mobile number</strong> is a required field.', $domain ), 'error' );

    if( !isset($_POST['transaction']) || empty($_POST['transaction']) )
        wc_add_notice( __( '<strong>Please add your transaction ID</strong> is a required field.', $domain ), 'error' );

}

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'tng_payment_update_order_meta' );
function tng_payment_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'tng_gateway')
        return;

    // echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";
    // exit();
	update_post_meta( $order_id, 'full_pay_name', $_POST['full_pay_name'] );
    update_post_meta( $order_id, 'mobile', $_POST['mobile'] );
    update_post_meta( $order_id, 'transaction', $_POST['transaction'] );
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'tng_checkout_field_display_admin_order_meta', 10, 1 );
function tng_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'tng_gateway')
        return;
	$full_pay_name = get_post_meta( $order->id, 'full_pay_name', true );
    $mobile = get_post_meta( $order->id, 'mobile', true );
    $transaction = get_post_meta( $order->id, 'transaction', true );
	echo '<h4>(Payment By Touch N Go Details)</h4>';
	echo '<p><strong>'.__( 'Payment Full Name' ).':</strong> ' . $full_pay_name . '</p>';
    echo '<p><strong>'.__( 'Mobile Number' ).':</strong> ' . $mobile . '</p>';
    echo '<p><strong>'.__( 'Transaction ID').':</strong> ' . $transaction . '</p>';
}
function wc_tng_uninstall_plugin( $plugin ) {
	$file = plugin_basename( $plugin );

	$uninstallable_plugins = (array) get_option( 'uninstall_plugins' );

	/**
	 * Fires in uninstall_plugin() immediately before the plugin is uninstalled.
	 *
	 * @since 4.5.0
	 *
	 * @param string $plugin                Path to the plugin file relative to the plugins directory.
	 * @param array  $uninstallable_plugins Uninstallable plugins.
	 */
	do_action( 'pre_uninstall_plugin', $plugin, $uninstallable_plugins );

	if ( file_exists( WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php' ) ) {
		if ( isset( $uninstallable_plugins[ $file ] ) ) {
			unset( $uninstallable_plugins[ $file ] );
			update_option( 'uninstall_plugins', $uninstallable_plugins );
		}
		unset( $uninstallable_plugins );

		define( 'WP_UNINSTALL_PLUGIN', $file );

		wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $file );
		include_once WP_PLUGIN_DIR . '/' . dirname( $file ) . '/uninstall.php';

		return true;
	}

	if ( isset( $uninstallable_plugins[ $file ] ) ) {
		$callable = $uninstallable_plugins[ $file ];
		unset( $uninstallable_plugins[ $file ] );
		update_option( 'uninstall_plugins', $uninstallable_plugins );
		unset( $uninstallable_plugins );

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
