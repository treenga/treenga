<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    const STATUS_NEW = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_INVITED = 2;
    const STATUS_DELETED = 3;

    const USER_ROLE = 0;
    const ADMIN_ROLE = 1;

    const BALANCE_WHEN_REGISTER = 200;

    protected $fillable = [
        'status',
        'name',
        'email',
        'password',
        'is_team_author',
        'email_verified_at',
        'new_email',
        'show_task_all_comments',
        'show_task_details',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'show_task_all_comments' => 'boolean',
        'show_task_details' => 'boolean',
    ];

    public function receivesBroadcastNotificationsOn()
    {
        return 'user.'.$this->id;
    }

    /**Start Relations */
    public function hashes()
    {
        return $this->morphToMany('App\Hash', 'hashable')->withTimestamps();
    }

    public function categories()
    {
        return $this->morphToMany('App\Category', 'categoryable')->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class)->withPivot('is_owner', 'username', 'current', 'filter', 'treestate', 'current_state')->withTimestamps();
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }

    public function deleted_tasks()
    {
        return $this->belongsToMany(Task::class)->onlyTrashed();
    }

    public function authoredTasks()
    {
        return $this->hasMany(Task::class, 'author_id');
    }

    public function deletedAuthoredTasks()
    {
        return $this->hasMany(Task::class, 'author_id')->onlyTrashed();
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    public function assignedTasks()
    {
        return $this->belongsToMany(Task::class, 'task_user');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'author_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'author_id');
    }

    public function readComments()
    {
        return $this->belongsToMany(Comment::class, 'user_read_comment')->withTimestamps();
    }

    public function unreadActivities()
    {
        return $this->belongsToMany(Activity::class, 'user_unread_activity')->withTimestamps();
    }

    public function lastViewed()
    {
        return $this->belongsToMany(Task::class, 'user_task_viewed')->withTimestamps();
    }

    public function taskOptions()
    {
        return $this->belongsToMany(Task::class, 'user_task_option')->withPivot('commentsstate')->withTimestamps();
    }

    /**End Relations */

    /**Start Scopes*/
    public function scopeWithUsername($query, Team $team)
    {
        return $query->with(['teams' => function($q) use ($team){
            $q->where('team_id', $team->id);
        }]);
    }
    /**End Scopes */

    /**Start Mutators*/
    public function getStatusTextAttribute()
    {
        return $this->getStatuses($this->status);
    }

    public function getAllTeamsAttribute()
    {
        return $this->teams;
    }

    //team_username
    public function getTeamUsernameAttribute()
    {
        return count($this->teams) ? optional($this->teams[0]->pivot)->username : null;
    }

    /**End Mutators */

    /**Start Helper*/
    public function getStatuses($id = null)
    {
        $statuses = [
            self::STATUS_NEW => 'new',
            self::STATUS_ACTIVE => 'active',
            self::STATUS_INVITED => 'invited',
        ];
        return ($id === null) ? collect($statuses) : array_get($statuses, $id);
    }

    public function hasPrivate()
    {
        return $this->teams->where('private', true)->count() > 0;
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function isAdmin()
    {
        return $this->role == User::ADMIN_ROLE;
    }

    public function isTeamOwner(Team $team)
    {
        $this->loadMissing('teams');
        return !empty($this->teams->where('id', $team->id)->where('pivot.is_owner', true)->count());
    }
    /**End Helper*/
}
