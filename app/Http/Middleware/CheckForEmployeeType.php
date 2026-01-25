<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckForEmployeeType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('employees')->check()) {
            if (Auth::guard('employees')->user()->type != 'supervisor') {
                return response()->json([
                    'success' => false,
                    'message' => __('responses.You are not authorized to access this resource'),
                ], 403);
            } else {
                return $next($request);
            }
        } else {
            return $next($request);
        }
    }
}
