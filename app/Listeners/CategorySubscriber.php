<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Category\Subscribed as CategorySubscribedNotify;
use App\Notifications\Category\EditedDesc as CategoryEditedDescNotify;
use App\Notifications\Category\Commented as CategoryCommentedNotify;
use App\Notifications\Category\Reverted as CategoryRevertedNotify;
use App\Repositories\UserRepository;
use App\User;


class CategorySubscriber
{
    public $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function onDescCreated($event)
    {
        $team = $event->team;
        $category = $event->category;
        $me = $event->me;
        if ($category->isPublic()) {
            $resipients = $category->subscribers->except(['id' => $me->id]);
            Notification::send($resipients, new CategorySubscribedNotify($team, $category, $me));
        }
    }

    public function onDescEdited($event)
    {
        $team = $event->team;
        $category = $event->category;
        $me = $event->me;
        $newSubscribersIds = $event->newSubscribersIds;
        if ($category->isPublic()) {
            if(count($newSubscribersIds)) {
                Notification::send(User::find($newSubscribersIds) , new CategorySubscribedNotify($team, $category, $me));
            }
            $resipients = $category->subscribers->except(['id' => $me->id])->except($newSubscribersIds);
            Notification::send($resipients, new CategoryEditedDescNotify($team, $category, $me));
        }
    }

    public function onCommented($event)
    {
        $team = $event->team;
        $category = $event->category;
        $me = $event->me;
        if ($category->isPublic()) {
            $resipients = $category->subscribers->except(['id' => $me->id]);
            Notification::send($resipients, new CategoryCommentedNotify($team, $category, $me));
        }
    }

    public function onViewed($event)
    {
        $team = $event->team;
        $category = $event->category;
        $me = $event->me;
        if ($category->isPublic()) {
            $notifications = $this->userRepository->getNotificationByCategory($me, $category);
            $notifications->each(function($item) {
                $item->delete();
            });
        }
    }

    public function onReverted($event)
    {
        $team = $event->team;
        $category = $event->category;
        $me = $event->me;
        if ($category->isPublic()) {
            $resipients = $category->subscribers->except(['id' => $me->id]);
            Notification::send($resipients, new CategoryRevertedNotify($team, $category, $me));
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
            'App\Events\Category\DescCreated',
            'App\Listeners\CategorySubscriber@onDescCreated'
        );
        $events->listen(
            'App\Events\Category\DescEdited',
            'App\Listeners\CategorySubscriber@onDescEdited'
        );
        $events->listen(
            'App\Events\Category\Commented',
            'App\Listeners\CategorySubscriber@onCommented'
        );
        $events->listen(
            'App\Events\Category\Viewed',
            'App\Listeners\CategorySubscriber@onViewed'
        );
        $events->listen(
            'App\Events\Category\Reverted',
            'App\Listeners\CategorySubscriber@onReverted'
        );
    }
}
