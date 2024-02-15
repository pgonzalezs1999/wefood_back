<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'description',
        'price',
        'amount',
        'ending_date',
        'vegetarian',
        'vegan',
        'bakery',
        'fresh',
        'working_on_monday',
        'working_on_tuesday',
        'working_on_wednesday',
        'working_on_thursday',
        'working_on_friday',
        'working_on_saturday',
        'working_on_sunday',
        'starting_hour',
        'ending_hour',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
