<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class WithTeamUsername extends JsonResource
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
            'username' => $this->whenLoaded('teams', function () {
                return count($this->teams) ? optional($this->teams[0]->pivot)->username : null;
            }),
        ];
    }
}
