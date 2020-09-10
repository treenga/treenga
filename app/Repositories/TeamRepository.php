<?php

namespace App\Repositories;

use App\User;
use App\Team;

class TeamRepository
{
    protected $categoryRepository;
    protected $taskRepository;

    public function __construct(
        CategoryRepository $categoryRepository,
        TaskRepository $taskRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->taskRepository = $taskRepository;
    }

    public function itemCreate(array $data)
    {
        $team = new Team();
        $team->fill($data);
        $team->save();

        return $team;
    }

    public function itemUpdate(Team $team, array $data)
    {
        $team->fill($data);
        $team->save();
        return $team;
    }

    public function getItemBySlug(string $slug = '')
    {
        return Team::where('slug', strtolower($slug))->first();
    }

    public function getPrivateTeam(User $user)
    {
        return $user->teams()->where('private', true)->first();
    }

    public function deleteTeamWithAllRelations(Team $team)
    {
        $team->users()->detach();
        $team->load(['categories' => function($q){
            $q->withTrashed();
        }, 'tasks'=> function($q){
            $q->withTrashed();
        }, 'files'=> function($q){
            $q->withTrashed();
        }]);

        foreach($team->categories as $category) {
            $this->categoryRepository->forceDeleteWithRelations($category);
        }
        $team->categories()->detach();
        foreach($team->tasks as $task) {
            $this->taskRepository->forceDeleteWithRelations($task);
        }
        foreach($team->files as $file) {
            $file->forceDelete();
        }
        $team->userNotifications()->delete();
        $team->forceDelete();
    }

    public function addUser(Team $team, User $user, array $pivotData)
    {
        $team->users()->attach([ $user->id => $pivotData ]);

        return $team;
    }

    public function detachUser(Team $team, User $user)
    {
        $team->users()->detach($user->id);

        return $team;
    }

    public function updateUserPivot(Team $team, User $user, array $pivotData)
    {
        $team->users()->updateExistingPivot($user->id, $pivotData);

        return $team;
    }

    public function deletePrivateTasks(Team $team, User $user)
    {
        $team->tasks()->private()->author($user)->delete();
    }

    public function detachUserFromTasks(Team $team, User $user)
    {
        $team->loadMissing('tasks');
        $team->tasks->each(function($value) use($user){
            $value->users()->detach($user->id);
        });
        return $team;
    }

    public function deletePrivateCategories(Team $team, User $user)
    {
        $categories = $team->categories()->privateUser($user)->get();
        $categories->each(function($value){
            $value->delete();
        });
        return $team;
    }

    public function getUsersWithCountTasks(Team $team, User $user)
    {
        $team->load(['users' => function($q) use($user, $team){
            $q->orderBy('name', 'asc');
            $q->withCount(['tasks' => function($q) use($user, $team){
                $q->team($team);
                $q->public();
                $q->onlyUserDrafts($user);
            }]);
        }]);
        return $this->moveToTop($team->users, $user);
    }

    public function getAuthorUsersWithCountTasks(Team $team, User $user)
    {
        $team->load(['users' => function($q) use($team, $user){
            $q->orderBy('name', 'asc');
            $q->withCount(['authoredTasks' => function($q) use($user, $team){
                $q->team($team);
                $q->public();
                $q->onlyUserDrafts($user);
            }]);
        }]);
        return $this->moveToTop($team->users, $user);
    }

    public function loadCountsTasks(Team $team, User $user)
    {
        $countPublicNotifications = $team->tasks()->withTrashed()->whereHas('userNotifications', function($q) use($user) {
            $q->where('user_id', $user->id);
            $q->where('read_at', null);
        })->count();

        $counts['countPublicDrafts'] = $team->tasks()->withTrashed()->userDrafts($user)->count();
        $counts['countPublicNotifications'] = $countPublicNotifications;
        $counts['countUnassignedTasks'] = $team->tasks()->doesntHave('users')->onlyUserDrafts($user)->count();
        $counts['countUnsortedPublicTasks'] = $team->tasks()->noDraft()->noCats()->count();
        return (object) $counts;
    }

    public function loadTeamShotInfo(Team $team, User $user, $withTrashed = false, $onlyTrashed = false)
    {
        return $team->load([
            'privateCategories' => function($q) use($user){
                $q->with('descendants');
                $q->privateUser($user);
            },
            'publicCategories' => function($q) use($user){
                $q->with('descendants');
            },
            'users' => function($q) use($user, $team){
                $q->orderBy('name', 'asc');
            }
        ]);
    }

    public function getDueDates($team)
    {
        $dueDates = $team->tasks()->selectRaw("id, due_date, (DATE_PART('day', due_date - now()) * 24 + DATE_PART('hour', due_date - now())) as diff_hours")->get();
        return $dueDates;
    }

    private function moveToTop($items, $item)
    {
        $items->each(function($value) use($item){
            $value->current = $item->id == $value->id ? 0 : 1;
        });
        return $items->sortBy('current');
    }
}
