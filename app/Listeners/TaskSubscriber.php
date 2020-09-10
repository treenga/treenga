<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use App\Notifications\Task\Created as TaskCreatedNotify;
use App\Notifications\Task\Edited as TaskEditedNotify;
use App\Notifications\Task\Commented as TaskCommentedNotify;
use App\Notifications\Task\Deleted as TaskDeletedNotify;
use App\Notifications\Task\Restored as TaskRestoredNotify;
use App\Notifications\Task\Email\Assigned as TaskEmailAssignedNotify;
use App\Notifications\Task\Email\Unassigned as TaskEmailUnassignedNotify;
use App\Notifications\Task\Email\Edited as TaskEmailEditedNotify;
use App\Notifications\Task\Email\Commented as TaskEmailCommentedNotify;
use App\Notifications\Task\Email\Reverted as TaskEmailRevertedNotify;
use App\Notifications\Task\Email\Subscribed as TaskEmailSubscribedNotify;
use App\Notifications\Task\Email\Closed as TaskEmailClosedNotify;
use App\Notifications\Task\Email\Reopened as TaskEmailReopenedNotify;
use App\Repositories\UserRepository;
use App\Repositories\TaskRepository;
use App\User;
use App\Team;
use App\Task;
use App\Events\Task\NewSubscriber as TaskNewSubscriberEvent;
use Illuminate\Support\Facades\Log;

class TaskSubscriber implements ShouldQueue
{
    public $userRepository;
    public $taskRepository;

    public function __construct(UserRepository $userRepository, TaskRepository $taskRepository)
    {
        $this->userRepository = $userRepository;
        $this->taskRepository = $taskRepository;
    }

    public function onCreated($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        $subscribe = $event->subscribe;
        if ($task->isPublic()) {
            foreach($subscribe as $type => $item) {
                $this->taskRepository->addUnsubscribeHashBySyncUsers($task, $item);
            }
            $resipients = $task->subscribers->except(['id' => $me->id]);
            $resipientsUsers = $task->users->except(['id' => $me->id]);
            Notification::send($resipients, new TaskCreatedNotify($task, $team));
            Notification::send($resipientsUsers, new TaskEmailAssignedNotify($task, $team, $me));
        }
    }

    public function onEdited($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        $sync = $event->sync;
        $subscribe = $event->subscribe;
        $changesText = $event->changesText;
        if ($task->isPublic()) {
            foreach($subscribe as $type => $item) {
                $this->taskRepository->addUnsubscribeHashBySyncUsers($task, $item);
            }
            $resipients = $task->subscribers->except(['id' => $me->id]);
            Notification::send($resipients, new TaskEditedNotify($task, $team));
            //Send email about assign
            $newUsersIds = $sync['users']['attached'];
            $newUsersIds = array_diff($newUsersIds, [$me->id]);
            if (count($newUsersIds)){
                Notification::send(User::find($newUsersIds), new TaskEmailAssignedNotify($task, $team, $me));
            }
            $unassignedUsersIds = $sync['users']['detached'];
            $unassignedUsersIds = array_diff($unassignedUsersIds, [$me->id]);
            if (count($unassignedUsersIds)){
                Notification::send(User::find($unassignedUsersIds), new TaskEmailUnassignedNotify($task, $team, $me));
            }
            if ($changesText) {
                $newUsersIds = $sync['users']['attached'];
                $emailResipients = $task->subscribers()->whereNotIn('id', array_merge($newUsersIds, [$me->id]))->whereDoesntHave('notifications', function($q) use($task){
                    $q->where('data->task_id', $task->id);
                })->get();
                Notification::send($emailResipients, new TaskEmailEditedNotify($task, $team, $me));
                $resipients = $task->subscribers->except(['id' => $me->id]);
                Notification::send($resipients, new TaskEditedNotify($task, $team));
            }
        }
    }

    public function onViewed($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;

        if ($me->lastViewed()->where('task_id', $task->id)->withTrashed()->count() > 0) {
            $me->lastViewed()->detach($task->id);
        }
        $me->lastViewed()->attach($task->id);
        $me->lastViewed()->detach($me->lastViewed()->where('team_id', $team->id)->skip(100)->get()->pluck('id')->toArray());

        $notifications = $this->userRepository->getNotificationByTask($me, $task);
        $notifications->each(function($item) {
            $item->delete();
        });
        //mark comments as read
        $task->load(['comments']);
        $taskCommentIds = $task->comments->pluck('id')->toArray();
        $me->readComments()->syncWithoutDetaching($taskCommentIds);
    }

