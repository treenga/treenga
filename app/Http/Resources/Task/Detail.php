<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Comment\Tree as CommentTreeResourse;
use App\Http\Resources\User\WithTeamUsername as UserWithTeamUsernameResourse;
use App\Http\Resources\Activity\WithComments as ActivityWithCommentsResourse;

class Detail extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $last_updated_at = $this->updated_at;
        $tempItem = $this->whenLoaded('tempItem', $this->tempItem);
        
        $author = $this->team->users()->find($this->author_id);
        $author_name = $author ? $author->pivot->username : '';
        
        return [
            'id' => $this->id,
            'team_task_id' => $this->team_task_id,
            'text' => $this->whenLoaded('text', function() use($tempItem) {
                return optional($tempItem)->body ?? $this->text->body;
            }),
            'is_draft' => $this->isDraft(),
            'is_subscriber' => $this->whenLoaded('subscribers', function() {
                return $this->subscribers->contains('id', auth()->id());
            }),
            'is_archived' => $this->is_archived,
            'is_temp' => (bool) $tempItem,
            'name' => optional($tempItem)->name ?? $this->name,
            'username' => $author_name,
            'diff' => optional($last_updated_at)->diffForHumans(),
            'due_date' => $this->due_date ? $this->due_date->toDateTimeString() : null,
            'activities' => $this->when(count($this->allActivities), ActivityWithCommentsResourse::collection($this->allActivities)),
            'categories_id' => $this->whenLoaded('categories', $this->categories->pluck('id')),
            'usersIds' => $this->whenLoaded('users', $this->users->pluck('id')),
            'participants' => $this->whenLoaded('subscribers', UserWithTeamUsernameResourse::collection($this->subscribers)),
            'commentsstate' => $this->whenLoaded('userOptions', function(){
                return ($commentsstate = optional(optional($this->userOptions->first())->pivot)->commentsstate) ? json_decode($commentsstate) : null;
            }),
        ];
    }
}
