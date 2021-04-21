<?php

namespace App\Models\Adresse;

use App\Models\Agence\Agence;
use App\Models\Property\Propertie;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    use HasFactory;
    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function property()
    {
        return $this->hasOne(Propertie::class);
    }
    public function agence()
    {
        return $this->hasOne(Agence::class);
    }
}
