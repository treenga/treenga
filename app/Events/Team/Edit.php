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
use App\Http\Resources\Team\BroadcastEdit as TeamBroadcastEditResourse;

class Edit implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $team;
    public $user;

    public function __construct(Team $team, User $user)
    {
        $this->team = $team;
        $this->user = $user;
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
        $userId = $this->user->id;
        $team = $this->team->load(['users' => function($q) use ($userId){
            $q->where('user_id', $userId);
        }]);

        return ['team' => new TeamBroadcastEditResourse($team)];
    }
}
