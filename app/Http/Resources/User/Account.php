<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomData;

class Account extends JsonResource
{
    use CustomData;
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
            'email' => $this->email,
            'is_team_author' => $this->is_team_author,
            'show_task_all_comments' => $this->show_task_all_comments,
            'show_task_details' => $this->show_task_details,
        ];
    }
}
