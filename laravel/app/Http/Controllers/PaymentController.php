<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }
}
