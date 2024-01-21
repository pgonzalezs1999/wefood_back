<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'tax_id',
        'id_breakfast_product',
        'id_lunch_product',
        'id_dinner_product',
        'logo_path',
        'id_country',
        'longitude',
        'latitude',
        'directions',
        'id_currency',
    ];

    protected $hidden = [
    ];
}
