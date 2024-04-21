<?php

namespace App\Http\Controllers;

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
        $image_find = Image::where('id_user', $request -> input('id_user')) -> where('id_user', $request -> input('id_user')) -> first();
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
        $image = Image::where('id_user', $request -> input('id_user')) -> where('meaning', $request -> input('meaning')) -> first();
        return response() -> json([
            'image' => $image
        ], 200);
    }
}