<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Models\User;

class UserController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['signin']]);
    }

    public function signin(Request $request) {
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
            ['password' => bcrypt($request -> input('password'))]
        ));
        return response() -> json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    public function signout() {
        $user = auth() -> user();
        $user -> delete();
        auth() -> logout();

        return response()->json([
            'message' => 'User successfully  deleted'
        ]);
    }
}
