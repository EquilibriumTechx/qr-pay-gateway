<?php
// If uninstall.php is not called by WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'tng_gateway_enabled' );
delete_option( 'tng_gateway_title' );
delete_option( 'tng_gateway_order_status' );
delete_option( 'tng_gateway_description' );
delete_option( 'tng_gateway_instructions' );
delete_option( 'tng_gateway_media' );
delete_option( 'woocommerce_tng_gateway_settings' );


// Remove plugin meta data from orders
$args = array(
	'post_type'      => 'shop_order',
	'posts_per_page' => -1,
	'post_status'    => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed' ),
	'meta_query'     => array(
		array(
			'key'   => '_payment_method',
			'value' => 'tng_gateway',
		),
	),
);

$orders = get_posts( $args );

if ( $orders ) {
	foreach ( $orders as $order ) {
		delete_post_meta( $order->ID, 'full_pay_name' );
		delete_post_meta( $order->ID, 'mobile' );
		delete_post_meta( $order->ID, 'transaction' );
	}
}
