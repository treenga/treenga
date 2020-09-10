<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'author_id',
        'text',
        'username',
    ];

    public function activityable()
    {
        return $this->morphTo();
    }

    /**Start Relations */
    /**End Relations */

    /**Start Scopes*/
    /**End Scopes */

    /**Start Mutators*/
    public function getBodyAttribute()
    {
        return $this->username . ' ' . $this->text;
    }
    /**End Mutators */

    /**Start Helper*/
    public function unreadUsers()
    {
        return $this->belongsToMany(User::class, 'user_unread_activity')->withTimestamps();
    }
    /**End Helper*/

}
