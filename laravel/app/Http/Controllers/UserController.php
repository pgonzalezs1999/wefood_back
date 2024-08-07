<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use JWTAuth;
use Auth;
use Validator;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Item;
use App\Models\Order;
use App\Models\Favourite;
use Illuminate\Support\Facades\Mail;
use App\Mail\ChangePasswordMail;

class UserController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => [
            'signin',
            'checkUsernameAvailability',
            'checkEmailAvailability',
            'checkPhoneAvailability',
            'emailChangePassword',
        ]]);
    }

    public function signIn(Request $request) {
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
        ]);
        if($user -> id_business != null) {
            $business = Business::find($user -> id_business);
            $user -> business_verified = $business -> is_validated;
        }
        return response() -> json([
            'message' => $user,
        ], 200);
    }

    public function signout() {
        $user = auth() -> user();
        $business = Business::find($user -> id_business);
        if($business != null) {
            $products = new Collection();
            $breakfastProduct = Product::where('id_business', $business -> id) -> where('product_type', 'b');
            $lunchProduct = Product::where('id_business', $business -> id) -> where('product_type', 'l');
            $dinnerProduct = Product::where('id_business', $business -> id) -> where('product_type', 'd');
            if($breakfastProduct != null) {
                $products -> push($breakfastProduct);
            }
            if($lunchProduct != null) {
                $products -> push($lunchProduct);
            }
            if($dinnerProduct != null) {
                $products -> push($dinnerProduct);
            }
            if(count($products) > 0) {
                foreach($products as $product) {
                    $product -> delete();
                }
            }
            $favourites = Favourite::where('id_business', $business -> id);
            foreach($favourites as $favourite) {
                $favourite -> delete();
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
            'real_name' => 'required|string|min:2|max:30',
            'real_surname' => 'required|string|min:2|max:30',
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

    public function emailChangePassword(String $email1, String $email2, String $email3) {
        $email = $email1 . '@' . $email2 . '.' . $email3;
        Mail::to($email) -> send(new ChangePasswordMail());
        return response() -> json([
            'message' => 'Email sent successfully.'
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

    public function verifyEmail(Request $request) {
        $user = Auth::user();
        $userDb = User::find($user -> id);
        if($userDb -> email_verified) {
            return response() -> json([
                'error' => 'Email already verified.'
            ], 400);
        }
        $userDb -> email_verified = true;
        $userDb -> save();
        return response() -> json([
            'message' => 'Email verified successfully.',
            'user' => $userDb
        ], 200);
    }

    public function checkUsernameAvailability(Request $request) {
        $validator = Validator::make($request -> all(), [
            'username' => 'required',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 400);
        }
        $username = $request -> input('username');
        $user = User::where('username', $username) -> first();
        if($user == null) {
            return response() -> json([
                'availability' => true,
            ], 200);
        }
        return response() -> json([
            'availability' => false,
        ], 200);
    }

    public function checkEmailAvailability(Request $request) {
        $validator = Validator::make($request -> all(), [
            'email' => 'required',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 400);
        }
        $email = $request -> input('email');
        $user = User::where('email', $email) -> first();
        if($user == null) {
            return response() -> json([
                'availability' => true,
            ], 200);
        }
        return response() -> json([
            'availability' => false,
        ], 200);
    }

    public function checkPhoneAvailability(Request $request) {
        $validator = Validator::make($request -> all(), [
            'phone' => 'required|numeric|min:100000000|max:999999999', // 9 digits
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 400);
        }
        $phone = $request -> input('phone');
        $user = User::where('phone', $phone) -> first();
        if($user == null) {
            return response() -> json([
                'availability' => true,
            ], 200);
        }
        return response() -> json([
            'availability' => false,
        ], 200);
    }
}