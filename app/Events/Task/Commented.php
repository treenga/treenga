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
use App\Comment;
use App\Http\Resources\Comment\ListItem as CommentListItemResourse;

class Commented implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $comment;
    public $team;
    public $task;
    public $me;
    public $subscribe;

    public function __construct(Comment $comment, Team $team, Task $task, User $me, array $subscribe)
    {
        $this->comment = $comment;
        $this->team = $team;
        $this->task = $task;
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
        return new PrivateChannel('task.'.$this->task->id);
    }

    public function broadcastWhen()
    {
        return $this->task->isPublic();
    }


    public function broadcastWith()
    {
        return ['comment' => new CommentListItemResourse($this->comment)];
    }
}
