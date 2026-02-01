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
				'category'            => 'site',
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

	public function can_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}
}
