<?php

namespace App\Notifications\Task\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Task;
use App\Team;
use App\Repositories\TaskRepository;
use App\User;

class Reverted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $task;
    public $team;
    public $user;
    private $taskRepository;

    public function __construct(Task $task, Team $team, User $user)
    {
        $this->task = $task;
        $this->team = $team;
        $this->user = $user;
        $this->taskRepository = new TaskRepository();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $this->task = $this->taskRepository->loadMailInfo($this->task, $this->team, $notifiable);
        $username = $this->team->users->find($this->user->id)->pivot->username;

        return (new MailMessage)
            ->from(config('mail.from.address'), config('app.name') . ': ' . $this->team->name)
            ->subject($username . ' reverted ' . $this->task->name)
            ->markdown('mail.task.email.reverted', ['task' => $this->task, 'username' => $username]);
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
            //
        ];
    }
}
