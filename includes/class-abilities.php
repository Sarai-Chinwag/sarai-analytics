<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sarai_Analytics_Abilities {
	private $database;

	public function __construct( Sarai_Analytics_Database $database ) {
		$this->database = $database;
	}

	public function register_abilities(): void {
		wp_register_ability(
			'sarai-analytics/get-event-counts',
			array(
				'label'               => __( 'Get Event Counts', 'sarai-analytics' ),
				'description'         => __( 'Returns event counts grouped by type for a given number of days.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'get_event_counts' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'days' => array(
							'type'        => 'integer',
							'description' => __( 'Number of days to look back.', 'sarai-analytics' ),
				'category'            => 'site',
							'default'     => 7,
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'event_type' => array( 'type' => 'string' ),
							'total'      => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/get-top-searches',
			array(
				'label'               => __( 'Get Top Searches', 'sarai-analytics' ),
				'description'         => __( 'Returns the most common search queries.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'get_top_searches' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'limit' => array(
							'type'        => 'integer',
							'description' => __( 'Maximum number of results to return.', 'sarai-analytics' ),
				'category'            => 'site',
							'default'     => 10,
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'query' => array( 'type' => 'string' ),
							'total' => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/get-top-referrers',
			array(
				'label'               => __( 'Get Top Referrers', 'sarai-analytics' ),
				'description'         => __( 'Returns the most common referrers for a given time period.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'get_top_referrers' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'limit' => array(
							'type'        => 'integer',
							'description' => __( 'Maximum number of results to return.', 'sarai-analytics' ),
				'category'            => 'site',
							'default'     => 10,
						),
						'days'  => array(
							'type'        => 'integer',
							'description' => __( 'Number of days to look back.', 'sarai-analytics' ),
				'category'            => 'site',
							'default'     => 30,
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'referrer' => array( 'type' => 'string' ),
							'total'    => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/get-recent-events',
			array(
				'label'               => __( 'Get Recent Events', 'sarai-analytics' ),
				'description'         => __( 'Returns the most recent analytics events.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'get_recent_events' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'limit' => array(
							'type'        => 'integer',
							'description' => __( 'Maximum number of events to return.', 'sarai-analytics' ),
							'default'     => 20,
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'event_type' => array( 'type' => 'string' ),
							'event_data' => array( 'type' => 'string' ),
							'page_url'   => array( 'type' => 'string' ),
							'referrer'   => array( 'type' => 'string' ),
							'created_at' => array( 'type' => 'string' ),
						),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/query-events',
			array(
				'label'               => __( 'Query Events', 'sarai-analytics' ),
				'description'         => __( 'Query events with flexible filters.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'query_events' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'event_type'  => array(
							'type'        => 'string',
							'description' => __( 'Single event type to filter by.', 'sarai-analytics' ),
						),
						'event_types' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'Array of event types to filter by.', 'sarai-analytics' ),
						),
						'date_from'   => array(
							'type'        => 'string',
							'description' => __( 'Start date (YYYY-MM-DD or datetime).', 'sarai-analytics' ),
						),
						'date_to'     => array(
							'type'        => 'string',
							'description' => __( 'End date (YYYY-MM-DD or datetime).', 'sarai-analytics' ),
						),
						'limit'       => array(
							'type'        => 'integer',
							'description' => __( 'Maximum number of results.', 'sarai-analytics' ),
							'default'     => 50,
						),
						'offset'      => array(
							'type'        => 'integer',
							'description' => __( 'Offset for pagination.', 'sarai-analytics' ),
							'default'     => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'         => array( 'type' => 'integer' ),
							'event_type' => array( 'type' => 'string' ),
							'event_data' => array( 'type' => 'string' ),
							'page_url'   => array( 'type' => 'string' ),
							'referrer'   => array( 'type' => 'string' ),
							'session_id' => array( 'type' => 'string' ),
							'user_agent' => array( 'type' => 'string' ),
							'created_at' => array( 'type' => 'string' ),
						),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/get-nav-analytics',
			array(
				'label'               => __( 'Get Nav Analytics', 'sarai-analytics' ),
				'description'         => __( 'Returns navigation click analytics grouped by link.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'get_nav_analytics' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'days'  => array(
							'type'        => 'integer',
							'description' => __( 'Number of days to look back.', 'sarai-analytics' ),
							'default'     => 30,
						),
						'limit' => array(
							'type'        => 'integer',
							'description' => __( 'Maximum number of results.', 'sarai-analytics' ),
							'default'     => 20,
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'text'        => array( 'type' => 'string' ),
							'href'        => array( 'type' => 'string' ),
							'click_count' => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/get-time-series',
			array(
				'label'               => __( 'Get Time Series', 'sarai-analytics' ),
				'description'         => __( 'Returns time series data for an event type.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'get_time_series' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'event_type' ),
					'properties' => array(
						'event_type'  => array(
							'type'        => 'string',
							'description' => __( 'Event type to query.', 'sarai-analytics' ),
						),
						'days'        => array(
							'type'        => 'integer',
							'description' => __( 'Number of days to look back.', 'sarai-analytics' ),
							'default'     => 30,
						),
						'granularity' => array(
							'type'        => 'string',
							'description' => __( 'Granularity: day or hour.', 'sarai-analytics' ),
							'enum'        => array( 'day', 'hour' ),
							'default'     => 'day',
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'period' => array( 'type' => 'string' ),
							'count'  => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/get-smi-funnel',
			array(
				'label'               => __( 'Get SMI Funnel', 'sarai-analytics' ),
				'description'         => __( 'Returns Sell My Images conversion funnel metrics.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'get_smi_funnel' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'days' => array(
							'type'        => 'integer',
							'description' => __( 'Number of days to look back.', 'sarai-analytics' ),
							'default'     => 30,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'clicks'          => array( 'type' => 'integer' ),
						'payments'        => array( 'type' => 'integer' ),
						'conversion_rate' => array( 'type' => 'number' ),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/get-spawn-metrics',
			array(
				'label'               => __( 'Get Spawn Metrics', 'sarai-analytics' ),
				'description'         => __( 'Returns Spawn service metrics.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'get_spawn_metrics' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'days' => array(
							'type'        => 'integer',
							'description' => __( 'Number of days to look back.', 'sarai-analytics' ),
							'default'     => 30,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'signups'           => array( 'type' => 'integer' ),
						'failed'            => array( 'type' => 'integer' ),
						'success_rate'      => array( 'type' => 'number' ),
						'credits_purchased' => array( 'type' => 'number' ),
						'domains_renewed'   => array( 'type' => 'integer' ),
					),
				),
			)
		);

		wp_register_ability(
			'sarai-analytics/track-event',
			array(
				'label'               => __( 'Track Event', 'sarai-analytics' ),
				'description'         => __( 'Track a custom analytics event programmatically.', 'sarai-analytics' ),
				'category'            => 'site',
				'execute_callback'    => array( $this, 'track_event' ),
				'permission_callback' => array( $this, 'can_manage_options' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'event_type' ),
					'properties' => array(
						'event_type' => array(
							'type'        => 'string',
							'description' => __( 'Event type to track.', 'sarai-analytics' ),
						),
						'event_data' => array(
							'type'        => 'object',
							'description' => __( 'Event data object.', 'sarai-analytics' ),
						),
						'page_url'   => array(
							'type'        => 'string',
							'description' => __( 'Page URL where event occurred.', 'sarai-analytics' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	public function get_event_counts( $input ) {
		$days = 7;
		if ( isset( $input['days'] ) ) {
			$days = absint( $input['days'] );
		}
		if ( $days < 1 ) {
			$days = 1;
		}

		return $this->database->get_event_counts( $days );
	}

	public function get_top_searches( $input ) {
		$limit = 10;
		if ( isset( $input['limit'] ) ) {
			$limit = absint( $input['limit'] );
		}
		if ( $limit < 1 ) {
			$limit = 1;
		}

		return $this->database->get_top_search_queries( $limit );
	}

	public function get_top_referrers( $input ) {
		$limit = 10;
		$days  = 30;
		if ( isset( $input['limit'] ) ) {
			$limit = absint( $input['limit'] );
		}
		if ( isset( $input['days'] ) ) {
			$days = absint( $input['days'] );
		}
		if ( $limit < 1 ) {
			$limit = 1;
		}
		if ( $days < 1 ) {
			$days = 1;
		}

		return $this->database->get_top_referrers( $limit, $days );
	}

	public function get_recent_events( $input ) {
		$limit = 20;
		if ( isset( $input['limit'] ) ) {
			$limit = absint( $input['limit'] );
		}
		if ( $limit < 1 ) {
			$limit = 1;
		}

		return $this->database->get_recent_events( $limit );
	}

	/**
	 * Query events with filters.
	 *
	 * @param array $input Input parameters.
	 * @return array Events matching the query.
	 */
	public function query_events( $input ) {
		$args = array(
			'event_type'  => isset( $input['event_type'] ) ? sanitize_text_field( $input['event_type'] ) : null,
			'event_types' => null,
			'date_from'   => isset( $input['date_from'] ) ? sanitize_text_field( $input['date_from'] ) : null,
			'date_to'     => isset( $input['date_to'] ) ? sanitize_text_field( $input['date_to'] ) : null,
			'limit'       => isset( $input['limit'] ) ? absint( $input['limit'] ) : 50,
			'offset'      => isset( $input['offset'] ) ? absint( $input['offset'] ) : 0,
			'order'       => isset( $input['order'] ) ? sanitize_text_field( $input['order'] ) : 'DESC',
		);

		if ( isset( $input['event_types'] ) && is_array( $input['event_types'] ) ) {
			$args['event_types'] = array_map( 'sanitize_text_field', $input['event_types'] );
		}

		return $this->database->query_events( $args );
	}

	/**
	 * Get navigation analytics.
	 *
	 * @param array $input Input parameters.
	 * @return array Nav analytics data.
	 */
	public function get_nav_analytics( $input ) {
		$days  = isset( $input['days'] ) ? absint( $input['days'] ) : 30;
		$limit = isset( $input['limit'] ) ? absint( $input['limit'] ) : 20;

		if ( $days < 1 ) {
			$days = 30;
		}
		if ( $limit < 1 ) {
			$limit = 20;
		}

		return $this->database->get_nav_analytics( $days, $limit );
	}

	/**
	 * Get time series data.
	 *
	 * @param array $input Input parameters.
	 * @return array Time series data.
	 */
	public function get_time_series( $input ) {
		if ( empty( $input['event_type'] ) ) {
			return array();
		}

		$event_type  = sanitize_text_field( $input['event_type'] );
		$days        = isset( $input['days'] ) ? absint( $input['days'] ) : 30;
		$granularity = isset( $input['granularity'] ) ? sanitize_text_field( $input['granularity'] ) : 'day';

		if ( $days < 1 ) {
			$days = 30;
		}
		if ( ! in_array( $granularity, array( 'day', 'hour' ), true ) ) {
			$granularity = 'day';
		}

		return $this->database->get_time_series( $event_type, $days, $granularity );
	}

	/**
	 * Get SMI conversion funnel.
	 *
	 * @param array $input Input parameters.
	 * @return array Funnel metrics.
	 */
	public function get_smi_funnel( $input ) {
		$days = isset( $input['days'] ) ? absint( $input['days'] ) : 30;
		if ( $days < 1 ) {
			$days = 30;
		}

		return $this->database->get_smi_funnel( $days );
	}

	/**
	 * Get Spawn metrics.
	 *
	 * @param array $input Input parameters.
	 * @return array Spawn metrics.
	 */
	public function get_spawn_metrics( $input ) {
		$days = isset( $input['days'] ) ? absint( $input['days'] ) : 30;
		if ( $days < 1 ) {
			$days = 30;
		}

		return $this->database->get_spawn_metrics( $days );
	}

	/**
	 * Track an event programmatically.
	 *
	 * @param array $input Input parameters.
	 * @return array Result with success and message.
	 */
	public function track_event( $input ) {
		if ( empty( $input['event_type'] ) ) {
			return array(
				'success' => false,
				'message' => 'event_type is required',
			);
		}

		$event_type = sanitize_text_field( $input['event_type'] );
		$event_data = isset( $input['event_data'] ) && is_array( $input['event_data'] ) ? $input['event_data'] : array();
		$page_url   = isset( $input['page_url'] ) ? esc_url_raw( $input['page_url'] ) : '';

		$result = Sarai_Analytics::track( $event_type, $event_data, $page_url );

		if ( $result ) {
			return array(
				'success' => true,
				'message' => "Event '{$event_type}' tracked successfully",
			);
		}

		return array(
			'success' => false,
			'message' => "Event '{$event_type}' is not in allowed events list",
		);
	}

	public function can_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}
}
