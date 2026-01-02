<?php

namespace MadeInUA\LaravelDripFlow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * DripSubscription Model
 *
 * Represents a subscriber's subscription to a drip stream
 *
 * @property int $id
 * @property string $subscriber_type
 * @property int $subscriber_id
 * @property int $stream_id
 * @property \Carbon\Carbon $joined_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DripSubscription extends Model
{
    protected $fillable = [
        'subscriber_type',
        'subscriber_id',
        'stream_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return config('dripflow.tables.subscriptions', parent::getTable());
    }

    /**
     * Get the subscriber (e.g., User)
     *
     * @return MorphTo
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the stream this subscription belongs to
     *
     * @return BelongsTo
     */
    public function stream(): BelongsTo
    {
        return $this->belongsTo(config('dripflow.models.stream'), 'stream_id');
    }
}
