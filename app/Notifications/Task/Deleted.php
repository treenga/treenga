<?php

namespace App\Notifications\Task;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Task;
use App\Channels\CategoryChannel;
use App\Channels\TaskChannel;
use App\Http\Resources\Task\Short as TaskShortResourse;

class Deleted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
        ];
    }

    public function toCategories($notifiable)
    {
        return $this->task->categories;
    }

    public function toTask($notifiable)
    {
        return $this->task;
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'task' => new TaskShortResourse($this->task),
        ]);
    }
}
