<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sarai_Analytics_Admin {
	private $database;

	public function __construct( Sarai_Analytics_Database $database ) {
		$this->database = $database;
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	public function register_menu() {
		add_management_page(
			__( 'Sarai Analytics', 'sarai-analytics' ),
			__( 'Sarai Analytics', 'sarai-analytics' ),
			'manage_options',
			'sarai-analytics',
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		$counts_7  = $this->database->get_event_counts( 7 );
		$counts_30 = $this->database->get_event_counts( 30 );
		$searches  = $this->database->get_top_search_queries( 10 );
		$referrers = $this->database->get_top_referrers( 10, 30 );
		$recent    = $this->database->get_recent_events( 20 );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Sarai Analytics', 'sarai-analytics' ); ?></h1>
			<h2><?php echo esc_html__( 'Event Counts (Last 7 Days)', 'sarai-analytics' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Event Type', 'sarai-analytics' ); ?></th>
						<th><?php echo esc_html__( 'Total', 'sarai-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $counts_7 ) ) : ?>
						<tr><td colspan="2"><?php echo esc_html__( 'No events yet.', 'sarai-analytics' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $counts_7 as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['event_type'] ); ?></td>
								<td><?php echo esc_html( $row['total'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<h2><?php echo esc_html__( 'Event Counts (Last 30 Days)', 'sarai-analytics' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Event Type', 'sarai-analytics' ); ?></th>
						<th><?php echo esc_html__( 'Total', 'sarai-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $counts_30 ) ) : ?>
						<tr><td colspan="2"><?php echo esc_html__( 'No events yet.', 'sarai-analytics' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $counts_30 as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['event_type'] ); ?></td>
								<td><?php echo esc_html( $row['total'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<h2><?php echo esc_html__( 'Top Search Queries', 'sarai-analytics' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Query', 'sarai-analytics' ); ?></th>
						<th><?php echo esc_html__( 'Total', 'sarai-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $searches ) ) : ?>
						<tr><td colspan="2"><?php echo esc_html__( 'No searches yet.', 'sarai-analytics' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $searches as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['query'] ); ?></td>
								<td><?php echo esc_html( $row['total'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<h2><?php echo esc_html__( 'Top Referrers (Last 30 Days)', 'sarai-analytics' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Referrer', 'sarai-analytics' ); ?></th>
						<th><?php echo esc_html__( 'Total', 'sarai-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $referrers ) ) : ?>
						<tr><td colspan="2"><?php echo esc_html__( 'No referrers yet.', 'sarai-analytics' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $referrers as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['referrer'] ); ?></td>
								<td><?php echo esc_html( $row['total'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<h2><?php echo esc_html__( 'Recent Events', 'sarai-analytics' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Event Type', 'sarai-analytics' ); ?></th>
						<th><?php echo esc_html__( 'Data', 'sarai-analytics' ); ?></th>
						<th><?php echo esc_html__( 'Page URL', 'sarai-analytics' ); ?></th>
						<th><?php echo esc_html__( 'Referrer', 'sarai-analytics' ); ?></th>
						<th><?php echo esc_html__( 'Created', 'sarai-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $recent ) ) : ?>
						<tr><td colspan="5"><?php echo esc_html__( 'No recent events.', 'sarai-analytics' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $recent as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['event_type'] ); ?></td>
								<td><?php echo esc_html( $row['event_data'] ); ?></td>
								<td><?php echo esc_html( $row['page_url'] ); ?></td>
								<td><?php echo esc_html( $row['referrer'] ); ?></td>
								<td><?php echo esc_html( $row['created_at'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
