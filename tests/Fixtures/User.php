<?php

namespace MadeInUA\LaravelDripFlow\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MadeInUA\LaravelDripFlow\Contracts\DripSubscriber;
use MadeInUA\LaravelDripFlow\Traits\HasDripSubscriptions;

class User extends Model implements DripSubscriber
{
    use HasDripSubscriptions;

    protected $fillable = ['name', 'email'];
}
