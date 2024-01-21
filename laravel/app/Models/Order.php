<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'id_user',
        'id_product',
        'order_date',
        'reception_date',
        'reception_method',
        'id_payment',
    ];

    protected $hidden = [
    ];
}
