<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Validator;
use App\Utils;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Item;
use App\Models\Product;

class OrderController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function orderItem(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_item' => 'required|integer|exists:items,id',
            'amount' => 'required|integer|min:1',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $item = Item::find($request -> input('id_item'));
        $parentProduct = Product::find($item -> id_product);
        $availableItems = Utils::getAvailableAmountOfItem($item, $parentProduct);
        if($request -> input('amount') > $availableItems) {
            return response() -> json([
                'error' => 'Not enough items available.'
            ], 422);
        }
        $order = Order::create([
            'id_user' => $user -> id,
            'order_date' => Carbon::now(),
            'id_payment' => 1,
            'id_item' => $request -> input('id_item'),
            'amount' => $request -> input('amount'),
        ]);
        return response() -> json([
            'message' => 'Order created successfully.',
            'order' => $order,
        ], 201);
    }
}
