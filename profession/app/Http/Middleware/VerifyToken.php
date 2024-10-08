<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class VerifyToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function  handle(Request $request, Closure $next): Response
    {
        $response = Http::withHeaders([
            'decode_content' => false,
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $request->bearerToken(),
        ])->get(env('AUTH_API') . '/api/verify-token', [
            'client_id' => env('CLIENT_ID'),
            'client_secret' => env('CLIENT_SECRET'),
        ]);
        if ($response->failed()) {
            $statusCode = $response->status();
            $errorBody = $response->json();
            abort($statusCode, $errorBody['message'] ?? 'Unknown error');
        }

        if ($response->serverError()) {
            abort(500, 'Server error occurred');
        }

        if ($response->clientError()) {
            abort($response->status(), 'Client error occurred');
        }

        if ($response->status() === 200) {
            $data = $response->json();
            $user = $data['user'];
            $request->merge(['user' => $user]);
            return $next($request);
        }
        abort(401, 'Unauthenticated');
    }
}
