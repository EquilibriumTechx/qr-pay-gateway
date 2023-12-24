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

function qr_pay_gateway_update_order_meta($order_id){
 
        // Check if the selected payment method is QR Payment Gateway
        if (empty($_POST['payment_method']) || $_POST['payment_method'] !== 'qr_pay_gateway') {
            return;
        }

        // Sanitize and save the Payment Full Name data
        if (isset($_POST['essb_full_pay_name']) && !empty($_POST['essb_full_pay_name'])) {
            $essb_full_pay_name = wc_clean(wp_unslash($_POST['essb_full_pay_name']));
            update_post_meta($order_id, 'essb_full_pay_name', $essb_full_pay_name);
        }

        // Validate and sanitize the Mobile Number data
        if (isset($_POST['essb_mobile']) && !empty($_POST['essb_mobile'])) {
            $essb_mobile = wc_clean(wp_unslash($_POST['essb_mobile']));
            $essb_mobile = preg_replace('/[^\d\-\+]/', '', $essb_mobile); // Retains only digits, dash, and plus
            update_post_meta($order_id, 'essb_mobile', $essb_mobile);
        }

        // Sanitize and save the Transaction ID data
        if (isset($_POST['essb_transaction']) && !empty($_POST['essb_transaction'])) {
            $essb_transaction = wc_clean(wp_unslash($_POST['essb_transaction']));
            update_post_meta($order_id, 'essb_transaction', $essb_transaction);
        }

        // Get and save the Qr-Type value for the order
        if (isset($_POST['essb_transaction_type']) && !empty($_POST['essb_transaction_type'])) {
            $essb_transaction_type = wc_clean(wp_unslash($_POST['essb_transaction_type']));
            update_post_meta($order_id, 'essb_transaction_type', $essb_transaction_type);
        }
}
?>
