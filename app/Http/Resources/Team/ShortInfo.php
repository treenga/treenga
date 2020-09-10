<?php

namespace App\Http\Resources\Team;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomData;
use App\Http\Resources\Category\TreeShort as CategoryTreeShortResource;
use App\Http\Resources\User\TeamWithCountTasks;
use App\Http\Resources\User\TeamWithCountAuthoredTask;

class ShortInfo extends JsonResource
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
            'privateTree' => CategoryTreeShortResource::collection($this->privateCategories),
            'publicTree' => CategoryTreeShortResource::collection($this->publicCategories),
            'teamUsers' => TeamWithCountTasks::collection($this->users),
        ];
    }
}
