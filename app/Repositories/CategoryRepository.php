<?php

namespace App\Repositories;

use App\User;
use App\Team;
use App\Category;
use App\History;

class CategoryRepository
{
    public function getItemById(int $id)
    {
        return Category::find($id);
    }

    public function update(Category $category, array $data)
    {
        $category->fill($data);
        $category->save();
        return $category;
    }

    public function delete(Category $category)
    {
        $category->delete();
    }

    public function detachTasks(Category $category)
    {
        $category->tasks()->detach();
    }

    public function createRoot($data)
    {
        $cat = new Category($data);
        $cat->makeRoot()->save();
        return $cat;
    }

    public function createChild(Category $parent, array $data)
    {
        $category = $parent->children()->create($data);
        return $category;
    }

    public function addToTeam(Team $team, Category $category)
    {
        $team->categories()->attach($category->id);
    }

    public function addToUser(User $user, Category $category)
    {
        $user->categories()->attach($category->id);
    }

    //TODO DELETE
    public function getPrivateTreeWithCounts(Team $team, User $user, $withTrashed = false, $onlyTrashed = false)
    {
        $query = $team->categories();
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $categories = $query->privateUser($user)->forTree($user)->get();
        return $categories->toTree();
    }

    public function getPublicTreeWithCounts(Team $team, User $user, $withTrashed = false, $onlyTrashed = false)
    {
        $query = $team->categories();
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $categories = $query->public()->forTree($user)->get();
        return $categories->toTree();
    }

    public function getCurrentTasks(Category $category, Team $team, User $user, $search = null, $withTrashed = false, $onlyTrashed = false)
    {
        $query = $category->tasks()->filter($search);
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forList($team, $user)->get();

        $tasks_count = $baseQuery->withTrashed()->count() - $baseQuery->onlyTrashed()->count();
        $deleted_tasks_count = $baseQuery->onlyTrashed()->count();

        return compact('tasks', 'tasks_count', 'deleted_tasks_count');
    }

    public function getCurrentPrivateTasks(Category $category, User $user, $search = null, $withTrashed = false, $onlyTrashed = false)
    {
        $query = $category->tasks()->filter($search);
        $baseQuery = clone $query;
        $withTrashed ? $query->withTrashed() : false;
        $onlyTrashed ? $query->onlyTrashed() : false;
        $tasks = $query->forPrivateList($user)->get();

        $tasks_count = $onlyTrashed ? $baseQuery->count() : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $baseQuery->onlyTrashed()->count();

        return compact('tasks', 'tasks_count', 'deleted_tasks_count');
    }

    public function getChildTasks(Category $category, Team $team, User $user, array $currentTaskIds, $search = null, $withTrashed = false, $onlyTrashed = false)
    {
        /* if($onlyTrashed) {
            $converseTasks = $category->tasks()->onlyFields(['id', 'drafted_by'])->onlyUserDrafts($user)->get();
        } else {
            $converseTasks = $category->tasks()->onlyTrashed()->onlyFields(['id', 'drafted_by'])->onlyUserDrafts($user)->get();
        }
        $converseTaskIds = $converseTasks->pluck('id');

        $category->load(['descendants' => function($q) use($team, $user, $search, $withTrashed, $onlyTrashed, $currentTaskIds, $converseTaskIds){
            if($onlyTrashed) {
                $q->with(['tasks' => function($q2) use($team, $user, $search, $withTrashed, $onlyTrashed, $currentTaskIds){
                    $q2->onlyTrashed();
                    $q2->whereNotIn('id', $currentTaskIds);
                    $q2->filter($search);
                    $q2->forList($team, $user);
                    $withTrashed ? $q2->withTrashed() : false;
                }]);
                $q->withCount(['tasks' => function($q2) use($converseTaskIds){
                    $q2->whereNotIn('id', $converseTaskIds);
                }]);
            } else {
                $q->with(['tasks' => function($q2) use($team, $user, $search, $withTrashed, $onlyTrashed, $currentTaskIds){
                    $q2->whereNotIn('id', $currentTaskIds);
                    $q2->filter($search);
                    $q2->forList($team, $user);
                    $withTrashed ? $q2->withTrashed() : false;
                }]);
                $q->withCount(['tasks' => function($q2) use($converseTaskIds){
                    $q2->onlyTrashed();
                    $q2->whereNotIn('id', $converseTaskIds);
                }]);
            }
        }]); */

        $category->load(['descendants' => function($q) use($team, $user, $search, $withTrashed, $onlyTrashed, $currentTaskIds){
            $q->with(['tasks' => function($q2) use($team, $user, $search, $withTrashed, $onlyTrashed, $currentTaskIds){
                $q2->filter($search);
                $q2->forList($team, $user);
                $q2->withTrashed();
            }]);
        }]);

        $all_tasks = $category->descendants->pluck('tasks')->flatten()->unique('id')->values();
        $tasks = collect([]);
        $tasks_count = 0;
        $deleted_tasks_count = 0;

        foreach ($all_tasks as $task) {
            if ($task->trashed()) {
                if ($onlyTrashed && ! in_array($task->id, $currentTaskIds)) {
                    $tasks->push($task);
                }
                $deleted_tasks_count++;
            } else {
                if ( ! $onlyTrashed && ! in_array($task->id, $currentTaskIds)) {
                    $tasks->push($task);
                }
                $tasks_count++;
            }
        }
        return compact('tasks', 'tasks_count', 'deleted_tasks_count');
    }

