<?php
namespace App\Http\Resources;

trait CustomData
{
    public function withCustomData($data)
    {
        foreach($data as $key => $items) {
            $this->{$key} = $items;
        }

        return $this;
    }
}