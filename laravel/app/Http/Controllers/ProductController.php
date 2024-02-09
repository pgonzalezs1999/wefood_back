<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Models\Product;
use App\Models\Business;

class ProductController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function createProduct(Request $request) {
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
        $validator = Validator::make($request -> all(), [
            'description' => 'required|string|min:6|max:255',
            'price' => 'required|numeric|min:0.1',
            'amount' => 'required|integer|min:1',
            'ending_date' => 'nullable|date_format:Y-m-d H:i:s',
            'starting_hour' => 'required|date_format:H:i',
            'ending_hour' => 'required|date_format:H:i',
            'vegetarian' => 'required|boolean',
            'vegan' => 'required|boolean',
            'bakery' => 'required|boolean',
            'fresh' => 'required|boolean',
            'working_on_monday' => 'required|boolean',
            'working_on_tuesday' => 'required|boolean',
            'working_on_wednesday' => 'required|boolean',
            'working_on_thursday' => 'required|boolean',
            'working_on_friday' => 'required|boolean',
            'working_on_saturday' => 'required|boolean',
            'working_on_sunday' => 'required|boolean',
            'type' => 'required|string|in:B,L,D', // breakfast, lunch, dinner
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        if($request -> input('type') == 'B') {
            if($business -> id_breakfast_product != null) {
                return response() -> json([
                    'error' => 'Breakfast already exists.'
                ], 422);
            }
            $business -> id_breakfast_product = $product -> id;
        } else if($request -> input('type') == 'L') {
            if($business -> id_lunch_product != null) {
                return response() -> json([
                    'error' => 'Lunch already exists.'
                ], 422);
            }
            $business -> id_lunch_product = $product -> id;
        } else if($request -> input('type') == 'D') {
            if($business -> id_dinner_product != null) {
                return response() -> json([
                    'error' => 'Dinner already exists.'
                ], 422);
            }
            $business -> id_dinner_product = $product -> id;
        } else {
            return response() -> json([
                'error' => 'Invalid type.'
            ], 422);
        }
        $business -> save();
        $product = Product::create([
            'description' => $request -> input('description'),
            'price' => $request -> input('price'),
            'amount' => $request -> input('amount'),
            'ending_date' => $request -> input('ending_date'),
            'starting_hour' => $request -> input('starting_hour'),
            'ending_hour' => $request -> input('ending_hour'),
            'vegetarian' => $request -> input('vegetarian'),
            'vegan' => $request -> input('vegan'),
            'bakery' => $request -> input('bakery'),
            'fresh' => $request -> input('fresh'),
            'working_on_monday' => $request -> input('working_on_monday'),
            'working_on_tuesday' => $request -> input('working_on_tuesday'),
            'working_on_wednesday' => $request -> input('working_on_wednesday'),
            'working_on_thursday' => $request -> input('working_on_thursday'),
            'working_on_friday' => $request -> input('working_on_friday'),
            'working_on_saturday' => $request -> input('working_on_saturday'),
            'working_on_sunday' => $request -> input('working_on_sunday'),
        ]);
        return response() -> json([
            'message' => 'Product created successfully.',
            'product' => $product
        ], 201);
    }

    public function deleteProduct(Request $request) {
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
        $validator = Validator::make($request -> all(), [
            'type' => 'required|string|in:B,L,D', // breakfast, lunch, dinner
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        if($request -> input('type') == 'B') {
            $product = Product::find($business -> id_breakfast_product);
            if($product == null) {
                return response() -> json([
                    'error' => 'No breakfast found for this business.'
                ], 404);
            }
            $business -> id_breakfast_product = null;
        } else if($request -> input('type') == 'L') {
            $product = Product::find($business -> id_lunch_product);
            if($product == null) {
                return response() -> json([
                    'error' => 'No lunch found for this business.'
                ], 404);
            }
            $business -> id_lunch_product = null;
        } else if($request -> input('type') == 'D') {
            $product = Product::find($business -> id_dinner_product);
            if($product == null) {
                return response() -> json([
                    'error' => 'No dinner found for this business.'
                ], 404);
            }
            $business -> id_dinner_product = null;
        } else {
            return response() -> json([
                'error' => 'Invalid type.'
            ], 422);
        }
        $business -> save();
        $product -> delete();
        return response() -> json([
            'message' => 'Product deleted successfully.',
        ], 200);
    }
}