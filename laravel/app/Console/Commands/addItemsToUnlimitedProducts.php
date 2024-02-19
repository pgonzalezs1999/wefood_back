<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Item;
use App\Models\Business;

class addItemsToUnlimitedProducts extends Command
{
    protected $signature = 'command:addItemsToUnlimitedProducts';

    protected $description = 'Create future items for products
                whose "ending_date" field is NULL (they will
                create potentially infinite items).';

    public function handle()
    {
        $threeDaysFromNow = Carbon::now() -> addDays(3);
        $products = Product::where('ending_date', null)
                -> orWhere('ending_date', '>', $threeDaysFromNow)
                -> get();
        $todayField = 'working_on_' . strtolower(Carbon::now() -> englishDayOfWeek);
        $tomorrowField = 'working_on_' . strtolower(Carbon::tomorrow() -> englishDayOfWeek);
        $afterTomorrowField = 'working_on_' . strtolower(Carbon::tomorrow() -> addDay() -> englishDayOfWeek);
        $weekDays = [ $todayField, $tomorrowField, $afterTomorrowField ];
        $dates = [
            Carbon::now() -> startOfDay(),
            Carbon::tomorrow() -> startOfDay(),
            Carbon::tomorrow() -> addDay() -> startOfDay(),
        ];
        foreach($products as $product) {
            $business = Business::where('id_breakfast_product', $product -> id)
                -> orWhere('id_lunch_product', $product -> id)
                -> orWhere('id_dinner_product', $product -> id)
                -> first();
            if($business == null) {
                $product -> delete();
                $items = Item::where('id_product', $product -> id) -> get();
                foreach($items as $item) {
                    $item -> delete();
                }
            } else {
                for($i = 0; $i < 3; $i++) {
                    if($product -> {$weekDays[$i]} == 1) {
                        $todayItem = Item::where('id_product', $product -> id)
                        -> where('date', $dates[$i])
                        -> first();
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