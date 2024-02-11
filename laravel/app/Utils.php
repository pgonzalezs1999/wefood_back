<?php

namespace App;

use App\Models\Business;

class Utils {
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
}