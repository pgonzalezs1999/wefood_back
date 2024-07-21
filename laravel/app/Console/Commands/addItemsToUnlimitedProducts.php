<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Item;
use App\Models\Business;
use App\Utils;

class addItemsToUnlimitedProducts extends Command
{
    protected $signature = 'command:addItemsToUnlimitedProducts';

    protected $description = 'Create future items for products whose "ending_date" field is NULL
                (they will create potentially infinite items).';

    public function handle() {
        $twoDaysFromNow = Carbon::now() -> addDays(2);
        $products = Product::where('ending_date', null) -> orWhere('ending_date', '>', $twoDaysFromNow) -> get();
        $todayField = 'working_on_' . strtolower(Carbon::now() -> englishDayOfWeek);
        $tomorrowField = 'working_on_' . strtolower(Carbon::tomorrow() -> englishDayOfWeek);
    $weekDays = [ $todayField, $tomorrowField ];
        $dates = [
            Carbon::now() -> startOfDay(),
            Carbon::tomorrow() -> startOfDay()
        ];
        foreach($products as $product) {
            $business = Utils::findBusinessFromProduct($product);
            if($business == null) {
                $product -> delete();
                $items = Item::where('id_product', $product -> id) -> get();
                foreach($items as $item) {
                    $item -> delete();
                }
            } else {
                for($i = 0; $i < 2; $i++) {
                    if($product -> {$weekDays[$i]} == 1) {
                        $todayItem = Item::where('id_product', $product -> id) -> where('date', $dates[$i]) -> first();
                        if($todayItem == null) {
                            Item::create([
                                'id_product' => $product -> id,
                                'date' => $dates[$i],
                            ]);
                        }
                    }
                }
            }
        }
        return Command::SUCCESS;
    }
}