<?php
/*
 * Plugin Name: QR Payments Gateway
 * Plugin URI: https://www.equilibrium.my/qr-pay-gateway/
 * Description: QR Payments For Woocommerce Payment Gateway For Touch N Go, DuitNow, Grab, Shopee Pay, Boost
 * Version: 1.1.9
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
	admin_qr_pay_gateway_enqueue_assets();
}

// Function to enqueue styles and scripts - Front End - Dynamic Code file css,js 
function qr_pay_gateway_enqueue_assets() {
    $plugin_path = plugin_dir_path(__FILE__);
    $css_folder = $plugin_path . 'assets/css/';
    $js_folder = $plugin_path . 'assets/js/';

    // Get file modification time as the version number for CSS
    $css_files = glob($css_folder . '*.css');
    foreach ($css_files as $css_file) {
        $css_version = filemtime($css_file);
        wp_enqueue_style(
            'qr-pay-gateway-' . basename($css_file, '.css'),
            plugin_dir_url(__FILE__) . 'assets/css/' . basename($css_file),
            array(),
            $css_version,
            'all'
        );
    }

    // Get file modification time as the version number for JS
    $js_files = glob($js_folder . '*.js');
    foreach ($js_files as $js_file) {
        $js_version = filemtime($js_file);
        wp_enqueue_script(
            'qr-pay-gateway-' . basename($js_file, '.js'),
            plugin_dir_url(__FILE__) . 'assets/js/' . basename($js_file),
            array('jquery'),
            $js_version,
            true
        );
    }
}

// Function to enqueue styles and scripts - Back End  - Dynamic Code file css,js 
add_action('wp_enqueue_scripts', 'qr_pay_gateway_enqueue_assets');

// Function to enqueue styles and scripts - admin
function admin_qr_pay_gateway_enqueue_assets() {
    $plugin_path = plugin_dir_path(__FILE__);
    $css_folder = $plugin_path . 'assets/admin/css/';
    $js_folder = $plugin_path . 'assets/admin/js/';

    // Enqueue media if not already enqueued
    if (!did_action('wp_enqueue_media')) {
        wp_enqueue_media();
    }

    // Enqueue CSS files from the folder
    $css_files = glob($css_folder . '*.css');
    foreach ($css_files as $css_file) {
        $css_version = filemtime($css_file);
        wp_enqueue_style(
            'qr-pay-gateway-' . basename($css_file, '.css'),
            plugin_dir_url(__FILE__) . 'assets/admin/css/' . basename($css_file),
            array(),
            $css_version,
            'all'
        );
    }

    // Enqueue JS files from the folder
    $js_files = glob($js_folder . '*.js');
    foreach ($js_files as $js_file) {
        $js_version = filemtime($js_file);
        wp_enqueue_script(
            'qr-pay-gateway-' . basename($js_file, '.js'),
            plugin_dir_url(__FILE__) . 'assets/admin/js/' . basename($js_file),
            array('jquery'),
            $js_version,
            true
        );
    }
}

// Enqueue styles and scripts in the admin area
add_action('admin_enqueue_scripts', 'admin_qr_pay_gateway_enqueue_assets');