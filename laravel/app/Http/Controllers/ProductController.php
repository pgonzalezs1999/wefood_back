<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Auth;
use Carbon\Carbon;
use Validator;
use App\Models\Product;
use App\Models\Item;
use App\Models\Order;
use App\Models\Business;
use App\Utils;

class ProductController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function createProduct(Request $request) {
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
        try {
            if($request -> input('type') == 'B') {
                if($request -> input('mw_business') -> id_breakfast_product != null) {
                    return response() -> json([
                        'error' => 'Breakfast already exists.'
                    ], 422);
                }
                $request -> input('mw_business') -> id_breakfast_product = $product -> id;
            } else if($request -> input('type') == 'L') {
                if($request -> input('mw_business') -> id_lunch_product != null) {
                    return response() -> json([
                        'error' => 'Lunch already exists.'
                    ], 422);
                }
                $request -> input('mw_business') -> id_lunch_product = $product -> id;
            } else if($request -> input('type') == 'D') {
                if($request -> input('mw_business') -> id_dinner_product != null) {
                    return response() -> json([
                        'error' => 'Dinner already exists.'
                    ], 422);
                }
                $request -> input('mw_business') -> id_dinner_product = $product -> id;
            } else {
                return response() -> json([
                    'error' => 'Invalid type.'
                ], 422);
            }
            $request -> input('mw_business') -> save();
        } catch(\Exception $e) {
            $product -> forceDelete();
            print_r($e -> getMessage());
            return response() -> json([
                'error' => 'Could not create the product.'
            ], 500);
        }
        $todayField = 'working_on_' . strtolower(Carbon::now() -> englishDayOfWeek);
        $tomorrowField = 'working_on_' . strtolower(Carbon::tomorrow() -> englishDayOfWeek);
        $afterTomorrowField = 'working_on_' . strtolower(Carbon::tomorrow() -> addDay() -> englishDayOfWeek);
        $weekDays = [ $todayField, $tomorrowField, $afterTomorrowField ];
        $dates = [
            Carbon::now() -> startOfDay(),
            Carbon::tomorrow() -> startOfDay(),
            Carbon::tomorrow() -> addDay() -> startOfDay(),
        ];
        for($i = 0; $i < 3; $i++) {
            if($product -> {$weekDays[$i]} == 1) {
                Item::create([
                    'id_product' => $product -> id,
                    'date' => $dates[$i],
                ]);
            }
        }
        return response() -> json([
            'message' => 'Product created successfully.',
            'product' => $product
        ], 201);
    }

    public function deleteProduct(Request $request) {
        $validator = Validator::make($request -> all(), [
            'type' => 'required|string|in:B,L,D', // breakfast, lunch, dinner
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $chosenField = '';
        if($request -> input('type') == 'B') {
            $chosenField = 'id_breakfast_product';
        } else if($request -> input('type') == 'L') {
            $chosenField = 'id_lunch_product';
        } else if($request -> input('type') == 'D') {
            $chosenField = 'id_dinner_product';
        } else {
            return response() -> json([
                'error' => 'Invalid type.'
            ], 422);
        }
        $product = Product::find($request -> input('mw_business') -> $chosenField);
        if($product == null) {
            return response() -> json([
                'error' => 'No ' . $chosenField . ' found for this business.'
            ], 404);
        }
        $items = Item::where('id_product', $product -> id) -> get();
        foreach($items as $item) {
            $orders = Order::where('id_item', $item -> id) -> get();
            foreach($orders as $order) {
                $reception_deadLine = $item -> date -> addDay() -> startOfDay();
                if(
                    Carbon::now() < $reception_deadLine &&
                    $order -> reception_date == null
                ) {
                    return response() -> json([
                        'error' => 'Cannot delete the product. There are pending orders.'
                    ], 422);
                }
            }
        }
        foreach($items as $item) {
            $item -> delete();
        }
        $request -> input('mw_business') -> $chosenField = null;
        $request -> input('mw_business') -> save();
        $product -> delete();
        return response() -> json([
            'message' => 'Product deleted successfully.',
        ], 200);
    }

    public function updateProduct(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id' => 'required|integer|exists:products,id',
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
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        if($request -> input('id') != $request -> input('mw_business') -> id_breakfast_product &&
           $request -> input('id') != $request -> input('mw_business') -> id_lunch_product &&
           $request -> input('id') != $request -> input('mw_business') -> id_dinner_product
        ) {
            return response() -> json([
                'error' => 'This product does not belong to this business.'
            ], 422);
        }
        $product = Product::find($request -> input('id'));
        $product -> description = $request -> input('description');
        $product -> price = $request -> input('price');
        $product -> amount = $request -> input('amount');
        $product -> ending_date = $request -> input('ending_date');
        $product -> starting_hour = $request -> input('starting_hour');
        $product -> ending_hour = $request -> input('ending_hour');
        $product -> vegetarian = $request -> input('vegetarian');
        $product -> vegan = $request -> input('vegan');
        $product -> bakery = $request -> input('bakery');
        $product -> fresh = $request -> input('fresh');
        $product -> working_on_monday = $request -> input('working_on_monday');
        $product -> working_on_tuesday = $request -> input('working_on_tuesday');
        $product -> working_on_wednesday = $request -> input('working_on_wednesday');
        $product -> working_on_thursday = $request -> input('working_on_thursday');
        $product -> working_on_friday = $request -> input('working_on_friday');
        $product -> working_on_saturday = $request -> input('working_on_saturday');
        $product -> working_on_sunday = $request -> input('working_on_sunday');
        $product -> save();
        return response() -> json([
            'message' => 'Product created successfully.',
            'product' => $product,
        ], 201);
    }

    public function addProductImage(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id' => 'required|integer|exists:products,id',
            'image' => 'required|file|max:2048|image',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $type = Utils::getProductType($request -> input('mw_business') -> id, $request -> input('id'));
        if($type == null) {
            return response() -> json([
                'error' => 'This product does not belong to this business.'
            ], 422);
        }
        try {
            $image_path = "storage/images/{$request -> input('mw_user') -> id}/business/{$type}";
            $nextImageNumber = Utils::getNextImageNumber($image_path);
            if($nextImageNumber >= 10) {
                return response() -> json([
                    'error' => 'Maximum number of images reached.'
                ], 422);
            }
            $image_name = $nextImageNumber . '.' . $request -> file('image') -> getClientOriginalExtension();
            Storage::disk('public') -> putFileAs(
                $image_path,
                $request -> file('image'),
                $image_name,
            );
        } catch(\Exception $e) {
            return response() -> json([
                'error' => 'Could not upload the image.',
            ], 500);
        }
        return response() -> json([
            'message' => 'Image uploaded successfully.',
        ], 201);
    }

    public function deleteProductImage(Request $request) {
        $validator = Validator::make($request -> all(), [
            'id' => 'required|integer|exists:products,id',
            'image_number' => 'required|integer',
        ]);
        if($validator -> fails()) {
            return response() -> json([
                'error' => $validator -> errors() -> toJson()
            ], 422);
        }
        $type = Utils::getProductType($request -> input('mw_business') -> id, $request -> input('id'));
        if($type == null) {
            return response() -> json([
                'error' => 'This product does not belong to this business.'
            ], 422);
        }
        try {
            $image_path = "storage/images/{$request -> input('mw_user') -> id}/business/{$type}";
            $files = Storage::disk('public') -> files($image_path);
            $filesToRename = [];
            foreach ($files as $file) {
                $fileName = pathinfo($file, PATHINFO_FILENAME);
                if($fileName > $request -> input('image_number')) {
                    $newFileName = ($fileName - 1) . '.' . pathinfo($file, PATHINFO_EXTENSION);
                    Storage::disk('public') -> move($file, $image_path . '/' . $newFileName);
                }
            }
        } catch(\Exception $e) {
            print_r($e -> getMessage());
            return response() -> json([
                'error' => 'Could not delete the image.',
            ], 500);
        }
        return response() -> json([
            'message' => 'Image deleted successfully.',
        ], 201);
    }
}