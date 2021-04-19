<?php

namespace App\Models\Property;

use App\Models\User;
use App\Models\Property\Propertie;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Save extends Model
{
    use HasFactory;
    public function property()
    {
        return $this->belongsTo(Propertie::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
