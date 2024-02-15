<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use JWTAuth;
use Auth;
use Validator;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Item;
use App\Models\Order;

class UserController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['signin']]);
    }

    public function signin(Request $request) {
        $validator = Validator::make($request -> all(), [
            'username' => 'required|string|min:5|max:50|unique:users',
            'email' => 'required|string|email|max:50|unique:users',
            'password' => 'required|string|min:6|max:20',
            'real_name' => 'nullable|string|min:6|max:50|regex:/^[^\d]+$/',
            'real_surname' => 'nullable|string|min:6|max:50|regex:/^[^\d]+$/',
            'phone' => 'nullable|integer|min:6|max:15',
            'phone_prefix' => 'nullable|integer',
            'sex' => 'nullable|string|in:M,F,O', // male, female, other
            'last_latitude' => 'nullable|numeric',
            'last_longitude' => 'nullable|numeric',
            'last_login_date' => 'nullable|date',
            'id_business' => 'nullable|numeric',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = User::create(array_merge(
            $validator -> validated(),
            ['password' => bcrypt($request -> input('password'))]
        ));
        return response() -> json([
            'message' => 'User successfully registered.',
            'user' => $user
        ], 201);
    }

    public function getProfile() {
        $user = Auth::user();
        $user -> makeHidden([
            'last_latitude', 'last_longitude', 'last_login_date',
            'id_business',
        ]);
        return response() -> json([
            'message' => $user,
        ], 200);
    }

    public function signout() {
        $user = auth() -> user();
        
        $business = Business::find($user -> id_business);
        if($business != null) {
            $products = [];
            $breakfastProduct = Product::find($business -> id_breakfast_product);
            $lunchProduct = Product::find($business -> id_lunch_product);
            $dinnerProduct = Product::find($business -> id_dinner_product);
            if($breakfastProduct != null) {
                $products[] = $breakfastProduct;
            }
            if($lunchProduct != null) {
                $products[] = $lunchProduct;
            }
            if($dinnerProduct != null) {
                $products[] = $dinnerProduct;
            }
            if(count($products) > 0) {
                foreach($products as $product) {
                    $items = Item::where('id_product', $product -> id) -> get();
                    if(count($items) > 0) {
                        foreach($items as $item) {
                            $item -> delete();
                        }
                    }
                    $product -> delete();
                }
            }
            $business -> delete();
        }
        $user -> delete();
        auth() -> logout();
        return response() -> json([
            'message' => 'User successfully deleted.'
        ], 200);
    }

    public function updateRealName(Request $request) {
        $validator = Validator::make($request -> all(), [
            'real_name' => 'required|string|min:6|max:50',
            'real_surname' => 'required|string|min:6|max:50',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $userDb = User::find($user -> id);
        $userDb -> real_name = $request -> input('real_name');
        $userDb -> real_surname = $request -> input('real_surname');
        $userDb -> save();
        return response() -> json([
            'message' => 'Real name successfully updated.',
            'user' => $userDb
        ], 200);
    }

    public function updateUsername(Request $request) {
        $validator = Validator::make($request -> all(), [
            'username' => 'required|string|min:5|max:50|unique:users',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $userDb = User::find($user -> id);
        $userDb -> username = $request -> input('username');
        $userDb -> save();
        return response() -> json([
            'message' => 'Username successfully updated.',
            'user' => $userDb
        ], 200);
    }

    public function updatePassword(Request $request) {
        $validator = Validator::make($request -> all(), [
            'password' => 'required|string|min:6|max:20',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $userDb = User::find($user -> id);
        $userDb -> password = bcrypt($request -> input('password'));
        $userDb -> save();
        return response() -> json([
            'message' => 'Password successfully updated.',
            'user' => $userDb
        ], 200);
    }

    public function updateEmail(Request $request) {
        $validator = Validator::make($request -> all(), [
            'email' => 'required|string|email|max:50|unique:users',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $userDb = User::find($user -> id);
        $userDb -> email = $request -> input('email');
        $userDb -> save();
        return response() -> json([
            'message' => 'Email successfully updated.',
            'user' => $userDb
        ], 200);
    }

    public function updatePhone(Request $request) {
        $validator = Validator::make($request -> all(), [
            'phone' => 'required|numeric|digits_between:6,15',
            'phone_prefix' => 'nullable|integer|digits_between:0,15',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $userDb = User::find($user -> id);
        if($userDb -> phone_prefix == null && $request -> input('phone_prefix') == null) {
            return response() -> json([
                'error' => 'No phone prefix provided.'
            ], 400);
        }
        if($request -> input('phone_prefix') != null) {
            $userDb -> phone_prefix = $request -> input('phone_prefix');
        }
        $userDb -> phone = $request -> input('phone');
        $userDb -> save();
        return response() -> json([
            'message' => 'Phone successfully updated.',
            'user' => $userDb
        ], 200);
    }

    public function updateSex(Request $request) {
        $validator = Validator::make($request -> all(), [
            'sex' => 'required|string|in:M,F,O',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $userDb = User::find($user -> id);
        $userDb -> sex = $request -> input('sex');
        $userDb -> save();
        return response() -> json([
            'message' => 'Sex successfully updated.',
            'user' => $userDb
        ], 200);
    }
}