<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\CustomData;

class PrivateListCollection extends ResourceCollection
{
    use CustomData;
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public $collects = \App\Http\Resources\Task\PrivateListItem::class;

    public function toArray($request)
    {
        return [
            'tasks' => $this->collection,
            'tasks_count' => $this->tasks_count,
            'deleted_tasks_count' => $this->deleted_tasks_count,
        ];
    }
}
