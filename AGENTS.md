# AGENTS.md - Sarai Analytics Plugin

## Project Overview
A lightweight WordPress analytics plugin for saraichinwag.com. Tracks custom events that GA4 misses, stores them locally, and provides simple querying.

## Coding Preferences

### PHP Style
- WordPress coding standards
- No inline CSS in PHP files
- Use prepared statements for ALL database queries
- Prefix everything with `sarai_analytics_`
- Use hooks and filters, not hard-coded values
- Keep files small and focused (<300 lines each)

### JavaScript Style  
- Vanilla JS only (no jQuery dependency)
- Use `fetch()` for AJAX, not XMLHttpRequest
- Respect Do Not Track header
- Lightweight - entire JS should be <5KB minified

### Database
- Single events table: `{prefix}sarai_events`
- Use WordPress table prefix
- Include proper indexes
- Don't store PII (no IPs, no user IDs)

### File Structure
```
sarai-analytics/
├── sarai-analytics.php      # Main plugin file
├── includes/
│   ├── class-tracker.php    # Event logging logic
│   ├── class-database.php   # DB schema & queries
│   └── class-admin.php      # Admin dashboard (simple)
├── assets/
│   └── js/
│       └── tracker.js       # Frontend tracking script
└── README.md
```

## Events to Track

1. **random_click** - User clicks random icon
2. **image_mode** - User views an /images/ gallery page
3. **smi_click** - User clicks "Download Hi-Res" button
4. **search** - User submits search (capture query)
5. **page_view** - Basic page view with referrer

## Database Schema

```sql
CREATE TABLE {prefix}sarai_events (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## REST Endpoint

`POST /wp-json/sarai-analytics/v1/event`
- Rate limited (10 events/minute per session)
- Validates event_type against allowlist
- Returns 204 No Content on success

## Admin Page

Simple dashboard under Tools menu:
- Event counts by type (last 7/30 days)
- Top search queries
- Top referrers
- Recent events table

Keep it minimal. This is for me to query, not a fancy dashboard.
