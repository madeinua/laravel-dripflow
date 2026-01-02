<?php

namespace MadeInUA\LaravelDripFlow\DTOs;

use Carbon\Carbon;
use MadeInUA\LaravelDripFlow\Contracts\DripEventSource;

/**
 * Data Transfer Object representing the current state of a drip event
 * for a specific subscriber
 */
readonly class DripEventState
{
    public function __construct(
        public DripEventSource $eventSource,
        public DripEventStateEnum $state,
        public ?Carbon $unlocksAt = null,
    ) {
    }

    /**
     * Get the appropriate payload based on current state
     *
     * @return array
     */
    public function getPayload(): array
    {
        return match ($this->state) {
            DripEventStateEnum::HIDDEN => [],
            DripEventStateEnum::LOCKED => $this->eventSource->getTeaserPayload(),
            DripEventStateEnum::OPENED => $this->eventSource->getFullPayload(),
        };
    }

    /**
     * Get time remaining until unlock (for locked content)
     * Returns null if already opened or hidden
     *
     * @return string|null
     */
    public function timeRemaining(): ?string
    {
        if ($this->state->isOpened() || $this->state->isHidden()) {
            return null;
        }

        return $this->unlocksAt?->diffForHumans();
    }

    /**
     * Convert to array for JSON responses
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'state'          => $this->state->value,
            'is_hidden'      => $this->state->isHidden(),
            'is_locked'      => $this->state->isLocked(),
            'is_opened'      => $this->state->isOpened(),
            'unlocks_at'     => $this->unlocksAt?->toISOString(),
            'time_remaining' => $this->timeRemaining(),
            'payload'        => $this->getPayload(),
        ];
    }
}