    public function onCommented($event)
    {
        $task = $event->task;
        $team = $event->team;
        $comment =$event->comment;
        $me = $event->me;
        $subscribe = $event->subscribe;
        if ($task->isPublic()) {
            foreach($subscribe as $type => $item) {
                $this->taskRepository->addUnsubscribeHashBySyncUsers($task, $item);
            }
            $resipients = $task->subscribers->except(['id' => $me->id]);
            Notification::send($resipients, new TaskCommentedNotify($comment, $task, $team));
            Notification::send($resipients, new TaskEmailCommentedNotify($task, $team, $me));
            //send broadcats in task about new subscriber
            $newSubscribers = array_merge($subscribe['subscribers']['attached'], $subscribe['author']['attached']);
            $this->sendBroadcatsToEditParticipants($team, $task, $newSubscribers);
        }
    }

    private function sendBroadcatsToEditParticipants(Team $team, Task $task, array $newSubscribers)
    {
        if(count($newSubscribers)) {
            $finds = $team->users()->find($newSubscribers);
            if(count($finds)) {
                foreach ($finds as $user) {
                    event(new TaskNewSubscriberEvent($task, $team, $user));
                }
            } else {
                Log::warning(var_export($task, true));
                Log::warning(var_export($team, true));
                Log::warning(var_export($finds, true));
            }
        }
    }

    public function onDeleted($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        if ($task->isPublic()) {
            $resipients = $task->subscribers->except(['id' => $me->id]);
            Notification::send($resipients, new TaskDeletedNotify($task));
        }
    }

    public function onRestored($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        if ($task->isPublic()) {
            $resipients = $task->subscribers->except(['id' => $me->id]);
            Notification::send($resipients, new TaskRestoredNotify($task));
            Notification::send($resipients, new TaskEmailReopenedNotify($task, $team, $me));
        }
    }

    public function onClosed($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        if ($task->isPublic()) {
            $resipients = $task->subscribers->except(['id' => $me->id]);
            Notification::send($resipients, new TaskEmailClosedNotify($task, $team, $me));
        }
    }

    public function onNewSubscriber($event)
    {
        $event->user->notify(new TaskEmailSubscribedNotify($event->task, $event->team, $event->me));
    }

    public function onRevertedHistory($event)
    {
        $task = $event->task;
        $team = $event->team;
        $me = $event->me;
        $subscribe = $event->subscribe;
        if ($task->isPublic()) {
            //send broadcats in task about new subscriber
            $newSubscribers = array_merge($subscribe['author']['attached']);
            $resipients = $task->subscribers->except(['id' => $me->id]);
            $this->sendBroadcatsToEditParticipants($team, $task, $newSubscribers);
            Notification::send($resipients, new TaskEmailRevertedNotify($task, $team, $me));
        }
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
            'App\Listeners\TaskSubscriber@onCreated'
        );
        $events->listen(
            'App\Events\Task\Edited',
            'App\Listeners\TaskSubscriber@onEdited'
        );
        $events->listen(
            'App\Events\Task\Commented',
            'App\Listeners\TaskSubscriber@onCommented'
        );
        $events->listen(
            'App\Events\Task\Viewed',
            'App\Listeners\TaskSubscriber@onViewed'
        );
        $events->listen(
            'App\Events\Task\Deleted',
            'App\Listeners\TaskSubscriber@onDeleted'
        );
        $events->listen(
            'App\Events\Task\Restored',
            'App\Listeners\TaskSubscriber@onRestored'
        );
        $events->listen(
            'App\Events\Task\NewSubscriber',
            'App\Listeners\TaskSubscriber@onNewSubscriber'
        );
        $events->listen(
            'App\Events\Task\Closed',
            'App\Listeners\TaskSubscriber@onClosed'
        );
        $events->listen(
            'App\Events\Task\RevertedHistory',
            'App\Listeners\TaskSubscriber@onRevertedHistory'
        );
    }
}
