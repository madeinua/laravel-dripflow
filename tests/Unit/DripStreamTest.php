<?php

namespace MadeInUA\LaravelDripFlow\Tests\Unit;

use MadeInUA\LaravelDripFlow\Models\DripStream;
use MadeInUA\LaravelDripFlow\Tests\Fixtures\Course;
use MadeInUA\LaravelDripFlow\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DripStreamTest extends TestCase
{
    #[Test]
    public function it_can_create_a_stream_with_relative_mode(): void
    {
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(DripStream::class, $stream);
        $this->assertTrue($stream->isRelativeMode());
        $this->assertFalse($stream->isFixedMode());
    }

    #[Test]
    public function it_can_create_a_stream_with_fixed_mode(): void
    {
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'fixed',
            'start_date' => now(),
            'is_active' => true,
        ]);

        $this->assertInstanceOf(DripStream::class, $stream);
        $this->assertTrue($stream->isFixedMode());
        $this->assertFalse($stream->isRelativeMode());
    }

    #[Test]
    public function it_throws_exception_when_fixed_mode_has_no_start_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('start_date is required when unlock_mode is "fixed"');

        $course = Course::create(['title' => 'Test Course']);

        DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'fixed',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_has_is_public_helper(): void
    {
        $course = Course::create(['title' => 'Test Course']);

        $publicStream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $privateStream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => false,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $this->assertTrue($publicStream->isPublic());
        $this->assertFalse($privateStream->isPublic());
    }

    #[Test]
    public function it_has_is_active_helper(): void
    {
        $course = Course::create(['title' => 'Test Course']);

        $activeStream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $inactiveStream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => false,
        ]);

        $this->assertTrue($activeStream->isActive());
        $this->assertFalse($inactiveStream->isActive());
    }

    #[Test]
    public function it_belongs_to_an_origin(): void
    {
        $course = Course::create(['title' => 'Test Course']);

        $stream = DripStream::create([
            'origin_type' => $course->getMorphClass(),
            'origin_id' => $course->id,
            'is_public' => true,
            'unlock_mode' => 'relative',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Course::class, $stream->origin);
        $this->assertEquals($course->id, $stream->origin->id);
    }
}
