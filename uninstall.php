<?php // exit if uninstall constant is not defined
// uninstall.php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// remove plugin options
delete_option( 'title' );
delete_option( 'description' );
delete_option( 'instructions' );
delete_option( 'order_status' );
delete_option( 'media' );

// Call the renamed uninstall function
wc_tng_uninstall_plugin( __FILE__ );
