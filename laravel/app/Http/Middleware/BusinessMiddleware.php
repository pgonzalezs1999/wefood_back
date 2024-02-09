<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Business;

class BusinessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $mw_user = Auth::user();
        $mw_business = Business::find($mw_user -> id_business);
        if($mw_business == null) {
            return response() -> json([
                'error' => 'No business found for this user.'
            ], 404);
        } else if($mw_business -> is_validated == false) {
            return response() -> json([
                'error' => 'Business not yet validated.'
            ], 422);
        }
        $request -> merge([
            'mw_user' => $mw_user,
            'mw_business' => $mw_business
        ]);
        return $next($request);
    }
}