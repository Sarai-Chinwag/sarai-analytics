<?php
/**
 * Plugin Name: Sarai Analytics
 * Description: Lightweight analytics for custom events on saraichinwag.com.
 * Version: 1.0.0
 * Author: Sarai
 * License: GPL-2.0+
 * Text Domain: sarai-analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SARAI_ANALYTICS_VERSION', '1.0.0' );
define( 'SARAI_ANALYTICS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SARAI_ANALYTICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-database.php';
require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-tracker.php';
require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-admin.php';
require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-abilities.php';

function sarai_analytics_activate() {
	$database = new Sarai_Analytics_Database();
	$database->create_table();
}

register_activation_hook( __FILE__, 'sarai_analytics_activate' );

function sarai_analytics_init() {
	$database = new Sarai_Analytics_Database();
	new Sarai_Analytics_Tracker( $database );
	new Sarai_Analytics_Admin( $database );
	$abilities = new Sarai_Analytics_Abilities( $database );
	add_action( 'wp_abilities_api_init', array( $abilities, 'register_abilities' ) );
}

add_action( 'plugins_loaded', 'sarai_analytics_init' );

function sarai_analytics_enqueue_scripts() {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'sarai-analytics-tracker',
		SARAI_ANALYTICS_PLUGIN_URL . 'assets/js/tracker.js',
		array(),
		SARAI_ANALYTICS_VERSION,
		true
	);

	wp_localize_script(
		'sarai-analytics-tracker',
		'SaraiAnalytics',
		array(
			'endpoint'   => rest_url( 'sarai-analytics/v1/event' ),
			'events'     => apply_filters( 'sarai_analytics_allowed_events', array( 'random_click', 'image_mode', 'smi_click', 'search', 'page_view' ) ),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'sarai_analytics_enqueue_scripts' );
