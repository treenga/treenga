<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Hash extends Model
{
    const TYPE_VERIFY = 'verify_email';
    const TYPE_RECOVERY = 'recovery_password';
    const TYPE_CHANGE_EMAIL = 'change_email';
    const TYPE_TASK_SUBSCRIBE = 'task_subscribe';
    const TYPE_TEAM_INVITE = 'team_invite';

    protected $fillable = [
        'hash',
        'type',
        'expired_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($model)
        {
            $model->users()->detach();
            $model->tasks()->detach();
        });
    }

    /**Start Relations */
    public function users()
    {
        return $this->morphedByMany(User::class, 'hashable')->withTimestamps();
    }

    public function tasks()
    {
        return $this->morphedByMany(Task::class, 'hashable')->withTimestamps();
    }
    /**End Relations */

    /**Start Scopes*/
    public function scopeTaskUserSubscribed($query, Task $task, User $user)
    {
        return $query
            ->where('type', self::TYPE_TASK_SUBSCRIBE)
            ->whereHas('users', function($q2) use($user) {
                $q2->where('hashable_id', $user->id);
            }
            )->whereHas('tasks', function($q2) use($task) {
                $q2->where('hashable_id', $task->id);
            });
    }
    /**End Scopes */

    /**Start Mutators*/
    /**End Mutators */

    /**Start Helper*/
    /**End Helper*/

}
