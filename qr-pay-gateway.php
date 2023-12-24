<?php
/*
 * Plugin Name: QR Payments Gateway
 * Plugin URI: https://www.equilibrium.my/qr-pay-gateway/
 * Description: QR Payments For Woocommerce Payment Gateway For Touch N Go, DuitNow, Grab, Shopee Pay, Boost, MaybankQR
 * Version: 1.1.6
 * Requires at least: 6.4
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


// Declare the this plugin is HPOS Compatibility
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

// Load translations
add_action('plugins_loaded', 'qr_pay_gateway_load_textdomain');
/**
 * Load translation text domain for localization.
 *
 * @since 1.0.0
 */
function qr_pay_gateway_load_textdomain() {
    load_plugin_textdomain('qr-pay-gateway', false, dirname(plugin_basename(__FILE__)) . '/languages');
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


// trigger the required class files once
require_once(plugin_dir_path(__FILE__) . 'assets/classes/class-qr-pay-gateway.php');

// Register activation hook
register_activation_hook(__FILE__, 'qr_pay_gateway_activate');

// Activation function
function qr_pay_gateway_activate() {
    // Additional activation tasks, if needed
    // Example: Set default options, create database tables, etc.
    // Enqueue styles and scripts when the plugin is activated
    qr_pay_gateway_enqueue_assets();
}

// Function to enqueue styles and scripts
function qr_pay_gateway_enqueue_assets() {
    $plugin_path = plugin_dir_path(__FILE__);

    // Get file modification time as the version number
    $css_version = filemtime($plugin_path . 'assets/css/qr-pay-gateway.css');
    $js_version = filemtime($plugin_path . 'assets/js/qr-pay-gateway.js');

    // Enqueue CSS
    wp_enqueue_style('qr-pay-gateway', plugin_dir_url(__FILE__) . 'assets/css/qr-pay-gateway.css', array(), $css_version, 'all');

    // Enqueue JS
    wp_enqueue_script('qr-pay-gateway', plugin_dir_url(__FILE__) . 'assets/js/qr-pay-gateway.js', array('jquery'), $js_version, true);
}

// Enqueue styles and scripts on every page load (not just during activation)
add_action('wp_enqueue_scripts', 'qr_pay_gateway_enqueue_assets');