    public function getChildPrivateTasks(Category $category, User $user, array $currentTaskIds, $search = null, $withTrashed = false, $onlyTrashed = false)
    {
        $category->load(['descendants' => function($q) use($user, $search, $withTrashed, $onlyTrashed, $currentTaskIds){
            if($onlyTrashed) {
                $q->with(['tasks' => function($q2) use($user, $search, $withTrashed, $onlyTrashed, $currentTaskIds){
                    $q2->onlyTrashed();
                    $q2->whereNotIn('id', $currentTaskIds);
                    $q2->filter($search);
                    $q2->forPrivateList($user);
                    $withTrashed ? $q2->withTrashed() : false;
                }]);
                $q->withCount(['tasks']);
            } else {
                $q->with(['tasks' => function($q2) use($user, $search, $withTrashed, $onlyTrashed, $currentTaskIds){
                    $q2->whereNotIn('id', $currentTaskIds);
                    $q2->filter($search);
                    $q2->forPrivateList($user);
                    $withTrashed ? $q2->withTrashed() : false;
                }]);
                $q->withCount(['deleted_tasks']);
            }
        }]);
        $tasks = $category->descendants->pluck('tasks')->flatten()->unique('id')->values();

        $tasks_count = $onlyTrashed ?  $category->descendants->sum('tasks_count') : $tasks->count();
        $deleted_tasks_count = $onlyTrashed ? $tasks->count() : $category->descendants->sum('deleted_tasks_count');
        return compact('tasks', 'tasks_count', 'deleted_tasks_count');
    }

    public function loadDetailInfo(Category $category, Team $team)
    {
        $category->load(['text', 'subscribers', 'histories', 'comments' => function($q) use($team){
            $q->orderBy('created_at', 'desc');
        }]);
        return $category;
    }

    public function saveText(Category $category, $body)
    {
        $data = compact('body');
        $category->text ? $category->text()->update($data) : $category->text()->create($data);
        return $category;
    }

    public function loadHistoryInfo(Category $category)
    {
        return $category->load(['text', 'histories' => function($q){
            $q->orderBy('created_at', 'desc');
        }]);
    }

    public function addCategoryHistory(Category $category, User $user)
    {
        $data = [
            'body' => $category->text->body,
            'author_id' => $user->id,
        ];
        $category->histories()->create($data);
    }

    public function setHistory(Category $category, History $history)
    {
        $category->text->update(['body' => $history->body]);
        $category->update(['author_id' => $history->author_id]);
        return $category;
    }

    public function subscribeUser(Category $category, array $userIds)
    {
        return $category->subscribers()->syncWithoutDetaching($userIds);
    }

    public function unsubscribeUser(Category $category, array $userIds)
    {
        $category->subscribers()->detach($userIds);
        return $category;
    }

    public function forceDeleteWithRelations(Category $category)
    {
        $category->users()->detach();
        $category->teams()->detach();
        $category->text()->delete();
        $category->histories()->delete();
        $category->comments()->delete();
        $category->comments()->detach();
        $category->tasks()->detach();
        foreach ($category->userNotifications as $notify) {
            $notify->delete();
        }
        $category->subscribers()->detach();
        foreach($category->children as $item) {
            $this->forceDeleteWithRelations($item);
        }
        $category->forceDelete();
    }


}
