# Laravel DripFlow

**Laravel DripFlow** is a headless scheduling engine for managing sequential and time-gated access to Eloquent models. It allows you to "drip-feed" content (like lessons or videos) without hardcoding timing logic into your models.

## Key Features

* **Content-Agnostic**: Works with any model via polymorphic relationships.
* **Dual-Mode Scheduling**:
    * **Fixed**: Unlocks at a specific calendar date/time for everyone.
    * **Relative**: Unlocks based on when a user *joined* the stream (e.g., "Day 2").
* **Smart Visibility**: `Opened`, `Locked` (Teaser), and `Hidden` states.
* **Caching Support**: Built-in tools for efficient caching.

---

## Installation

1. Add to `composer.json`:
   ```json
   "repositories": [{ "type": "path", "url": "./packages/laravel-dripflow" }],
   "require": { "madeinua/laravel-dripflow": "@dev" }
   ```
2. Update and migrate:
   ```bash
   composer update && php artisan migrate
   ```

---

## Quick Start

### 1. Setup Models

Implement contracts on your models:

* **User**: `implements DripSubscriber`, `use HasDripSubscriptions`
* **Course**: `implements DripStreamOrigin`, `use HasDripStream`
* **Lesson**: `implements DripEventSource`, `use HasDripEvents`

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

// User joins (for private streams or relative mode)
DripFlow::join($user, $stream);

// Get content states
$events = DripFlow::getStreamEvents($user, $stream);

foreach ($events as $event) {
    if ($event->state->isOpened()) {
        // Show full content: $event->getPayload()
    } elseif ($event->state->isLocked()) {
        // Show teaser: $event->getPayload(), $event->timeRemaining()
    } elseif ($event->state->isHidden()) {
        // Don't show anything
    }
}
```

---

## Examples

### Example 1: Public Fixed-Schedule Stream (Blog Posts)

```php
// Create a public stream with fixed schedule
$stream = DripStream::create([
    'origin_type' => Blog::class,
    'origin_id' => 1,
    'unlock_mode' => 'fixed',
    'start_date' => '2024-01-01 00:00:00',
    'is_public' => true,  // No subscription needed
    'is_active' => true,
]);

// Add posts that unlock on specific dates
DripEvent::create([
    'stream_id' => $stream->id,
    'eventable_type' => Post::class,
    'eventable_id' => 1,
    'offset_interval' => '0',  // Unlocks Jan 1
    'is_visible' => true,
]);

DripEvent::create([
    'stream_id' => $stream->id,
    'eventable_type' => Post::class,
    'eventable_id' => 2,
    'offset_interval' => 'P7D',  // Unlocks Jan 8 (7 days later)
    'is_visible' => true,
]);

// Usage: No subscription needed for public streams
$events = DripFlow::getStreamEvents(null, $stream);
```

### Example 2: Private Relative Stream (Online Course)

```php
// Create a private course with relative unlocking
$stream = DripStream::create([
    'origin_type' => Course::class,
    'origin_id' => 1,
    'unlock_mode' => 'relative',
    'is_public' => false,  // Requires subscription
    'is_active' => true,
]);

// Add lessons that unlock relative to subscription date
DripEvent::create([
    'stream_id' => $stream->id,
    'eventable_type' => Lesson::class,
    'eventable_id' => 1,
    'offset_interval' => '0',  // Immediate on subscribe
    'is_visible' => true,
]);

DripEvent::create([
    'stream_id' => $stream->id,
    'eventable_type' => Lesson::class,
    'eventable_id' => 2,
    'offset_interval' => 'P1D',  // 1 day after subscribe
    'is_visible' => true,  // Shows teaser if locked
]);

DripEvent::create([
    'stream_id' => $stream->id,
    'eventable_type' => Lesson::class,
    'eventable_id' => 3,
    'offset_interval' => 'P7D',  // 7 days after subscribe
    'is_visible' => false,  // Completely hidden until unlocked
]);

// Usage: Requires subscription
DripFlow::join($user, $stream);
$events = DripFlow::getStreamEvents($user, $stream);
```

---

## Stream Configuration

### DripStream Options

| Option        | Type      | Required | Default      | Description                              |
|---------------|-----------|----------|--------------|------------------------------------------|
| `origin_type` | string    | ✅        | -            | Polymorphic type (e.g., `Course::class`) |
| `origin_id`   | int       | ✅        | -            | ID of the origin model                   |
| `unlock_mode` | enum      | ✅        | `'relative'` | `'fixed'` or `'relative'`                |
| `is_public`   | bool      | ❌        | `true`       | Whether stream requires subscription     |
| `is_active`   | bool      | ❌        | `true`       | Whether stream is active                 |
| `start_date`  | timestamp | ❌        | `null`       | Start date for fixed mode                |

#### `unlock_mode` Options

**Fixed Mode** (`'fixed'`):

- Content unlocks at specific calendar dates/times for everyone
- Requires `start_date` to be set
- Example: "Episode 1 unlocks Jan 1, Episode 2 unlocks Jan 8"

**Relative Mode** (`'relative'`):

- Content unlocks based on when user subscribed
- Uses `joined_at` timestamp from subscription
- Example: "Episode 1 immediate, Episode 2 after 7 days"

#### `is_public` Options

**Public Stream** (`true`):

- Users can view content without subscribing
- Best for: blog posts, announcements, free content
- Works well with **fixed mode** (calendar-based unlocking)
- Note: Relative mode needs a subscription for `joined_at` reference

**Private Stream** (`false`):

- Users must subscribe to view content
- All content shows as `HIDDEN` without subscription
- Best for: paid courses, premium content, personalized learning

### DripEvent Options

| Option | Type | Requir ed | Def ault | Description |
|--------|------|--------------------------------|-------------|
| `stream_id` | int | ✅ | - | ID of t he parent stream |
| `eventable_type` | str ing | ✅ | - | Poly morphic type (e.g., `Lesson::class`) |
| `eventable_id` | int | ✅ | - | ID of t he content model |
| `offset_interval` | st ring | ✅ | - | ISO 8601 duration or `'0'` |
| `is_visible` | bool | ❌ | `true` | W hether to show teaser when locked |

#### `offset_interval` (ISO 8601 Duration)

Standard ISO 8601 duration formats:

* `'0'` - Immediate (unlocks at stream start)
* `'PT30M'` - 30 Minutes
* `'PT1H'` - 1 Hour
* `'PT2H30M'` - 2 Hours 30 Minutes
* `'P1D'` - 1 Day
* `'P1W'` - 1 Week (7 days)
* `'P1M'` - 1 Month
* `'P1Y'` - 1 Year

#### `is_visible` Options

**Visible** (`true`):

- Shows teaser content when locked (title, excerpt, etc.)
- State: `LOCKED` (can see it exists but can't access)

**Hidden** (`false`):

- Completely hides the event until unlocked
- State: `HIDDEN` (user doesn't know it exists)

---

## API Reference

### DripManager (Facade: `DripFlow`)

* `join($user, $stream)`: Subscribe a user (idempotent).
* `rejoin($user, $stream)`: Reset progress to now.
* `leave($user, $stream)`: Remove subscription.
* `getStreamEvents($user, $stream)`: Get all visible events with calculated states.
* `resolveState($event, $subscription)`: Get state for a single event.
* `getCacheKey($user, $stream)`: Generate a smart cache key.

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
