<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamWithCountAuthoredTask extends JsonResource
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
            'teamusername' => $this->whenPivotLoaded('team_user', function(){
                return $this->pivot->username;
            }),
            'tasks_count' => $this->authored_tasks_count,
        ];
    }
}
