<?php

namespace App;

use Illuminate\Support\Facades\Storage;
use App\Models\Business;
use App\Models\Product;
use App\Models\Item;
use App\Models\Order;

class Utils {

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
}