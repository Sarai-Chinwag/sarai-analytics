# Sarai Analytics

Lightweight WordPress analytics plugin for saraichinwag.com. Tracks custom events missed by GA4, stores them locally, and provides a simple admin dashboard.

## Features
- Tracks custom events: random_click, image_mode, smi_click, search, page_view
- Local storage in `{prefix}sarai_events`
- REST endpoint: `POST /wp-json/sarai-analytics/v1/event`
- Simple admin dashboard under Tools

## Installation
1. Upload this plugin folder to `wp-content/plugins/sarai-analytics`.
2. Activate "Sarai Analytics" in the WordPress admin.

## Event Tracking
The plugin automatically enqueues a lightweight JS tracker on the frontend that:
- Respects Do Not Track
- Sends events via `fetch()` to the REST endpoint

## Admin Dashboard
Go to Tools → Sarai Analytics to view:
- Event counts (last 7/30 days)
- Top search queries
- Top referrers
- Recent events

## Customization
Allowed events can be filtered via:
```
add_filter( 'sarai_analytics_allowed_events', function( $events ) {
    $events[] = 'custom_event';
    return $events;
} );
```
