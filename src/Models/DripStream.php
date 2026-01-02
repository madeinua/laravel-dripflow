<?php

namespace MadeInUA\LaravelDripFlow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * DripStream Model
 *
 * Represents a stream configuration that controls how content is unlocked
 *
 * @property int $id
 * @property string $origin_type
 * @property int $origin_id
 * @property bool $is_public
 * @property string $unlock_mode (fixed|relative)
 * @property \Carbon\Carbon|null $start_date
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DripStream extends Model
{
    protected $fillable = [
        'origin_type',
        'origin_id',
        'is_public',
        'unlock_mode',
        'start_date',
        'is_active',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return config('dripflow.tables.streams', parent::getTable());
    }

    /**
     * Get the owning origin model (e.g., Course, Marathon)
     *
     * @return MorphTo
     */
    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all events in this stream
     *
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(config('dripflow.models.event'), 'stream_id');
    }

    /**
     * Get all subscriptions to this stream
     *
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(config('dripflow.models.subscription'), 'stream_id');
    }

    /**
     * Check if unlock mode is fixed (absolute date)
     *
     * @return bool
     */
    public function isFixedMode(): bool
    {
        return $this->unlock_mode === 'fixed';
    }

    /**
     * Check if unlock mode is relative (from subscription date)
     *
     * @return bool
     */
    public function isRelativeMode(): bool
    {
        return $this->unlock_mode === 'relative';
    }

    /**
     * Check if stream is public (no subscription required)
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Check if stream is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Validation: if unlock_mode is 'fixed', start_date is required
     */
    protected static function booted(): void
    {
        static::saving(static function (DripStream $stream) {
            if ($stream->unlock_mode === 'fixed' && !$stream->start_date) {
                throw new \InvalidArgumentException('start_date is required when unlock_mode is "fixed"');
            }
        });
    }
}
