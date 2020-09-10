<?php

namespace App\Notifications\Task;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Task;
use App\Comment;
use App\Team;
use App\Channels\CategoryChannel;
use App\Channels\TaskChannel;
use App\Channels\TeamChannel;
use App\Http\Resources\Task\Broadcast as TaskBroadcastResourse;

class Commented extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $comment;
    public $task;
    public $team;

    public function __construct(Comment $comment, Task $task, Team $team)
    {
        $this->comment = $comment;
        $this->task = $task;
        $this->team = $team;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast', CategoryChannel::class, TaskChannel::class, TeamChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    // public function toMail($notifiable)
    // {
    //     // return (new MailMessage)
    //     //             ->line('The introduction to the notification.')
    //     //             ->action('Notification Action', url('/'))
    //     //             ->line('Thank you for using our application!');
    // }

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
            'comment_id' => $this->comment->id,
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

    public function toTeam($notifiable)
    {
        return $this->team;
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'task' => new TaskBroadcastResourse($this->task),
        ]);
    }
}
