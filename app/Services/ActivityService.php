<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Repositories\ActivityRepository;
use App\User;
use App\Team;
use App\Task;
use App\Category;
use App\Events\Activity\CreatedInTask as ActivityCreatedInTaskEvent;

class ActivityService extends CoreService
{
    protected $activityRepository;

    public function __construct(ActivityRepository $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    public function taskCreated(User $user, Team $team, Task $task)
    {
        $task = $team->tasks()->withAuthorUsername($team)->find($task->id);
        $username = $task->author->team_username;
        $data = [
            'text' => 'created task',
            'author_id' => $user->id,
            'username' => $username,
        ];
        $activity = $this->activityRepository->createTaskItem($task, $data);

        event(new ActivityCreatedInTaskEvent($task, $activity));

        return $activity;
    }

    public function taskEdited(User $user, Team $team, Task $task, array $sync, array $original, bool $changesText)
    {
        $task = $team->tasks()->withTrashed()->withAuthorUsername($team)->with(['users' => function($q) use($team){
            $q->withUsername($team);
        }, 'categories'])->find($task->id);
        $username = $team->users->find($user->id)->pivot->username;
        $text = '';
        $author_id = $user->id;
        
        //changes in text
        $changesTitle = array_get($original, 'name') !== $task['name'];
        if ($changesText || $changesTitle) {
            if ($changesText && $changesTitle) {
                $text .= 'changed text and title';
            } elseif ($changesText) {
                $text .= 'changed text';
            } elseif ($changesTitle) {
                $text .= 'changed title';
            }

            $activity = $this->activityRepository->createTaskItem($task, compact('text', 'author_id', 'username'));
            $this->activityRepository->createUnreadActivities($activity, $task->subscribers()->where('user_id', '<>', $author_id)->get()->pluck('id')->toArray());

            event(new ActivityCreatedInTaskEvent($task, $activity));
        }
        
        // changes in users
        $text = '';
        $users = array_get($sync, 'users');
        if ($users) {
            if (count($users['attached'])) {
                $text .= 'assigned ';
                foreach ($users['attached'] as $userId) {
                    $user = $task->users->firstWhere('id', $userId);
                    $text .= $user->team_username;
                    end($users['attached']) != $userId ? $text .= ', ' : false;
                }
                $activity = $this->activityRepository->createTaskItem($task, compact('text', 'author_id', 'username'));
                $this->activityRepository->createUnreadActivities($activity, $task->subscribers()->where('user_id', '<>', $author_id)->get()->pluck('id')->toArray());

                event(new ActivityCreatedInTaskEvent($task, $activity));
                count($users['detached']) ? $text .= ' ' : false;
            }
            if (count($users['detached'])) {
                $text .= 'unassigned ';
                $detachedUsers = User::withUsername($team)->find($users['detached']);
                foreach ($users['detached'] as $userId) {
                    $user = $detachedUsers->firstWhere('id', $userId);
                    $text .= $user->team_username;
                    end($users['detached']) != $userId ? $text .= ', ' : false;
                }
                $activity = $this->activityRepository->createTaskItem($task, compact('text', 'author_id', 'username'));
                $this->activityRepository->createUnreadActivities($activity, $task->subscribers()->where('user_id', '<>', $author_id)->get()->pluck('id')->toArray());

                event(new ActivityCreatedInTaskEvent($task, $activity));
            }
        }

        // changes in categories
        $text = '';
        $categories = array_get($sync, 'categories');
        if ($categories) {
            if (count($categories['attached'])) {
                $text .= 'added category ';
                foreach ($categories['attached'] as $catId) {
                    $cat = $task->categories->firstWhere('id', $catId);
                    $text .= $cat->name;
                    end($categories['attached']) != $catId ? $text .= ', ' : false;
                }
                $activity = $this->activityRepository->createTaskItem($task, compact('text', 'author_id', 'username'));
                $this->activityRepository->createUnreadActivities($activity, $task->subscribers()->where('user_id', '<>', $author_id)->get()->pluck('id')->toArray());

                event(new ActivityCreatedInTaskEvent($task, $activity));
            }
            count($categories['detached']) ? $text .= ' ' : false;
            if (count($categories['detached'])) {
                $text .= 'removed category ';
                $detachedCategories = Category::find($categories['detached']);
                foreach ($categories['detached'] as $catId) {
                    $cat = $detachedCategories->firstWhere('id', $catId);
                    $text .= $cat->name;
                    end($categories['detached']) != $catId ? $text .= ', ' : false;
                }
                $activity = $this->activityRepository->createTaskItem($task, compact('text', 'author_id', 'username'));
                $this->activityRepository->createUnreadActivities($activity, $task->subscribers()->where('user_id', '<>', $author_id)->get()->pluck('id')->toArray());

                event(new ActivityCreatedInTaskEvent($task, $activity));
            }
        }
    }

    public function taskArchived(User $user, Team $team, Task $task)
    {
        $task = $team->tasks()->withTrashed()->withAuthorUsername($team)->find($task->id);
        $username = $team->users->find($user->id)->pivot->username;
        $data = [
            'text' => 'closed task',
            'author_id' => $user->id,
            'username' => $username,
        ];
        $activity = $this->activityRepository->createTaskItem($task, $data);

        event(new ActivityCreatedInTaskEvent($task, $activity));

        return $activity;
    }

    public function taskRestored(User $user, Team $team, Task $task)
    {
        $task = $team->tasks()->withTrashed()->withAuthorUsername($team)->find($task->id);
        $username = $team->users->find($user->id)->pivot->username;
        $data = [
            'text' => 'reopened task',
            'author_id' => $user->id,
            'username' => $username,
        ];
        $activity = $this->activityRepository->createTaskItem($task, $data);

        event(new ActivityCreatedInTaskEvent($task, $activity));

        return $activity;
    }
}
