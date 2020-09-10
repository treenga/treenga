<?php

namespace App\Listeners;
use App\Services\ActivityService;
use App\Repositories\ActivityRepository;

class ActivitySubscriber
{
    public $activityService;
    public $activityRepository;

    public function __construct(ActivityService $activityService, ActivityRepository $activityRepository)
    {
        $this->activityService = $activityService;
        $this->activityRepository = $activityRepository;
    }

    public function onTaskCreated($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        $subscribe = $event->subscribe;
        $this->activityService->taskCreated($me, $team, $task);
    }

    public function onTaskEdited($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        $sync = $event->sync;
        $subscribe = $event->subscribe;
        $original = $event->original;
        $changesText = $event->changesText;
        $this->activityService->taskEdited($me, $team, $task, $sync, $original, $changesText);
    }

    public function onDeleted($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        $this->activityService->taskArchived($me, $team, $task);
    }

    public function onRestored($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        $this->activityService->taskRestored($me, $team, $task);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\Task\Created',
            'App\Listeners\ActivitySubscriber@onTaskCreated'
        );
        $events->listen(
            'App\Events\Task\Edited',
            'App\Listeners\ActivitySubscriber@onTaskEdited'
        );
        $events->listen(
            'App\Events\Task\Deleted',
            'App\Listeners\ActivitySubscriber@onDeleted'
        );
        $events->listen(
            'App\Events\Task\Restored',
            'App\Listeners\ActivitySubscriber@onRestored'
        );
    }
}
