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
        $this -> middleware('auth:api', ['except' => ['signin', 'login']]);
    }

    public function login(Request $request) {
        $validator = Validator::make($request -> all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if($validator -> fails()) {
            return response() -> json($validator -> errors() -> toJson(), 422);
        }
        if(! $token = Auth::attempt($validator -> validated())) {
            return response() -> json(['error' => 'Unauthorized'], 401);
        }
        return $this -> createNewToken($token);
    }

    public function createNewToken($token) {
        return response() -> json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth() -> factory() -> getTTL() * 60,
            'user' => auth() -> user(),
        ]);
    }

    public function getProfileInfo() {
        return response() -> json(auth() -> user());
    }

    public function logout() {
        auth() -> logout();
        return response() -> json([
            'message' => 'User successfully logged out'
        ]);
    }
}