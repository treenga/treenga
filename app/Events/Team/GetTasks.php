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

class GetTasks
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $team;
    public $user;
    public $point;
    public $onlyTrashed;
    public $id;

    public function __construct(Team $team, User $user, string $point, $onlyTrashed, int $id = 0)
    {
        $this->team = $team;
        $this->user = $user;
        $this->point = $point;
        $this->onlyTrashed = $onlyTrashed;
        $this->id = $id;
    }

}
