<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Requests\PostStoreImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Utils;
use Illuminate\Support\Collection;
use Auth;
use Validator;
use App\Models\User;
use App\Models\Business;
use App\Models\Favourite;
use App\Models\Comment;
use App\Models\Currency;
use App\Models\Country;
use App\Models\LegalCurrency;
use App\Models\AcceptedCurrency;
use App\Models\Order;
use App\Models\Product;
use App\Models\Item;
use App\Models\Image;

class BusinessController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => [
            'createBusiness',
            'checkTaxIdAvailability',
            'checkValidity',
            'cancelValidation',
        ]]);
    }

    public function createBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            // Create linked user
            'email' => 'required|string|email|min:6|max:255|unique:users',
            'password' => 'required|string|min:6|max:15',
            'phone_prefix' => 'required|integer',
            'phone' => 'required|integer|unique:users',
            // Create business
            'name' => 'required|string|min:6|max:100',
            'description' => 'required|string|min:6|max:255',
            'tax_id' => 'required|string|min:6|max:50|unique:businesses',
            'directions' => 'required|string|min:6|max:255',
            'country' => 'required',
            'longitude' => 'required|numeric|min:-180|max:180',
            'latitude' => 'required|numeric|min:-90|max:90',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        
        $country = Country::where('google_maps_name', $request -> input('country')) -> first();
        if($country == null) {
            return [
                'error' => 'Country not found.',
                'code' => '404',
            ];
        }
        $business = Business::create([
            'name' => $request -> input('name'),
            'description' => $request -> input('description'),
            'tax_id' => $request -> input('tax_id'),
            'directions' => $request -> input('directions'),
            'country' => $country -> id,
            'longitude' => $request -> input('longitude'),
            'latitude' => $request -> input('latitude'),
        ]);
        $user = User::create([
            'username' => $request -> input('email'),
            'email' => $request -> input('email'),
            'password' => bcrypt($request -> input('password')),
            'phone_prefix' => $request -> input('phone_prefix'),
            'phone' => $request -> input('phone'),
            'id_business' => $business -> id,
        ]);
        /*try {
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
        }*/
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
            'is_validated',
        ]);
        $favourites = Favourite::where('id_business', $request -> input('mw_business') -> id) -> count();
        $comments = Comment::where('id_business', $request -> input('mw_business') -> id) -> get();
        $products = Product::where('id_business', $request -> input('mw_business') -> id) -> get() -> pluck('id');
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
        $user = Auth::User();
        $business = Business::find($request -> input('id_business'));
        $business -> makeHidden([
            'longitude', 'latitude', 'is_validated',
            'tax_id', 'id_country',
        ]);
        $owner = User::where('id_business', $business -> id) -> first();
        $owner -> makeHidden([
            'id_business', 'real_name', 'real_surname', 'username', 'email', 'phone', 'phone_prefix', 'sex',
            'last_login_date', 'last_latitude', 'last_longitude', 'is_admin', 'email_verified',
        ]);
        $image = Image::where('id_user', $owner -> id) -> first();
        $favourites = Favourite::where('id_business', $request -> input('id_business')) -> count();
        $is_favourite = Favourite::where('id_business', $request -> input('id_business')) -> where('id_user', $user -> id) -> count();
        $comments = Comment::where('id_business', $request -> input('id_business')) -> get();
        $requester_has_bought = Utils::userHasBoughtInBusiness($user, $business);
        $parsed_comments = new Collection();
        if(count($comments) > 0) {
            foreach($comments as $comment) {
                $comment_user = User::find($comment -> id_user);
                $comment -> makeHidden([
                    'id', 'id_user',
                ]);
                $comment_user -> makeHidden([
                    'real_name', 'real_surname', 'phone', 'phone_prefix', 'sex',
                    'is_admin', 'id_business', 'email_verified',
                    'last_login_date', 'last_longitude', 'last_latitude',
                ]);
                $image = Image::where('id_user', $comment_user -> id) -> where('meaning', 'profile') -> first();
                if($image != null) {
                    $image -> makeHidden([
                        'id_user', 'meaning',
                    ]);
                }
                $parsed_comments -> push([
                    'content' => $comment,
                    'user' => $comment_user,
                    'image' => $image,
                ]);
            }
        }
        $business -> comments = $parsed_comments;
        $products = Product::where('id_business', $business -> id) -> get() -> pluck('id');
        $items = Item::whereIn('id_product', $products) -> get() -> pluck('id');
        $totalOrders = Order::whereIn('id_item', $items) -> get() -> count();
        $business -> rate = (float) Utils::getBusinessRate($business -> id);
        return response() -> json([
            'user' => $owner,
            'business' => $business,
            'image' => $image,
            'favourites' => $favourites,
            'is_favourite' => ($is_favourite > 0),
            'total_orders' => $totalOrders,
            'requester_has_bought' => $requester_has_bought,
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
        $business -> is_validated = 1;
        $business -> save();
        return response() -> json([
            'message' => 'Business successfully validated.'
        ], 200);
    }

    public function refuseBusiness(Request $request) {
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
        $business -> is_validated = 2;
        $business -> save();
        $business -> delete();
        return response() -> json([
            'message' => 'Business refused validated.'
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
            'country' => 'required',
            'longitude' => 'required|numeric|min:-180|max:180',
            'latitude' => 'required|numeric|min:-90|max:90',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $country = Country::where('google_maps_name', $request -> input('country')) -> first();
        if($country == null) {
            return [
                'error' => 'Country not found.',
                'code' => '404',
            ];
        }
        $request -> input('mw_business') -> directions = $request -> input('directions');
        $request -> input('mw_business') -> id_country = $country -> id;
        $request -> input('mw_business') -> longitude = $request -> input('longitude');
        $request -> input('mw_business') -> latitude = $request -> input('latitude');
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
        $businesses = Utils::getBusinessesFromDistance(
            $request -> latitude, $request -> longitude, 0.05
        );
        foreach($businesses as $business) {
            $distance = Utils::get2dDistance($request -> longitude, $request -> latitude, $business -> longitude, $business -> latitude);
            $business -> distance = $distance;
        }
        $sortedBusinesses = $businesses -> sortBy('distance') -> values() -> take(30) -> all();
        $results = array();
        foreach($sortedBusinesses as $business) {
            $items = Utils::getItemsFromBusiness($business);
            if($items != null) {
                foreach($items as $item) {
                    if($item -> date == Carbon::today() -> startOfDay()
                        || $item -> date == Carbon::tomorrow() -> startOfDay()
                    ){
                        $product = Product::find($item -> id_product);
                        if(($item -> date == Carbon::today() -> startOfDay() && Carbon::parse($product -> ending_hour) -> isPast()) == false) {
                            $item -> makeHidden([
                                'id_product',
                            ]);
                            $product -> amount = Utils::getAvailableAmountOfItem($item, $product);
                            $product -> makeHidden([
                                'id', 'description', 'ending_date', 'id_business', 
                                'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
                            ]);
                            $business -> makeHidden([
                                'description', 'tax_id', 'is_validated',
                                'id_country', 'directions', 'distance', 'created_at', 'deleted_at',
                            ]);
                            $business -> rate = Utils::getBusinessRate($business -> id);
                            $owner = User::where('id_business', $business -> id) -> first();
                            if($owner != null) {
                                $owner -> makeHidden([
                                    'id_business', 'real_name', 'real_surname', 'username', 'email', 'phone_prefix', 'phone', 'sex',
                                    'last_login_date', 'is_admin', 'last_latitude', 'last_longitude', 'email_verified',
                                ]);
                                $image = Image::where('id_user', $owner -> id) -> where('meaning', $product -> product_type . '1') -> first();
                                if($image != null) {
                                    $image -> makeHidden([
                                        'id', 'id_user',
                                    ]);
                                }
                                $available = Utils::getAvailableAmountOfItem($item, $product);
                                $results = array_merge($results, [[
                                    'user' => $owner,
                                    'product' => $product,
                                    'business' => $business,
                                    'item' => $item,
                                    'image' => $image,
                                    'available' => $available,
                                ]]);
                            }
                        }
                    }
                }
            }
        }
        $sortedResults = collect($results) -> sortBy('item.date') -> values() -> all();
        return response() -> json([
            'items' => $results,
        ], 200);
    }

    public function checkTaxIdAvailability(Request $request) {
        $validator = Validator::make($request -> all(), [
            'tax_id' => 'required',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 400);
        }
        $tax_id = $request -> input('tax_id');
        $business = Business::where('tax_id', $tax_id) -> first();
        if($business == null) {
            return response() -> json([
                'availability' => true,
            ], 200);
        }
        return response() -> json([
            'availability' => false,
        ], 200);
    }

    public function checkValidity(Request $request) {
        $validator = Validator::make($request -> all(), [
            'username' => 'required',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 400);
        }
        $user = User::where('username', $request -> input('username')) -> first();
        if($user == null) {
            return response() -> json([
                'error' => 'User not found',
            ], 404);
        }
        $business = Business::find($user -> id_business);
        if($business == null) {
            return response() -> json([
                'error' => 'Business not found',
            ], 404);
        }
        $validity = $business -> is_validated;
        if($validity == 1 || $validity == '1' || $validity == true) {
            return response() -> json([
                'validity' => true,
            ], 200);
        } else {
            return response() -> json([
                'validity' => false,
            ], 200);
        }
    }

    public function cancelValidation(Request $request) {
        $validator = Validator::make($request -> all(), [
            'username' => 'required',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 400);
        }
        $user = User::where('username', $request -> input('username')) -> first();
        if($user == null) {
            return response() -> json([
                'error' => 'User not found',
            ], 404);
        }
        $business = Business::find($user -> id_business);
        if($business == null) {
            return response() -> json([
                'error' => 'Business not found',
            ], 404);
        }
        $validity = $business -> is_validated;
        if($validity == 0 && $validity != '0' && $validity != false) {
            return response() -> json([
                'error' => 'Business not unsubmitable',
            ], 404);
        }
        $business -> is_validated = 2; // 0 = not validated; 1 = validated; 2 = cancelled while waiting for validation
        $business -> save();
        return response() -> json([
            'message' => 'Business unsubmited successfully',
        ], 200);
    }

    public function businessProductsResume(Request $request) {
        $request -> input('mw_user') -> makeHidden([
            'last_login_date', 'last_latitude', 'last_longitude',
            'id_business', 'is_admin', 'sex',
        ]);
        $request -> input('mw_business') -> makeHidden([
            'is_validated',
        ]);
        $breakfast = Product::where('id_business', $request -> input('mw_business') -> id) -> where('product_type', 'b') -> first();
        $lunch = Product::where('id_business', $request -> input('mw_business') -> id) -> where('product_type', 'l') -> first();
        $dinner = Product::where('id_business', $request -> input('mw_business') -> id) -> where('product_type', 'd') -> first();
        return response() -> json([
            'breakfast' => $breakfast,
            'lunch' => $lunch,
            'dinner' => $dinner,
        ], 200);
    }

    public function getValidatableBusinesses() {
        $businesses = Business::where('is_validated', 0) -> get();
        $result = new Collection();
        foreach($businesses as $business) {
            $user = User::where('id_business', $business -> id) -> first();
            if($user != null) {
                $user = $user -> makeHidden(
                    'id_business', 'real_name', 'real_surname', 'sex', 'is_admin',
                    'last_login_date', 'last_longitude', 'last_latitude',
                );
                $business = $business -> makeHidden(
                    'id_country', 'is_validated', 'deleted_at',
                );
                $result = $result -> push([
                    'user' => $user,
                    'business' => $business,
                ]);
            }
        }
        return response() -> json([
            'results' => $result,
        ], 200);
    }

    public function updateBankInfo(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_business' => 'required|exists:businesses,id',
            'bank_owner_name' => 'required',
            'bank_name' => 'required',
            'bank_account' => 'required',
            'bank_account_type' => 'required',
            'interbank_account' => 'required',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 400);
        }
        $user = $request -> input('mw_user');
        $business = Business::find($request -> input('id_business'));
        if($user -> id_business != $business -> id) {
            return response() -> json([
                'error' => 'Not your business',
            ], 403);
        }
        $business -> bank_owner_name = $request -> input('bank_owner_name');
        $business -> bank_name = $request -> input('bank_name');
        $business -> bank_account = $request -> input('bank_account');
        $business -> bank_account_type = $request -> input('bank_account_type');
        $business -> interbank_account = $request -> input('interbank_account');
        $business -> save();
        return response() -> json([
            'message' => 'Bank data updated successfully',
        ], 200);
    }
}