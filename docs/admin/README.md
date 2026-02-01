# Admin Dashboard

Sarai Analytics provides a simple admin dashboard for viewing event analytics. The dashboard is accessible to users with `manage_options` capability (typically administrators).

## Accessing the Dashboard

### Menu Location
- **WordPress Admin** → **Tools** → **Sarai Analytics**

### Direct URL
`wp-admin/tools.php?page=sarai-analytics`

### Permissions
Requires `manage_options` capability (administrator level access)

## Dashboard Features

The dashboard displays five main sections with event data:

### 1. Event Counts (Last 7 Days)

Shows the total number of events for each event type in the past week.

- **Data Source**: Aggregated by event_type with date filter
- **Columns**: Event Type, Total Count
- **Sorting**: Descending by total count
- **Empty State**: "No events yet." message

### 2. Event Counts (Last 30 Days)

Shows the total number of events for each event type in the past month.

- **Data Source**: Same as 7-day view but with 30-day window
- **Purpose**: Compare recent activity trends

### 3. Top Search Queries

Displays the most popular search terms submitted through the site.

- **Data Source**: Search events with non-null queries
- **Limit**: Top 10 queries (configurable)
- **Display**: Query text and frequency count
- **Empty State**: "No searches yet." message

### 4. Top Referrers (Last 30 Days)

Shows the websites that send the most traffic to the site.

- **Data Source**: Events with non-empty referrer URLs
- **Time Frame**: Last 30 days
- **Limit**: Top 10 referrers
- **Filtering**: Excludes empty/null referrers
- **Empty State**: "No referrers yet." message

### 5. Recent Events

Lists the most recently recorded events.

- **Data Source**: Last 20 events by creation date
- **Columns**: Event Type, Event Data, Page URL, Referrer, Created Date
- **Purpose**: Debug and monitor recent activity
- **Empty State**: "No recent events." message

## Data Presentation

### Tables

All data is displayed using WordPress's `widefat striped` table styling:

- Zebra-striped rows for readability
- Responsive design
- Proper escaping of all output data

### Internationalization

All text strings use WordPress internationalization functions:

- `__()` for translatable strings
- `esc_html__()` for HTML-escaped translatable strings
- Text domain: `sarai-analytics`

### Date Formatting

- Uses WordPress's default date/time formatting
- Timestamps displayed in local timezone
- Human-readable relative dates where appropriate

## Customization Options

### Modifying Display Limits

The dashboard uses hardcoded limits but can be customized by extending the admin class:

```php
class Custom_Sarai_Admin extends Sarai_Analytics_Admin {
    public function render_page() {
        $counts_7  = $this->database->get_event_counts(7);
        $counts_30 = $this->database->get_event_counts(30);
        $searches  = $this->database->get_top_search_queries(20); // Increased limit
        $referrers = $this->database->get_top_referrers(15, 30);  // Custom limit
        $recent    = $this->database->get_recent_events(50);      // More recent events

        // Custom rendering logic...
    }
}
```

### Adding Custom Metrics

You can extend the dashboard to show additional analytics:

```php
add_action( 'admin_notices', function() {
    $screen = get_current_screen();
    if ( $screen->id === 'tools_page_sarai-analytics' ) {
        // Add custom content to the dashboard
        echo '<div class="notice notice-info">';
        echo '<p>Custom metric: ' . get_custom_metric() . '</p>';
        echo '</div>';
    }
} );
```

### Custom Styling

Add custom CSS for the dashboard:

```php
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( $hook === 'tools_page_sarai-analytics' ) {
        wp_enqueue_style( 'custom-sarai-analytics', get_stylesheet_directory_uri() . '/sarai-analytics.css' );
    }
} );
```

## Security Considerations

### Capability Checks

- Dashboard only accessible to users with `manage_options`
- All data properly escaped before output
- No direct database queries in templates

### Data Privacy

- No personally identifiable information displayed
- Session IDs are not shown (anonymous)
- User agents are truncated if very long
- URLs are properly escaped

## Performance

### Database Queries

The dashboard performs 5 separate database queries:

1. 7-day event counts
2. 30-day event counts
3. Top search queries (with JSON extraction)
4. Top referrers (30-day window)
5. Recent events (limited to 20)

### Caching

No built-in caching is implemented. For high-traffic sites, consider:

```php
// Cache expensive queries
$counts_7 = get_transient( 'sarai_counts_7' );
if ( false === $counts_7 ) {
    $counts_7 = $this->database->get_event_counts(7);
    set_transient( 'sarai_counts_7', $counts_7, HOUR_IN_SECONDS );
}
```

## Troubleshooting

### Empty Tables

If tables show "No events yet":

- Check if the tracker script is loading on frontend
- Verify Do Not Track settings aren't blocking events
- Check browser console for JavaScript errors
- Confirm the REST API endpoint is accessible

### Missing Data

If certain events aren't appearing:

- Verify the event type is in the allowed list
- Check if the frontend detection logic matches your elements
- Review JavaScript console for fetch errors
- Confirm rate limiting isn't blocking events

### Performance Issues

If the dashboard loads slowly:

- Check database table size
- Verify indexes are present
- Consider adding caching
- Review MySQL slow query logs

## Exporting Data

The dashboard doesn't include export functionality by default. To export data:

```php
// Add export button to dashboard
add_action( 'admin_footer-tools_page_sarai-analytics', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.wrap h1').after('<a href="?page=sarai-analytics&export=1" class="page-title-action">Export Data</a>');
    });
    </script>
    <?php
} );

// Handle export in admin class
if ( isset( $_GET['export'] ) ) {
    // CSV export logic...
}
```

## Future Enhancements

Potential dashboard improvements:

- Date range picker for custom time periods
- Charts/graphs for visual analytics
- Export functionality (CSV/JSON)
- Filtering and search capabilities
- Real-time updates via AJAX
- Comparative period analysis

This dashboard provides a simple, effective way to monitor custom events that standard analytics tools miss.