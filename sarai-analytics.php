<?php
/**
 * Plugin Name: Sarai Analytics
 * Description: Lightweight analytics for custom events on saraichinwag.com.
 * Version: 1.1.0
 * Author: Sarai
 * License: GPL-2.0+
 * Text Domain: sarai-analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SARAI_ANALYTICS_VERSION', '1.1.0' );
define( 'SARAI_ANALYTICS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SARAI_ANALYTICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-database.php';
require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-tracker.php';
require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-admin.php';
require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-abilities.php';
require_once SARAI_ANALYTICS_PLUGIN_PATH . 'includes/class-integrations.php';

/**
 * Main Sarai Analytics class for static access.
 */
class Sarai_Analytics {
	/**
	 * Database instance (singleton).
	 *
	 * @var Sarai_Analytics_Database|null
	 */
	private static $database = null;

	/**
	 * Get the database instance.
	 *
	 * @return Sarai_Analytics_Database
	 */
	private static function get_database() {
		if ( null === self::$database ) {
			self::$database = new Sarai_Analytics_Database();
		}
		return self::$database;
	}

	/**
	 * Track an event programmatically.
	 *
	 * Usage: Sarai_Analytics::track( 'custom_event', array( 'key' => 'value' ), 'https://page.url' );
	 *
	 * @param string $event_type Event type.
	 * @param array  $event_data Event data.
	 * @param string $page_url   Page URL (optional).
	 * @return bool True if tracked, false if event type not allowed.
	 */
	public static function track( $event_type, $event_data = array(), $page_url = '' ) {
		$allowed_events = apply_filters(
			'sarai_analytics_allowed_events',
			sarai_analytics_default_events()
		);

		$event_type = sanitize_text_field( $event_type );
		if ( empty( $event_type ) || ! in_array( $event_type, $allowed_events, true ) ) {
			return false;
		}

		// Sanitize event data.
		$clean_data = array();
		if ( is_array( $event_data ) ) {
			foreach ( $event_data as $key => $value ) {
				$clean_key = sanitize_key( $key );
				if ( is_scalar( $value ) ) {
					$clean_data[ $clean_key ] = is_bool( $value ) ? $value : sanitize_text_field( (string) $value );
				}
			}
		}

		self::get_database()->insert_event(
			$event_type,
			wp_json_encode( $clean_data ),
			esc_url_raw( $page_url ),
			'',
			'api-call',
			'Sarai_Analytics::track()'
		);

		return true;
	}
}

/**
 * Get default allowed events.
 *
 * @return array List of allowed event types.
 */
function sarai_analytics_default_events() {
	return array(
		'random_click',
		'image_mode',
		'smi_click',
		'search',
		'page_view',
		'nav_click',
		'smi_payment',
		'smi_job_status',
		'spawn_credits',
		'spawn_provisioning',
		'spawn_domain',
	);
}

function sarai_analytics_activate() {
	$database = new Sarai_Analytics_Database();
	$database->create_table();
}

register_activation_hook( __FILE__, 'sarai_analytics_activate' );

function sarai_analytics_init() {
	$database = new Sarai_Analytics_Database();
	new Sarai_Analytics_Tracker( $database );
	new Sarai_Analytics_Admin( $database );
	new Sarai_Analytics_Integrations( $database );

	// Register abilities - check if hook already fired.
	$abilities = new Sarai_Analytics_Abilities( $database );
	if ( did_action( 'wp_abilities_api_init' ) ) {
		$abilities->register_abilities();
	} else {
		add_action( 'wp_abilities_api_init', array( $abilities, 'register_abilities' ) );
	}
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
			'endpoint' => rest_url( 'sarai-analytics/v1/event' ),
			'events'   => apply_filters( 'sarai_analytics_allowed_events', sarai_analytics_default_events() ),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'sarai_analytics_enqueue_scripts' );
