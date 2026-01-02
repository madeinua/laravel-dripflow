<?php

namespace MadeInUA\LaravelDripFlow\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Interface for models that can act as a DripFlow stream origin
 * (e.g., Course, Marathon, Channel)
 */
interface DripStreamOrigin
{
    /**
     * Get the drip stream configuration for this origin
     *
     * @return MorphOne
     */
    public function dripStream(): MorphOne;
}
