<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Task extends Model
{
    use SoftDeletes;

    const TYPE_PRIVATE = 1;
    const TYPE_PUBLIC = 2;

    const SUBSCRIBE_TYPE_HAND = 0;
    const SUBSCRIBE_TYPE_AUTHOR = 1;
    const SUBSCRIBE_TYPE_EDITOR = 2;
    const SUBSCRIBE_TYPE_ASSIGN = 3;
    const SUBSCRIBE_TYPE_MENTION = 4;
    const SUBSCRIBE_TYPE_COMMENT_AUTHOR = 5;
    const SUBSCRIBE_TYPE_COMMENT_MENTION = 6;
    const SUBSCRIBE_TYPE_REVERT_HISTORY = 7;

    const MASS_ACTION_ASSIGN = 'assign';
    const MASS_ACTION_UNASSIGN = 'unassign';
    const MASS_ACTION_OPEN = 'open';
    const MASS_ACTION_CLOSE = 'close';
    const MASS_ACTION_SET_DUE_DATE = 'set-due-date';

    protected $fillable = [
        'name',
        'type',
        'team_id',
        'author_id',
        'owner_id',
        'drafted_by',
        'due_date',
        'team_task_id',
    ];

    protected $dates = [
        'due_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            $model->team()->increment('task_sequence');
            $model->team_task_id = $model->team->task_sequence;
        });
    }

    /**Start Relations */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function text()
    {
        return $this->hasOne(TaskText::class);
    }

    public function histories()
    {
        return $this->morphMany(History::class, 'historyable');
    }

    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'task_subscriber')->withTimestamps()->withPivot('type');
    }

    public function userNotifications()
    {
        return $this->morphToMany(Notification::class, 'notifyables')->withTimestamps()->withPivot('user_id');
    }

    public function comments()
    {
        return $this->morphToMany(Comment::class, 'comentables')->withTimestamps();
    }

    public function hashes()
    {
        return $this->morphToMany('App\Hash', 'hashable')->withTimestamps();
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'activityable');
    }

    public function lastActivity()
    {
        return $this->morphOne(Activity::class, 'activityable')->orderBy('created_at', 'desc');
    }

    public function usersViewed()
    {
        return $this->belongsToMany(User::class, 'user_task_viewed')->withTimestamps();
    }

    public function userOptions()
    {
        return $this->belongsToMany(User::class, 'user_task_option')->withPivot('commentsstate')->withTimestamps();
    }

    public function tempItems()
    {
        return $this->hasMany(TempTask::class);
    }

    public function tempItem()
    {
        return $this->hasOne(TempTask::class);
    }
    /**End Relations */

    /**Start Scopes*/
    public function scopeOnlyFields($query, array $fields)
    {
        return $query->select($fields);
    }

    public function scopeFilter($query, $search = null)
    {
        if ($search) {
            $search = strtolower($search);
            $textsIds = TaskText::search($search)->get()->pluck('id');
            return $query->where(function($q1) use($search, $textsIds){
                $q1->whereHas('text',function($q2) use($textsIds){
                    $q2->whereIn('id', $textsIds);
                });
                $q1->orWhere('name','ilike', '%'.strtolower($search).'%');
                if (is_numeric($search)) {
                    $q1->orWhere('team_task_id', $search);
                }
            });
        }
        return $query;
    }

    public function scopeOnlyUserDrafts($query, User $user)
    {
        return $query->where(function($q) use ($user){
            $q->whereNull('drafted_by');
            $q->orWhere('drafted_by', $user->id);
        });
    }

    public function scopeWithAuthorUsername($query, Team $team)
    {
        return $query->with(['author.teams' => function($q2) use($team){
            $q2->where('team_id', $team->id);
        }]);
    }

    public function scopeForList($query, Team $team, User $user)
    {
        $query->orderBy('updated_at', 'desc');
        $query->onlyUserDrafts($user);
        $query->with('lastActivity');
        $query->withCount(['tempItem' => function($q) use($user){
            $q->where('user_id', $user->id);
        }]);
        $query->withCount(['userNotifications' => function($q) use($user){
            $q->where('user_id', $user->id);
            $q->where('read_at', null);
        }]);
        $query->withCatUserIds();
        return $query;
    }

    public function scopeForPrivateList($query, User $user)
    {
        $query->orderBy('updated_at', 'desc');
        return $query;
    }

    public function scopeWithCatUserIds($query)
    {
        return $query->with(['categories' => function($q){
            $q->select('id');
        }, 'users' => function($q){
            $q->select('id');
        }]);
    }

    public function scopeOnlyUserPrivate($query,  User $user)
    {
        return $query->where('type', self::TYPE_PUBLIC)->orWhere(function($q) use($user){
            $q->where('type', self::TYPE_PRIVATE);
            $q->where('author_id', $user->id);
        });
    }

    public function scopeTeam($query, Team $team)
    {
        return $query->where('team_id', $team->id);
    }

    public function scopePublic($query)
    {
        return $query->where('type', self::TYPE_PUBLIC);
    }

    public function scopePrivate($query)
    {
        return $query->where('type', self::TYPE_PRIVATE);
    }

    public function scopeAuthor($query, User $user)
    {
        return $query->where('author_id', $user->id);
    }

    public function scopeUserDrafts($query, User $user)
    {
        return $query->where('drafted_by', $user->id);
    }

    public function scopeDraft($query)
    {
        return $query->whereNotNull('drafted_by');
    }

    public function scopeNoDraft($query)
    {
        return $query->whereNull('drafted_by');
    }

    public function scopeNoCats($query)
    {
        return $query->doesntHave('categories');
    }
    /**End Scopes */

    /**Start Mutators*/
    public function setDueDateAttribute($value)
    {
        if (is_string($value)) {
            try{
                $this->attributes['due_date'] = Carbon::parse($value);
            } catch(\Exception $e) {
                $this->attributes['due_date'] = null;
            }
        } else {
            $this->attributes['due_date'] = $value;
        }
    }

    public function getUrlAttribute()
    {
        $this->loadMissing('team');
        return $this->team->url . '/' . $this->team_task_id;
    }

    public function getIsArchivedAttribute()
    {
        return $this->deleted_at != null;
    }

    public function getSubscribeTypeEmailTextAttribute()
    {
        return count($this->subscribers) ? $this->getSubscribeTypeEmailText(optional($this->subscribers[0]->pivot)->type) : false;
    }

    public function getUnsubscribeEmailUrlAttribute()
    {
        return count($this->hashes) ? secure_url('task/unsubscribe/'.$this->hashes[0]->hash) : null;
    }

    public function getLastUpdatedAtAttribute()
    {
        if($this->relationLoaded('lastActivity') && ($lastActivity = $this->lastActivity)) {
            return $lastActivity->created_at->gt($this->updated_at) ? $this->lastActivity->created_at : $this->updated_at;
        }
        return $this->updated_at;
    }

    public function getDueDateFilterAttribute()
    {
        $due_date = $this->due_date;
        $nowStartDay = now()->startOfDay();
        switch (true) {
            case (is_null($due_date)):
                return 'no';
                break;
            case ($due_date < $nowStartDay):
                return 'overdue';
                break;
            case ($due_date->isSameDay($nowStartDay)):
                return 'today';
                break;
            case ($due_date->isSameDay($nowStartDay->copy()->addDay(1))):
                return 'tommorow';
                break;
            case ($due_date <= $nowStartDay->copy()->endOfWeek()):
                return 'thisWeek';
                break;
            case ($due_date <= $nowStartDay->copy()->addWeeks(1)->endOfWeek()):
                return 'nextWeek';
                break;
            case ($due_date <= $nowStartDay->copy()->endOfMonth()):
                return 'thisMonth';
                break;
            case ($due_date <= $nowStartDay->copy()->addMonth(1)->endOfMonth()):
                return 'nextMonth';
                break;
        }
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

    public function isUserDraft(User $user)
    {
        return $this->drafted_by == $user->id;
    }

    public function isDraft()
    {
        return ! is_null($this->drafted_by);
    }

    public function isAutored(User $user)
    {
        return $this->author_id == $user->id;
    }

    public function isOpened()
    {
        return is_null($this->deleted_at);
    }

    public function isClosed()
    {
        return ! is_null($this->deleted_at);
    }

    public function getTypes($type = null)
    {
        $types = [
            self::TYPE_PRIVATE => 'Private',
            self::TYPE_PUBLIC => 'Public',
        ];
        return ($type === null) ? collect($types) : $types[$type];
    }

    public function getSubscribeTypeEmailText($id = null)
    {
        $types = [
            self::SUBSCRIBE_TYPE_HAND => 'you subscribed to this task',
            self::SUBSCRIBE_TYPE_AUTHOR => 'you are the author of this task',
            self::SUBSCRIBE_TYPE_EDITOR => 'you are the editor of this task',
            self::SUBSCRIBE_TYPE_ASSIGN => 'you were assigned to this task',
            self::SUBSCRIBE_TYPE_MENTION => 'you were mentioned in this task',
            self::SUBSCRIBE_TYPE_COMMENT_AUTHOR => 'you wrote a comment in this task',
            self::SUBSCRIBE_TYPE_COMMENT_MENTION => 'you were mentioned in a comment of this task',
            self::SUBSCRIBE_TYPE_REVERT_HISTORY => 'you reverted this task to the previous version',
        ];
        return ! ($id === null) ? array_get($types, $id) : '';
    }

    public function getMassActions()
    {
        return collect([
            self::MASS_ACTION_ASSIGN,
            self::MASS_ACTION_UNASSIGN,
            self::MASS_ACTION_OPEN,
            self::MASS_ACTION_CLOSE,
            self::MASS_ACTION_SET_DUE_DATE,
        ]);
    }
    /**End Helper*/


}
