<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class TeamChannel
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
        $team = $notification->toTeam($notifiable);
        $team->userNotifications()->attach([$notification->id => ['user_id' => $notifiable->id]]);
    }
}