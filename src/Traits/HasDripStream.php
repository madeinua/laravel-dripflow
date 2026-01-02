<?php

namespace MadeInUA\LaravelDripFlow\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Trait for models that can act as a drip stream origin
 * (e.g., Course, Marathon, Channel)
 *
 * Add this trait to models and implement DripStreamOrigin contract
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasDripStream
{
    /**
     * Get the drip stream configuration for this origin
     *
     * @return MorphOne
     */
    public function dripStream(): MorphOne
    {
        return $this->morphOne(config('dripflow.models.stream'), 'origin');
    }

    /**
     * Get the morph type for this origin (class name for polymorphic relations)
     *
     * @return string
     */
    public function getOriginType(): string
    {
        return $this->getMorphClass();
    }

    /**
     * Check if this model has a drip stream configured
     *
     * @return bool
     */
    public function hasDripStream(): bool
    {
        return $this->dripStream()->exists();
    }
}
