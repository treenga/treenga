<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class TaskText extends Model
{
    use Searchable;

    protected $fillable = [
        'body',
    ];

    public function toSearchableArray()
    {
        return [
            'body' => $this->body,
        ];
    }
}
