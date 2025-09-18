<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // OPTIONSリクエスト（プリフライト）はスキップ
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
        }
        
        $user = $request->user();

        if (!$user) {
            Log::warning('Unauthenticated user tried to access admin endpoint');
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (!$user->isAdmin()) {
            Log::warning('Non-admin user tried to access admin endpoint', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'firebase_uid' => $user->firebase_uid
            ]);

            return response()->json(['error' => 'Admin access required'], 403);
        }

        return $next($request);
    }
}
