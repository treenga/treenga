<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Category;

class CategoryChannel
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
        $categories = $notification->toCategories($notifiable);
        if($categories instanceof Category) {
            $categories->userNotifications()->attach([$notification->id => ['user_id' => $notifiable->id]]);
        } else {
            foreach($categories as $category) {
                $category->userNotifications()->attach([$notification->id => ['user_id' => $notifiable->id]]);
            }
        }
    }
}