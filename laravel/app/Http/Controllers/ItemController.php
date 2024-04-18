<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Utils;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\Item;
use App\Models\Favourite;
use App\Models\Comment;

class ItemController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['addItemsToFutureProducts']]);
    }

    public function getItem($id) {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|integer|exists:items,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $item = Item::find($id);
        $product = Product::find($item -> id_product);
        $business = Utils::findBusinessFromProduct($product -> id);
        $is_favourite = false;
        $is_favourite_ = Favourite::where('id_business', $business -> id)
            -> where('id_user', Auth::user() -> id) -> first();
        if($is_favourite_ != null) {
            $is_favourite = true;
        }
        $business -> rate = Utils::getBusinessRate($business -> id);
        $available = null;
        if($item != null) {
            $available = Utils::getAvailableAmountOfItem($item, $product);
        }
        $comments = Comment::where('id_business', $business -> id) -> get();
        $comments_expanded = new Collection();
        foreach($comments as $comment) {
            $user = User::find($comment -> id_user);
            $product -> makeHidden([
                'description',
            ]);
            $comment -> makeHidden([
                'id_user', 'id_business',
            ]);
            $business -> makeHidden([
                'longitude', 'latitude',
            ]);
            $user -> makeHidden([
                'real_name', 'real_surname', 'phone', 'phone_prefix', 'sex',
                'is_admin', 'id_business', 'email_verified',
                'last_login_date', 'last_longitude', 'last_latitude',
            ]);
            $comments_expanded -> push([
                'content' => $comment,
                'user' => $user,
            ]);
            $business -> comments = $comments_expanded;
        }
        return response() -> json([
            'item' => $item,
            'product' => $product,
            'business' => $business,
            'available' => $available,
            'is_favourite' => $is_favourite,
        ], 200);
    }

    public function getRecommendedItems(Request $request) {
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
        foreach($businesses as $business) {
            $distance = Utils::get2dDistance($request -> longitude, $request -> latitude, $business -> longitude, $business -> latitude);
            $business -> distance = $distance;
        }
        $businesses = $businesses -> values() -> take(30) -> all();
        $results = new Collection();
        foreach($businesses as $business) {
            $items = Item::where('id_product', $business -> id_breakfast_product)
                    -> orWhere('id_product', $business -> id_lunch_product)
                    -> orWhere('id_product', $business -> id_dinner_product)
                    -> get();
            foreach($items as $item) {
                if($item -> date == Carbon::today() -> startOfDay()
                    || $item -> date == Carbon::tomorrow() -> startOfDay()
                ){
                    $favourite = Favourite::where('id_business', $business -> id) -> where('id_user', $user -> id) -> first();
                    $is_favourite = ($favourite != null);
                    $product = Product::find($item -> id_product);
                    $product -> amount = Utils::getAvailableAmountOfItem($item, $product);
                    $product -> makeHidden([
                        'description', 'ending_date',
                        'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
                    ]);
                    $product -> favourite = $is_favourite;
                    $product -> type = Utils::getProductType($business -> id, $product -> id);
                    $business -> makeHidden([
                        'description', 'tax_id', 'is_validated',
                        'id_country', 'longitude', 'latitude', 'directions',
                        'id_breakfast_product', 'id_lunch_product', 'id_dinner_product', 'distance',
                    ]);
                    $business -> rate = Utils::getBusinessRate($business -> id);
                    $owner = User::where('id_business', $business -> id) -> first();
                    $owner -> makeHidden([
                        'real_name', 'real_surname', 'username', 'email', 'phone', 'phone_prefix', 'sex',
                        'last_latitude', 'last_longitude', 'last_login_date', 'email_verified', 'is_admin',
                    ]);
                    $results = $results -> push([
                        'product' => $product,
                        'business' => $business,
                        'user' => $owner,
                        'item' => $item,
                        'is_favourite' => $is_favourite,
                    ]);
                }
            }
        }
        $random_items = ($results -> count() >= 3)
            ? $results -> random(3)
            : $results;
        return response() -> json([
            'items' => $random_items,
        ], 200);
    }

    public function getNearbyItems(Request $request) {
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
        foreach($businesses as $business) {
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
                    $product -> type = Utils::getProductType($business -> id, $product -> id);
                    $business -> makeHidden([
                        'description', 'tax_id', 'is_validated',
                        'id_country', 'longitude', 'latitude', 'directions',
                        'id_breakfast_product', 'id_lunch_product', 'id_dinner_product', 'distance',
                    ]);
                    $business -> rate = Utils::getBusinessRate($business -> id);
                    $owner = User::where('id_business', $business -> id) -> first();
                    $owner -> makeHidden([
                        'real_name', 'real_surname', 'username', 'email', 'phone', 'phone_prefix', 'sex',
                        'last_latitude', 'last_longitude', 'last_login_date', 'email_verified', 'is_admin',
                    ]);
                    $results = array_merge($results, [[
                        'product' => $product,
                        'business' => $business,
                        'user' => $owner,
                        'item' => $item,
                        'is_favourite' => $is_favourite,
                    ]]);
                }
            }
        }
        $sortedResults = collect($results) -> sortBy('item.date') -> values() -> all();
        return response() -> json([
            'items' => $results,
        ], 200);
    }
}