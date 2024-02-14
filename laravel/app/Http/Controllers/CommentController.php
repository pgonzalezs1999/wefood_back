<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;
use Auth;
use App\Models\Comment;
use App\Models\Product;

class CommentController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function addComment(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_product' => 'required|integer|exists:products,id',
            'message' => 'nullable|string|max:500',
            'rate' => 'required|integer|min:1|max:5',
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
        $comment = Comment::where('id_user', $user -> id)
                -> where('id_product', $request -> input('id_product')) 
                -> first();
        if($comment != null) {
            return response() -> json([
                'error' => 'Already sent a comment to this product.'
            ], 409);
        }
        $comment = Comment::create([
            'id_user' => $user -> id,
            'id_product' => $request -> input('id_product'),
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
            'id_product' => 'required|integer|exists:products,id'
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $comment = Comment::where('id_user', $user -> id)
                -> where('id_product', $request -> input('id_product')) 
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
}