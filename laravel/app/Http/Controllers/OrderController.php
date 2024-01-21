<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\Order;

class OrderController extends Controller
{
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }
}
