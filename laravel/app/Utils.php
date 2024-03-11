<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Item;
use App\Models\Order;
use App\Models\Favourite;
use App\Models\Comment;

class Utils {

    use SoftDeletes;

    public static array $validImageTypes = ['jpg', 'png', 'gif'];

    public static function getProductType(int $id_business, int $id_product) {
        $business = Business::where('id', $id_business) -> first();
        if($business -> id_breakfast_product == $id_product) {
            return 'B';
        } else if($business -> id_lunch_product == $id_product) {
            return 'L';
        } else if($business -> id_dinner_product == $id_product) {
            return 'D';
        } else {
            return null;
        }
    }

    public static function getNextImageNumber(string $imagePath) {
        $i = 0;
        while(Storage::disk('public') -> exists("$imagePath/$i.jpg") ||
                Storage::disk('public') -> exists("$imagePath/$i.png") ||
                Storage::disk('public') -> exists("$imagePath/$i.gif")
        ) {
            $i++;
        }
        return $i;
    }

    public static function getAvailableAmountOfItem(Item $item, Product $parentProduct) {
        $ordered = Order::where('id_item', $item -> id) -> sum('amount');
        $available = $parentProduct -> amount - $ordered;
        return $available;
    }

    public static function get2dDistance(float $lat1, float $long1, float $lat2, float $long2) {
        $distance = sqrt(
            pow($lat2 - $lat1, 2) +
            pow($long2 - $long1, 2)
        );
        return $distance * 111.32;
    }

    public static function getProductInfo($id) {
        $product = Product::find($id);
        if($product == null) {
            return [
                'error' => 'Product not found.',
                'code' => '404',
            ];
        }
        $business = Business::where('id_breakfast_product', $product -> id)
                -> orWhere('id_lunch_product', $product -> id)
                -> orWhere('id_dinner_product', $product -> id)
                -> first();
        $business -> makeHidden([
            'tax_id', 'id_country', 'is_validated',
            'id_breakfast_product', 'id_lunch_product', 'id_dinner_product',
        ]);
        $product -> makeHidden([
            'ending_date',
            'working_on_monday', 'working_on_tuesday', 'working_on_wednesday', 'working_on_thursday', 'working_on_friday', 'working_on_saturday', 'working_on_sunday',
        ]);
        $item = Item::where('id_product', $product -> id)
                -> orderByDesc('date') -> first();
        $available = null;
        if($item != null) {
            $available = Utils::getAvailableAmountOfItem($item, $product);
        }
        $favourites = Favourite::where('id_business', $business -> id) -> count();
        $comments = Comment::where('id_business', $business -> id) -> get();
        $comments_expanded = new Collection();
        foreach($comments as $comment) {
            $user = User::find($comment -> id_user);
            $product -> makeHidden([
                'description',
            ]);
            $comment -> makeHidden([
                'id_user', 'id_business',
            ]);
            $business -> makeHidden([
                'longitude', 'latitude',
            ]);
            $user -> makeHidden([
                'real_name', 'real_surname', 'phone', 'phone_prefix', 'sex',
                'is_admin', 'id_business', 'email_verified',
                'last_login_date', 'last_longitude', 'last_latitude',
            ]);
            $comments_expanded -> push([
                'content' => $comment,
                'user' => $user,
            ]);
            $business -> comments = $comments_expanded;
        }
        return [
            'product' => $product,
            'business' => $business,
            'item' => $item,
            'available' => $available,
            'favourites' => $favourites,
        ];
    }

    public static function getProductsFromBusiness(int $id_business) {
        $business = Business::find($id_business);
        if($business == null) {
            return [
                'error' => 'Business not found.',
                'code' => '404',
            ];
        }
        $products = Product::where('id', $business -> id_breakfast_product)
                -> orWhere('id', $business -> id_lunch_product)
                -> orWhere('id', $business -> id_dinner_product)
                -> get();
        if(count($products) == 0) {
            return null;
        }
        return $products;
    }

    public static function getItemsFromProduct(int $id_product) {
        $product = Product::find($id_product);
        if($product == null) {
            return [
                'error' => 'Product not found.',
                'code' => '404',
            ];
        }
        $items = Item::where('id_product', $product -> id) -> get();
        if(count($items) == 0) {
            return null;
        }
        return $items;
    }

    public static function getBusinessesFromDistance(float $latitude, float $longitude, float $distance) {
        // Distance in longitude and latitude
        $businesses = Business::whereBetween('longitude', [$longitude - $distance, $longitude + $distance])
                -> whereBetween('latitude', [$latitude - $distance, $latitude + $distance])
                -> get();
        return $businesses;
    }

    public static function findBusinessFromProduct(int $id_product) {
        $business = Business::where('id_breakfast_product', $id_product)
                -> orWhere('id_lunch_product', $id_product)
                -> orWhere('id_dinner_product', $id_product)
                -> first();
        return $business;
    }

    public static function getBusinessRate(int $id_business) {
        $comments = Comment::where('id_business', $id_business) -> get();
        if(count($comments) == 0) {
            return 0;
        }
        $rate = 0;
        foreach($comments as $comment) {
            $rate += $comment -> rate;
        }
        $rate /= count($comments);
        return $rate;
    }
}