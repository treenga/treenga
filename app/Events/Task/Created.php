<?php

namespace App\Events\Task;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Task;
use App\Team;
use App\User;
use App\Http\Resources\Task\Broadcast as TaskBroadcastResourse;

class Created implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $task;
    public $team;
    public $me;
    public $subscribe;

    public function __construct(Task $task, Team $team, User $me, array $subscribe)
    {
        $this->task = $task;
        $this->team = $team;
        $this->me = $me;
        $this->subscribe = $subscribe;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('team.'.$this->team->id);
    }

    public function broadcastWhen()
    {
        return $this->task->isPublic();
    }

    public function broadcastWith()
    {
        return ['task' => new TaskBroadcastResourse($this->task)];
    }
}
