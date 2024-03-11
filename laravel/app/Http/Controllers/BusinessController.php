<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Requests\PostStoreImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Utils;
use Auth;
use Validator;
use App\Models\User;
use App\Models\Business;
use App\Models\Favourite;
use App\Models\Comment;
use App\Models\Currency;
use App\Models\LegalCurrency;
use App\Models\AcceptedCurrency;
use App\Models\Order;
use App\Models\Product;
use App\Models\Item;

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
            'longitude' => 'required|numeric|min:-180|max:180',
            'latitude' => 'required|numeric|min:-90|max:90',
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
            'last_login_date', 'last_latitude', 'last_longitude',
            'id_business', 'is_admin', 'sex',
        ]);
        $request -> input('mw_business') -> makeHidden([
            'longitude', 'latitude', 'is_validated',
        ]);
        $favourites = Favourite::where('id_business', $request -> input('mw_business') -> id) -> count();
        $comments = Comment::where('id_business', $request -> input('mw_business') -> id) -> get();
        $products = Product::where('id', $request -> input('mw_business') -> id_breakfast_product)
                -> orWhere('id', $request -> input('mw_business') -> id_lunch_product)
                -> orWhere('id', $request -> input('mw_business') -> id_dinner_product)
                -> get() -> pluck('id');
        $items = Item::whereIn('id_product', $products) -> get() -> pluck('id');
        $totalOrders = Order::whereIn('id_item', $items) -> get() -> count();
        $pendingItems = Item::whereIn('id_product', $products)
                -> where(function($query) {
                    $query -> where('date', '>', Carbon::yesterday());
                })
                -> get() -> pluck('id');
        $pendingOrders = Order::whereIn('id_item', $pendingItems)
                -> where('reception_date', null) -> get();
        return response() -> json([
            'user' => $request -> input('mw_user'),
            'business' => $request -> input('mw_business'),
            'favourites' => $favourites,
            'comments' => $comments,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
        ], 200);
    }

    public function getBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_business' => 'required|numeric|exists:businesses,id',
        ]);
        if($validator -> fails()) {
            echo $validator -> errors() -> toJson();
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $business = Business::find($request -> input('id_business'));
        $business -> makeHidden([
            'id_breakfast_product', 'id_lunch_product', 'id_dinner_product',
            'longitude', 'latitude', 'is_validated',
            'tax_id', 'id_country',
        ]);
        $breakfast = Product::find($business -> id_breakfast_product);
        $lunch = Product::find($business -> id_lunch_product);
        $dinner = Product::find($business -> id_dinner_product);
        $favourites = Favourite::where('id_business', $request -> input('id_business')) -> count();
        $comments = Comment::where('id_business', $request -> input('id_business')) -> get();
        $products = Product::where('id', $business -> id_breakfast_product)
                -> orWhere('id', $business -> id_lunch_product)
                -> orWhere('id', $business -> id_dinner_product)
                -> get() -> pluck('id');
        $items = Item::whereIn('id_product', $products) -> get() -> pluck('id');
        $totalOrders = Order::whereIn('id_item', $items) -> get() -> count();
        return response() -> json([
            'business' => $business,
            'breakfast' => $breakfast,
            'lunch' => $lunch,
            'dinner' => $dinner,
            'favourites' => $favourites,
            'comments' => $comments,
            'totalOrders' => $totalOrders,
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

    public function getNearbyBusinesses(Request $request) {
        $validator = Validator::make($request -> all(), [
            'longitude' => 'required|numeric|min:-180|max:180',
            'latitude' => 'required|numeric|min:-90|max:90',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $businesses = Utils::getBusinessesFromDistance(
            $request -> latitude, $request -> longitude, 0.5
        );
        foreach ($businesses as $business) {
            $distance = Utils::get2dDistance($request -> longitude, $request -> latitude, $business -> longitude, $business -> latitude);
            $business -> distance = $distance;
        }
        $sortedBusinesses = $businesses -> sortBy('distance') -> values() -> take(30) -> all();
        $results = array();
        foreach($sortedBusinesses as $business) {
            $items = Item::where('id_product', $business -> id_breakfast_product)
                    -> orWhere('id_product', $business -> id_lunch_product)
                    -> orWhere('id_product', $business -> id_dinner_product)
                    -> get();
            foreach($items as $item) {
                if($item -> date == Carbon::today() -> startOfDay()
                    || $item -> date == Carbon::tomorrow() -> startOfDay()
                ){
                    $favourite = Favourite::where('id_business', $business -> id)
                            -> where('id_user', $user -> id) -> first();
                    $is_favourite = ($favourite != null);
                    $product = Product::find($item -> id_product);
                    $product -> amount = Utils::getAvailableAmountOfItem($item, $product);
                    $product -> makeHidden([
                        'description', 'ending_date',
                        'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
                    ]);
                    $product -> favourite = $is_favourite;
                    $business -> makeHidden([
                        'description', 'tax_id', 'is_validated',
                        'id_country', 'longitude', 'latitude', 'directions',
                        'id_breakfast_product', 'id_lunch_product', 'id_dinner_product', 'distance',
                    ]);
                    $business -> rate = Utils::getBusinessRate($business -> id);
                    $results = array_merge($results, [[
                        'product' => $product,
                        'business' => $business,
                        'item' => $item,
                        'is_favourite' => $is_favourite,
                    ]]);
                }
            }
        }
        $sortedResults = collect($results) -> sortBy('item.date') -> values() -> all();
        return response() -> json([
            'products' => $results,
        ], 200);
    }
}