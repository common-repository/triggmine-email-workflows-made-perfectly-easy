<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// plugin settings actions
add_action( 'admin_menu', 'triggmine_menu' );
add_action( 'admin_init', 'triggmine_settings' );
add_action( 'admin_enqueue_scripts', 'triggmine_admin_enqueue_styles' );
add_action( 'admin_enqueue_scripts', 'triggmine_enqueue_date_picker' );

// triggmine events triggered on plugin settings page
add_action( 'add_option_triggmine_settings', 'triggmine_on_diagnostic_information_updated' );
add_action( 'add_option_triggmine_settings', 'triggmine_send_order_history' );
add_action( 'update_option_triggmine_settings', 'triggmine_on_diagnostic_information_updated' );
add_action( 'update_option_triggmine_settings', 'triggmine_send_order_history' );
add_action( 'update_option_triggmine_settings', 'triggmine_send_customer_history' );

function triggmine_menu() {
	add_menu_page( 'Triggmine Settings', 'Triggmine', 'administrator', 'triggmine-settings', 'triggmine_settings_page', 'dashicons-email' );
}

function triggmine_settings() {
	/* 
	* this is an array of settings, contains the following keys:
	* plugin_enabled
	* api_url
	* api_key
	* order_export_enabled
	* order_export_date_from
	* order_export_date_to
	* customer_export_enabled
	* customer_export_date_from
	* customer_export_date_to
	*/
	register_setting( 'triggmine-settings-group', 'triggmine_settings' );
}

function triggmine_admin_enqueue_styles() {
	wp_enqueue_style( plugin_dir_url( __FILE__ ) . 'css/triggmine-admin.css' );
}

function triggmine_settings_page( $args ) {
    require_once( TRIGGMINE__PLUGIN_DIR . 'views/triggmine-admin-display.php' );
}

function triggmine_enqueue_date_picker() {
    wp_enqueue_script(
		'field-date-js', 
		'Field_Date.js', 
		array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ),
		time(),
		true
	);	

	wp_enqueue_style( 'jquery-ui-datepicker' );
}