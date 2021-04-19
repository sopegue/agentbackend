<?php

namespace App\Http\Resources\Property;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PropertyAgentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public $collect = Member::class;
    public function toArray($request)
    {
        return [
            'data' => $this->collection
        ];
    }
}
