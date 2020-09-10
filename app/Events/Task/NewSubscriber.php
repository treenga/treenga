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
use App\User;
use App\Team;

class NewSubscriber implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $task;
    public $team;
    public $user;
    public $username;

    public function __construct(Task $task, Team $team, User $user)
    {
        $this->task = $task;
        $this->team = $team;
        $this->user = $user;
        $this->username = $user->team_username;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('task.'.$this->task->id);
    }

    public function broadcastWith()
    {
        return ['user' => [
            'id' => $this->user->id,
            'username' => $this->username,
        ]];
    }
}
