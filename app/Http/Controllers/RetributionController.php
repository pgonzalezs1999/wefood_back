<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;
use Carbon\Carbon;
use App\Models\Retribution;

class RetributionController extends Controller
{
    use SoftDeletes;

    public function __construct() {
        $this -> middleware('auth:api', ['except' => []]);
    }

    public function createRetribution(Request $request) {
        $validator = Validator::make($request -> all(), [
            'date' => 'nullable|date',
            'id_business' => 'required|integer|exists:businesses,id',
            'amount' => 'required|numeric',
            'transfer_id' => 'nullable',
            'status' => 'nullable|integer|min:0|max:4',
        ]);
        $retribution = Retribution::create([
            'date' => $request -> input('date'),
            'id_business' => $request -> input('id_business'),
            'amount' => $request -> input('amount'),
            'transfer_id' => $request -> input('transfer_id'),
            'status' => $request -> input('status'),
        ]);
        return response() -> json([
            'message' => 'Retribution created successfully',
            'retribution' => $retribution,
        ], 200);
    }

    public function getRetributionsFromBusiness($id) {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|integer',
        ]);
        $retributions = Retribution::where('id_business', $id) -> get();
        return response() -> json([
            'retributions' => $retributions,
        ], 200);
    }
}