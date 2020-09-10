<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Comment;
use App\Activity;
use App\Http\Resources\Comment\TreeForActivities as CommentTreeForActivitiesResourse;
use App\Http\Resources\Activity\Short as ActivityShortResourse;

class WithComments extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        switch (true) {
            case $this->resource instanceof Comment:
                return new CommentTreeForActivitiesResourse($this->resource);
                break;
            case $this->resource instanceof Activity:
                return new ActivityShortResourse($this->resource);
                break;
        }
    }
}
