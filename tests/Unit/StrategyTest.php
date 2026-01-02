<?php

namespace MadeInUA\LaravelDripFlow\Tests\Unit;

use MadeInUA\LaravelDripFlow\Models\DripEvent;
use MadeInUA\LaravelDripFlow\Models\DripStream;
use MadeInUA\LaravelDripFlow\Models\DripSubscription;
use MadeInUA\LaravelDripFlow\Strategies\FixedDateStrategy;
use MadeInUA\LaravelDripFlow\Strategies\RelativeDelayStrategy;
use MadeInUA\LaravelDripFlow\Tests\Fixtures\Course;
use MadeInUA\LaravelDripFlow\Tests\Fixtures\User;

use MadeInUA\LaravelDripFlow\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StrategyTest extends TestCase
{
    #[Test]
    public function fixed_date_strategy_calculates_unlock_time_correctly(): void
    {
        $streamOrigin = Course::create(['title' => 'Test Course']);
        $eventContent = Course::create(['title' => 'Lesson 1', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $startDate = now()->addDays(10);
        $stream = DripStream::create([
            'origin_type' => $streamOrigin->getMorphClass(),
            'origin_id' => $streamOrigin->id,
            'is_public' => true,
            'unlock_mode' => 'fixed',
            'start_date' => $startDate,
            'is_active' => true,
        ]);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $eventContent->getMorphClass(),
            'eventable_id' => $eventContent->id,
            'offset_interval' => '86400', // 1 day in seconds
            'is_visible' => true,
        ]);

        $strategy = new FixedDateStrategy();
        $unlockTime = $strategy->calculateUnlockTime($event, null);

        $expectedTime = $startDate->copy()->addDay();
        $this->assertEquals($expectedTime->timestamp, $unlockTime->timestamp);
    }

    #[Test]
    public function fixed_date_strategy_checks_unlock_status_correctly(): void
    {
        $course = Course::create(['title' => 'Test Course']);
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'fixed',
            'start_date' => now()->subDays(10), // Past date
            'is_active' => true,
        ]);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => '86400', // 1 day
            'is_visible' => true,
        ]);

        $strategy = new FixedDateStrategy();
        $this->assertTrue($strategy->isUnlocked($event, null));
    }

    #[Test]
    public function relative_delay_strategy_calculates_unlock_time_correctly(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $joinedAt = now()->subDay(); // Joined 1 day ago
        $subscription = DripSubscription::create([
            'subscriber_type' => $user->getMorphClass(),
            'subscriber_id' => $user->id,
            'stream_id' => $stream->id,
            'joined_at' => $joinedAt,
        ]);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => '172800', // 2 days in seconds
            'is_visible' => true,
        ]);

        $strategy = new RelativeDelayStrategy();
        $unlockTime = $strategy->calculateUnlockTime($event, $subscription);

        $expectedTime = $joinedAt->copy()->addDays(2);
        $this->assertEquals($expectedTime->timestamp, $unlockTime->timestamp);
    }

    #[Test]
    public function relative_delay_strategy_checks_unlock_status_correctly(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $subscription = DripSubscription::create([
            'subscriber_type' => $user->getMorphClass(),
            'subscriber_id' => $user->id,
            'stream_id' => $stream->id,
            'joined_at' => now()->subDays(10), // Joined 10 days ago
        ]);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => '86400', // 1 day
            'is_visible' => true,
        ]);

        $strategy = new RelativeDelayStrategy();
        $this->assertTrue($strategy->isUnlocked($event, $subscription));
    }

    #[Test]
    public function event_parses_iso_8601_duration_correctly(): void
    {
        $course = Course::create(['title' => 'Test Course']);
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => 'P1D', // 1 day ISO 8601
            'is_visible' => true,
        ]);

        $this->assertEquals(86400, $event->getOffsetInSeconds());
    }

    #[Test]
    public function event_throws_exception_for_invalid_offset_interval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid offset_interval format');

        $course = Course::create(['title' => 'Test Course']);
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => 'INVALID_FORMAT', // Invalid format
            'is_visible' => true,
        ]);

        // This should throw InvalidArgumentException
        $event->getOffsetInSeconds();
    }
}
