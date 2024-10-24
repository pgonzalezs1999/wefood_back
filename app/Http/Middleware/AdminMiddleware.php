<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if(!$user || $user -> is_admin != true) {
            return response()->json([
                'error' => 'Admin privileges required.'
            ], 403);
        }
        return $next($request);
    }
}