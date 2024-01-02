<?php
// If uninstall.php is not called by WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'qr_pay_gateway_enabled' );
delete_option( 'qr_pay_gateway_title' );
delete_option( 'qr_pay_gateway_order_status' );
delete_option( 'qr_pay_gateway_description' );
delete_option( 'qr_pay_gateway_instructions' );
delete_option( 'qr_pay_gateway_upload_qr' );
delete_option( 'qr_pay_gateway_media' );
delete_option( 'qr_pay_gateway_preview_qr' );
delete_option( 'qr_pay_gateway_account_name' );
delete_option( 'qr_pay_gateway_qr_type_selector' );
delete_option( 'qr_pay_gateway_qr_required_types' );
delete_option( 'woocommerce_qr_pay_gateway_settings' );

// Remove plugin meta data from orders
$args = array(
	'post_type'      => 'shop_order',
	'posts_per_page' => -1,
	'post_status'    => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed' ),
	'meta_query'     => array(
		array(
			'key'   => '_payment_method',
			'value' => 'qr_pay_gateway',
		),
	),
);

$orders = get_posts( $args );

if ( $orders ) {
	foreach ( $orders as $order ) {
		delete_post_meta( $order->ID, 'full_pay_name' );
		delete_post_meta( $order->ID, 'mobile' );
		delete_post_meta( $order->ID, 'transaction' );
		delete_post_meta( $order->ID, 'transaction_type' );
	}
}