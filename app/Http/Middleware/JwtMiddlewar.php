<?php

namespace App\Http\Middleware;

use Closure;
use Constants;
use Exception;
use Illuminate\Http\Request;
use JWTAuth;

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
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_ERROR
                ]);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'message' => 'Token is Expired',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_UNAUTHORIZED_ERROR
                ]);
            } else {
                return response()->json([
                    'message' => 'Authorization token not found',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_VALIDATION_ERROR
                ]);
            }
        }

        return $next($request);
    }
}
