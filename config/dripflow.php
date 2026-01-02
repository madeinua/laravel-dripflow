<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the package if needed
    |
    */
    'tables' => [
        'streams' => 'drip_streams',
        'events' => 'drip_events',
        'subscriptions' => 'drip_subscriptions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Override default models if you need to extend them
    |
    */
    'models' => [
        'stream' => \MadeInUA\LaravelDripFlow\Models\DripStream::class,
        'event' => \MadeInUA\LaravelDripFlow\Models\DripEvent::class,
        'subscription' => \MadeInUA\LaravelDripFlow\Models\DripSubscription::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Strategies
    |--------------------------------------------------------------------------
    |
    | Override or add custom unlock strategies
    |
    */
    'strategies' => [
        'fixed' => \MadeInUA\LaravelDripFlow\Strategies\FixedDateStrategy::class,
        'relative' => \MadeInUA\LaravelDripFlow\Strategies\RelativeDelayStrategy::class,
    ],
];
