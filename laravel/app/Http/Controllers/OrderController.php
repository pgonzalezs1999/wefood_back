<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Auth;
use Validator;
use App\Utils;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Item;
use App\Models\Product;
use App\Models\Business;

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

    public function getPendingOrdersCustomer(Request $request) {
        $user = Auth::user();
        $orders = Order::where('id_user', $user -> id)
                -> whereNull('reception_date')
                -> get();
        $results = array();
        foreach($orders as $order) {
            $item = Item::find($order -> id_item);
            if($item != null) {
                if($item -> date >= Carbon::today() -> startOfDay()) {
                    $product = Product::find($item -> id_product);
                    if($product != null) {
                        $business = Business::where('id_breakfast_product', $item -> id)
                                -> orWhere('id_lunch_product', $item -> id)
                                -> orWhere('id_dinner_product', $item -> id)
                                -> first();
                        if($business != null) {
                            $business -> makeHidden([
                                'description', 'tax_id', 'is_validated',
                                'id_breakfast_product', 'id_lunch_product', 'id_dinner_product',
                                'id_currency', 'id_country',
                                'directions', 'longitude', 'latitude',
                            ]);
                            $product -> makeHidden([
                                'description', 'price', 'ending_date',
                                'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
                                'vegetarian', 'vegan', 'fresh', 'bakery',
                            ]);
                            $item -> makeHidden([
                                'id_product',
                            ]);
                            $order -> makeHidden([
                                'id_user', 'id_item', 'id_payment',
                                'reception_date', 'reception_method', 'order_date',
                            ]);
                            $result = [
                                'business' => $business,
                                'product' => $product,
                                'item' => $item,
                                'order' => $order,
                            ];
                            $results[] = $result;
                        }
                    }
                }
            }
        }
        return response() -> json([
            'results' => $results,
        ], 200);
    }

    public function getPendingOrdersBusiness(Request $request) {
        $products = Product::where('id', $request -> input('mw_business') -> id_breakfast_product)
                -> orWhere('id', $request -> input('mw_business') -> id_lunch_product)
                -> orWhere('id', $request -> input('mw_business') -> id_dinner_product)
                -> get() -> pluck('id');
        $items = Item::whereIn('id_product', $products)
                -> where('date', '>=', Carbon::today() -> startOfDay())
                -> get() -> pluck('id');
        $orders = Order::whereIn('id_item', $items) -> get();
        foreach ($orders as $order) {
            $order->makeHidden([
                'id_user', 'id_payment', 'reception_date', 'reception_method', 'order_date',
            ]);
        }
        return response() -> json([
            'orders' => $orders,
        ], 200);
    }
}
