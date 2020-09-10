<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\JsonResource;

class PrivateListItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $last_updated_at = $this->last_updated_at;
        return [
            'id' => $this->id,
            'team_task_id' => $this->team_task_id,
            'is_draft' => $this->isDraft(),
            'is_private' => $this->isPrivate(),
            'is_archived' => $this->is_archived,
            'is_temp' =>  (bool)$this->temp_item_count,
            'name' => $this->name,
            'username' => 'Me',
            'author_id' => $this->author_id,
            'categories_id' => $this->whenLoaded('categories', function(){
                return $this->categories->pluck('id');
            }),
            'usersIds' => $this->whenLoaded('users', function(){
                return $this->users->pluck('id');
            }),
            'due_date' => optional($this->due_date)->toDateTimeString(),
            'due_date_filter' => $this->due_date_filter,
            'diff' => optional($last_updated_at)->diffForHumans(),
            'updated_at' => optional($last_updated_at)->toDateTimeString(),
        ];
    }
}
