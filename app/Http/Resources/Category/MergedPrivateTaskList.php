<?php

namespace App\Http\Resources\Category;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomData;
use App\Http\Resources\Task\PrivateListCollection;

class MergedPrivateTaskList extends JsonResource
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
            'currentTasks' => (new PrivateListCollection($this->currentData['tasks']))->withCustomData(['tasks_count' => $this->currentData['tasks_count'], 'deleted_tasks_count' => $this->currentData['deleted_tasks_count']]),
            'childTasks' => (new PrivateListCollection($this->childData['tasks']))->withCustomData(['tasks_count' => $this->childData['tasks_count'], 'deleted_tasks_count' => $this->childData['deleted_tasks_count']]),
        ];
    }
}
