<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Comment\Tree as CommentTreeResourse;
use App\Http\Resources\User\WithTeamUsername as UserWithTeamUsernameResourse;
use App\Http\Resources\Activity\WithComments as ActivityWithCommentsResourse;

class PrivateDetail extends JsonResource
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
            'text' => $this->whenLoaded('text', function() {
                return $this->text->body;
            }),
            'is_draft' => $this->isDraft(),
            'is_archived' => $this->is_archived,
            'name' => $this->name,
            'username' => 'Me',
            'diff' => $this->updated_at->diffForHumans(),
            'due_date' => $this->due_date ? $this->due_date->toDateTimeString() : null,
            'categories_id' => $this->whenLoaded('categories', $this->categories->pluck('id')),
            // 'commentsstate' => $this->whenLoaded('userOptions', function(){
            //     return ($commentsstate = optional(optional($this->userOptions->first())->pivot)->commentsstate) ? json_decode($commentsstate) : null;
            // }),
        ];
    }
}
