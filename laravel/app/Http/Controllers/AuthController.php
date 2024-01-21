<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['register', 'login']]);
    }

    public function register(Request $request) {
        $validator = Validator::make($request -> all(), [
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'real_name' => 'nullable|string|regex:/^[^\d]+$/',
            'phone' => 'nullable|integer',
            'phone_prefix' => 'nullable|integer',
            'sex' => 'nullable|string|in:M,F,O',
            'last_latitude' => 'nullable|numeric',
            'last_longitude' => 'nullable|numeric',
            'last_login_date' => 'nullable|date',
            'id_business' => 'nullable|numeric',
        ]);
        if($validator -> fails()) {
            return response() -> json($validator -> errors() -> toJson(), 400);
        }
        $user = User::create(array_merge(
            $validator -> validated(),
            ['password' => bcrypt($request -> password)]
        ));
        return response() -> json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
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
            'message' => 'User successfully signed out'
        ]);
    }
}
