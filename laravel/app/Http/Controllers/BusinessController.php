<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Models\User;
use App\Models\Business;

class BusinessController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['createBusiness']]);
    }

    public function createBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            // User
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone_prefix' => 'required|integer',
            'phone' => 'required|integer|unique:users',
            // Business
            'name' => 'required|string|max:255|regex:/^[^\d]+$/',
            'tax_id' => 'required|string|max:255|unique:businesses',
            'directions' => 'required|string|max:255',
            'logo_path' => 'nullable|string|max:255|image',
        ]);
        if($validator -> fails()) {
            return response() -> json($validator -> errors() -> toJson(), 422);
        }
        $user = User::create([
            'username' => $request->email,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone_prefix' => $request->phone_prefix,
            'phone' => $request->phone,
        ]);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'tax_id' => $request->tax_id,
            'directions' => $request->directions,
            'logo_path' => $request->logo_path,
        ]);
        return response()->json([
            'message' => 'Business created successfully. Waiting to be validated',
            'business' => $business,
            'user' => $user,
        ], 201);
    }

    public function getAllBusinesses() {
        $businesses = Business::all();
        return response() -> json($businesses);
    }
}