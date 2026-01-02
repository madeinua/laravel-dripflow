<?php

namespace MadeInUA\LaravelDripFlow\Services;

use MadeInUA\LaravelDripFlow\Contracts\DripSubscriber;
use MadeInUA\LaravelDripFlow\DTOs\DripEventState;
use MadeInUA\LaravelDripFlow\DTOs\DripEventStateEnum;
use MadeInUA\LaravelDripFlow\Models\DripEvent;
use MadeInUA\LaravelDripFlow\Models\DripStream;
use MadeInUA\LaravelDripFlow\Models\DripSubscription;
use Illuminate\Support\Collection;

/**
 * DripManager Service
 *
 * Core service for managing drip content streams, subscriptions, and access control
 */
class DripManager
{
    /**
     * Join a subscriber to a stream
     *
     * This method is idempotent - calling it multiple times will not change
     * the original joined_at timestamp. Use rejoin() to reset progress.
     *
     * @param DripSubscriber $subscriber The user joining the stream
     * @param DripStream $stream The stream to join
     * @return DripSubscription The subscription record
     */
    public function join(DripSubscriber $subscriber, DripStream $stream): DripSubscription
    {
        return config('dripflow.models.subscription')::firstOrCreate([
            'subscriber_id'   => $subscriber->getSubscriberId(),
            'subscriber_type' => $subscriber->getSubscriberType(),
            'stream_id'       => $stream->id,
        ], [
            'joined_at' => now(),
        ]);
    }

    /**
     * Rejoin a stream and reset progress
     *
     * This will update the joined_at timestamp to now, effectively
     * restarting the drip schedule from the beginning.
     *
     * @param DripSubscriber $subscriber The user rejoining the stream
     * @param DripStream $stream The stream to rejoin
     * @return DripSubscription The updated subscription record
     */
    public function rejoin(DripSubscriber $subscriber, DripStream $stream): DripSubscription
    {
        return config('dripflow.models.subscription')::updateOrCreate([
            'subscriber_id'   => $subscriber->getSubscriberId(),
            'subscriber_type' => $subscriber->getSubscriberType(),
            'stream_id'       => $stream->id,
        ], [
            'joined_at' => now(),
        ]);
    }

    /**
     * Leave a stream and remove the subscription
     *
     * @param DripSubscriber $subscriber The user leaving the stream
     * @param DripStream $stream The stream to leave
     * @return bool True if subscription was deleted, false if not found
     */
    public function leave(DripSubscriber $subscriber, DripStream $stream): bool
    {
        return config('dripflow.models.subscription')::where('subscriber_id', $subscriber->getSubscriberId())
                   ->where('subscriber_type', $subscriber->getSubscriberType())
                   ->where('stream_id', $stream->id)
                   ->delete() > 0;
    }

    /**
     * Get all visible events in a stream with their states for a subscriber
     *
     * Note: For performance optimization, consider caching the result using getCacheKey()
     *
     * @param DripSubscriber|null $subscriber The subscriber (null for public streams)
     * @param DripStream $stream The stream to get events from
     * @return Collection<DripEventState>
     */
    public function getStreamEvents(?DripSubscriber $subscriber, DripStream $stream): Collection
    {
        $subscription = null;

        if ($subscriber) {
            $subscription = config('dripflow.models.subscription')::where('stream_id', $stream->id)
                ->where('subscriber_id', $subscriber->getSubscriberId())
                ->where('subscriber_type', $subscriber->getSubscriberType())
                ->first();
        }

        return $stream->events()
            ->where('is_visible', true)
            ->get()
            ->map(fn(DripEvent $event) => $this->resolveState($event, $subscription));
    }

    /**
     * Generate a cache key for stream events
     *
     * Use this to cache getStreamEvents() results. The key includes:
     * - Stream ID
     * - Subscriber ID (or 'guest' for public)
     * - Stream's updated_at timestamp (invalidates on stream changes)
     * - Subscription's joined_at timestamp (invalidates on rejoin)
     *
     * @param DripSubscriber|null $subscriber The subscriber (null for public streams)
     * @param DripStream $stream The stream
     * @return string Cache key for this subscriber-stream combination
     */
    public function getCacheKey(?DripSubscriber $subscriber, DripStream $stream): string
    {
        $subscriberKey = $subscriber
            ? "{$subscriber->getSubscriberType()}:{$subscriber->getSubscriberId()}"
            : 'guest';

        $streamTimestamp = $stream->updated_at->timestamp;

        $subscriptionTimestamp = '';
        if ($subscriber) {
            $subscription = config('dripflow.models.subscription')::where('stream_id', $stream->id)
                ->where('subscriber_id', $subscriber->getSubscriberId())
                ->where('subscriber_type', $subscriber->getSubscriberType())
                ->first();

            $subscriptionTimestamp = $subscription?->joined_at?->timestamp ?? 'none';
        }

        return "dripflow:events:{$stream->id}:{$subscriberKey}:{$streamTimestamp}:{$subscriptionTimestamp}";
    }

    /**
     * Resolve the state of a single event for a subscriber
     *
     * @param DripEvent $event The event to resolve
     * @param DripSubscription|null $subscription The subscription (null for public streams)
     * @return DripEventState The resolved event state
     */
    public function resolveState(DripEvent $event, ?DripSubscription $subscription): DripEventState
    {
        // Check if stream is active
        if (!$event->stream->isActive()) {
            return new DripEventState(
                $event->eventable,
                DripEventStateEnum::HIDDEN,
                now()->addCentury()
            );
        }

        // Invalid case: private streams must come with a subscription
        if (!$event->stream->isPublic() && !$subscription) {
            return new DripEventState(
                $event->eventable,
                DripEventStateEnum::HIDDEN,
                now()->addCentury()
            );
        }

        // Get strategy from config
        $strategyClass = config("dripflow.strategies.{$event->stream->unlock_mode}");
        $strategy = new $strategyClass();

        $unlockTime = $strategy->calculateUnlockTime($event, $subscription);
        $isUnlocked = $strategy->isUnlocked($event, $subscription);

        return new DripEventState(
            $event->eventable,
            $isUnlocked ? DripEventStateEnum::OPENED : DripEventStateEnum::LOCKED,
            $unlockTime ?? now()->addCentury()
        );
    }
}