<?php

namespace MadeInUA\LaravelDripFlow\Strategies;

use Carbon\Carbon;
use MadeInUA\LaravelDripFlow\Models\DripEvent;
use MadeInUA\LaravelDripFlow\Models\DripSubscription;

/**
 * Interface for unlock time calculation strategies
 */
interface UnlockStrategy
{
    /**
     * Calculate when an event should unlock for a subscriber
     *
     * @param DripEvent $event The event to calculate unlock time for
     * @param DripSubscription|null $subscription The subscriber's subscription (null if public stream)
     * @return Carbon|null The unlock datetime, or null if already unlocked
     */
    public function calculateUnlockTime(DripEvent $event, ?DripSubscription $subscription): ?Carbon;

    /**
     * Check if an event is currently unlocked
     *
     * @param DripEvent $event The event to check
     * @param DripSubscription|null $subscription The subscriber's subscription
     * @return bool True if unlocked, false otherwise
     */
    public function isUnlocked(DripEvent $event, ?DripSubscription $subscription): bool;
}
