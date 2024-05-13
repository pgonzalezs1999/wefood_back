<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;

class ImageController extends Controller
{
    public function uploadImage(Request $request) {
        $request -> validate([
            'id_user' => 'required',
            'meaning' => 'required',
            'image' => 'required'
        ]);
        $image_find = Image::where('id_user', $request -> input('id_user')) -> where('meaning', $request -> input('meaning')) -> first();
        $image = null;
        if($image_find != null) {
            $image = $image_find;
        } else {
            $image = new Image();
        }
        
        $filename = '';
        if($request -> hasFile('image')) {
            $filename = $request -> file('image') -> store('posts', 'public');
        } else {
            $filename = null;
        }

        $image -> id_user = $request -> input('id_user');
        $image -> meaning = $request -> input('meaning');
        $image -> image = $filename;
        $result = $image -> save();
        if($result) {
            return response() -> json([
                'image' => $image
            ], 200);
        } else {
            return response() -> json([
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function getImage(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_user' => 'required',
            'meaning' => 'required',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $image;
        if(strlen($request -> input('meaning')) < 4) { // If is everything but "profile"
            $user = User::find($request -> input('id_user'));
            $business = Business::find($user -> id_business);
            if(strtolower($request -> input('meaning')[0]) == 'b') {
                $product = Product::where('id_business', $business -> id) -> where('product_type', 'b') -> first();
            } else if(strtolower($request -> input('meaning')[0]) == 'l') {
                $product = Product::where('id_business', $business -> id) -> where('product_type', 'l') -> first();
            } else if(strtolower($request -> input('meaning')[0]) == 'd') {
                $product = Product::where('id_business', $business -> id) -> where('product_type', 'd') -> first();
            }
            if($product != null) {
                $image = Image::where('id_user', $request -> input('id_user')) -> where('meaning', $request -> input('meaning')) -> first();
            }
        } else { // If is "profile"
            $image = Image::where('id_user', $request -> input('id_user')) -> where('meaning', $request -> input('meaning')) -> first();
        }
        return response() -> json([
            'image' => $image
        ], 200);
    }

    public function removeImage(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_user' => 'required',
            'meaning' => 'required',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors()
            ], 400);
        }
        $image = Image::where('id_user', $request -> input('id_user'))->where('meaning', $request -> input('meaning')) -> first();
        if($image == null) {
            return response() -> json([
                'error' => 'Image not found'
            ], 404);
        }
        if($request -> input('meaning') === 'profile') {
            $image -> delete();
        } else {
            // All image with the same id_user and the same meaning letter
            $related_images = Image::where('id_user', $request -> input('id_user')) -> where('meaning', 'like', substr($request -> input('meaning'), 0, 1) . '%') -> get();
            $related_images = $related_images -> sortBy(function ($image) { // Sort by meaning number
                return intval(substr($image -> meaning, 1));
            });
            $deleted_image_number = intval(substr($request -> input('meaning'), 1));
            $image -> delete();
            foreach($related_images as $related_image) {
                $current_number = intval(substr($related_image -> meaning, 1));
                if($current_number > $deleted_image_number) {
                    $new_number = $current_number - 1;
                    $related_image -> meaning = substr($request -> input('meaning'), 0, 1) . $new_number;
                    $related_image -> save();
                }
            }
        }
        return response() -> json([
            'message' => 'Image removed successfully'
        ], 200);
    }
}