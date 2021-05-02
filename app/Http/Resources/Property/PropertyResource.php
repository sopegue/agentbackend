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

class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // if auth user make saved true or false,
        // else make'saved' = false key
        return [
            'status' => '200',
            'property' => parent::toArray($request),
            'email' => $this->user->main_email,
            'user_pic' => $this->user->picture_link,
            'adresse' => $this->adresse->adresse,
            'ville' => $this->adresse->ville,
            'agence' => [
                'id' => $this->user->agence->id,
                'name' => $this->user->agence->name,
                'super' => $this->user->agence->super,
                'tel' => $this->user->agence->phone
            ],
            'saved' => Auth::user(),
            'images' =>  new ImageCollection($this->images),
            'links' => new LinkResource($this->link),
            'options' => new MultiOptionsCollection($this->multioptions)
        ];
    }
}
