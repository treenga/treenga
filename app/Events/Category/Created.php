<?php

namespace App\Events\Category;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Team;
use App\Category;
use App\Http\Resources\Category\Short;

class Created implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $team;

    public $category;

    public function __construct(Category $category, Team $team)
    {
        $this->category = $category;
        $this->team = $team;
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
        return $this->category->isPublic();
    }

    public function broadcastWith()
    {
        $category = $this->team->categories()->find($this->category->id);
        return ['category' => new Short($category)];
    }
}
