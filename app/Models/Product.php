<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amount',
        'price',
        'original_price',
        'ending_date',
        'vegetarian',
        'mediterranean',
        'dessert',
        'junk',
        'working_on_monday',
        'working_on_tuesday',
        'working_on_wednesday',
        'working_on_thursday',
        'working_on_friday',
        'working_on_saturday',
        'working_on_sunday',
        'starting_hour',
        'ending_hour',
        'product_type',
        'id_business',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
