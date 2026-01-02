<?php

namespace MadeInUA\LaravelDripFlow\Tests\Unit;

use MadeInUA\LaravelDripFlow\Models\DripEvent;
use MadeInUA\LaravelDripFlow\Models\DripStream;
use MadeInUA\LaravelDripFlow\Tests\Fixtures\Course;
use MadeInUA\LaravelDripFlow\Tests\Fixtures\User;

use MadeInUA\LaravelDripFlow\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TraitTest extends TestCase
{
    #[Test]
    public function has_drip_subscriptions_trait_provides_relationship(): void
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

        $subscription = $user->joinDripStream($stream);

        $this->assertCount(1, $user->dripSubscriptions);
        $this->assertEquals($stream->id, $user->dripSubscriptions->first()->stream_id);
    }

    #[Test]
    public function has_drip_subscriptions_trait_provides_helper_methods(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $this->assertEquals($user->id, $user->getSubscriberId());
        $this->assertIsString($user->getSubscriberType());
    }

    #[Test]
    public function has_drip_subscriptions_trait_can_join_stream(): void
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

        $subscription = $user->joinDripStream($stream);

        $this->assertNotNull($subscription);
        $this->assertTrue($user->isJoinedToDripStream($stream));
    }

    #[Test]
    public function has_drip_stream_trait_provides_relationship(): void
    {
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(DripStream::class, $course->dripStream);
        $this->assertEquals($stream->id, $course->dripStream->id);
    }

    #[Test]
    public function has_drip_stream_trait_provides_helper_methods(): void
    {
        $course = Course::create(['title' => 'Test Course']);

        $this->assertIsString($course->getOriginType());
        $this->assertFalse($course->hasDripStream());

        DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $this->assertTrue($course->hasDripStream());
    }

    #[Test]
    public function has_drip_events_trait_provides_relationship(): void
    {
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);
        $course = Course::create(['title' => 'Test Course']);

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
            'offset_interval' => '0',
            'is_visible' => true,
        ]);

        $this->assertCount(1, $lesson->dripEvents);
        $this->assertEquals($event->id, $lesson->dripEvents->first()->id);
    }

    #[Test]
    public function has_drip_events_trait_provides_payload_methods(): void
    {
        $lesson = Course::create(['title' => 'Test Course', 'excerpt' => 'Test excerpt', 'content' => 'Test content']);

        $teaserPayload = $lesson->getTeaserPayload();
        $fullPayload = $lesson->getFullPayload();

        $this->assertIsArray($teaserPayload);
        $this->assertIsArray($fullPayload);
        $this->assertArrayHasKey('title', $teaserPayload);
        $this->assertArrayNotHasKey('content', $teaserPayload);
        $this->assertArrayHasKey('content', $fullPayload);
    }
}
