<?php

namespace App\Notifications\Team;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Team;
use App\User;
use App\Http\Resources\Team\Short as TeamShortResourse;

class DeleteUser extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $team;
    public $user;
    public $me;

    public function __construct(Team $team, User $user, User $me)
    {
        $this->team = $team;
        $this->user = $user;
        $this->me = $me;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $username = $this->me->teams->find($this->team->id)->pivot->username;
        return (new MailMessage)->subject($username.' removed you from '.$this->team->name)->from(config('mail.from.address'), config('app.name').': '.$this->team->name)->markdown('mail.team.remove-user', ['team' => $this->team, 'username' => $username]);
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

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'team' => new TeamShortResourse($this->team),
        ]);
    }
}
