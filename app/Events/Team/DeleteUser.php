<?php

namespace App\Events\Team;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\User;
use App\Team;

class DeleteUser implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $team;

    public $user;
    public $me;

    public function __construct(Team $team, User $user, User $me)
    {
        $this->user = $user;
        $this->team = $team;
        $this->me = $me;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [new PrivateChannel('team.'.$this->team->id), new PrivateChannel('user.'.$this->user->id)];
    }

    public function broadcastWith()
    {
        return [
            'user' => [
                'id' => $this->user->id,
            ],
            'team' => [
                'id' => $this->team->id,
                'slug' => $this->team->slug,
            ],
        ];
    }
}
