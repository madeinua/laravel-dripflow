<?php

namespace MadeInUA\LaravelDripFlow\Strategies;

use Carbon\Carbon;
use MadeInUA\LaravelDripFlow\Models\DripEvent;
use MadeInUA\LaravelDripFlow\Models\DripSubscription;

/**
 * Fixed Date Strategy
 *
 * Unlocks content at a specific calendar date/time for all users
 * Uses the stream's start_date + event's offset_interval
 */
class FixedDateStrategy implements UnlockStrategy
{
    /**
     * @param DripEvent $event
     * @param DripSubscription|null $subscription
     * @return Carbon|null
     */
    public function calculateUnlockTime(DripEvent $event, ?DripSubscription $subscription): ?Carbon
    {
        $stream = $event->stream;
        if (!$stream->start_date) {
            return null;
        }

        $unlockTime = $stream->start_date->copy()->addSeconds($event->getOffsetInSeconds());
        if ($unlockTime->isPast()) {
            return null;
        }

        return $unlockTime;
    }

    /**
     * @param DripEvent $event
     * @param DripSubscription|null $subscription
     * @return bool
     */
    public function isUnlocked(DripEvent $event, ?DripSubscription $subscription): bool
    {
        // If calculateUnlockTime returns null, it means it's unlocked
        // If it returns a future date, it's still locked
        return $this->calculateUnlockTime($event, $subscription) === null;
    }
}
