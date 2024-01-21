<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcceptedCurrency extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_country',
        'id_currency',
    ];
    
    protected $hidden = [
    ];
}
