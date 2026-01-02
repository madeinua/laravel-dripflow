<?php

namespace MadeInUA\LaravelDripFlow\DTOs;

/**
 * Enum representing the three possible states of a drip event
 */
enum DripEventStateEnum: string
{
    case HIDDEN = 'hidden';   // Event is not visible at all
    case LOCKED = 'locked';   // Event is visible but locked (show teaser/blur)
    case OPENED = 'opened';   // Event is unlocked and fully accessible

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this === self::HIDDEN;
    }

    /**
     * @return bool
     */
    public function isOpened(): bool
    {
        return $this === self::OPENED;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this === self::LOCKED;
    }
}
