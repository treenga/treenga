<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\JsonResource;

class ListItem extends JsonResource
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
        $first_notification = $this->userNotifications()->orderBy('updated_at', 'desc')->first();
        $last_user = $this->usersViewed()->where('user_id', auth()->id())->first();
        
        $author = $this->team->users()->find($this->author_id);
        $author_name = $author ? $author->pivot->username : '';

        return [
            'id' => $this->id,
            'team_task_id' => $this->team_task_id,
            'is_draft' => $this->isDraft(),
            'is_private' => $this->isPrivate(),
            'is_archived' => $this->is_archived,
            'is_temp' =>  (bool) $this->temp_item_count,
            'name' => $this->name,
            'username' => $author_name,
            'author_id' => $this->author_id,
            'categories_id' => $this->whenLoaded('categories', function(){
                return $this->categories->pluck('id');
            }),
            'usersIds' => $this->whenLoaded('categories', function(){
                return $this->users->pluck('id');
            }),
            'due_date' => optional($this->due_date)->toDateTimeString(),
            'due_date_filter' => $this->due_date_filter,
            'diff' => optional($last_updated_at)->diffForHumans(),
            'user_notifications_count' => $this->user_notifications_count,
            'updated_at' => optional($last_updated_at)->toDateTimeString(),
            'updated_notification_at' => $first_notification ? optional($first_notification->updated_at)->toDateTimeString() : 0,
            'updated_lastviewed_at' => $last_user ? optional($last_user->pivot->updated_at)->toDateTimeString() : 0,
        ];
    }
}
