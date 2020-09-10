<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use NodeTrait, SoftDeletes;

    const TYPE_PRIVATE = 1;
    const TYPE_PUBLIC = 2;

    protected $fillable = [
        'name',
        'type',
    ];

    /**Start Relations */
    public function users()
    {
        return $this->morphedByMany(User::class, 'categoryable');
    }

    public function teams()
    {
        return $this->morphedByMany(Team::class, 'categoryable')->withTimestamps();
    }

    public function roots()
    {
        return $this->ancestors()->whereIsRoot();
    }

    public function text()
    {
        return $this->hasOne(CategoryText::class);
    }

    public function histories()
    {
        return $this->morphMany(History::class, 'historyable');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }

    public function deleted_tasks()
    {
        return $this->belongsToMany(Task::class)->onlyTrashed();
    }

    public function comments()
    {
        return $this->morphToMany(Comment::class, 'comentables')->withTimestamps();
    }

    public function userNotifications()
    {
        return $this->morphToMany(Notification::class, 'notifyables')->withTimestamps()->withPivot('user_id');
    }

    public function categoryNotifications()
    {
        return $this->morphToMany(Notification::class, 'notifyables')->withTimestamps()->withPivot('user_id')->where('data->category_id', '<>', '');
    }

    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'category_subscriber')->withTimestamps();
    }
    /**End Relations */

    /**Start Scopes*/
    public function scopePrivateUser($query, User $user)
    {
        return $query->whereHas('users', function($q) use($user){
            $q->where('categoryable_id', $user->id);
        });
    }

    public function scopePublic($query)
    {
        return $query->where('type', self::TYPE_PUBLIC);
    }

    public function scopeForTree($query, User $user)
    {
        return $query->withCounts($user)->with([
            'text', 
            'descendants' => function($q) use($user){
                $q->withCounts($user);
            }
        ]);
    }

    public function scopeWithCounts($query, User $user)
    {
        $query->orderBy('name');
        $query->withCount([
            'categoryNotifications',
            'userNotifications' => function($q2) use($user){
                $q2->where('user_id', $user->id);
                $q2->where('read_at', null);
            },
        ]);
    }

    public function scopeWithTasksIds($query, User $user)
    {
        return $query->with(['tasks' => function($q) use($user){
            $q->onlyFields(['id', 'drafted_by']);
            $q->onlyUserDrafts($user);
        }]);
    }
    /**End Scopes */

    /**Start Mutators*/
    public function getIsArchivedAttribute()
    {
        return $this->deleted_at != null;
    }

    public function getLoadedTasksCountAttribute()
    {
        return $this->tasks->count();
    }

    public function getLoadedUniqueDescendantsTasksCountAttribute()
    {
        $currentTaskIds = $this->tasks->pluck('id');
        return $currentTaskIds->count() + $this->descendants->pluck('tasks')->flatten(1)->whereNotIn('id', $currentTaskIds)->unique('id')->count();
    }
    /**End Mutators */

    /**Start Helper*/
    public function isPublic()
    {
        return $this->type == self::TYPE_PUBLIC;
    }

    public function isPrivate()
    {
        return $this->type == self::TYPE_PRIVATE;
    }
    /**End Helper*/
}
