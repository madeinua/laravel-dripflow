<?php

namespace MadeInUA\LaravelDripFlow\Traits;

use MadeInUA\LaravelDripFlow\Models\DripSubscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait for models that can subscribe to drip streams (typically User)
 *
 * Add this trait to your User model and implement DripSubscriber contract
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \MadeInUA\LaravelDripFlow\Contracts\DripSubscriber
 */
trait HasDripSubscriptions
{
    /**
     * Get all drip subscriptions for this subscriber
     *
     * @return MorphMany
     */
    public function dripSubscriptions(): MorphMany
    {
        return $this->morphMany(config('dripflow.models.subscription'), 'subscriber');
    }

    /**
     * Get the identifier for this subscriber (primary key)
     *
     * @return int
     */
    public function getSubscriberId(): int
    {
        return $this->getKey();
    }

    /**
     * Get the morph type for this subscriber (class name for polymorphic relations)
     *
     * @return string
     */
    public function getSubscriberType(): string
    {
        return $this->getMorphClass();
    }

    /**
     * Join/subscribe to a drip stream
     *
     * @param \MadeInUA\LaravelDripFlow\Models\DripStream $stream
     * @return DripSubscription
     */
    public function joinDripStream($stream): DripSubscription
    {
        /** @var \MadeInUA\LaravelDripFlow\Contracts\DripSubscriber $this */
        return app(\MadeInUA\LaravelDripFlow\Services\DripManager::class)
            ->join($this, $stream);
    }

    /**
     * Check if joined/subscribed to a stream
     *
     * @param \MadeInUA\LaravelDripFlow\Models\DripStream $stream
     * @return bool
     */
    public function isJoinedToDripStream($stream): bool
    {
        return $this->dripSubscriptions()
            ->where('stream_id', is_object($stream) ? $stream->id : $stream)
            ->exists();
    }
}
