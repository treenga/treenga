<?php

namespace App\Http\Resources\Team;

use Illuminate\Http\Resources\Json\JsonResource;

class ShortWithCount extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->private ? 'private' : $this->slug,
            'original_slug' => $this->slug,
            'private' => $this->private,
            'is_owner' => $this->whenPivotLoaded('team_user', function () {
                return $this->pivot->is_owner;
            }),
            'current' => $this->whenPivotLoaded('team_user', function () {
                return $this->pivot->current;
            }),
            'block' => $this->isBlock(),
            'username' => $this->whenPivotLoaded('team_user', function () {
                return $this->pivot->username;
            }),
            'user_notifications_count' => $this->user_notifications_count,
        ];
    }
}
