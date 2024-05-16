<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Validator;
use Auth;
use App\Models\User;
use App\Models\Comment;
use App\Models\Product;
use App\Models\Image;

class CommentController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function addComment(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_business' => 'required|integer|exists:businesses,id',
            'message' => 'nullable|string|max:500',
            'rate' => 'required|numeric|min:0.5|max:5',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $comment = Comment::where('id_user', $user -> id)
                -> where('id_business', $request -> input('id_business'))
                -> first();
        if($comment != null) {
            return response() -> json([
                'error' => 'Already sent a comment to this product.'
            ], 409);
        }
        $comment = Comment::create([
            'id_user' => $user -> id,
            'id_business' => $request -> input('id_business'),
            'message' => $request -> input('message'),
            'rate' => $request -> input('rate'),
        ]);
        return response() -> json([
            'message' => 'Comment sent to product successfully.',
            'comment' => $comment,
        ], 201);
    }

    public function deleteComment(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_business' => 'required|integer|exists:businesses,id'
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $comment = Comment::where('id_user', $user -> id)
                -> where('id_business', $request -> input('id_business')) 
                -> first();
        if($comment == null) {
            return response() -> json([
                'error' => 'Not yet commented to this product.'
            ], 409);
        }
        $comment -> delete();
        return response() -> json([
            'message' => 'Comment removed successfully.',
        ], 200);
    }

    public function getCommentsFromBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_business' => 'required|integer|exists:businesses,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $comments = Comment::where('id_business', $request -> input('id_business')) -> get();
        $results = new Collection();
        if(count($comments) > 0) {
            foreach($comments as $comment) {
                $user = User::find($comment -> id_user);
                $user -> makeHidden([
                    'real_name', 'real_surname', 'phone', 'phone_prefix', 'sex',
                    'is_admin', 'id_business', 'email_verified',
                    'last_login_date', 'last_longitude', 'last_latitude',
                ]);
                $image = Image::where('id_user', $user -> id) -> where('meaning', 'profile') -> first();
                if($image != null) {
                    $image -> makeHidden([
                        'id_user', 'meaning',
                    ]);
                }
                $results -> push([
                    'content' => $comment,
                    'user' => $user,
                    'image' => $image,
                ]);
            }
        }
        return response() -> json([
            'comments' => $results,
        ], 200);
    }
}