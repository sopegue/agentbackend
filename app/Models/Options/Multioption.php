<?php

namespace App\Models\Options;

use App\Models\Property\Propertie;
use App\Models\Options\Option;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Multioption extends Model
{
    use HasFactory;
    public function property()
    {
        return $this->belongsTo(Propertie::class);
    }
    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}
