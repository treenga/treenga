<?php

namespace App\Http\Resources\Category;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomData;
// use App\Http\Resources\Task\ListItem as TaskListItemResource;
use App\Http\Resources\Task\ListCollection as TaskListCollection;

class MergedTaskList extends JsonResource
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
            'category_id' => $this->category_id,
            'currentTasks' => (new TaskListCollection($this->currentData['tasks']))->withCustomData(['tasks_count' => $this->currentData['tasks_count'], 'deleted_tasks_count' => $this->currentData['deleted_tasks_count']]),
            'childTasks' => (new TaskListCollection($this->childData['tasks']))->withCustomData(['tasks_count' => $this->childData['tasks_count'], 'deleted_tasks_count' => $this->childData['deleted_tasks_count']]),
        ];
    }
}
