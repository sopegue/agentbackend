<?php

namespace App\Models\Image;

use App\Models\Property\Propertie;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;
    public function property()
    {
        return $this->belongsTo(Propertie::class);
    }
}
