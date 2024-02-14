<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Item;

class ItemController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['addItemsToFutureProducts']]);
    }
}