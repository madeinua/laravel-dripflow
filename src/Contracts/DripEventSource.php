<?php

namespace MadeInUA\LaravelDripFlow\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Interface for models that can be used as DripFlow event content
 * (e.g., Video, Lesson, Post, Audio)
 */
interface DripEventSource
{
    /**
     * Get all drip events for this content
     *
     * @return MorphMany
     */
    public function dripEvents(): MorphMany;

    /**
     * Get data for locked content (teaser/preview state)
     *
     * Return whatever data structure your project needs for the locked state.
     * This is completely project-defined - could be title, thumbnail, duration, etc.
     *
     * @return array
     */
    public function getTeaserPayload(): array;

    /**
     * Get data for unlocked content (full access state)
     *
     * Return whatever data structure your project needs for the unlocked state.
     * This is completely project-defined - could be video URL, transcript, attachments, etc.
     *
     * @return array
     */
    public function getFullPayload(): array;
}
