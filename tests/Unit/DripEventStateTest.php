<?php

namespace MadeInUA\LaravelDripFlow\Tests\Unit;

use MadeInUA\LaravelDripFlow\DTOs\DripEventState;
use MadeInUA\LaravelDripFlow\DTOs\DripEventStateEnum;
use MadeInUA\LaravelDripFlow\Tests\Fixtures\Course;
use MadeInUA\LaravelDripFlow\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DripEventStateTest extends TestCase
{
    #[Test]
    public function it_returns_teaser_payload_for_locked_state(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $state = new DripEventState(
            $course,
            DripEventStateEnum::LOCKED,
            now()->addDay()
        );

        $payload = $state->getPayload();

        $this->assertArrayHasKey('excerpt', $payload);
        $this->assertArrayNotHasKey('content', $payload);
    }

    #[Test]
    public function it_returns_full_payload_for_opened_state(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $state = new DripEventState(
            $course,
            DripEventStateEnum::OPENED,
            now()
        );

        $payload = $state->getPayload();

        $this->assertArrayHasKey('content', $payload);
        $this->assertArrayHasKey('excerpt', $payload);
    }

    #[Test]
    public function it_returns_empty_payload_for_hidden_state(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $state = new DripEventState(
            $course,
            DripEventStateEnum::HIDDEN,
            now()->addCentury()
        );

        $payload = $state->getPayload();

        $this->assertEmpty($payload);
    }

    #[Test]
    public function time_remaining_returns_null_for_opened_state(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $state = new DripEventState(
            $course,
            DripEventStateEnum::OPENED,
            now()
        );

        $this->assertNull($state->timeRemaining());
    }

    #[Test]
    public function time_remaining_returns_null_for_hidden_state(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $state = new DripEventState(
            $course,
            DripEventStateEnum::HIDDEN,
            now()->addCentury()
        );

        $this->assertNull($state->timeRemaining());
    }

    #[Test]
    public function time_remaining_returns_diff_for_locked_state(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $unlockTime = now()->addDays(2);
        $state = new DripEventState(
            $course,
            DripEventStateEnum::LOCKED,
            $unlockTime
        );

        $timeRemaining = $state->timeRemaining();

        $this->assertNotNull($timeRemaining);
        $this->assertIsString($timeRemaining);
        $this->assertStringContainsString('day', strtolower($timeRemaining));
    }

    #[Test]
    public function to_array_includes_all_properties(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $unlockTime = now()->addDay();
        $state = new DripEventState(
            $course,
            DripEventStateEnum::LOCKED,
            $unlockTime
        );

        $array = $state->toArray();

        $this->assertArrayHasKey('state', $array);
        $this->assertArrayHasKey('is_hidden', $array);
        $this->assertArrayHasKey('is_locked', $array);
        $this->assertArrayHasKey('is_opened', $array);
        $this->assertArrayHasKey('unlocks_at', $array);
        $this->assertArrayHasKey('time_remaining', $array);
        $this->assertArrayHasKey('payload', $array);

        $this->assertEquals('locked', $array['state']);
        $this->assertFalse($array['is_hidden']);
        $this->assertTrue($array['is_locked']);
        $this->assertFalse($array['is_opened']);
        $this->assertNotNull($array['unlocks_at']);
        $this->assertNotNull($array['time_remaining']);
        $this->assertArrayHasKey('excerpt', $array['payload']);
    }

    #[Test]
    public function to_array_handles_opened_state_correctly(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $state = new DripEventState(
            $course,
            DripEventStateEnum::OPENED,
            now()
        );

        $array = $state->toArray();

        $this->assertEquals('opened', $array['state']);
        $this->assertTrue($array['is_opened']);
        $this->assertFalse($array['is_locked']);
        $this->assertFalse($array['is_hidden']);
        $this->assertNull($array['time_remaining']);
        $this->assertArrayHasKey('content', $array['payload']);
    }

    #[Test]
    public function to_array_handles_hidden_state_correctly(): void
    {
        $course = Course::create([
            'title' => 'Test Course',
            'excerpt' => 'Test excerpt',
            'content' => 'Full content',
        ]);

        $state = new DripEventState(
            $course,
            DripEventStateEnum::HIDDEN,
            now()->addCentury()
        );

        $array = $state->toArray();

        $this->assertEquals('hidden', $array['state']);
        $this->assertTrue($array['is_hidden']);
        $this->assertFalse($array['is_locked']);
        $this->assertFalse($array['is_opened']);
        $this->assertNull($array['time_remaining']);
        $this->assertEmpty($array['payload']);
    }
}