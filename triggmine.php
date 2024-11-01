<?php
/*
Plugin Name:    Triggmine
Plugin URI:     http://www.triggmine.com/
Description:    Ecommerce email marketing automation for your ideal customer experience.
Version:        3.24.2
Author:         Triggmine
Author URI:     http://www.triggmine.com/
License:        GPLv3
License URI:    https://www.gnu.org/licenses/gpl-3.0.html
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define( 'TRIGGMINE_VERSION', '3.24.2' );
define( 'TRIGGMINE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRIGGMINE_DIAGNOSTIC_URL', 'plugindiagnostic.triggmine.com' );

if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once( TRIGGMINE__PLUGIN_DIR . 'includes/triggmine-admin.php' );
}

include_once( TRIGGMINE__PLUGIN_DIR . 'includes/triggmine-debugger.php' );

register_activation_hook( __FILE__, 'triggmine_send_activation_info' );
register_deactivation_hook( __FILE__, 'triggmine_send_deactivation_info' );
register_uninstall_hook( __FILE__, 'triggmine_send_uninstall_info' );

// cookie scripts for device ids
add_action( 'wp_footer', 'enqueue_triggmine_scripts' );

// triggmine events
add_action( 'woocommerce_after_single_product_summary', 'triggmine_send_pageinit' );
add_action( 'woocommerce_cart_updated', 'triggmine_send_cart_data' );
add_action( 'woocommerce_checkout_order_processed', 'triggmine_send_order_data' );
add_action( 'user_register', 'triggmine_send_register_data' );
add_action( 'wp_authenticate', 'triggmine_send_login_data' );
add_action( 'wp_logout', 'triggmine_send_logout_data' );
add_action( 'woocommerce_saved_order_items', 'triggmine_send_admin_order_data' ); // this also fires when no changes were made in edit mode and save button was clicked
add_action( 'added_post_meta', 'triggmine_on_product_save', 10, 4 );
add_action( 'updated_post_meta', 'triggmine_on_product_save', 10, 4 );

add_action( 'wp_ajax_triggmine_product_export', 'triggmine_export_products' );

function enqueue_triggmine_scripts() {
    wp_register_script( 'fingerprint2', plugin_dir_url( __FILE__ ) . 'js/fingerprint2.min.js' );
    wp_register_script( 'client', plugin_dir_url( __FILE__ ) . 'js/client.min.js' );
    wp_register_script( 'jscookie', plugin_dir_url( __FILE__ ) . 'js/jscookie.min.js' );
    wp_register_script( 'triggmine-client', plugin_dir_url( __FILE__ ) . 'js/triggmine-client.js' );
    
    wp_enqueue_script( 'fingerprint2' );
    wp_enqueue_script( 'client' );
    wp_enqueue_script( 'jscookie' );
    wp_enqueue_script( 'triggmine-client' );
}

require_once( TRIGGMINE__PLUGIN_DIR . 'includes/triggmine-core.php' );

function triggmine_send_activation_info() {
    $data = triggmine_get_diagnostic_info();
    triggmine_api_client( $data, 'api/diagnostic', TRIGGMINE_DIAGNOSTIC_URL );
}

function triggmine_send_deactivation_info() {
    $data = triggmine_get_diagnostic_info( 'DeactivatePlugin' );
    triggmine_api_client( $data, 'api/diagnostic', TRIGGMINE_DIAGNOSTIC_URL );
}

function triggmine_send_uninstall_info() {
    $data = triggmine_get_diagnostic_info( 'UninstallPlugin' );
    triggmine_api_client( $data, 'api/diagnostic', TRIGGMINE_DIAGNOSTIC_URL );
}

function triggmine_on_diagnostic_information_updated() {
    $data = triggmine_soft_check();
    $res = triggmine_api_client( $data, 'control/api/plugin/onDiagnosticInformationUpdated' );
    if ( $res['status'] === 503 ) {
        add_settings_error(
            'triggmine_settings',
            esc_attr( 'invalid_api_url' ),
            'Invalid API URL'
        );
    }
    if ( $res['status'] === 401 ) {
        add_settings_error(
            'triggmine_settings',
            esc_attr( 'invalid_api_key' ),
            'Invalid API key'
        );
    }
        
    $data = triggmine_get_diagnostic_info( 'ConfigurePlugin' );
    triggmine_api_client( $data, 'api/diagnostic', TRIGGMINE_DIAGNOSTIC_URL );
}

function triggmine_send_register_data( $user_id ) {
    if ( is_triggmine_enabled() ) {
        $data = triggmine_get_customer_register_data( $user_id );
        triggmine_api_client( $data, 'api/events/prospect/registration' );
        
        $data = triggmine_get_customer_login_data( null, $user_id );
        triggmine_api_client( $data, 'api/events/prospect/login' );
    }
}

function triggmine_send_login_data( $username ) {
    if ( is_triggmine_enabled() 
            && strpos( $_SERVER['REQUEST_URI'], '/wp-login' ) === false
            && strpos( $_SERVER['REQUEST_URI'], '/wp-admin/' ) === false ) {
        $current_user = get_user_by( 'login', $username );
        $user_id = $current_user->ID;
        update_user_meta( $user_id, 'last_login', date( 'Y-m-d h:i:s', time() ) );
        
        $data = triggmine_get_customer_login_data( $username, null );
        triggmine_api_client( $data, 'api/events/prospect/login' );
    }
}

function triggmine_send_logout_data() {
    if ( is_triggmine_enabled() ) {
        $data = triggmine_get_customer_logout_data();
        triggmine_api_client( $data, 'api/events/prospect/logout' );
    }
}

function triggmine_send_pageinit() {
    if ( triggmine_is_bot() ) {
        // bot detected
        // triggmine_log('bot detected: ' . $_SERVER['HTTP_USER_AGENT'], 'pageinit');
    }
    else {
        if ( is_triggmine_enabled() ) {
            $data = triggmine_page_init();
            triggmine_api_client( $data, 'api/events/navigation' );
        }
    }
}

function triggmine_send_cart_data() {
    if ( is_triggmine_enabled() && !is_checkout() ) {
        $data = triggmine_get_cart_data();
        triggmine_api_client( $data, 'api/events/cart' );
    }
}

function triggmine_send_order_data( $order_id ) {
    if ( is_triggmine_enabled() ) {
        $data = triggmine_get_order_data( $order_id );
        triggmine_api_client( $data, 'api/events/order' );
    }
}

function triggmine_send_admin_order_data( $order_id ) {
    if ( is_triggmine_enabled() ) {
        $data = triggmine_get_admin_order_data( $order_id );
        triggmine_api_client( $data, 'api/events/order' );
    }
}

function triggmine_send_order_history() {
    if ( is_triggmine_enabled() && triggmine_export_enabled() ) {
        $data = triggmine_get_order_history();
        triggmine_api_client( $data, 'api/events/history' );
    }
}

function triggmine_send_customer_history() {
    if ( is_triggmine_enabled() && triggmine_customer_export_enabled() ) {
        $data = triggmine_get_customer_history();
        triggmine_api_client( $data, 'api/events/history/prospects' );
    }
}

function triggmine_on_product_save( $meta_id, $post_id, $meta_key, $meta_value ) {
    if ( $meta_key == '_edit_lock' ) { // we've been editing the post
        if ( get_post_type( $post_id ) == 'product' ) { // we've been editing a product
            $data = triggmine_get_product_edit_data( $post_id );
            triggmine_api_client( $data, 'api/products/import' );
        }
    }
}

function triggmine_export_products() {
    $settings = get_option( 'triggmine_settings' );
   
    $page = $_POST['page'];
    $pageSize = $_POST['pageSize'];
    $pagesTotal = $_POST['pagesTotal'];
            
    $data = triggmine_get_product_history( $pageSize, $page );
        triggmine_log( json_encode( $data ), 'product-export' );
    $res = triggmine_api_client( $data, 'api/products/import' );
        triggmine_log( $res, 'product-export' );
            
    if ( $page == $pagesTotal ) {
        // finish export and set settings values
        $settings['triggmine_setup_ok'] = 1;
        $settings['product_export_enabled'] = 0;
        update_option( 'triggmine_settings', $settings );
    }

}