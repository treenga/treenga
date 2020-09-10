<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Team\AddUser as TeamAddUserNotification;
use App\Notifications\Team\AddNewUser as TeamAddNewUserNotification;
use App\Notifications\Team\Delete as TeamDeleteNotification;
use App\Notifications\Team\DeleteUser as TeamDeleteUserNotification;
use App\Repositories\TeamRepository;

class TeamSubscriber
{
    protected $teamRepository;

    public function __construct(TeamRepository $teamRepository)
    {
        $this->teamRepository = $teamRepository;
    }

    public function onAddUser($event)
    {
        $event->user->notify((new TeamAddUserNotification($event->team, $event->user, $event->me)));
    }
    public function onAddNewUser($event)
    {
        $event->user->notify((new TeamAddNewUserNotification($event->team, $event->user, $event->link, $event->me)));
    }
    public function onDelete($event)
    {
        $team = $event->team;
        $me = $event->me;
        $resipients = $team->users->except(['id' => $me->id]);
        Notification::send($resipients, new TeamDeleteNotification($team->toArray()));
    }
    public function onDeleteUser($event)
    {
        $team = $event->team;
        $user = $event->user;
        $me = $event->me;
        $resipients = $team->users->except(['id' => $me->id]);
        $event->user->notify((new TeamDeleteUserNotification($event->team, $event->user, $event->me)));
    }

    public function onGetTasks($event)
    {
        $currentStateData = [
            'current_state' => json_encode([
                'point' => $event->point,
                'curentTasksStatus' => $event->onlyTrashed ? 'closed' : 'opened',
                'id' => $event->id,
            ]),
        ];

        $this->teamRepository->updateUserPivot($event->team, $event->user, $currentStateData);
    }



    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\Team\AddUser',
            'App\Listeners\TeamSubscriber@onAddUser'
        );
        $events->listen(
            'App\Events\Team\AddNewUser',
            'App\Listeners\TeamSubscriber@onAddNewUser'
        );
        $events->listen(
            'App\Events\Team\Delete',
            'App\Listeners\TeamSubscriber@onDelete'
        );
        $events->listen(
            'App\Events\Team\DeleteUser',
            'App\Listeners\TeamSubscriber@onDeleteUser'
        );
        $events->listen(
            'App\Events\Team\GetTasks',
            'App\Listeners\TeamSubscriber@onGetTasks'
        );
    }
}
