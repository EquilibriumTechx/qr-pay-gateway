<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Process custom payment fields for QR Payment Gateway during WooCommerce checkout.
 * Trigger WooCommerce default error messages if the fields are empty or if mobile number validation fails.
 *
 * @since 1.0.0
 */
add_action('woocommerce_checkout_process', 'qr_pay_gateway_process_custom_payment');

function qr_pay_gateway_process_custom_payment()
{
    // Check if the selected payment method is QR Payment Gateway
    if ($_POST['payment_method'] !== 'qr_pay_gateway') {
        return;
    }

    // Check if Payment Full Name is empty and trigger an error
    if (!isset($_POST['essb_full_pay_name']) || empty($_POST['essb_full_pay_name'])) {
        wc_add_notice(esc_html__('Payment Full Name is a required field.', 'qr-pay-gateway'), 'error');
    }

    // Check if Mobile Number is empty and trigger an error
    if (!isset($_POST['essb_mobile']) || empty($_POST['essb_mobile'])) {
        wc_add_notice(esc_html__('Mobile Number is a required field.', 'qr-pay-gateway'), 'error');
    } else {
        // Validate and sanitize the Mobile Number
        $mobile = sanitize_text_field($_POST['essb_mobile']);
        if (!preg_match('/^[\d\+\-]+$/', $mobile)) {
            wc_add_notice(esc_html__('Please enter a valid mobile number. Only digits, plus, and dash are allowed.', 'qr-pay-gateway'), 'error');
        }
    }
	$this_class = new QrPayGateway();
	$required_types_cons = $this_class->get_option('required_types');
				
	if ($required_types_cons == 'yes') {
		if (!isset($_POST['essb_transaction']) || empty($_POST['essb_transaction'])) {
        wc_add_notice(esc_html__('Transaction ID is a required field.', 'qr-pay-gateway'), 'error');
    	}
	} 
}
?>
