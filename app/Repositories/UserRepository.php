<?php

namespace App\Repositories;

use App\User;
use App\Team;
use App\Task;
use App\Category;

class UserRepository
{
    protected $mainModel;

    public function __construct()
    {
        $this->mainModel = new User;
    }

    public function getItemByEmail(string $email)
    {
        return $this->mainModel->where('email', strtolower($email))->first();
    }

    public function getItemByHash(string $hash, string $type)
    {
        return $this->mainModel->whereHas('hashes', function($q) use($hash, $type) {
            $q->where('hash', $hash);
            $q->where('type', $type);
        })->first();
    }

    public function getItemByKey(string $key)
    {
        return User::whereHas('key', function($q) use ($key) {
            $q->where('key', $key);
        })->first();
    }

    public function itemCreate(array $data)
    {
        $item = $this->mainModel->fill($data);
        $item->save();
        return $item;
    }

    public function itemUpdate(User $user, array $data)
    {
        $user->fill($data);
        $user->save();
        return $user;
    }

    public function addRandomHash(User $user, string $type, $expired_at = null)
    {
        $hash = $user->hashes()->firstOrNew([
            'type' => $type,
        ]);

        if ($hash->exists) {
            $user->hashes()->detach($hash->id);
        }

        $hash->hash = md5(str_random(10));
        $hash->expired_at = $expired_at;
        $user->hashes()->save($hash);

        return $hash->hash;
    }

    public function deleteUserHash(User $user, string $type)
    {
        $user->hashes()->where('type', $type)->get()->each(function($value){
            $value->delete();
        });
    }

    public function setCurrentTeam(User $user, Team $team)
    {
        $old = $user->teams()->wherePivot('current', true)->first();

        if ($old) {
            $old->users()->updateExistingPivot($user->id, ['current' => false]);
        }
        $user->teams()->updateExistingPivot($team->id, ['current' => true]);

        return $user;
    }

    public function getUsersNotInTeam(Team $team)
    {
        return User::whereDoesntHave('teams', function($q) use($team) {
            $q->where('team_id', $team->id);
        })->get();
    }

    public function getUsersForAutocomplite(Team $team, User $user)
    {
        return User::where(function($q) use ($user, $team) {
                $q->where('id', '<>', $user->id);
                $q->whereDoesntHave('teams', function($q2) use($team) {
                    $q2->where('team_id', $team->id);
                });
            })
            ->get()
            ->keyBy('id');
    }

    public function addFile(User $user, array $data)
    {
        $user->files()->create($data);
    }

    public function loadTeamsWithNotify(User $user)
    {
        $user->load(['teams' => function($q) use($user) {
            $q->withUserNotify($user);
        }]);
        $userTeamsIds = $user->teams->pluck('id')->toArray();
        $merged['userteams'] = $user->teams->where('pivot.is_owner', true);
        $merged['shared'] = $user->teams->where('pivot.is_owner', false);
        return $merged;
    }

    public function getNotificationByTask(User $user, Task $task)
    {
        $notifications = $user->notifications()->where('data->task_id', $task->id)->get();
        return $notifications;
    }

    public function getNotificationByCategory(User $user, Category $category)
    {
        $notifications = $user->notifications()->where('data->category_id', $category->id)->get();
        return $notifications;
    }

    public function getReadTaskComments(User $user, Task $task)
    {
        return $user->readComments()->whereHas('tasks', function($q) use($task){
            $q->where('comentables_id', $task->id);
            $q->withTrashed();
        })->get();
    }

    public function updateUsername(User $user, string $username):void
    {
        $user->activities()->update(['username' => $username]);
        $user->comments()->update(['username' => $username]);
    }

    public function fullDeleteAccount(User $user)
    {
        foreach($user->hashes as $item) {
            $item->delete();
        }
        $user->teams()->detach();
        foreach($user->notifications as $item) {
            $item->delete();
        }
        $user->assignedTasks()->detach();
        $user->readComments()->detach();
        $user->taskOptions()->detach();
        $user->delete();
    }
}
