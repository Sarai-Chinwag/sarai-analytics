<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sarai_Analytics_Tracker {
	private $database;
	private $allowed_events;
	private $rate_limit;

	public function __construct( Sarai_Analytics_Database $database ) {
		$this->database       = $database;
		$this->allowed_events = apply_filters( 'sarai_analytics_allowed_events', array( 'random_click', 'image_mode', 'smi_click', 'search', 'page_view' ) );
		$this->rate_limit     = (int) apply_filters( 'sarai_analytics_rate_limit', 10 );

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'sarai-analytics/v1',
			'/event',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'handle_event' ),
			)
		);
	}

	public function handle_event( WP_REST_Request $request ) {
		if ( $this->is_do_not_track() ) {
			return new WP_REST_Response( null, 204 );
		}

		$event_type = sanitize_text_field( $request->get_param( 'event_type' ) );
		if ( empty( $event_type ) || ! in_array( $event_type, $this->allowed_events, true ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid event type.' ), 400 );
		}

		$event_data = $request->get_param( 'event_data' );
		if ( is_array( $event_data ) ) {
			$event_data = wp_json_encode( $this->sanitize_event_data( $event_data ) );
		} elseif ( is_string( $event_data ) ) {
			$event_data = wp_json_encode( array( 'value' => sanitize_text_field( $event_data ) ) );
		} else {
			$event_data = wp_json_encode( array() );
		}

		$session_id = $this->get_session_id();
		if ( ! $this->within_rate_limit( $session_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Rate limit exceeded.' ), 429 );
		}

		$page_url = esc_url_raw( $request->get_param( 'page_url' ) );
		$referrer = esc_url_raw( $request->get_param( 'referrer' ) );
		$user_agent = sanitize_text_field( $request->get_header( 'user_agent' ) );

		$this->database->insert_event(
			$event_type,
			$event_data,
			$page_url,
			$referrer,
			$session_id,
			$user_agent
		);

		return new WP_REST_Response( null, 204 );
	}

	private function sanitize_event_data( $data ) {
		$clean = array();
		foreach ( $data as $key => $value ) {
			$clean_key = sanitize_key( $key );
			if ( is_scalar( $value ) ) {
				$clean[ $clean_key ] = sanitize_text_field( $value );
			}
		}

		return $clean;
	}

	private function get_session_id() {
		$cookie_name = apply_filters( 'sarai_analytics_session_cookie', 'sarai_analytics_session' );
		if ( empty( $_COOKIE[ $cookie_name ] ) ) {
			$session_id = wp_generate_uuid4();
			setcookie( $cookie_name, $session_id, 0, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			$_COOKIE[ $cookie_name ] = $session_id;
		}

		return sanitize_text_field( $_COOKIE[ $cookie_name ] );
	}

	private function within_rate_limit( $session_id ) {
		$transient_key = 'sarai_analytics_rate_' . md5( $session_id );
		$count = (int) get_transient( $transient_key );
		if ( $count >= $this->rate_limit ) {
			return false;
		}

		set_transient( $transient_key, $count + 1, MINUTE_IN_SECONDS );

		return true;
	}

	private function is_do_not_track() {
		if ( isset( $_SERVER['HTTP_DNT'] ) && '1' === $_SERVER['HTTP_DNT'] ) {
			return true;
		}

		return false;
	}
}
