<?php

namespace MadeInUA\LaravelDripFlow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * DripEvent Model
 *
 * Represents a single content item in a drip stream with its unlock schedule
 *
 * @property int $id
 * @property int $stream_id
 * @property string $eventable_type
 * @property int $eventable_id
 * @property string $offset_interval (ISO 8601 duration or seconds)
 * @property bool $is_visible
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DripEvent extends Model
{
    protected $fillable = [
        'stream_id',
        'eventable_type',
        'eventable_id',
        'offset_interval',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return config('dripflow.tables.events', parent::getTable());
    }

    /**
     * Get the stream this event belongs to
     *
     * @return BelongsTo
     */
    public function stream(): BelongsTo
    {
        return $this->belongsTo(config('dripflow.models.stream'), 'stream_id');
    }

    /**
     * Get the owning content model (e.g., Video, Lesson, Post)
     *
     * @return MorphTo
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Parse the offset interval into seconds
     *
     * Accepts either numeric seconds or ISO 8601 duration format (e.g., 'P1D', 'PT1H')
     *
     * @return int
     * @throws \InvalidArgumentException If offset_interval format is invalid
     */
    public function getOffsetInSeconds(): int
    {
        if (is_numeric($this->offset_interval)) {
            return (int) $this->offset_interval;
        }

        try {
            $interval = new \DateInterval($this->offset_interval);
            $reference = new \DateTime();
            $endTime = (clone $reference)->add($interval);

            return $endTime->getTimestamp() - $reference->getTimestamp();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                "Invalid offset_interval format: '{$this->offset_interval}'. " .
                "Expected numeric seconds (e.g., '86400') or ISO 8601 duration (e.g., 'P1D'). " .
                "Error: {$e->getMessage()}"
            );
        }
    }

    /**
     * Scope: Get visible events only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
