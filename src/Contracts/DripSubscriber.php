<?php

namespace MadeInUA\LaravelDripFlow\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Interface for models that can subscribe to DripFlow streams
 * (typically User model)
 */
interface DripSubscriber
{
    /**
     * Get all drip subscriptions for this subscriber
     *
     * @return MorphMany
     */
    public function dripSubscriptions(): MorphMany;

    /**
     * Get the subscriber's identifier (primary key)
     *
     * @return int
     */
    public function getSubscriberId(): int;

    /**
     * Get the subscriber's morph type (class name for polymorphic relations)
     *
     * @return string
     */
    public function getSubscriberType(): string;
}
