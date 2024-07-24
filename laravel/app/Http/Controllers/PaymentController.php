<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function openpayGetToken(Request $request) {
        $validator = Validator::make($request -> all(), [
            'card_number' => 'required',
            'holder_name' => 'required',
            'expiration_year' => 'required|numeric|min:2024|max:2222',
            'expiration_month' => 'required|numeric|min:1|max:12',
            'cvv2' => 'required|numeric|min:0|max:999',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        return response() -> json([
            'message' => '¡Todo bien por ahora!!'
        ], 200);
    }
}
