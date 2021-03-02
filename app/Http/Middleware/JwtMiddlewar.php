<?php

namespace App\Http\Middleware;

use Closure;
use Constants;
use Exception;
use Illuminate\Http\Request;
use JWTAuth;
use Route;

class JwtMiddlewar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'message' => 'Token is Invalid',
                    'payload' => 'invalid_token',
                    'status'  => Constants::STATUS_CODE_ERROR
                ], Constants::STATUS_CODE_ERROR);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                if (Route::getRoutes()->match($request)->getName() === 'refresh-token') {
                    return $next($request);
                }

                return response()->json([
                    'message' => 'Token is Expired',
                    'payload' => 'expired_token',
                    'status'  => Constants::STATUS_CODE_UNAUTHORIZED_ERROR
                ], Constants::STATUS_CODE_UNAUTHORIZED_ERROR);
            } else {
                return response()->json([
                    'message' => 'Authorization token not found',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_VALIDATION_ERROR
                ], Constants::STATUS_CODE_VALIDATION_ERROR);
            }
        }

        return $next($request);
    }
}
