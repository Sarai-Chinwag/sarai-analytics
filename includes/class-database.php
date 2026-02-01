<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sarai_Analytics_Database {
	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'sarai_events';
	}

	public function get_table_name() {
		return $this->table_name;
	}

	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			event_type VARCHAR(50) NOT NULL,
			event_data JSON,
			page_url VARCHAR(500),
			referrer VARCHAR(500),
			session_id VARCHAR(64),
			user_agent VARCHAR(500),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_event_type (event_type),
			INDEX idx_created_at (created_at),
			INDEX idx_session (session_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function insert_event( $event_type, $event_data, $page_url, $referrer, $session_id, $user_agent ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"INSERT INTO {$this->table_name} (event_type, event_data, page_url, referrer, session_id, user_agent)
			VALUES (%s, %s, %s, %s, %s, %s)",
			$event_type,
			$event_data,
			$page_url,
			$referrer,
			$session_id,
			$user_agent
		);

		$wpdb->query( $query );
	}

	public function get_event_counts( $days ) {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$query = $wpdb->prepare(
			"SELECT event_type, COUNT(*) as total
			FROM {$this->table_name}
			WHERE created_at >= %s
			GROUP BY event_type
			ORDER BY total DESC",
			$cutoff
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	public function get_top_search_queries( $limit = 10 ) {
		global $wpdb;
		$limit = absint( $limit );

		$query = $wpdb->prepare(
			"SELECT JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.query')) as query, COUNT(*) as total
			FROM {$this->table_name}
			WHERE event_type = %s
			AND JSON_EXTRACT(event_data, '$.query') IS NOT NULL
			GROUP BY query
			ORDER BY total DESC
			LIMIT %d",
			'search',
			$limit
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	public function get_top_referrers( $limit = 10, $days = 30 ) {
		global $wpdb;
		$limit  = absint( $limit );
		$days   = absint( $days );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$query = $wpdb->prepare(
			"SELECT referrer, COUNT(*) as total
			FROM {$this->table_name}
			WHERE created_at >= %s
			AND referrer IS NOT NULL
			AND referrer != ''
			GROUP BY referrer
			ORDER BY total DESC
			LIMIT %d",
			$cutoff,
			$limit
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	public function get_recent_events( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );

		$query = $wpdb->prepare(
			"SELECT event_type, event_data, page_url, referrer, created_at
			FROM {$this->table_name}
			ORDER BY created_at DESC
			LIMIT %d",
			$limit
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}
}
