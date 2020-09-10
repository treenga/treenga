<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\CustomData;

class ListCollection extends ResourceCollection
{
    use CustomData;
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public $collects = \App\Http\Resources\Task\ListItem::class;

    public function toArray($request)
    {
        return [
            'tasks' => $this->collection,
            'tasks_count' => $this->tasks_count,
            'deleted_tasks_count' => $this->deleted_tasks_count,
            'category_id' => empty($this->category_id) ? 0 : $this->category_id,
        ];
    }
}
