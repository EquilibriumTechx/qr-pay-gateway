<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Sanitize, Escape, and Validate the input fields on the checkout page.
 * Update the order meta with the sanitized field values for QR Payment orders.
 *
 * @since 1.0.0
 * @param int $order_id The WooCommerce order ID.
 */
add_action('woocommerce_checkout_update_order_meta', 'qr_pay_gateway_update_order_meta', 10, 2);

function qr_pay_gateway_update_order_meta($order_id, $order) {
    // Check if the selected payment method is QR Payment Gateway  
    if (empty($_POST['payment_method']) || $_POST['payment_method'] !== 'qr_pay_gateway') {
        return;
    }

    // Retrieve the order using $order_id
    $order = wc_get_order($order_id);

    // Make sure $order is an instance of WC_Order before proceeding
  	if (!is_a($order, 'WC_Order')) {
        return;
    }

    // Sanitize and save the Payment Full Name data
    if (isset($_POST['essb_full_pay_name']) && !empty($_POST['essb_full_pay_name'])) {
        $essb_full_pay_name = wc_clean(wp_unslash($_POST['essb_full_pay_name']));
        $order->update_meta_data('essb_full_pay_name', $essb_full_pay_name);
    }

    // Validate and sanitize the Mobile Number data
    if (isset($_POST['essb_mobile']) && !empty($_POST['essb_mobile'])) {
        $essb_mobile = wc_clean(wp_unslash($_POST['essb_mobile']));
        $essb_mobile = preg_replace('/[^\d\-\+]/', '', $essb_mobile); // Retains only digits, dash, and plus
        $order->update_meta_data('essb_mobile', $essb_mobile);
    }

    // Sanitize and save the Transaction ID data
    if (isset($_POST['essb_transaction']) && !empty($_POST['essb_transaction'])) {
        $essb_transaction = wc_clean(wp_unslash($_POST['essb_transaction']));
        $order->update_meta_data('essb_transaction', $essb_transaction);
    }

    // Get and save the Qr-Type value for the order
    if (isset($_POST['essb_transaction_type']) && !empty($_POST['essb_transaction_type'])) {
        $essb_transaction_type = wc_clean(wp_unslash($_POST['essb_transaction_type']));
        $order->update_meta_data('essb_transaction_type', $essb_transaction_type);
    }

    // Save the changes to the order
    $order->save();
}


?>
