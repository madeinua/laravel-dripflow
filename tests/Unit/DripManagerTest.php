<?php

namespace MadeInUA\LaravelDripFlow\Tests\Unit;

use MadeInUA\LaravelDripFlow\DTOs\DripEventStateEnum;
use MadeInUA\LaravelDripFlow\Models\DripEvent;
use MadeInUA\LaravelDripFlow\Models\DripStream;
use MadeInUA\LaravelDripFlow\Services\DripManager;
use MadeInUA\LaravelDripFlow\Tests\Fixtures\Course;
use MadeInUA\LaravelDripFlow\Tests\Fixtures\User;

use MadeInUA\LaravelDripFlow\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DripManagerTest extends TestCase
{
    private DripManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(DripManager::class);
    }

    #[Test]
    public function it_can_join_a_stream(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $subscription = $this->manager->join($user, $stream);

        $this->assertNotNull($subscription);
        $this->assertEquals($user->id, $subscription->subscriber_id);
        $this->assertEquals($stream->id, $subscription->stream_id);
    }

    #[Test]
    public function it_does_not_create_duplicate_subscriptions(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $subscription1 = $this->manager->join($user, $stream);
        $subscription2 = $this->manager->join($user, $stream);

        $this->assertEquals($subscription1->id, $subscription2->id);
    }

    #[Test]
    public function it_returns_opened_events_for_unlocked_content(): void
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

        $subscription = $this->manager->join($user, $stream);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => '0', // Immediate unlock
            'is_visible' => true,
        ]);

        $events = $this->manager->getStreamEvents($user, $stream);

        $this->assertCount(1, $events);
        $this->assertEquals(DripEventStateEnum::OPENED, $events->first()->state);
        $this->assertArrayHasKey('content', $events->first()->getPayload());
    }

    #[Test]
    public function it_returns_locked_events_for_locked_content(): void
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

        $subscription = $this->manager->join($user, $stream);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => '86400', // 1 day in future
            'is_visible' => true,
        ]);

        $events = $this->manager->getStreamEvents($user, $stream);

        $this->assertCount(1, $events);
        $this->assertEquals(DripEventStateEnum::LOCKED, $events->first()->state);
        $this->assertArrayNotHasKey('content', $events->first()->getPayload());
    }

    #[Test]
    public function it_returns_hidden_events_for_inactive_streams(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => false, // Inactive stream
        ]);

        $subscription = $this->manager->join($user, $stream);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => '0',
            'is_visible' => true,
        ]);

        $events = $this->manager->getStreamEvents($user, $stream);

        $this->assertCount(1, $events);
        $this->assertEquals(DripEventStateEnum::HIDDEN, $events->first()->state);
    }

    #[Test]
    public function it_allows_public_streams_without_subscription(): void
    {
        $course = Course::create(['title' => 'Test Course']);
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true, // Public stream
            'unlock_mode' => 'fixed',
            'start_date' => now()->subDays(10),
            'is_active' => true,
        ]);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => '0',
            'is_visible' => true,
        ]);

        $events = $this->manager->getStreamEvents(null, $stream);

        $this->assertCount(1, $events);
        $this->assertEquals(DripEventStateEnum::OPENED, $events->first()->state);
    }

    #[Test]
    public function it_hides_private_stream_events_without_subscription(): void
    {
        $course = Course::create(['title' => 'Test Course']);
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false, // Private stream
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $event = DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson->getMorphClass(),
            'eventable_id' => $lesson->id,
            'offset_interval' => '0',
            'is_visible' => true,
        ]);

        $events = $this->manager->getStreamEvents(null, $stream);

        $this->assertCount(1, $events);
        $this->assertEquals(DripEventStateEnum::HIDDEN, $events->first()->state);
    }

    #[Test]
    public function it_only_returns_visible_events(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);
        $lesson1 = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);
        $lesson2 = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $this->manager->join($user, $stream);

        DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson1->getMorphClass(),
            'eventable_id' => $lesson1->id,
            'offset_interval' => '0',
            'is_visible' => true,
        ]);

        DripEvent::create([
            'stream_id' => $stream->id,
            'eventable_type' => $lesson2->getMorphClass(),
            'eventable_id' => $lesson2->id,
            'offset_interval' => '0',
            'is_visible' => false, // Not visible
        ]);

        $events = $this->manager->getStreamEvents($user, $stream);

        $this->assertCount(1, $events);
    }

    #[Test]
    public function it_can_rejoin_stream_and_reset_progress(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        // Initial join
        $subscription1 = $this->manager->join($user, $stream);
        $originalJoinedAt = $subscription1->joined_at;

        // Wait a bit
        sleep(1);

        // Rejoin should reset the joined_at timestamp
        $subscription2 = $this->manager->rejoin($user, $stream);

        $this->assertEquals($subscription1->id, $subscription2->id);
        $this->assertTrue($subscription2->joined_at->isAfter($originalJoinedAt));
    }

    #[Test]
    public function it_can_leave_a_stream(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        // Join first
        $this->manager->join($user, $stream);
        $this->assertCount(1, $user->dripSubscriptions);

        // Leave
        $result = $this->manager->leave($user, $stream);

        $this->assertTrue($result);
        $this->assertCount(0, $user->fresh()->dripSubscriptions);
    }

    #[Test]
    public function it_returns_false_when_leaving_nonexistent_subscription(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        // Try to leave without joining
        $result = $this->manager->leave($user, $stream);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_generates_unique_cache_keys_for_different_subscribers(): void
    {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $this->manager->join($user1, $stream);
        $this->manager->join($user2, $stream);

        $cacheKey1 = $this->manager->getCacheKey($user1, $stream);
        $cacheKey2 = $this->manager->getCacheKey($user2, $stream);
        $cacheKeyGuest = $this->manager->getCacheKey(null, $stream);

        // Each should be different
        $this->assertNotEquals($cacheKey1, $cacheKey2);
        $this->assertNotEquals($cacheKey1, $cacheKeyGuest);
        $this->assertNotEquals($cacheKey2, $cacheKeyGuest);

        // Should contain stream ID
        $this->assertStringContainsString((string) $stream->id, $cacheKey1);
    }

    #[Test]
    public function it_changes_cache_key_after_rejoin(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $this->manager->join($user, $stream);
        $originalKey = $this->manager->getCacheKey($user, $stream);

        sleep(1);

        $this->manager->rejoin($user, $stream);
        $newKey = $this->manager->getCacheKey($user, $stream);

        // Cache key should change because joined_at changed
        $this->assertNotEquals($originalKey, $newKey);
    }
}
