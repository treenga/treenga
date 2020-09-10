<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class TaskChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $task = $notification->toTask($notifiable);
        $task->userNotifications()->attach([$notification->id => ['user_id' => $notifiable->id]]);
    }
}