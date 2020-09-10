<?php

namespace App\Http\Resources\Team;

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
            'name' => $this->name,
            'user_id' => $this->whenLoaded('users', function () {
                return $this->users[0]->pivot->user_id;
            }),
            'username' => $this->whenLoaded('users', function () {
                return $this->users[0]->pivot->username;
            }),
        ];
    }
}
