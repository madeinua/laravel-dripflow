# Laravel DripFlow

**Laravel DripFlow** is a headless scheduling engine for managing sequential and time-gated access to Eloquent models. It allows you to "drip-feed" content (like lessons or videos) without hardcoding timing logic into your models.

## Key Features

*   **Content-Agnostic**: Works with any model via polymorphic relationships.
*   **Dual-Mode Scheduling**:
    *   **Fixed**: Unlocks at a specific calendar date/time for everyone.
    *   **Relative**: Unlocks based on when a user *joined* the stream (e.g., "Day 2").
*   **Smart Visibility**: `Opened`, `Locked` (Teaser), and `Hidden` states.
*   **Caching Support**: Built-in tools for efficient caching.

---

## Installation

1.  Add to `composer.json`:
    ```json
    "repositories": [{ "type": "path", "url": "./packages/laravel-dripflow" }],
    "require": { "madeinua/laravel-dripflow": "@dev" }
    ```
2.  Update and migrate:
    ```bash
    composer update && php artisan migrate
    ```

---

## Quick Start

### 1. Setup Models

Implement contracts on your models:

*   **User**: `implements DripSubscriber`, `use HasDripSubscriptions`
*   **Course**: `implements DripStreamOrigin`, `use HasDripStream`
*   **Lesson**: `implements DripEventSource`, `use HasDripEvents`

### 2. Create Stream & Events

```php
// Create a Stream
$stream = DripStream::create([
    'origin_type' => Course::class,
    'origin_id' => 1,
    'unlock_mode' => 'relative', // or 'fixed'
    'is_public' => false,
    'is_active' => true,
]);

// Add Events (Content)
DripEvent::create([
    'stream_id' => $stream->id,
    'eventable_type' => Lesson::class,
    'eventable_id' => 1,
    'offset_interval' => '0', // Immediate
    'is_visible' => true,
]);

DripEvent::create([
    'stream_id' => $stream->id,
    'eventable_type' => Lesson::class,
    'eventable_id' => 2,
    'offset_interval' => 'P1D', // 1 Day later
    'is_visible' => true,
]);
```

### 3. Usage

```php
use MadeInUA\LaravelDripFlow\Facades\DripFlow;

// User joins
DripFlow::join($user, $stream);

// Get content states
$events = DripFlow::getStreamEvents($user, $stream);

foreach ($events as $event) {
    if ($event->state->isOpened()) {
        // Show full content: $event->getPayload()
    } elseif ($event->state->isLocked()) {
        // Show teaser: $event->getPayload(), $event->timeRemaining()
    }
}
```

---

## API Reference

### DripManager (Facade: `DripFlow`)

*   `join($user, $stream)`: Subscribe a user (idempotent).
*   `rejoin($user, $stream)`: Reset progress to now.
*   `leave($user, $stream)`: Remove subscription.
*   `getStreamEvents($user, $stream)`: Get all events with calculated states.
*   `getCacheKey($user, $stream)`: Generate a smart cache key.

### ISO 8601 Durations

Use standard ISO formats for `offset_interval`:
*   `'0'` (Immediate)
*   `'PT1H'` (1 Hour)
*   `'P1D'` (1 Day)
*   `'P1W'` (1 Week)

---

## Performance & Caching

For production, cache `getStreamEvents()` results using the smart cache key:

```php
use Illuminate\Support\Facades\Cache;

$cacheKey = DripFlow::getCacheKey($user, $stream);

$events = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $stream) {
    return DripFlow::getStreamEvents($user, $stream);
});
```

**Cache automatically invalidates when:**
- Stream is updated (via `updated_at` timestamp)
- User rejoins (via `joined_at` timestamp)

---

## Configuration

Publish the config file to customize table names, models, or strategies:

```bash
php artisan vendor:publish --tag=dripflow-config
```

---

## Testing

```bash
composer install
vendor/bin/phpunit
```

---

## License

MIT License
