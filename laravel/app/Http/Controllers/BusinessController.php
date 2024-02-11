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
            'name' => $request -> input('name'),
            'description' => $request -> input('description'),
            'tax_id' => $request -> input('tax_id'),
            'directions' => $request -> input('directions'),
        ]);
        $user = User::create([
            'username' => $request -> input('email'),
            'email' => $request -> input('email'),
            'password' => bcrypt($request -> input('password')),
            'phone_prefix' => $request -> input('phone_prefix'),
            'phone' => $request -> input('phone'),
            'id_business' => $business -> id,
        ]);
        try {
            $user_id = $user -> id;
            $image_path = "storage/images/{$user_id}";
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

    public function getSessionBusiness(Request $request) {
        $request -> input('mw_user') -> makeHidden([
            'created_at', 'updated_at', 'deleted_at',
            'last_login_date', 'last_latitude', 'last_longitude',
            'id_business', 'is_admin', 'sex',
        ]);

        $request -> input('mw_business') -> makeHidden([
            'created_at', 'updated_at', 'deleted_at',
            'longitude', 'latitude', 'is_validated',
        ]);
        return response() -> json([
            'user' => $request -> input('mw_user'),
            'business' => $request -> input('mw_business'),
        ], 200);
    }

    public function getBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id' => 'required|numeric|exists:businesses,id',
        ]);
        if($validator -> fails()) {
            echo $validator -> errors() -> toJson();
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $business = Business::find($request -> input('id'));
        $business -> makeHidden([
            'created_at', 'updated_at', 'deleted_at',
            'longitude', 'latitude', 'is_validated',
            'tax_id', 'id_country',
        ]);
        return response() -> json([
            $business,
        ], 200);
    }

    public function deleteBusiness(Request $request) {
        $request -> input('mw_user') -> delete();
        $request -> input('mw_business') -> delete();
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
        $request -> input('mw_business') -> name = $request -> input('name');
        $request -> input('mw_business') -> save();
        return response() -> json([
            'message' => 'Business name updated successfully.',
            'business' => $request -> input('mw_business')
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
        $request -> input('mw_business') -> description = $request -> input('description');
        $request -> input('mw_business') -> save();
        return response() -> json([
            'message' => 'Business description updated successfully.',
            'business' => $request -> input('mw_business'),
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
        
        $request -> input('mw_business') -> directions = $request -> input('directions');
        $request -> input('mw_business') -> save();
        return response() -> json([
            'message' => 'Business directions updated successfully.',
            'business' => $request -> input('mw_business'),
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
        $legalCurrency = LegalCurrency::where('id_country', $request -> input('mw_business') -> id_country)
                    -> where('id_currency', $request -> input('id_currency'))
                    -> first();
        if($legalCurrency == null) {
            return response() -> json([
                'error' => 'Currency not allowed in its country.'
            ], 400);
        }
        $acceptedCurrency = AcceptedCurrency::where('id_business', $request -> input('mw_business') -> id)
                    -> where('id_currency', $request -> input('id_currency'))
                    -> first();
        if($acceptedCurrency != null) {
            return response() -> json([
                'error' => 'Currency already added.'
            ], 400);
        }
        $newAcceptedCurrency = AcceptedCurrency::create([
            'id_currency' => $request -> input('id_currency'),
            'id_business' => $request -> input('mw_business') -> id,
        ]);
        $acceptedList = AcceptedCurrency::where('id_business', $request -> input('mw_business') -> id) -> get() -> pluck('id_currency');
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
        $acceptedCurrency = AcceptedCurrency::where('id_business', $request -> input('mw_business') -> id)
                    -> where('id_currency', $request -> input('id_currency'))
                    -> first();
        if($acceptedCurrency != null) {
            $acceptedCurrency -> delete();
        } else {
            return response() -> json([
                'error' => 'Currency already not accepted.'
            ], 400);
        }
        $acceptedList = AcceptedCurrency::where('id_business', $request -> input('mw_business') -> id) -> get() -> pluck('id_currency');
        $currencies = Currency::whereIn('id', $acceptedList) -> get();
        return response() -> json([
            'message' => 'Accepted currency removed successfully.',
            'acceptedCurrencies' => $currencies,
        ], 200);
    }
}