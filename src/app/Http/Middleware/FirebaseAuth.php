<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FirebaseAuth
{
    private $auth;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
        $this->auth = $factory->createAuth();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return response()->json(['error' => 'Authorization token not provided'], 401);
        }

        try {
            // Firebase ID トークンを検証
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name');
            $avatar = $verifiedIdToken->claims()->get('picture');

            // ユーザーをデータベースで検索または作成
            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user) {
                // 新しいユーザーを作成
                $user = User::create([
                    'firebase_uid' => $firebaseUid,
                    'name' => $name ?? 'Unknown User',
                    'email' => $email,
                    'avatar' => $avatar,
                    'role' => 'user', // デフォルトはuser
                    'email_verified_at' => now(),
                ]);
            } else {
                // 既存ユーザーの情報を更新
                $user->update([
                    'name' => $name ?? $user->name,
                    'email' => $email ?? $user->email,
                    'avatar' => $avatar ?? $user->avatar,
                ]);
            }

            // リクエストにユーザー情報を添付
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

        } catch (\Exception $e) {
            Log::error('Firebase authentication failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}