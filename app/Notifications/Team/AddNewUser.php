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

class AddNewUser extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $team;
    public $user;
    public $link;
    public $me;

    public function __construct(Team $team, User $user, string $link, User $me)
    {
        $this->team = $team;
        $this->user = $user;
        $this->link = $link;
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
        return (new MailMessage)
            ->subject($username.' invited you to '.config('app.name'))
            ->markdown('mail.team.add-new-user', [
                'team' => $this->team,
                'username' => $username,
                'link' => $this->link,
            ]);
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
