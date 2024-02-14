<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Models\Favourite;
use App\Models\Product;

class FavouriteController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function addFavourite(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_product' => 'required|integer|exists:products,id'
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $product = Product::find($request -> input('id_product'));
        if($product == null) {
            return response() -> json([
                'error' => 'Product not found.'
            ], 404);
        }
        $user = Auth::user();
        $favourite = Favourite::where('id_user', $user -> id)
                -> where('id_product', $request -> input('id_product')) 
                -> first();
        if($favourite != null) {
            return response() -> json([
                'error' => 'Product already saved to favourites.'
            ], 409);
        }
        $favourite = Favourite::create([
            'id_user' => $user -> id,
            'id_product' => $request -> input('id_product'),
        ]);
        return response() -> json([
            'message' => 'Product saved to favourites successfully.',
            'favourite' => $favourite,
        ], 201);
    }

    public function removeFavourite(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_product' => 'required|integer|exists:products,id'
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $favourite = Favourite::where('id_user', $user -> id)
                -> where('id_product', $request -> input('id_product')) 
                -> first();
        if($favourite == null) {
            return response() -> json([
                'error' => 'Product not yet saved to favourites.'
            ], 409);
        }
        $favourite -> delete();
        return response() -> json([
            'message' => 'Product removed from favourites successfully.',
            'favourite' => $favourite,
        ], 200);
    }
}