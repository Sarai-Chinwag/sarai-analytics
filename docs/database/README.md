# Database Schema and Queries

Sarai Analytics uses a single database table to store all event data. The schema is optimized for analytics queries and includes proper indexing for performance.

## Table Schema

### Table Name
`{wp_prefix}sarai_events`

### Columns

| Column | Type | Length | Default | Description |
|--------|------|--------|---------|-------------|
| `id` | BIGINT UNSIGNED | - | AUTO_INCREMENT | Primary key |
| `event_type` | VARCHAR | 50 | - | Event type identifier |
| `event_data` | JSON | - | - | JSON-encoded event data |
| `page_url` | VARCHAR | 500 | - | Current page URL |
| `referrer` | VARCHAR | 500 | - | HTTP referrer URL |
| `session_id` | VARCHAR | 64 | - | Anonymous session identifier |
| `user_agent` | VARCHAR | 500 | - | Browser user agent string |
| `created_at` | DATETIME | - | CURRENT_TIMESTAMP | Event timestamp |

### Indexes

- **PRIMARY KEY**: `id` (auto-increment)
- **event_type**: For filtering events by type
- **created_at**: For time-based queries
- **session_id**: For session-based analytics

### Storage Engine
InnoDB with UTF8MB4 charset

## Schema SQL

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

## Database Operations

### Table Creation

The table is automatically created during plugin activation using WordPress's `dbDelta()` function, which handles:

- Table creation if it doesn't exist
- Safe column additions/modifications
- Index creation

### Data Insertion

Events are inserted using prepared statements:

```php
INSERT INTO {table} (event_type, event_data, page_url, referrer, session_id, user_agent)
VALUES (%s, %s, %s, %s, %s, %s)
```

## Query Methods

### Event Counts by Type

```sql
SELECT event_type, COUNT(*) as total
FROM {table}
WHERE created_at >= %s
GROUP BY event_type
ORDER BY total DESC
```

**Parameters**: Cutoff date (last 7 or 30 days)

**Returns**: Event types with their counts

### Top Search Queries

```sql
SELECT JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.query')) as query, COUNT(*) as total
FROM {table}
WHERE event_type = 'search'
AND JSON_EXTRACT(event_data, '$.query') IS NOT NULL
GROUP BY query
ORDER BY total DESC
LIMIT %d
```

**Parameters**: Limit (default 10)

**Returns**: Search queries with their frequencies

### Top Referrers

```sql
SELECT referrer, COUNT(*) as total
FROM {table}
WHERE created_at >= %s
AND referrer IS NOT NULL
AND referrer != ''
GROUP BY referrer
ORDER BY total DESC
LIMIT %d
```

**Parameters**: Cutoff date, limit

**Returns**: Referrer URLs with their visit counts

### Recent Events

```sql
SELECT event_type, event_data, page_url, referrer, created_at
FROM {table}
ORDER BY created_at DESC
LIMIT %d
```

**Parameters**: Limit (default 20)

**Returns**: Most recent events with metadata

## Data Types and Storage

### JSON Event Data

Event data is stored as JSON, allowing flexible schema:

- Objects: `{"key": "value"}`
- Arrays: `["item1", "item2"]`
- Primitives: `"string"`, `123`, `true`

MySQL JSON functions are used for querying:

- `JSON_EXTRACT()`: Extract values from JSON
- `JSON_UNQUOTE()`: Remove JSON quotes from extracted strings

### Text Fields

- All VARCHAR fields are properly sized for their expected content
- URLs are limited to 500 characters (reasonable maximum)
- User agents are truncated at 500 characters
- Session IDs are exactly 64 characters (UUID4 length)

### Timestamps

- Uses MySQL DATETIME format
- Automatically set on insertion
- UTC timezone (handled by WordPress)

## Performance Considerations

### Indexing Strategy

- **event_type**: Fast filtering for specific event types
- **created_at**: Efficient date range queries
- **session_id**: Session-based analytics queries

### Query Optimization

- All queries use prepared statements
- LIMIT clauses prevent large result sets
- WHERE clauses leverage indexes
- GROUP BY operations are optimized with appropriate indexes

### Data Retention

Currently, no automatic data cleanup is implemented. Consider adding data retention policies for long-term usage:

```sql
-- Delete events older than 1 year
DELETE FROM {table} WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

## Backup and Migration

### Exporting Data

```sql
-- Export all events to CSV
SELECT * FROM {table}
INTO OUTFILE '/path/to/export.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

### Table Size Monitoring

```sql
-- Check table size
SELECT
    table_name,
    ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb
FROM information_schema.tables
WHERE table_name = '{table_name}';
```

## Customization

### Custom Queries

The database class can be extended for custom analytics:

```php
class Custom_Sarai_Database extends Sarai_Analytics_Database {
    public function get_custom_metric() {
        // Custom query implementation
    }
}
```

### Table Prefix

The table uses WordPress's standard table prefix (`$wpdb->prefix`), ensuring compatibility with multisite installations.

### Charset and Collation

Uses WordPress's default charset and collation (`$wpdb->get_charset_collate()`), typically UTF8MB4.

## Security

- All queries use prepared statements to prevent SQL injection
- Input data is sanitized before insertion
- No direct user input in table/column names
- WordPress database permissions are respected

## Monitoring and Maintenance

### Index Usage

```sql
-- Check index usage
SELECT
    object_schema,
    object_name,
    index_name,
    count_read,
    count_fetch,
    count_insert,
    count_update,
    count_delete
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE object_name = '{table_name}';
```

### Table Optimization

```sql
-- Optimize table (rebuilds indexes)
OPTIMIZE TABLE {table_name};
```

This documentation covers the database layer's structure, operations, and best practices for the Sarai Analytics plugin.