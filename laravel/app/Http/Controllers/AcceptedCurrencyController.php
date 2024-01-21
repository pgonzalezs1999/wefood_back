<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\AcceptedCurrency;

class AcceptedCurrencyController extends Controller
{
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }
}
