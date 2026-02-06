<?php
/**
 * Cross-plugin integrations for Sarai Analytics.
 *
 * Listens to hooks from other plugins (SMI, Spawn) and logs events.
 *
 * @package Sarai_Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sarai_Analytics_Integrations {
	/**
	 * Database instance.
	 *
	 * @var Sarai_Analytics_Database
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @param Sarai_Analytics_Database $database Database instance.
	 */
	public function __construct( Sarai_Analytics_Database $database ) {
		$this->database = $database;
		$this->register_hooks();
	}

	/**
	 * Register all integration hooks.
	 */
	private function register_hooks() {
		// SMI hooks.
		add_action( 'smi_payment_completed', array( $this, 'track_smi_payment' ), 10, 2 );
		add_action( 'smi_job_status_changed', array( $this, 'track_smi_job_status' ), 10, 4 );

		// Spawn hooks.
		add_action( 'spawn_credits_purchased', array( $this, 'track_spawn_credits' ), 10, 3 );
		add_action( 'spawn_provisioning_complete', array( $this, 'track_spawn_provisioning_complete' ), 10, 3 );
		add_action( 'spawn_provisioning_failed', array( $this, 'track_spawn_provisioning_failed' ), 10, 3 );
		add_action( 'spawn_domain_renewed', array( $this, 'track_spawn_domain_renewed' ), 10, 3 );
		add_action( 'spawn_auto_refill_success', array( $this, 'track_spawn_auto_refill' ), 10, 3 );

		// Generic tracking hook for any plugin.
		add_action( 'sarai_analytics_track', array( $this, 'handle_generic_track' ), 10, 3 );
	}

	/**
	 * Track SMI payment completed.
	 *
	 * @param int        $job_id  Job ID.
	 * @param array|null $context Optional context array.
	 */
	public function track_smi_payment( $job_id, $context = null ) {
		$event_data = array(
			'job_id' => absint( $job_id ),
		);

		if ( is_array( $context ) && ! empty( $context['admin_override'] ) ) {
			$event_data['admin_override'] = true;
		}

		$this->track_event( 'smi_payment', $event_data );
	}

	/**
	 * Track SMI job status change.
	 *
	 * @param int    $job_id          Job ID.
	 * @param string $old_status      Previous status.
	 * @param string $new_status      New status.
	 * @param array  $additional_data Additional data.
	 */
	public function track_smi_job_status( $job_id, $old_status, $new_status, $additional_data = array() ) {
		$event_data = array(
			'job_id'     => absint( $job_id ),
			'old_status' => sanitize_text_field( $old_status ),
			'new_status' => sanitize_text_field( $new_status ),
		);

		$this->track_event( 'smi_job_status', $event_data );
	}

	/**
	 * Track Spawn credits purchased.
	 *
	 * @param int   $customer_id Spawn customer ID.
	 * @param int   $credits     Credits purchased.
	 * @param mixed $session     Stripe session object.
	 */
	public function track_spawn_credits( $customer_id, $credits, $session ) {
		$event_data = array(
			'customer_id' => absint( $customer_id ),
			'credits'     => absint( $credits ),
		);

		$this->track_event( 'spawn_credits', $event_data );
	}

	/**
	 * Track Spawn provisioning complete.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param string $domain      Domain name.
	 * @param string $server_ip   Server IP address.
	 */
	public function track_spawn_provisioning_complete( $customer_id, $domain, $server_ip ) {
		$event_data = array(
			'customer_id' => absint( $customer_id ),
			'domain'      => sanitize_text_field( $domain ),
			'status'      => 'complete',
		);

		$this->track_event( 'spawn_provisioning', $event_data );
	}

	/**
	 * Track Spawn provisioning failed.
	 *
	 * @param int    $customer_id   Customer ID.
	 * @param string $domain        Domain name.
	 * @param string $error_message Error message.
	 */
	public function track_spawn_provisioning_failed( $customer_id, $domain, $error_message ) {
		$event_data = array(
			'customer_id' => absint( $customer_id ),
			'domain'      => sanitize_text_field( $domain ),
			'status'      => 'failed',
			'error'       => sanitize_text_field( substr( $error_message, 0, 200 ) ),
		);

		$this->track_event( 'spawn_provisioning', $event_data );
	}

	/**
	 * Track Spawn domain renewal.
	 *
	 * @param int    $customer_id    Customer ID.
	 * @param string $domain         Domain name.
	 * @param mixed  $renewal_result Renewal result.
	 */
	public function track_spawn_domain_renewed( $customer_id, $domain, $renewal_result ) {
		$event_data = array(
			'customer_id' => absint( $customer_id ),
			'domain'      => sanitize_text_field( $domain ),
		);

		$this->track_event( 'spawn_domain', $event_data );
	}

	/**
	 * Track Spawn auto-refill success.
	 *
	 * @param int   $customer_id Spawn customer ID.
	 * @param int   $credits     Credits added.
	 * @param mixed $result      Refill result.
	 */
	public function track_spawn_auto_refill( $customer_id, $credits, $result ) {
		$event_data = array(
			'customer_id' => absint( $customer_id ),
			'credits'     => absint( $credits ),
			'auto_refill' => true,
		);

		$this->track_event( 'spawn_credits', $event_data );
	}

	/**
	 * Handle generic tracking action.
	 *
	 * Allows any plugin to track events via:
	 * do_action( 'sarai_analytics_track', 'event_type', array( 'key' => 'value' ), 'https://page.url' );
	 *
	 * @param string $event_type Event type.
	 * @param array  $event_data Event data.
	 * @param string $page_url   Page URL (optional).
	 */
	public function handle_generic_track( $event_type, $event_data = array(), $page_url = '' ) {
		if ( empty( $event_type ) || ! is_string( $event_type ) ) {
			return;
		}

		$this->track_event(
			sanitize_text_field( $event_type ),
			is_array( $event_data ) ? $event_data : array(),
			$page_url
		);
	}

	/**
	 * Track an event to the database.
	 *
	 * @param string $event_type Event type.
	 * @param array  $event_data Event data.
	 * @param string $page_url   Page URL (optional).
	 */
	private function track_event( $event_type, $event_data, $page_url = '' ) {
		$allowed_events = apply_filters(
			'sarai_analytics_allowed_events',
			array( 'random_click', 'image_mode', 'smi_click', 'search', 'page_view', 'nav_click', 'smi_payment', 'smi_job_status', 'spawn_credits', 'spawn_provisioning', 'spawn_domain' )
		);

		if ( ! in_array( $event_type, $allowed_events, true ) ) {
			return;
		}

		// Sanitize event data.
		$clean_data = array();
		foreach ( $event_data as $key => $value ) {
			$clean_key = sanitize_key( $key );
			if ( is_scalar( $value ) ) {
				$clean_data[ $clean_key ] = is_bool( $value ) ? $value : sanitize_text_field( (string) $value );
			}
		}

		$this->database->insert_event(
			$event_type,
			wp_json_encode( $clean_data ),
			esc_url_raw( $page_url ),
			'',                      // No referrer for server-side events.
			'server-side',           // Session ID indicator.
			'Sarai Analytics Plugin' // User agent indicator.
		);
	}
}
