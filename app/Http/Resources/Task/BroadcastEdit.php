<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\JsonResource;

class BroadcastEdit extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'team_task_id' => $this->team_task_id,
            'name' => $this->name,
            'team_id' => $this->team_id,
            'categories_id' => $this->whenLoaded('categories', $this->categories->pluck('id')),
            'usersIds' => $this->whenLoaded('users', $this->users->pluck('id')),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'diff' => $this->updated_at->diffForHumans(),
            'author_id' => $this->author_id,
            'due_date' => $this->due_date ? $this->due_date->toDateTimeString() : null,
            'due_date_filter' => $this->due_date_filter,
        ];
    }
}
