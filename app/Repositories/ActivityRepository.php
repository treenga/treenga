<?php

namespace App\Repositories;

use App\Task;
use App\Activity;

class ActivityRepository
{
    public function createTaskItem(Task $task, array $data)
    {
        return $task->activities()->create($data);
    }

    public function createUnreadActivities(Activity $activity, $subscribers)
    {
        $activity->unreadUsers()->attach($subscribers);
    }
}
