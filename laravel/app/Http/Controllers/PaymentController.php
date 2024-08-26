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
            'cvv2' => 'required|numeric|min:0|max:9999',
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
        $response1 = Http::withBasicAuth('pk_540273ba143943b999db84f432e85aa3', '')
            -> post('https://api.openpay.pe/v1/mg1ippvpuekjrkszeuxc/tokens', [
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
        $response2 = Http::withBasicAuth('sk_da74b3a391734614a0ab59eae4c3cb9c', '')
                -> post('https://api.openpay.pe/v1/mg1ippvpuekjrkszeuxc/charges', [
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
        $response2Data = $response2->json();
        if(array_key_exists('error_code', $response2Data)) {
            return response()->json([
                'error_code' => $response2Data['error_code'],
            ], 400);
        } else if(array_key_exists('error', $response2Data)) {
            return response()->json([
                'error' => $response2Data['error'],
            ], 400);
        } else {
			$order = Order::create([
				'id_user' => $user -> id,
				'id_item' => $request -> input('id_item'),
				'amount' => $request -> input('amount'),
				'order_date' => Carbon::now(),
				'id_payment' => 1,
			]);
			return response() -> json($response2Data, 200);
		}
    }
}
