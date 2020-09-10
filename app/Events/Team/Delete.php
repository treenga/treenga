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
use App\Http\Resources\User\BroadcastAdd as UserBroadcastAddResourse;

class Delete
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $team;
    public $me;

    public function __construct(Team $team, User $me)
    {
        $this->team = $team;
        $this->me = $me;
    }

}
