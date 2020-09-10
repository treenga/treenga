<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Comment\Tree as CommentTreeResourse;

class Attributes extends JsonResource
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
            'is_draft' => $this->isDraft(),
            'categories_id' => $this->whenLoaded('categories', $this->categories->pluck('id')),
            'usersIds' => $this->whenLoaded('users', $this->users->pluck('id')),
        ];
    }
}
