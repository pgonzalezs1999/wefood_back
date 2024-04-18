<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;

class ImageController extends Controller
{
    public function create(Request $request)
    {
        $images = new Image();
        $request -> validate([
            'id_user' => 'required',
            'meaning' => 'required',
            'image' => 'required|max:1024'
        ]);
        
        $filename = '';
        if($request -> hasFile('image')) {
            $filename = $request -> file('image') -> store('posts', 'public');
        } else {
            $filename = null;
        }

        $images -> id_user = $request -> input('id_user');
        $images -> meaning = $request -> input('meaning');
        $images -> image = $filename;
        $result = $images -> save();
        if($result) {
            return response() -> json(['succes' => true]);
        } else {
            return response() -> json(['succes' => false]);
        }
    }

    public function getImage(Request $request)
    {
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