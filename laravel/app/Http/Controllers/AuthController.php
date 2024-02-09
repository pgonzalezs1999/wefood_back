<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Models\User;

class AuthController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]); 
        if ($validator -> fails()) {
            return response()->json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        } 
        $credentials = $request -> input('username');
        
        // Check if the provided username is in email format
        if (filter_var($credentials, FILTER_VALIDATE_EMAIL)) {
            $credentials = ['email' => $credentials, 'password' => $request -> input('password')]; // Checks login with email-password
        } else {
            $credentials = ['username' => $credentials, 'password' => $request -> input('password')]; // Checks login with username-password
        }
        if(! $token = Auth::attempt($credentials)) {
            return response()->json([
                'error' => 'Unauthorized.'
            ], 401);
        }
        return $this->createNewToken($token);
    }

    public function createNewToken($token) {
        return response() -> json([
            'access_token' => $token,
        ], 200);
    }

    public function logout() {
        auth() -> logout();
        return response() -> json([
            'error' => 'User successfully logged out.'
        ], 400);
    }

    public function addAdmin(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id' => 'required|numeric|exists:users,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $adminUser = User::find($request->id);
        if ($adminUser -> is_admin) {
            return response()->json([
                'error' => 'User is already an admin.'
            ], 400);
        }
        if ($adminUser -> id_business != null) {
            return response()->json([
                'error' => 'Users that own a business cannot be admin.'
            ], 400);
        }
        $adminUser -> is_admin = true;
        $adminUser -> save();
        return response() -> json([
            'message' => 'Admin successfully added.'
        ], 200);
    }

    public function removeAdmin(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id' => 'required|numeric|exists:users,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $adminUser = User::find($request->id);
        if ($adminUser -> is_admin != true) {
            return response()->json([
                'error' => 'User is not an admin.'
            ], 400);
        }
        $adminUser -> is_admin = false;
        $adminUser -> save();
        return response() -> json([
            'message' => 'Admin successfully removed.'
        ], 200);
    }
}