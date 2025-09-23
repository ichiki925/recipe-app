<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth as LaravelAuth;

class FirebaseAuth
{
    private $auth;
    private $initializationError = null;

    public function __construct()
    {
        try {
            if (config('app.env') === 'local' || config('app.debug')) {
                $this->auth = null;
                return;
            }

            $credentialsPath = config('firebase.credentials');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                throw new \Exception('Firebase credentials file not found: ' . $credentialsPath);
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->auth = $factory->createAuth();

        } catch (\Exception $e) {
            $this->initializationError = $e;
            Log::error('❌ Firebase initialization failed', [
                'error' => $e->getMessage()
            ]);

            if (config('app.env') === 'local' || config('app.debug')) {
                $this->auth = null;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // OPTIONSリクエスト（プリフライト）はスキップ
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
        }

        $idToken = $request->bearerToken();

        if (!$idToken) {
            Log::error('❌ No bearer token found');
            return response()->json([
                'success' => false,
                'error' => 'Authorization token not provided'
            ], 401);
        }

        if (config('app.env') === 'local' || config('app.debug') || is_null($this->auth)) {
            // 開発環境では簡単な認証チェック
            Log::info('🔧 Development mode: Firebase auth bypassed', [
                'token_length' => strlen($idToken)
            ]);

            // デバッグモードでは現在ログインしているFirebaseユーザーのUIDを使用
            // 実際のFirebaseコンソールで確認したUIDを直接使用
            $firebaseUid = 'JrFVRRm0bvM3lhi3Ik3V08m04fD3'; // あなたのFirebase UID

            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user) {
                Log::error('❌ User not found for UID: ' . $firebaseUid);
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            Log::info('✅ Development auth success', [
                'user_id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'email' => $user->email
            ]);

            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            LaravelAuth::setUser($user);
            return $next($request);
        }

        // Firebase 認証（本番環境用）
        try {
            if (!$this->auth) {
                throw new \Exception('Firebase auth not initialized');
            }

            // Firebase ID トークンを検証
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name');
            $avatar = $verifiedIdToken->claims()->get('picture');

            $user = User::firstOrCreate(
                ['firebase_uid' => $firebaseUid],
                [
                    'name' => $name ?? 'Unknown User',
                    'email' => $email,
                    'avatar' => $avatar,
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]
            );

            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            LaravelAuth::setUser($user);

        } catch (\Exception $e) {
            Log::error('Firebase authentication error', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed'
            ], 401);
        }

        return $next($request);
    }
}