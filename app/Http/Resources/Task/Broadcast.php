<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\JsonResource;

class Broadcast extends JsonResource
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
        ];
    }
}
