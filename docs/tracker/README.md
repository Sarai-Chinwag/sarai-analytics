# JavaScript Tracker

The frontend JavaScript tracker automatically detects and sends events to the REST API. It's lightweight, privacy-respecting, and requires no jQuery dependency.

## Overview

The tracker (`assets/js/tracker.js`) is automatically enqueued on all frontend pages (excluding admin). It:

- Respects Do Not Track settings
- Detects events based on DOM interactions
- Sends data via the REST API
- Handles errors gracefully
- Manages session tracking

## File Structure

```
assets/js/tracker.js  # Main tracker script
```

## Integration

### Automatic Loading

The tracker is automatically loaded via WordPress's script enqueueing:

```php
wp_enqueue_script(
    'sarai-analytics-tracker',
    plugin_url . 'assets/js/tracker.js',
    array(),
    '1.0.0',
    true  // Load in footer
);
```

### Localization

Configuration is passed via `wp_localize_script()`:

```javascript
SaraiAnalytics: {
    endpoint: '/wp-json/sarai-analytics/v1/event',
    events: ['random_click', 'image_mode', 'smi_click', 'search', 'page_view']
}
```

## Event Detection

### Page View Events

**Trigger**: Page load (after DOM ready)

**Code**:
```javascript
function trackPageView() {
    if (hasEvent('page_view')) {
        sendEvent('page_view', {});
    }
}
```

**Conditions**: Only fires if `page_view` is in allowed events list

### Image Mode Events

**Trigger**: Page load with `/images/` in URL path

**Detection Pattern**: `window.location.pathname.indexOf('/images') !== -1`

**Code**:
```javascript
function trackImageMode() {
    if (window.location.pathname.indexOf('/images') !== -1 && hasEvent('image_mode')) {
        sendEvent('image_mode', { path: window.location.pathname });
    }
}
```

**Supported Paths**:
- `/images/`
- `/category/artwork/images/`
- `/tag/watercolor/images/`

### Random Click Events

**Trigger**: Click on elements with specific selectors

**Selectors**:
- `#random-icon-link`
- `.surprise-me`

**Code**:
```javascript
document.addEventListener('click', function (event) {
    var target = event.target;
    var randomLink = target.closest('#random-icon-link, .surprise-me');
    if (randomLink && hasEvent('random_click')) {
        sendEvent('random_click', { from: window.location.pathname });
    }
});
```

**Event Data**: Current page pathname

### SMI Click Events

**Trigger**: Click on download/SMI related elements

**Selectors**:
- `.download-hires`
- `[data-sarai-smi]`

**Code**:
```javascript
document.addEventListener('click', function (event) {
    var target = event.target;
    var button = target.closest ? target.closest('.download-hires, [data-sarai-smi]') : null;
    if (button && hasEvent('smi_click')) {
        sendEvent('smi_click', { label: button.textContent || '' });
    }
});
```

**Event Data**: Button text content

### Search Events

**Trigger**: Form submission with search input

**Detection**:
- Form submit event
- Input with `type="search"` or `name="s"`
- Must be inside a `<form>` element

**Code**:
```javascript
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form.tagName === 'FORM') {
        var input = form.querySelector('input[type="search"], input[name="s"]');
        if (input && hasEvent('search')) {
            sendEvent('search', { query: input.value || '' });
        }
    }
});
```

**Event Data**: Search query value

## Privacy Features

### Do Not Track Support

The tracker checks multiple Do Not Track indicators:

```javascript
if (navigator.doNotTrack === '1' ||
    window.doNotTrack === '1' ||
    navigator.msDoNotTrack === '1') {
    return; // Exit early
}
```

**Supported Headers**:
- `navigator.doNotTrack` (standard)
- `window.doNotTrack` (legacy)
- `navigator.msDoNotTrack` (Microsoft)

### No Personal Data

- No IP addresses collected
- No user IDs stored
- No cookies read (except session cookie set by server)
- Only anonymous session tracking

## Error Handling

### Network Failures

```javascript
fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
}).catch(function () {
    return null; // Silent failure
});
```

**Behavior**:
- Failed requests are silently ignored
- No console errors thrown
- No impact on page performance

### Missing Configuration

```javascript
if (!window.SaraiAnalytics || !window.SaraiAnalytics.endpoint) {
    return; // Exit if not configured
}
```

