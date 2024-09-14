<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'tax_id',
        'logo_path',
        'id_country',
        'longitude',
        'latitude',
        'directions',
        'bank_name',
        'bank_account',
        'bank_account_type',
        'bank_owner_name',
        'interbank_account',
        'created_at',
        'deleted_at',
    ];

    protected $hidden = [
        'updated_at',
    ];
}
