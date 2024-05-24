<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Auth;
use Validator;
use Illuminate\Support\Collection;
use App\Utils;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Item;
use App\Models\Product;
use App\Models\Business;
use App\Models\User;

class OrderController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function orderItem(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_item' => 'required|numeric|exists:items,id',
            'amount' => 'required|integer|min:1',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $user = Auth::user();
        $item = Item::find($request -> input('id_item'));
        if($item == null) {
            return response() -> json([
                'error' => 'Item not found.',
            ], 404);
        }
        $product = Product::find($item -> id_product);
        if($product == null) {
            return response() -> json([
                'error' => 'Product not found.',
            ], 404);
        }
        $business = Business::find($product -> id_business);
        $mealType = strtoupper($business -> product_type);
        $parentProduct = Product::find($item -> id_product);
        $availableItems = Utils::getAvailableAmountOfItem($item, $parentProduct);
        if($request -> input('amount') > $availableItems) {
            return response() -> json([
                'error' => 'Not enough items available.'
            ], 422);
        }
        if($item -> date < Carbon::today() -> startOfDay()) {
            return response() -> json([
                'error' => 'Item not available anymore.'
            ], 422);
        }
        $order = Order::create([
            'id_user' => $user -> id,
            'order_date' => Carbon::now(),
            'id_payment' => 1,
            'id_item' => $request -> input('id_item'),
            'amount' => $request -> input('amount'),
            'id_business' => $business -> id,
            'meal_type' => $mealType,
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
        $results = new Collection();
        foreach($orders as $order) {
            $item = Item::find($order -> id_item);
            if($item != null) {
                if($item -> date >= Carbon::today() -> startOfDay()) {
                    $product = Product::find($item -> id_product);
                    if($product != null) {
                        $business = Business::find($product -> id_business);
                        if($business != null) {
                            $business -> makeHidden([
                                'description', 'tax_id', 'is_validated',
                                'id_currency', 'id_country',
                                'directions', 'longitude', 'latitude',
                                'created_at',
                            ]);
                            $owner = User::where('id_business', $business -> id) -> first();
                            $owner -> makeHidden([
                                'real_name', 'real_surname', 'username', 'email', 'phone', 'phone_prefix', 'sex',
                                'last_latitude', 'last_longitude', 'last_login_date', 'id_business', 'is_admin', 'email_verified',
                            ]);
                            $product -> makeHidden([
                                'description', 'ending_date',
                                'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
                                'vegetarian', 'mediterranean', 'dessert', 'junk', 'amount',
                            ]);
                            $product -> type = $order -> meal_type;
                            $item -> makeHidden([
                                'id_product',
                            ]);
                            $order -> makeHidden([
                                'id_user', 'id_item', 'id_payment','id_business',
                                'reception_date', 'reception_method', 'order_date',
                                'meal_type',
                            ]);
                            $result = [
                                'business' => $business,
                                'user' => $owner,
                                'product' => $product,
                                'item' => $item,
                                'order' => $order,
                            ];
                            $results = $results -> push($result);
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
        $products = Product::where('id_business', $request -> input('mw_business') -> id) -> get() -> pluck('id');
        $items = Item::whereIn('id_product', $products)
                -> where('date', '>=', Carbon::today() -> startOfDay())
                -> get() -> pluck('id');
        $orders = Order::whereIn('id_item', $items) -> get();
        foreach ($orders as $order) {
            $order->makeHidden([
                'id_user', 'id_payment', 'reception_date', 'order_date',
            ]);
        }
        return response() -> json([
            'orders' => $orders,
        ], 200);
    }

    public function getOrderHistoryCustomer() {
        $user = Auth::user();
        $results = new Collection();
        $orders = Order::where('id_user', $user -> id) -> get();
        foreach($orders as $order) {
            $item = Item::withTrashed() -> find($order -> id_item);
            if($item != null) {
                $product = Product::withTrashed() -> find($item -> id_product);
                if($product != null) {
                    $business = Business::withTrashed() -> find($product -> id_business);
                    if($business != null) {
                        $owner = User::withTrashed() -> where('id_business', $business -> id) -> first();
                        $owner -> makeHidden([
                            'id_business', 'real_name', 'real_surname', 'username', 'email', 'phone', 'phone_prefix', 'sex',
                            'last_login_date', 'last_latitude', 'last_longitude', 'is_admin', 'email_verified',
                        ]);
                        $business -> makeHidden([
                            'description', 'tax_id', 'is_validated',
                            'id_currency', 'id_country',
                            'directions', 'longitude', 'latitude',
                            'created_at',
                        ]);
                        $product -> makeHidden([
                            'id', 'id_business', 'description', 'ending_date',
                            'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
                            'vegetarian', 'mediterranean', 'dessert', 'junk',
                            'starting_hour', 'ending_hour', 'amount',
                        ]);
                        $order -> makeHidden([
                            'id', 'id_user', 'id_item', 'id_payment', 'reception_method', 'reception_date',
                        ]);
                        $results -> push([
                            'user' => $owner,
                            'business' => $business,
                            'product' => $product,
                            'order' => $order,
                        ]);
                    }
                }
            }
        }
        return response() -> json([
            'orders' => $results,
        ], 200);
    }

    public function getOrderHistoryBusiness(Request $request) {
        $orders = Order::where('id_business', $request -> input('mw_business') -> id) -> get();
        $results = array();
        foreach($orders as $order) {
            $order -> makeHidden([
                'id_item', 'order_date', 'id_payment', 'id_business',
            ]);
            $item = Item::find($order -> id_item) -> withTrashed() -> first();
            $item -> makeHidden([
                'id', 'id_product',
            ]);
            $product = Product::find($item -> id_product) -> withTrashed() -> first();
            $product -> makeHidden([
                'id', 'description', 'ending_date',
                'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
                'starting_hour', 'ending_hour',
            ]);
            $results[] = [
                'order' => $order,
                'item' => $item,
                'product' => $product,
            ];
        }
        return response() -> json([
            'results' => $results,
        ], 200);
    }

    public function completeOrderCustomer(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_order' => 'required|integer|exists:orders,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $order = Order::find($request -> input('id_order'));
        if($order -> id_user != Auth::user() -> id) {
            return response() -> json([
                'error' => 'You are not allowed to complete this order.'
            ], 403);
        }
        if($order -> reception_date != null) {
            return response() -> json([
                'error' => 'Order already completed.'
            ], 422);
        }
        $order -> reception_date = Carbon::now();
        $order -> reception_method = 'PM'; // picked up manually
        $order -> save();
        return response() -> json([
            'message' => 'Order completed successfully.',
            'order' => $order,
        ], 200);
    }

    public function completeOrderBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id_order' => 'required|integer|exists:orders,id',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $order = Order::find($request -> input('id_order'));
        $item = Item::find($order -> id_item);
        $product = Product::find($item -> id_product);
        $business = Business::find($product -> id_business);
        if($business -> id != $request -> input('mw_business') -> id) {
            return response() -> json([
                'error' => 'Order not belonging to this business.'
            ], 403);
        }
        if($order -> reception_date != null) {
            return response() -> json([
                'error' => 'Order already completed.'
            ], 422);
        }
        if($item -> date < Carbon::today() -> startOfDay()) {
            return response() -> json([
                'error' => 'Order not available anymore.'
            ], 422);
        }
        $order -> reception_date = Carbon::now();
        $order -> reception_method = 'PM'; // picked up manually
        $order -> save();
        return response() -> json([
            'message' => 'Order completed successfully.',
            'order' => $order,
        ], 200);
    }
}