<?php

namespace MadeInUA\LaravelDripFlow\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MadeInUA\LaravelDripFlow\Contracts\DripEventSource;
use MadeInUA\LaravelDripFlow\Contracts\DripStreamOrigin;
use MadeInUA\LaravelDripFlow\Traits\HasDripEvents;
use MadeInUA\LaravelDripFlow\Traits\HasDripStream;

class Course extends Model implements DripStreamOrigin, DripEventSource
{
    use HasDripStream;
    use HasDripEvents;

    protected $fillable = ['title', 'excerpt', 'content'];

    public function getTeaserPayload(): array
    {
        return [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
        ];
    }

    public function getFullPayload(): array
    {
        return [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
        ];
    }
}
