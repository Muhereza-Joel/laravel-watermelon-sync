# Laravel Watermelon Sync

A robust, highly-configurable Laravel package designed to synchronize Eloquent models with WatermelonDB. It handles incremental pulls, batch pushes (upserts), and file synchronization via Spatie MediaLibrary.

## Features

- **Flexible Sync Keys**: Choose between `watermelon_id`, `uuid`, or any custom string column.
- **Automatic Scoping**: Automatically scopes sync data to the authenticated user.
- **Smart Type Conversion**: Handles Carbon timestamps to milliseconds (JS) and back.
- **Whitelisting**: Fine-grained control over which columns are sent to the client.
- **File Sync**: Built-in support for syncing media/images attached to models.

---

## Installation

### 1. Requirements

- PHP 8.1+
- Laravel 10.0,11.0 or 12.o
- Spatie MediaLibrary (Optional, for File Sync)

### 2. Install via Composer

```bash
composer require muhereza-joel/laravel-watermelon-sync
```
