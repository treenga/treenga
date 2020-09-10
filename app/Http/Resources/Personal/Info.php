<?php

namespace App\Http\Resources\Personal;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomData;
use App\Http\Resources\Category\PrivateTree as CategoryPrivateTreeResource;

class Info extends JsonResource
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
            'privateTree' => CategoryPrivateTreeResource::collection($this->privateTree),
        ];
    }
}
