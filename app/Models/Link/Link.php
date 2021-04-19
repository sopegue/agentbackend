<?php

namespace App\Models\Link;

use App\Models\Property\Propertie;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;
    public function property()
    {
        return $this->belongsTo(Propertie::class);
    }
}
