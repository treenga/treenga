<?php

namespace App\Http\Resources\Team;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomData;

class MergedTeams extends JsonResource
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
            'userteams' => ShortWithCount::collection($this->userteams),
            'shared' => ShortWithCount::collection($this->shared),
        ];
    }
}
