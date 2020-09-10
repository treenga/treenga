<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class Teams extends JsonResource
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
            'email' => $this->email,
            'username' => $this->whenPivotLoaded('team_user', function () {
                return $this->pivot->username;
            }),
            'is_owner' => $this->whenPivotLoaded('team_user', function () {
                return $this->pivot->is_owner;
            }),
        ];
    }
}
