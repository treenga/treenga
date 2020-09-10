<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'private',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model)
        {
            $model->slug = $model->id;
            $model->save();
        });
    }

    /**Start Relations */
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('is_owner', 'username', 'current', 'filter', 'treestate', 'current_state')->withTimestamps();
    }

    public function categories()
    {
        return $this->morphToMany(Category::class, 'categoryable')->withTimestamps();
    }

    public function privateCategories()
    {
        return $this->morphToMany(Category::class, 'categoryable')->withTimestamps()->where('type', Category::TYPE_PRIVATE);
    }

    public function publicCategories()
    {
        return $this->morphToMany(Category::class, 'categoryable')->withTimestamps()->where('type', Category::TYPE_PUBLIC);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function userNotifications()
    {
        return $this->morphToMany(Notification::class, 'notifyables')->withTimestamps()->withPivot('user_id');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }
    /**End Relations */

    /**Start Scopes*/
    public function scopeWithUserNotify($query, User $user)
    {
        return $query->withCount(['userNotifications' => function($q) use($user){
            $q->where('user_id', $user->id);
            $q->where('read_at', null);
        }]);
    }
    /**End Scopes */

    /**Start Mutators*/
    public function getUrlAttribute()
    {
        return secure_url($this->slug);
    }

    public function getAuthUsernameAttribute()
    {
        $this->loadMissing('users');
        $user = $this->users->firstWhere('id', auth()->id());
        return $user ? $user->pivot->username : 'unknown_acc';
    }

    public function getAuthFilterAttribute()
    {
        $this->loadMissing('users');
        $user = $this->users->firstWhere('id', auth()->id());
        return ($user && ($filter = $user->pivot->filter))  ? json_decode($filter) : null;
    }

    public function getAuthTreestateAttribute()
    {
        $this->loadMissing('users');
        $user = $this->users->firstWhere('id', auth()->id());
        return ($user && ($treestate = $user->pivot->treestate))  ? json_decode($treestate) : null;
    }

    public function getAuthCurrentStateAttribute()
    {
        $this->loadMissing('users');
        $user = $this->users->firstWhere('id', auth()->id());
        return ($user && ($current_state = $user->pivot->current_state))  ? json_decode($current_state) : null;
    }
    /**End Mutators */

    /**Start Helper*/
    public function hasOwner(User $user)
    {
        $this->loadMissing('users');
        return ! empty($this->users->where('id', $user->id)->where('pivot.is_owner', true)->count());
    }

    public function isBlock()
    {
        return false;
    }

    public function getUsername(User $user)
    {
        $this->loadMissing('users');
        $user = $this->users->firstWhere('id', $user->id);
        return $user ? $user->pivot->username : null;
    }

    public function getDefaultTreestate()
    {
        $data = new \stdClass;
        $data->create = [
            'assignees_tree' => [0],
            'private_tree' => [0],
            'public_tree' => [0],
        ];
        $data->main = [
            'assignees_tree' => [0],
            'private_tree' => [0],
            'public_tree' => [0],
            'system_tree' => [0],
            'authors_tree' => [0],
        ];
        $data->filter = [
            'public_tree' => [0],
            'private_tree' => [0],
            'authors_tree' => [0],
            "assignees_tree" => [0],
            "due_date_tree" => ["main title"]
        ];

        return json_encode($data);
    }

    public function getDefaultFilter()
    {
        $data = new \stdClass;
        $data->public = [
            'categories' => [],
            'users' => [],
            'authors' => [],
            'due_date_type' => 'daterange',
            'due_date' => null,
            'invert' => false,
            'combine' => 'and',
            'type' => 'public',
            'enabled' => true
        ];

        return json_encode($data);
    }

    public function getDefaultCurrentState()
    {
        $data = new \stdClass;
        $data->point = 'category';
        $data->curentTasksStatus = 'opened';
        $data->id = 0;

        return json_encode($data);
    }
    /**End Helper*/
}
