<?php

namespace App\Http\Resources\Category;

use Illuminate\Http\Resources\Json\JsonResource;

class Tree extends JsonResource
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
            'label' => $this->name,
            'user_notifications_count' => $this->user_notifications_count,
            'category_notifications_count' => $this->category_notifications_count,
            'is_archived' => $this->is_archived,
            'children' => $this->when(count($this->children), Tree::collection($this->children)),
            'count_word' => $this->whenLoaded('text', function(){
                return $this->text->count_word;
            }),
        ];
    }
}
