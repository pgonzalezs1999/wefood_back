<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use App\Models\Country;

class CountryController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function getAllCountries() {
        $countries = Country::all();
        return response() -> json([
            'message' => $countries
        ], 200);
    }
}
