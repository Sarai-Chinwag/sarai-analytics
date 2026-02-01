# Event Types

Sarai Analytics tracks five specific event types that are customized for saraichinwag.com's functionality. These events capture interactions that Google Analytics 4 doesn't automatically track.

## Overview

Events are automatically detected and sent by the JavaScript tracker on the frontend. Each event includes:

- Event type identifier
- Optional event-specific data (stored as JSON)
- Current page URL
- HTTP referrer
- User agent string
- Session ID (managed via cookies)

## Event Types

### page_view

**Description**: Tracks when a page is loaded.

**Triggered**: Automatically on page load, after checking Do Not Track settings.

**Event Data**: None (empty object)

**Example**:
```json
{
  "event_type": "page_view",
  "event_data": {},
  "page_url": "https://saraichinwag.com/some-page/",
  "referrer": "https://google.com"
}
```

### image_mode

**Description**: Tracks when a user visits an image gallery page.

**Triggered**: On page load if the URL path contains `/images/`.

**Event Data**:
- `path`: The full pathname of the current URL

**Example**:
```json
{
  "event_type": "image_mode",
  "event_data": {
    "path": "/category/artwork/images/"
  },
  "page_url": "https://saraichinwag.com/category/artwork/images/",
  "referrer": ""
}
```

### random_click

**Description**: Tracks clicks on the random/surprise functionality.

**Triggered**: When a user clicks an element with ID `random-icon-link` or class `surprise-me`, or their child elements.

**Event Data**:
- `from`: The pathname of the current page where the click occurred

**Example**:
```json
{
  "event_type": "random_click",
  "event_data": {
    "from": "/gallery/"
  },
  "page_url": "https://saraichinwag.com/gallery/",
  "referrer": "https://saraichinwag.com/"
}
```

### smi_click

**Description**: Tracks clicks on "Download Hi-Res" buttons or other SMI (Social Media Image) related elements.

**Triggered**: When a user clicks an element with class `download-hires` or attribute `data-sarai-smi`, or their child elements.

**Event Data**:
- `label`: The text content of the clicked button/element

**Example**:
```json
{
  "event_type": "smi_click",
  "event_data": {
    "label": "Download Hi-Res"
  },
  "page_url": "https://saraichinwag.com/artwork/example-piece/",
  "referrer": ""
}
```

### search

**Description**: Tracks search form submissions.

**Triggered**: When a user submits a form containing an input with `type="search"` or `name="s"`.

**Event Data**:
- `query`: The search query entered by the user

**Example**:
```json
{
  "event_type": "search",
  "event_data": {
    "query": "watercolor tutorial"
  },
  "page_url": "https://saraichinwag.com/",
  "referrer": "https://google.com"
}
```

## Customization

The list of allowed events can be modified using the `sarai_analytics_allowed_events` filter:

```php
add_filter( 'sarai_analytics_allowed_events', function( $events ) {
    // Add a custom event
    $events[] = 'custom_interaction';
    // Remove an existing event
    $events = array_diff( $events, ['page_view'] );
    return $events;
} );
```

## Privacy Considerations

- All events respect the Do Not Track HTTP header
- Session IDs are generated using WordPress's `wp_generate_uuid4()` function
- No personally identifiable information (PII) is stored
- IP addresses are not collected or stored
- User IDs are not tracked

## Data Storage

Event data is stored in the `wp_sarai_events` table with the following structure:

- `id`: Auto-incrementing primary key
- `event_type`: Event type string (50 char max)
- `event_data`: JSON-encoded event data
- `page_url`: Current page URL (500 char max)
- `referrer`: HTTP referrer (500 char max)
- `session_id`: Session identifier (64 char max)
- `user_agent`: Browser user agent string (500 char max)
- `created_at`: Timestamp of event creation

All text fields are properly sanitized before storage.