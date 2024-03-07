<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Utils;
use App\Models\Favourite;
use App\Models\Business;

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

    public function getSessionFavourites() {
        $user = Auth::user();
        $favourites = Favourite::where('id_user', $user -> id) -> get();
        $businesses = Array();
        foreach($favourites as $favourite) {
            $newBusiness = Business::find($favourite -> id_business);
            $newBusiness -> makeHidden([
                'tax_id', 'is_validated',
                'id_breakfast_product', 'id_lunch_product', 'id_dinner_product',
                'id_currency', 'id_country', 'longitude', 'latitude',
            ]);
            $businesses = array_merge($businesses, Array($newBusiness));
        }
        return response() -> json([
            'favourites' => $businesses,
        ], 201);
    }

    public function getFavouriteProducts() {
        $user = Auth::user();
        $favourites = Favourite::where('id_user', $user -> id) -> get();
        $businesses = Array();
        foreach($favourites as $favourite) {
            $newBusiness = Business::find($favourite -> id_business);
            $newBusiness -> makeHidden([
                'tax_id', 'is_validated', 'description', 'directions',
                'id_breakfast_product', 'id_lunch_product', 'id_dinner_product',
                'id_currency', 'id_country', 'longitude', 'latitude',
            ]);
            $favourite = Favourite::where('id_business', $newBusiness -> id)
                -> where('id_user', $user -> id) -> first();
            $is_favourite = ($favourite != null);
            $newBusiness -> rate = Utils::getBusinessRate($newBusiness -> id);
            $businesses = array_merge($businesses, Array($newBusiness));
        }
        $results = new Collection();
        foreach($businesses as $business) {
            $business_products = Utils::getProductsFromBusiness($business -> id);
            if($business_products !== null) {
                foreach($business_products as $product) {
                    $product -> makeHidden([
                        'description', 'amount', 'ending_date',
                        'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
                    ]);
                    $results = $results -> push([
                        'product' => $product,
                        'business' => $business,
                        'favourite' => $is_favourite,
                    ]);
                }
            }
        }
        return response() -> json([
            'products' => $results,
        ], 201);
    }
}