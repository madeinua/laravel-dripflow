<?php

namespace MadeInUA\LaravelDripFlow\Strategies;

use Carbon\Carbon;
use MadeInUA\LaravelDripFlow\Models\DripEvent;
use MadeInUA\LaravelDripFlow\Models\DripSubscription;

/**
 * Relative Delay Strategy
 *
 * Unlocks content based on when the user subscribed to the stream
 * Uses subscription's joined_at + event's offset_interval
 */
class RelativeDelayStrategy implements UnlockStrategy
{
    /**
     * @param DripEvent $event
     * @param DripSubscription|null $subscription
     * @return Carbon|null
     */
    public function calculateUnlockTime(DripEvent $event, ?DripSubscription $subscription): ?Carbon
    {
        // For public streams subscription isn't required
        if (!$subscription) {
            return null;
        }

        $unlockTime = $subscription->joined_at->copy()->addSeconds($event->getOffsetInSeconds());
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
        $unlockTime = $this->calculateUnlockTime($event, $subscription);

        // If calculateUnlockTime returns null, it means it's unlocked
        // If it returns a future date, it's still locked
        return $unlockTime === null;
    }
}
