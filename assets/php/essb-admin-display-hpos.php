<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
/**
 * Display additional information on the WooCommerce admin order page for QR Payment orders.
 *
 * @since 1.0.0
 * @param WC_Order $order The WooCommerce order object.
 */
// Add a custom meta box to the order edit screen
add_action('add_meta_boxes', 'qr_pay_gateway_add_meta_box');

function qr_pay_gateway_add_meta_box() {
	$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';
	
    add_meta_box(
        'qr_pay_gateway_meta_box',
        __('QR Payment Gateway Information', 'qr-pay-gateway'),
        'qr_pay_gateway_meta_box_content',
        $screen,
		'side',
		'high'
    );
}

// Callback function to render the content of the meta box
function qr_pay_gateway_meta_box_content($order_id, $order) {
	
    // Retrieve the order using $order_id
   $order = wc_get_order($order_id);
	
	// Retrieve existing values from the order meta
    $essb_full_pay_name = $order->get_meta( 'essb_full_pay_name', true);
    $essb_mobile = $order->get_meta( 'essb_mobile', true);
    $essb_transaction = $order->get_meta( 'essb_transaction', true);
    $essb_transaction_type = $order->get_meta( 'essb_transaction_type', true);
	

    // Output the HTML for the meta box
    ?>
    <p>
        <label for="essb_full_pay_name"><?php _e('Full Payment Name', 'qr-pay-gateway'); ?></label>
        <input type="text" id="essb_full_pay_name" name="essb_full_pay_name" value="<?php echo esc_attr($essb_full_pay_name); ?>">
    </p>
    <p>
        <label for="essb_mobile"><?php _e('Mobile Number', 'qr-pay-gateway'); ?></label>
        <input type="text" id="essb_mobile" name="essb_mobile" value="<?php echo esc_attr($essb_mobile); ?>">
    </p>
    <p>
        <label for="essb_transaction"><?php _e('Transaction ID', 'qr-pay-gateway'); ?></label>
        <input type="text" id="essb_transaction" name="essb_transaction" value="<?php echo esc_attr($essb_transaction); ?>">
    </p>
    <p>
        <label for="essb_transaction_type"><?php _e('Transaction Type', 'qr-pay-gateway'); ?></label>
        <input type="text" id="essb_transaction_type" name="essb_transaction_type" value="<?php echo esc_attr($essb_transaction_type); ?>">
    </p>
    <?php
}
?>