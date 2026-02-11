# Sarai Analytics

Lightweight WordPress analytics plugin for [saraichinwag.com](https://saraichinwag.com). Tracks custom events missed by GA4, stores them locally in the database, and provides an admin dashboard.

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Upload the `sarai-analytics` folder to `wp-content/plugins/`.
2. Activate **Sarai Analytics** in the WordPress admin.
3. The events table (`{prefix}sarai_events`) is created automatically on activation.

## Configuration

No configuration required. The plugin works out of the box. Customize behavior with filters (see [Hooks/Filters](#hooksfilters)).

## Usage

### Frontend Tracking

A lightweight JavaScript tracker (`assets/js/tracker.js`) is automatically enqueued on all frontend pages. It:

- Respects Do Not Track browser settings
- Sends events via `fetch()` to the REST endpoint
- Receives the list of allowed event types via `wp_localize_script`

### REST API

**Endpoint:** `POST /wp-json/sarai-analytics/v1/event`

**Authentication:** None required (public endpoint).

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `event_type` | string | Yes | Must be in the allowed events list |
| `event_data` | object/string | No | Arbitrary event metadata (JSON) |
| `page_url` | string | No | Page where event occurred |
| `referrer` | string | No | Referrer URL |

**Responses:**

| Code | Meaning |
|------|---------|
| 204 | Event tracked (or DNT respected) |
| 400 | Invalid event type |
| 429 | Rate limit exceeded |

**Rate limit:** 10 events per session (configurable via `sarai_analytics_rate_limit` filter).

### Programmatic Tracking

```php
Sarai_Analytics::track( 'custom_event', array( 'key' => 'value' ), 'https://page.url' );
```

Or fire the generic hook from any plugin:

```php
do_action( 'sarai_analytics_track', 'event_type', array( 'key' => 'value' ), 'https://page.url' );
```

### Default Event Types

`random_click`, `image_mode`, `smi_click`, `search`, `page_view`, `nav_click`, `smi_payment`, `smi_job_status`, `spawn_credits`, `spawn_provisioning`, `spawn_domain`

### Admin Dashboard

**Tools → Sarai Analytics** — view event counts (7/30 days), top search queries, top referrers, and recent events.

### Cross-Plugin Integrations

The plugin automatically listens to hooks from other plugins:

- **Sell My Images:** `smi_payment_completed`, `smi_job_status_changed`
- **Spawn:** `spawn_credits_purchased`, `spawn_provisioning_complete`, `spawn_provisioning_failed`, `spawn_domain_renewed`, `spawn_auto_refill_success`

### Abilities API

Registers abilities via the WordPress Abilities API (`wp_abilities_api_init`):

- `sarai-analytics/get-event-counts` — Event counts by type for N days
- `sarai-analytics/get-top-searches` — Most common search queries

### Database

Events are stored in `{prefix}sarai_events`:

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Auto-increment primary key |
| `event_type` | VARCHAR(50) | Event type identifier |
| `event_data` | JSON | Arbitrary event metadata |
| `page_url` | VARCHAR(500) | Page URL |
| `referrer` | VARCHAR(500) | Referrer URL |
| `session_id` | VARCHAR(64) | Hashed session identifier |
| `user_agent` | VARCHAR(500) | Browser user agent |
| `created_at` | DATETIME | Timestamp |

## Hooks/Filters

| Hook | Type | Description |
|------|------|-------------|
| `sarai_analytics_allowed_events` | Filter | Modify the list of accepted event types |
| `sarai_analytics_rate_limit` | Filter | Change rate limit per session (default: 10) |
| `sarai_analytics_track` | Action | Track an event from any plugin |

```php
// Add a custom event type
add_filter( 'sarai_analytics_allowed_events', function( $events ) {
    $events[] = 'custom_event';
    return $events;
} );
```

## Documentation

See the `docs/` directory for detailed documentation on each subsystem.
