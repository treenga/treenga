<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomData;

class Short extends JsonResource
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
            'status' => $this->status_text,
            'is_team_author' => $this->is_team_author,
            'date_of_reg' => optional($this->created_at)->format('M d, Y: h:i a'),
        ];
    }
}