**Safety Checks**:
- Verifies `SaraiAnalytics` object exists
- Ensures endpoint URL is available
- Checks for allowed events list

## Event Sending

### Payload Structure

```javascript
{
    event_type: 'page_view',
    event_data: {},
    page_url: window.location.href,
    referrer: document.referrer || ''
}
```

### Request Details

- **Method**: POST
- **Content-Type**: application/json
- **Body**: JSON-encoded payload
- **Endpoint**: `/wp-json/sarai-analytics/v1/event`

### Session Management

Sessions are managed server-side, but the client sends the session cookie automatically with each request.

## Customization

### Adding Custom Events

To add custom event detection:

1. Add event type to allowed events filter
2. Extend the tracker script
3. Add detection logic

**Example**:
```javascript
// In PHP
add_filter( 'sarai_analytics_allowed_events', function( $events ) {
    $events[] = 'custom_button_click';
    return $events;
} );

// In JavaScript (custom script)
document.addEventListener('click', function(event) {
    if (event.target.matches('.custom-button')) {
        sendEvent('custom_button_click', { buttonId: event.target.id });
    }
});
```

### Modifying Selectors

To change detection selectors, create a custom tracker:

```javascript
// Custom tracker.js
(function() {
    // Copy original tracker code
    // Modify selectors as needed
    var customSelectors = {
        random: '#my-random-link, .my-surprise',
        smi: '.my-download, [data-custom-smi]'
    };

    // Use customSelectors in event listeners
})();
```

### Conditional Loading

To load tracker only on specific pages:

```php
add_action( 'wp_enqueue_scripts', function() {
    if ( is_page( 'special-page' ) ) {
        wp_enqueue_script( 'sarai-analytics-tracker' );
        wp_localize_script( 'sarai-analytics-tracker', 'SaraiAnalytics', array(
            'endpoint' => rest_url( 'sarai-analytics/v1/event' ),
            'events'   => apply_filters( 'sarai_analytics_allowed_events', array( 'page_view' ) )
        ) );
    }
}, 20 ); // Priority after default enqueue
```

## Performance

### Bundle Size

- Minified: ~2KB
- Gzipped: ~1KB
- No external dependencies

### Execution

- Loads in footer to avoid render blocking
- Uses passive event listeners where supported
- Minimal DOM queries
- Asynchronous event sending

### Browser Support

- Modern browsers with `fetch()` API
- IE11+ with polyfill (if needed)
- Graceful degradation in older browsers

## Debugging

### Console Logging

Add debug logging to the tracker:

```javascript
function sendEvent(eventType, eventData) {
    console.log('Sarai Analytics Event:', eventType, eventData); // Debug line

    var payload = {
        event_type: eventType,
        event_data: eventData || {},
        page_url: window.location.href,
        referrer: document.referrer || ''
    };

    fetch(endpoint, { /* ... */ });
}
```

### Verifying Events

1. Open browser developer tools
2. Go to Network tab
3. Filter by `/wp-json/sarai-analytics/`
4. Trigger events and verify requests

### Testing Do Not Track

1. Enable Do Not Track in browser settings
2. Check that no network requests are made
3. Verify `navigator.doNotTrack === '1'`

## Troubleshooting

### Events Not Firing

**Common Issues**:

1. **Do Not Track enabled**: Check browser settings
2. **Event not in allowed list**: Verify `SaraiAnalytics.events` array
3. **Selector mismatch**: Confirm CSS selectors match your HTML
4. **JavaScript errors**: Check console for syntax errors

### Network Errors

**Symptoms**: Events not reaching server

1. **CORS issues**: Verify REST API is accessible
2. **Endpoint wrong**: Check `SaraiAnalytics.endpoint` value
3. **Rate limited**: Server returns 429 status
4. **Invalid event type**: Server returns 400 status

### Performance Impact

**Symptoms**: Slow page loads

1. **Multiple trackers**: Ensure only one instance loaded
2. **Heavy event listeners**: Minimize DOM event bindings
3. **Large payloads**: Keep event data minimal

## Future Enhancements

Potential tracker improvements:

- Custom event API for programmatic tracking
- Intersection Observer for view events
- Service Worker support for offline events
- Batch sending for multiple events
- Custom data attributes for flexible targeting

This tracker provides reliable, lightweight event detection for the Sarai Analytics plugin.