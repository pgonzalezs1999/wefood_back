<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Utils;
use Carbon\Carbon;
use App\Models\Favourite;
use App\Models\Business;
use App\Models\Item;
use App\Models\Product;
use App\Models\User;
use App\Models\Image;

class FavouriteController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function addFavourite(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_business' => 'required|integer|exists:businesses,id'
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $business = Business::find($request -> input('id_business'));
        if($business == null) {
            return response() -> json([
                'error' => 'Business not found.'
            ], 404);
        }
        $user = Auth::user();
        $favourite = Favourite::where('id_user', $user -> id)
                -> where('id_business', $request -> input('id_business')) 
                -> first();
        if($favourite != null) {
            return response() -> json([
                'error' => 'Business already saved to favourites.'
            ], 409);
        }
        $favourite = Favourite::create([
            'id_user' => $user -> id,
            'id_business' => $request -> input('id_business'),
        ]);
        return response() -> json([
            'message' => 'Business saved to favourites successfully.',
            'favourite' => $favourite,
        ], 201);
    }

    public function removeFavourite(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_business' => 'required|integer|exists:businesses,id'
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $favourite = Favourite::where('id_user', $user -> id)
                -> where('id_business', $request -> input('id_business')) 
                -> first();
        if($favourite == null) {
            return response() -> json([
                'error' => 'Business not yet saved to favourites.'
            ], 409);
        }
        $favourite -> delete();
        return response() -> json([
            'message' => 'Business removed from favourites successfully.',
            'favourite' => $favourite,
        ], 200);
    }

    public function getFavouriteItems() {
        $user = Auth::user();
        $favourites = Favourite::where('id_user', $user -> id) -> get();
        $businesses = new Collection();
        foreach($favourites as $favourite) {
            $newBusiness = Business::find($favourite -> id_business);
            if($newBusiness != null) { // double check just in case business signout didn't delete favs properly
                $newBusiness -> makeHidden([
                    'tax_id', 'is_validated', 'description', 'directions',
                    'id_currency', 'id_country', 'longitude', 'latitude',
                ]);
                $newBusiness -> rate = Utils::getBusinessRate($newBusiness -> id);
                $businesses = $businesses -> push($newBusiness);
            }
        }
        $results = new Collection();
        if(count($businesses) > 0) {
            foreach($businesses as $business) {
                $items = Utils::getItemsFromBusiness($business);
                if($items != null && count($items) > 0) {
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
                            ]);
                            $business -> rate = Utils::getBusinessRate($business -> id);
                            $owner = User::where('id_business', $business -> id) -> first();
                            $owner -> makeHidden([
                                'real_name', 'real_surname', 'username', 'email', 'phone', 'phone_prefix', 'sex',
                                'last_latitude', 'last_longitude', 'last_login_date', 'email_verified', 'is_admin',
                            ]);
                            $image = Image::where('id_user', $owner -> id) -> where('meaning', $product -> product_type . '1') -> first();
                            if($image != null) {
                                $image -> makeHidden([
                                    'id_user',
                                ]);
                            }
                            $results = $results -> push([
                                'product' => $product,
                                'business' => $business,
                                'user' => $owner,
                                'item' => $item,
                                'is_favourite' => $is_favourite,
                                'image' => $image,
                            ]);
                        }
                    }
                }
            }
        }
        return response() -> json([
            'products' => $results,
        ], 201);
    }
}