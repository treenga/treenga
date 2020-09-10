<?php

namespace App\Http\Resources\Team;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\User\Teams as UserTeams;

class WithUsers extends JsonResource
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
            'auth_username' => $this->auth_username,
            'auth_filter' => $this->auth_filter,
            'auth_treestate' => $this->auth_treestate,
            'auth_current_state' => $this->auth_current_state,
            'users' => UserTeams::collection($this->whenLoaded('users')),
        ];
    }
}
