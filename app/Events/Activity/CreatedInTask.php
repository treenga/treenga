<?php

namespace App\Events\Activity;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Task;
use App\Activity;
use App\Http\Resources\Activity\Short as ActivityShortResourse;

class CreatedInTask implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $task;
    public $activity;

    public function __construct(Task $task,  Activity $activity)
    {
        $this->task = $task;
        $this->activity = $activity;
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
        return ['data' => new ActivityShortResourse($this->activity)];
    }
}
