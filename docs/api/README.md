# REST API

Sarai Analytics provides a RESTful API endpoint for submitting event data. The API is designed to be simple, secure, and rate-limited.

## Endpoint

```
POST /wp-json/sarai-analytics/v1/event
```

## Authentication

The endpoint is public and does not require authentication. However, it includes several security measures:

- Rate limiting per session
- Input sanitization
- Event type validation
- Do Not Track header respect

## Request Format

### Method
`POST`

### Content-Type
`application/json`

### Body Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `event_type` | string | Yes | Event type identifier (must be in allowed events list) |
| `event_data` | object/string | No | Event-specific data (object or string) |
| `page_url` | string | No | Current page URL |
| `referrer` | string | No | HTTP referrer URL |

### Example Request

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

## Response Format

### Success Response (204 No Content)

When an event is successfully recorded:

```http
HTTP/1.1 204 No Content
```

No response body is returned for successful requests.

### Error Responses

#### Invalid Event Type (400 Bad Request)

```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "message": "Invalid event type."
}
```

#### Rate Limit Exceeded (429 Too Many Requests)

```http
HTTP/1.1 429 Too Many Requests
Content-Type: application/json

{
  "message": "Rate limit exceeded."
}
```

#### Do Not Track (204 No Content)

When the user's Do Not Track setting is enabled, the server responds with 204 No Content (same as success) but does not store the event.

## Validation Rules

### Event Type Validation

- Must be a non-empty string
- Must be included in the allowed events list
- Default allowed events: `['random_click', 'image_mode', 'smi_click', 'search', 'page_view']`

### Event Data Sanitization

- Arrays are sanitized recursively (keys sanitized, scalar values sanitized)
- Strings are sanitized using WordPress sanitization functions
- Non-scalar values in arrays are ignored

### URL Validation

- `page_url` and `referrer` are validated as URLs
- Invalid URLs are stored as empty strings

## Rate Limiting

- **Limit**: 10 events per minute per session
- **Implementation**: Uses WordPress transients with session-based keys
- **Duration**: 1 minute (60 seconds)
- **Behavior**: When limit is exceeded, returns 429 status code

## Session Management

- Sessions are managed via HTTP cookies
- Cookie name: `sarai_analytics_session` (filterable via `sarai_analytics_session_cookie`)
- Session IDs are UUID4 generated
- Cookies are set with:
  - Path: `/`
  - Secure: true if HTTPS
  - HttpOnly: true

## Privacy Features

### Do Not Track Support

The API respects Do Not Track headers:

- HTTP header: `DNT: 1`
- Server checks: `$_SERVER['HTTP_DNT'] === '1'`

When Do Not Track is enabled, events are silently ignored with a 204 response.

### Data Minimization

- No IP addresses stored
- No user IDs stored
- No personal information collected
- Session IDs are anonymous UUIDs

## Customization

### Modifying Allowed Events

```php
add_filter( 'sarai_analytics_allowed_events', function( $events ) {
    $events[] = 'custom_event';
    return $events;
} );
```

### Changing Rate Limit

```php
add_filter( 'sarai_analytics_rate_limit', function( $limit ) {
    return 20; // Allow 20 events per minute
} );
```

### Custom Session Cookie

```php
add_filter( 'sarai_analytics_session_cookie', function( $cookie_name ) {
    return 'my_custom_session_cookie';
} );
```

## Error Handling

The API is designed to fail gracefully:

- Invalid JSON in request body is handled by WordPress REST API
- Database errors are logged but don't expose details to client
- All responses include appropriate HTTP status codes
- Error messages are generic to avoid information disclosure

## Usage Examples

### JavaScript (Frontend)

```javascript
fetch('/wp-json/sarai-analytics/v1/event', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    event_type: 'search',
    event_data: { query: 'tutorial' },
    page_url: window.location.href,
    referrer: document.referrer
  })
});
```

### cURL

```bash
curl -X POST \
  https://example.com/wp-json/sarai-analytics/v1/event \
  -H 'Content-Type: application/json' \
  -d '{
    "event_type": "page_view",
    "event_data": {},
    "page_url": "https://example.com/page",
    "referrer": "https://google.com"
  }'
```