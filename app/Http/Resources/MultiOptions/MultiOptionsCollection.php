<?php

namespace App\Http\Resources\MultiOptions;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MultiOptionsCollection extends ResourceCollection
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
        return parent::toArray($request);
    }
}
