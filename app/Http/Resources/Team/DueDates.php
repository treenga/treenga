<?php

namespace App\Http\Resources\Team;

use Illuminate\Http\Resources\Json\JsonResource;

class DueDates extends JsonResource
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
            'no' => $this['no'],
            'overdue' => $this['overdue'],
            'today' => $this['today'],
            'tommorow' => $this['tommorow'],
            'thisWeek' => $this['thisWeek'],
            'nextWeek' => $this['nextWeek'],
            'thisMonth' => $this['thisMonth'],
            'nextMonth' => $this['nextMonth'],
        ];
    }
}
