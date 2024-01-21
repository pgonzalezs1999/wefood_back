<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'price',
        'amount',
        'ending_date',
        'vegetarian',
        'vegan',
        'bakery',
        'fresh',
        'workingOnMonday',
        'workingOnTuesday',
        'workingOnWednesday',
        'workingOnThursday',
        'workingOnFriday',
        'workingOnSaturday',
        'workingOnSunday',
    ];

    protected $hidden = [
    ];
}
