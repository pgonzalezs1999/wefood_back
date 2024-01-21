<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\Country;

class CountryController extends Controller
{
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function getAllCountries() {
        $countries = Country::all();
        return response() -> json($countries);
    }
}
