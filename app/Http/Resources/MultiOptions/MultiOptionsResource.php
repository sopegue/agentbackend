<?php

namespace App\Http\Resources\MultiOptions;

use App\Http\Resources\Options\OptionsResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MultiOptionsResource extends JsonResource
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
            'multioptions' => [
                'id' => $this->id,
                'option_id' => $this->option_id
            ],
            'options' => new OptionsResource($this->option),
        ];
    }
}
