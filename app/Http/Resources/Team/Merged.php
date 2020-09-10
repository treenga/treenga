<?php

namespace App\Http\Resources\Team;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomData;
use App\Http\Resources\Category\Tree as CategoryTreeResource;
use App\Http\Resources\User\TeamWithCountTasks;
use App\Http\Resources\User\TeamWithCountAuthoredTask;

class Merged extends JsonResource
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
            'count_unassigned_tasks' => $this->counts->countUnassignedTasks,
            'count_public_notifications' => $this->counts->countPublicNotifications,
            'count_public_drafts' => $this->counts->countPublicDrafts,
            'count_unsorted_public_tasks' => $this->counts->countUnsortedPublicTasks,
            'privateTree' => CategoryTreeResource::collection($this->privateTree),
            'publicTree' => CategoryTreeResource::collection($this->publicTree),
            'teamUsers' => TeamWithCountTasks::collection($this->teamUsers),
            'teamAuthors' => TeamWithCountAuthoredTask::collection($this->teamAuthors),
        ];
    }
}
