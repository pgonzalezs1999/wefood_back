<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
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
            $businesses = array_merge($businesses, Array(Business::find($favourite -> id_business)));
        }
        return response() -> json([
            'favourites' => $businesses,
        ], 201);
    }
}