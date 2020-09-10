<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Comment extends Model
{
    use NodeTrait;

    protected $fillable = [
        'body',
        'author_id',
        'username',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($model)
        {
            $model->readUsers()->detach();
        });
    }

    /**Start Relations */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tasks()
    {
        return $this->morphedByMany(Task::class, 'comentables');
    }

    public function readUsers()
    {
        return $this->belongsToMany(User::class, 'user_read_comment')->withTimestamps();
    }
    /**End Relations */

    /**Start Scopes*/
    /**End Scopes */

    /**Start Mutators*/
    /**End Mutators */

    /**Start Helper*/
    /**End Helper*/
}
