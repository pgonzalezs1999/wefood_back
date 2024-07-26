<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use App\Utils;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use DateTime;

class PaymentController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function openpayPayment(Request $request) {
        $validator = Validator::make($request -> all(), [
            'holder_name' => 'required',
            'price' => 'required|numeric',
            'card_number' => 'required|numeric',
            'expiration_year' => 'required|numeric|min:24|max:99',
            'expiration_month' => 'required|numeric|min:1|max:12',
            'cvv2' => 'required|numeric|min:0|max:999',
            'id_item' => 'required|numeric|exists:items,id',
            'amount' => 'required|numeric',
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
        $ordered = Order::where('id_item', $request -> input('id_item')) -> sum('amount');
        $available = $product -> amount - $ordered;
        if($request -> input('amount') > $available) {
            return response() -> json([
                'error' => 'Not enough items available.'
            ], 422);
        }
        if($item -> date < Carbon::today() -> startOfDay()) {
            return response() -> json([
                'error' => 'Item not available anymore.'
            ], 422);
        }
        $response1 = Http::withBasicAuth('pk_d0651ad6528145c69c79de9002864eac', '')
            -> post('https://sandbox-api.openpay.pe/v1/muorexrewu3587xbmooq/tokens', [
                'card_number' => $request -> input('card_number'),
                'holder_name' => $request -> input('holder_name'),
                'expiration_year' => $request -> input('expiration_year'),
                'expiration_month' => $request -> input('expiration_month'),
                'cvv2' => $request['cvv2'],
        ]);
        if($response1 -> failed()) {
            return response() -> json([
                'error' => 'Error al comunicarse con la entidad bancaria',
                'details' => $response1 -> json()
            ], $response1 -> status());
        }
        $responseData = $response1 -> json(); // Si $response SÍ es json, manejarlo como tal
        if(json_last_error() !== JSON_ERROR_NONE) {
            $responseData = $response1 -> body(); // Si $response NO es json, manejarlo como texto plano
        }
        $user = Auth::user();
        $paddedUserId = str_pad($user -> id, 15, '0', STR_PAD_LEFT);
        $date = new DateTime();
        $formattedDate = $date -> format('YmdHis');
        $oid = 'oid-' . $paddedUserId . '-' . $formattedDate;
        $response2 = Http::withBasicAuth('sk_11db13e676de4739ba6c728fbba3efe0', '')
                -> post('https://sandbox-api.openpay.pe/v1/muorexrewu3587xbmooq/charges', [
            'source_id' => $responseData['id'],
            'method' => 'card',
            'amount' => $request -> input('price'),
            'currency' => 'PEN',
            'description' => 'WeFood S.A.C.',
            'order_id' => $oid,
            'device_session_id' => 'mM8PdDJWZ4DcwoDg0F9tLh3gRW4FX2aQ',
            'customer' => [
                'name' => $request -> input('holder_name'),
                'email' => $user -> email,
            ],
        ]);
        $responseData2 = $response2 -> json(); // Si $response2 es JSON, manejarlo como tal
        if(json_last_error() !== JSON_ERROR_NONE) {
            return response() -> json([
                'error' => 'Respuesta no es JSON',
                'details' => $response2 -> body()
            ], 500);
        }
        $order = Order::create([
            'id_user' => $user -> id,
            'id_item' => $request -> input('id_item'),
            'amount' => $request -> input('amount'),
            'order_date' => Carbon::now(),
            'id_payment' => 1,
        ]);
        return response() -> json($responseData2, 200);
    }
}
