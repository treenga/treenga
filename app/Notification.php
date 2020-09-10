<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($model)
        {
            $model->categories()->detach();
            $model->tasks()->detach();
            $model->teams()->detach();
        });
    }
    /**Start Relations */
    public function categories()
    {
        return $this->morphedByMany(Category::class, 'notifyables');
    }

    public function tasks()
    {
        return $this->morphedByMany(Task::class, 'notifyables');
    }

    public function teams()
    {
        return $this->morphedByMany(Team::class, 'notifyables');
    }
    /**End Relations */

    /**Start Scopes*/
    /**End Scopes */

    /**Start Mutators*/
    /**End Mutators */

    /**Start Helper*/
    /**End Helper*/
}
