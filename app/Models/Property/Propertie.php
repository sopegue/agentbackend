<?php

namespace App\Models\Property;

use App\Models\Adresse\Adresse;
use App\Models\Agence\Agence;
use App\Models\Image\Image;
use App\Models\Link\Link;
use App\Models\Options\Multioption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propertie extends Model
{
    use HasFactory;

    public function adresse()
    {
        return $this->belongsTo(Adresse::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function properties_saved()
    {
        return $this->belongsToMany(User::class);
    }
    public function images()
    {
        return $this->hasMany(Image::class, 'property_id');
    }
    public function multioptions()
    {
        return $this->hasMany(Multioption::class, 'property_id');
    }
    public function link()
    {
        return $this->hasOne(Link::class, 'property_id');
    }
}
