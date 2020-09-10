<?php

namespace App\Notifications\Category;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Channels\CategoryChannel;
use App\Category;
use App\Team;
use App\User;
use App\Http\Resources\Category\Short as CategoryShortResourse;

class Subscribed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $category;
    public $team;
    public $user;

    public function __construct(Team $team, Category $category, User $user)
    {
        $this->team = $team;
        $this->category = $category;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast', CategoryChannel::class, 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $username = $this->user->teams->find($this->team->id)->pivot->username;
        return (new MailMessage)
        ->subject($username.' subscribed you to '.$this->category->name)
        ->from(config('mail.from.address'), config('app.name').': '.$this->team->name)
        ->markdown('mail.category.subscribed', ['category' => $this->category, 'username' => $username, 'team' => $this->team, 'link' => app()->make('url')->to('/team/'.$this->team->id.'/category/'.$this->category->id)]);
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
            'category_id' => $this->category->id,
        ];
    }

    public function toCategories($notifiable)
    {
        return $this->category;
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'data' => new CategoryShortResourse($this->category),
        ]);
    }
}
