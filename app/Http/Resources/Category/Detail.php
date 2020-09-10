<?php

namespace App\Http\Resources\Category;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Comment\Tree as CommentTreeResourse;

class Detail extends JsonResource
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
            'name' => $this->name,
            'body' => $this->whenLoaded('text', function(){
                return $this->text->body;
            }),
            'is_subscriber' => $this->whenLoaded('subscribers', function(){
                return $this->subscribers->contains('id', auth()->id());
            }),
            'count_word' => $this->whenLoaded('text', function(){
                return $this->text->count_word;
            }),
            'count_histories' => $this->whenLoaded('histories', function(){
                return $this->histories->count();
            }),
            'comments' => CommentTreeResourse::collection($this->whenLoaded('comments')),
        ];
    }
}
