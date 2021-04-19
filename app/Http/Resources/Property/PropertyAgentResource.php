<?php

namespace App\Http\Resources\Property;

use App\Http\Resources\Adresse\AdresseResource;
use App\Http\Resources\Image\ImageCollection;
use App\Http\Resources\Image\ImageResource;
use App\Http\Resources\Link\LinkResource;
use App\Http\Resources\MultiOptions\MultiOptionsCollection;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


class PropertyAgentResource extends JsonResource
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
            'status' => '200',
            'property' => parent::toArray($request),
            'adresse' => $this->adresse,
            'images' =>  new ImageCollection($this->images),
            'links' => new LinkResource($this->link),
            'options' => new MultiOptionsCollection($this->multioptions)
        ];
    }
}
