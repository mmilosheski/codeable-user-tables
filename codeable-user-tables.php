<?php
/**
 * Plugin Name: Codeable User Tables
 * Plugin URI: https://www.agorawebdesigns.com/
 * Version: 0.0.1
 * Description: Registers shortcode [codeable_users_table] for displaying sortable table of users with multiple options to sort
 * Author: Agora Web Designs Studio
 * Author URI: https://www.agorawebdesigns.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! defined( 'PLUGIN_FILE_URL' ) ) {
	define( 'PLUGIN_FILE_URL', __FILE__ );
}

require_once plugin_dir_path( __FILE__ ) . 'src/init.php';
