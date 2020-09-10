<?php

namespace App\Events\Category;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Category;
use App\Team;

class Deleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $categoryData;
    public $team;

    public function __construct($category, Team $team)
    {
        $this->categoryData = $category->toArray();
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
        return $this->categoryData['type'] == Category::TYPE_PUBLIC;
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->categoryData['id'],
        ];
    }
}
