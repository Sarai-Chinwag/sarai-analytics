# Sarai Analytics Plugin Documentation

## Overview

Sarai Analytics is a lightweight WordPress plugin designed specifically for saraichinwag.com to track custom events that Google Analytics 4 (GA4) misses. The plugin stores event data locally in a dedicated database table and provides a simple admin dashboard for viewing analytics.

## Features

- **Custom Event Tracking**: Tracks five specific event types tailored to the website's functionality
- **Local Data Storage**: Stores events in a dedicated WordPress database table without relying on external services
- **RESTful API**: Provides a secure REST endpoint for event submission
- **Rate Limiting**: Implements per-session rate limiting to prevent abuse
- **Privacy Respecting**: Respects Do Not Track headers and browser settings
- **Admin Dashboard**: Simple dashboard under Tools menu showing key metrics
- **Vanilla JavaScript**: Lightweight frontend tracker using native JavaScript without jQuery dependency

## Installation

1. Upload the `sarai-analytics` folder to `wp-content/plugins/`
2. Activate the "Sarai Analytics" plugin in the WordPress admin
3. The plugin will automatically create the required database table on activation
4. The tracker script will be automatically enqueued on frontend pages

## Requirements

- WordPress 4.7+ (for REST API support)
- PHP 7.0+
- MySQL 5.6+

## Architecture

The plugin consists of several components:

- **Main Plugin File** (`sarai-analytics.php`): Initializes the plugin and enqueues scripts
- **Database Layer** (`includes/class-database.php`): Handles table creation and data queries
- **Tracking API** (`includes/class-tracker.php`): REST endpoint and event processing
- **Admin Interface** (`includes/class-admin.php`): Dashboard for viewing analytics
- **Frontend Tracker** (`assets/js/tracker.js`): JavaScript that detects and sends events

## Documentation Structure

- [Events](./events/README.md) - Detailed documentation of tracked event types
- [REST API](./api/README.md) - API endpoint specification and usage
- [Database](./database/README.md) - Schema and query documentation
- [Admin Dashboard](./admin/README.md) - Dashboard features and customization
- [JavaScript Tracker](./tracker/README.md) - Frontend tracking implementation