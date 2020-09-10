<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Resources\Json\JsonResource;

class Short extends JsonResource
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
            'body' => $this->body,
            'username' => $this->username,
            'diff' => $this->created_at->diffForHumans(),
            'is_activity' => true,
            'is_new' => $this->is_new,
        ];
    }
}
