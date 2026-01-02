<?php

namespace MadeInUA\LaravelDripFlow\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait for models that can be drip event content
 * (e.g., Video, Lesson, Post, Audio)
 *
 * Add this trait to content models and implement DripEventSource contract
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasDripEvents
{
    /**
     * Get all drip events for this content
     *
     * @return MorphMany
     */
    public function dripEvents(): MorphMany
    {
        return $this->morphMany(config('dripflow.models.event'), 'eventable');
    }

    /**
     * Get data for locked content (teaser/preview state)
     *
     * Override this method to return whatever data structure your project needs.
     * The package doesn't care about the structure - it's up to your frontend to render it.
     *
     * Example:
     * return [
     *     'title' => $this->title,
     *     'thumbnail' => $this->thumbnail_url,
     *     'duration' => $this->duration,
     * ];
     *
     * @return array
     */
    public function getTeaserPayload(): array
    {
        return [];
    }

    /**
     * Get data for unlocked content (full access state)
     *
     * Override this method to return whatever data structure your project needs.
     * The package doesn't care about the structure - it's up to your frontend to render it.
     *
     * Example:
     * return [
     *     'title' => $this->title,
     *     'video_url' => $this->video_url,
     *     'attachments' => $this->attachments->toArray(),
     *     'transcript' => $this->transcript,
     * ];
     *
     * @return array
     */
    public function getFullPayload(): array
    {
        return [];
    }
}
