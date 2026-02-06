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

	/**
	 * Query events with filters.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of events.
	 */
	public function query_events( $args ) {
		global $wpdb;

		$defaults = array(
			'event_type'  => null,
			'event_types' => null,
			'date_from'   => null,
			'date_to'     => null,
			'limit'       => 50,
			'offset'      => 0,
			'order'       => 'DESC',
		);

		$args   = wp_parse_args( $args, $defaults );
		$where  = array( '1=1' );
		$values = array();

		// Handle single event_type or array of event_types.
		if ( ! empty( $args['event_types'] ) && is_array( $args['event_types'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['event_types'] ), '%s' ) );
			$where[]      = "event_type IN ($placeholders)";
			foreach ( $args['event_types'] as $type ) {
				$values[] = sanitize_text_field( $type );
			}
		} elseif ( ! empty( $args['event_type'] ) ) {
			$where[]  = 'event_type = %s';
			$values[] = sanitize_text_field( $args['event_type'] );
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = sanitize_text_field( $args['date_from'] );
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = sanitize_text_field( $args['date_to'] );
		}

		$order  = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';
		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		if ( $limit < 1 ) {
			$limit = 50;
		}

		$where_clause = implode( ' AND ', $where );

		// Build query with values for prepare.
		$values[] = $limit;
		$values[] = $offset;

		$query = $wpdb->prepare(
			"SELECT id, event_type, event_data, page_url, referrer, session_id, user_agent, created_at
			FROM {$this->table_name}
			WHERE {$where_clause}
			ORDER BY created_at {$order}
			LIMIT %d OFFSET %d",
			$values
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get navigation click analytics.
	 *
	 * @param int $days  Number of days to look back.
	 * @param int $limit Maximum results to return.
	 * @return array Array of nav analytics.
	 */
	public function get_nav_analytics( $days, $limit ) {
		global $wpdb;

		$days   = absint( $days );
		$limit  = absint( $limit );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$query = $wpdb->prepare(
			"SELECT 
				JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.text')) as text,
				JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.href')) as href,
				COUNT(*) as click_count
			FROM {$this->table_name}
			WHERE event_type = %s
			AND created_at >= %s
			GROUP BY href, text
			ORDER BY click_count DESC
			LIMIT %d",
			'nav_click',
			$cutoff,
			$limit
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get time series data for an event type.
	 *
	 * @param string $event_type  Event type to query.
	 * @param int    $days        Number of days to look back.
	 * @param string $granularity 'day' or 'hour'.
	 * @return array Array of { period, count }.
	 */
	public function get_time_series( $event_type, $days, $granularity = 'day' ) {
		global $wpdb;

		$days   = absint( $days );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		if ( 'hour' === $granularity ) {
			$period_sql = "DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as period";
		} else {
			$period_sql = 'DATE(created_at) as period';
		}

		$query = $wpdb->prepare(
			"SELECT {$period_sql}, COUNT(*) as count
			FROM {$this->table_name}
			WHERE event_type = %s
			AND created_at >= %s
			GROUP BY period
			ORDER BY period ASC",
			sanitize_text_field( $event_type ),
			$cutoff
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get SMI conversion funnel metrics.
	 *
	 * @param int $days Number of days to look back.
	 * @return array Funnel metrics.
	 */
	public function get_smi_funnel( $days ) {
		global $wpdb;

		$days   = absint( $days );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$clicks_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name}
			WHERE event_type = %s AND created_at >= %s",
			'smi_click',
			$cutoff
		);
		$clicks = (int) $wpdb->get_var( $clicks_query );

		$payments_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name}
			WHERE event_type = %s AND created_at >= %s",
			'smi_payment',
			$cutoff
		);
		$payments = (int) $wpdb->get_var( $payments_query );

		$conversion_rate = $clicks > 0 ? round( ( $payments / $clicks ) * 100, 2 ) : 0;

		return array(
			'clicks'          => $clicks,
			'payments'        => $payments,
			'conversion_rate' => $conversion_rate,
		);
	}

	/**
	 * Get Spawn service metrics.
	 *
	 * @param int $days Number of days to look back.
	 * @return array Spawn metrics.
	 */
	public function get_spawn_metrics( $days ) {
		global $wpdb;

		$days   = absint( $days );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Signups (spawn_provisioning with status=complete).
		$signups_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name}
			WHERE event_type = %s
			AND created_at >= %s
			AND JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.status')) = %s",
			'spawn_provisioning',
			$cutoff,
			'complete'
		);
		$signups = (int) $wpdb->get_var( $signups_query );

		// Failed provisioning.
		$failed_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name}
			WHERE event_type = %s
			AND created_at >= %s
			AND JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.status')) = %s",
			'spawn_provisioning',
			$cutoff,
			'failed'
		);
		$failed = (int) $wpdb->get_var( $failed_query );

		// Success rate.
		$total_provisioning = $signups + $failed;
		$success_rate       = $total_provisioning > 0 ? round( ( $signups / $total_provisioning ) * 100, 2 ) : 0;

		// Credits purchased (sum from spawn_credits events).
		$credits_query = $wpdb->prepare(
			"SELECT COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.amount')) AS DECIMAL(10,2))), 0)
			FROM {$this->table_name}
			WHERE event_type = %s AND created_at >= %s",
			'spawn_credits',
			$cutoff
		);
		$credits_purchased = (float) $wpdb->get_var( $credits_query );

		// Domains renewed.
		$domains_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name}
			WHERE event_type = %s AND created_at >= %s",
			'spawn_domain',
			$cutoff
		);
		$domains_renewed = (int) $wpdb->get_var( $domains_query );

		return array(
			'signups'           => $signups,
			'failed'            => $failed,
			'success_rate'      => $success_rate,
			'credits_purchased' => $credits_purchased,
			'domains_renewed'   => $domains_renewed,
		);
	}
}
