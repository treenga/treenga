<?php

namespace App\Http\Resources\Comment;

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
            'body' => $this->body,
            'username' => $this->username,
            'diff' => $this->created_at->diffForHumans(),
            'children' => $this->when(count($this->children), Tree::collection($this->children)),
            'is_comment' => true,
        ];
    }
}
