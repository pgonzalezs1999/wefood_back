<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Requests\PostStoreImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Auth;
use Validator;
use App\Models\User;
use App\Models\Business;
use App\Models\Favourite;
use App\Models\Comment;
use App\Models\Currency;
use App\Models\LegalCurrency;
use App\Models\AcceptedCurrency;

class BusinessController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['createBusiness']]);
    }

    public function createBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            // Create linked user
            'email' => 'required|string|email|min:6|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone_prefix' => 'required|integer',
            'phone' => 'required|integer|unique:users',
            // Create business
            'name' => 'required|string|min:6|max:100',
            'description' => 'required|string|min:6|max:255',
            'tax_id' => 'required|string|min:6|max:50|unique:businesses',
            'directions' => 'required|string|min:6|max:255',
            'logo_file' => 'required|file|max:2048|image',
            'id_country' => 'required|numeric|exists:countries,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $business = Business::create([
            'name' => $request -> name,
            'tax_id' => $request -> tax_id,
            'directions' => $request -> directions,
        ]);
        $user = User::create([
            'username' => $request -> email,
            'email' => $request -> email,
            'password' => bcrypt($request->password),
            'phone_prefix' => $request->phone_prefix,
            'phone' => $request -> phone,
            'id_business' => $business -> id_business,
        ]);
        try {
            $business_id = $business -> id;
            $image_path = "public/storage/images/{$business_id}";
            $image_name = 'profile.' . $request -> file('logo_file') -> getClientOriginalExtension();
            Storage::disk('public') -> putFileAs(
                $image_path,
                $request -> file('logo_file'),
                $image_name,
            );
        } catch(\Exception $e) {
            $user -> forceDelete();
            $business -> forceDelete();
            print_r($e -> getMessage());
            return response() -> json([
                'error' => 'Could not upload the image'
            ], 500);
        }
        return response()->json([
            'message' => 'Business created successfully. Waiting to be validated by an admin.',
            'business' => $business,
            'user' => $user,
        ], 201);
    }

    public function getAllBusinesses() {
        $businesses = Business::all();
        return response() -> json([
            'businesses' => $businesses
        ], 200);
    }

    public function getSessionBusiness() {
        $user = Auth::user();
        $user -> makeHidden([
            'created_at', 'updated_at', 'deleted_at',
            'last_login_date', 'last_latitude', 'last_longitude',
            'id_business', 'is_admin', 'sex',
        ]);
        $business = Business::find($user -> id_business);
        if($business == null) {
            return response() -> json([
                'error' => 'No business found for this user.'
            ], 404);
        } else if($business -> is_validated == false) {
            return response() -> json([
                'error' => 'Business not yet validated.'
            ], 422);
        } else {
            $business -> makeHidden([
                'created_at', 'updated_at', 'deleted_at',
                'longitude', 'latitude', 'is_validated',
            ]);
            return response() -> json([
                'user' => $user,
                'business' => $business
            ], 200);
        }
    }

    public function deleteBusiness() {
        $user = Auth::user();
        $id = $user -> id_business;
        $business = Business::find($id);
        if($business) {
            $business -> delete();
            $user -> delete();
        } else {
            return response() -> json([
                'error' => 'Business not found'
            ], 404);
        }
        return response() -> json([
            'message' => 'Business and associated user deleted successfully.'
        ], 200);
    }

    public function validateBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id' => 'required|numeric|exists:businesses,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $business = Business::find($request -> id);
        if($business -> is_validated) {
            return response()->json([
                'error' => 'Business is already validated.'
            ], 422);
        }
        $business -> is_validated = true;
        $business -> save();
        return response() -> json([
            'message' => 'Business successfully validated.'
        ], 200);
    }

    public function updateBusinessName(Request $request) {
        $validator = Validator::make($request -> all(), [
            'name' => 'required|string|min:6|max:100',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $business = Business::find($user -> id_business);
        if($business == null) {
            return response() -> json([
                'error' => 'No business found for this user.'
            ], 404);
        } else if($business -> is_validated == false) {
            return response() -> json([
                'error' => 'Business not yet validated.'
            ], 422);
        }
        $business -> name = $request -> input('name');
        $business -> save();
        return response() -> json([
            'message' => 'Business name updated successfully.',
            'business' => $business
        ], 200);
    }

    public function updateBusinessDescription(Request $request) {
        $validator = Validator::make($request -> all(), [
            'description' => 'required|string|min:6|max:255',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $business = Business::find($user -> id_business);
        if($business == null) {
            return response() -> json([
                'error' => 'No business found for this user.'
            ], 404);
        } else if($business -> is_validated == false) {
            return response() -> json([
                'error' => 'Business not yet validated.'
            ], 422);
        }
        $business -> description = $request -> input('description');
        $business -> save();
        return response() -> json([
            'message' => 'Business description updated successfully.',
            'business' => $business
        ], 200);
    }

    public function updateBusinessDirections(Request $request) {
        $validator = Validator::make($request -> all(), [
            'directions' => 'required|string|min:6|max:255',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $business = Business::find($user -> id_business);
        if($business == null) {
            return response() -> json([
                'error' => 'No business found for this user.'
            ], 404);
        } else if($business -> is_validated == false) {
            return response() -> json([
                'error' => 'Business not yet validated.'
            ], 422);
        }
        $business -> directions = $request -> input('directions');
        $business -> save();
        return response() -> json([
            'message' => 'Business directions updated successfully.',
            'business' => $business
        ], 200);
    }

    public function addBusinessCurrency(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_currency' => 'required|numeric|exists:currencies,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $business = Business::find($user -> id_business);
        if($business == null) {
            return response() -> json([
                'error' => 'No business found for this user.'
            ], 404);
        } else if($business -> is_validated == false) {
            return response() -> json([
                'error' => 'Business not yet validated.'
            ], 422);
        }
        $legalCurrency = LegalCurrency::where('id_country', $business -> id_country)
                    -> where('id_currency', $request -> input('id_currency'))
                    -> first();
        if($legalCurrency == null) {
            return response() -> json([
                'error' => 'Currency not allowed in its country.'
            ], 400);
        }
        $acceptedCurrency = AcceptedCurrency::where('id_business', $business -> id)
                    -> where('id_currency', $request -> input('id_currency'))
                    -> first();
        if($acceptedCurrency != null) {
            return response() -> json([
                'error' => 'Currency already added.'
            ], 400);
        }
        $newAcceptedCurrency = AcceptedCurrency::create([
            'id_currency' => $request -> input('id_currency'),
            'id_business' => $business -> id,
        ]);
        $acceptedList = AcceptedCurrency::where('id_business', $business -> id) -> get() -> pluck('id_currency');
        $currencies = Currency::whereIn('id', $acceptedList) -> get();
        return response() -> json([
            'message' => 'Accepted currency added successfully.',
            'acceptedCurrencies' => $currencies
        ], 200);
    }

    public function removeBusinessCurrency(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_currency' => 'required|numeric|exists:currencies,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $business = Business::find($user -> id_business);
        if($business == null) {
            return response() -> json([
                'error' => 'No business found for this user.'
            ], 404);
        } else if($business -> is_validated == false) {
            return response() -> json([
                'error' => 'Business not yet validated.'
            ], 422);
        }
        $acceptedCurrency = AcceptedCurrency::where('id_business', $business -> id)
                    -> where('id_currency', $request -> input('id_currency'))
                    -> first();
        if($acceptedCurrency != null) {
            $acceptedCurrency -> delete();
        } else {
            return response() -> json([
                'error' => 'Currency not yet accepted.'
            ], 400);
        }
        $acceptedList = AcceptedCurrency::where('id_business', $business -> id) -> get() -> pluck('id_currency');
        $currencies = Currency::whereIn('id', $acceptedList) -> get();
        return response() -> json([
            'message' => 'Accepted currency removed successfully.',
            'acceptedCurrencies' => $currencies,
        ], 200);
    }
}