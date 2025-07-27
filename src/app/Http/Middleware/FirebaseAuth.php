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
    private $initializationError = null;

    public function __construct()
    {
        try {
            Log::info('🔥 Firebase middleware constructor started');

            $projectId = env('FIREBASE_PROJECT_ID');
            $clientEmail = env('FIREBASE_CLIENT_EMAIL');
            $privateKey = env('FIREBASE_PRIVATE_KEY');
            
            Log::info('🔍 Environment variables check', [
                'project_id' => $projectId ?: 'NOT_SET',
                'client_email' => $clientEmail ?: 'NOT_SET',
                'has_private_key' => !empty($privateKey),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug')
            ]);

            if (config('app.env') === 'local' || config('app.debug')) {
                Log::info('🧪 Development environment detected - skipping Firebase init');
                $this->auth = null;
                return;
            }

            $credentials = config('firebase.credentials');
            
            if (!$credentials || empty($credentials['project_id']) || empty($credentials['client_email'])) {
                throw new \Exception('Firebase credentials incomplete');
            }

            $factory = (new Factory)->withServiceAccount($credentials);
            $this->auth = $factory->createAuth();

            Log::info('✅ Firebase auth initialized successfully');

        } catch (\Exception $e) {
            $this->initializationError = $e;
            Log::error('❌ Firebase initialization failed', [
                'error' => $e->getMessage()
            ]);
            
            if (config('app.env') === 'local' || config('app.debug')) {
                Log::warning('⚠️ Continuing in development mode without Firebase');
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
        Log::info('🔥 Firebase middleware handle called', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'has_firebase_auth' => !is_null($this->auth),
            'has_init_error' => !is_null($this->initializationError),
            'app_env' => config('app.env')
        ]);

        $idToken = $request->bearerToken();

        if (!$idToken) {
            Log::error('❌ No bearer token found');
            return response()->json([
                'success' => false,
                'error' => 'Authorization token not provided'
            ], 401);
        }

        Log::info('🔑 Bearer token found', [
            'token_length' => strlen($idToken),
            'token_preview' => substr($idToken, 0, 20) . '...'
        ]);

        // 開発環境での暫定対応
        if (config('app.env') === 'local' || config('app.debug') || is_null($this->auth)) {
            Log::info('🧪 Using development mode authentication');
            
            try {
                $testUser = User::firstOrCreate(
                    ['firebase_uid' => 'test_user_uid_004'],
                    [
                        'name' => 'テストユーザー',
                        'email' => 'test@example.com',
                        'role' => 'user',
                        'email_verified_at' => now(),
                    ]
                );

                $request->setUserResolver(function () use ($testUser) {
                    return $testUser;
                });

                Log::info('✅ Development user authenticated', [
                    'user_id' => $testUser->id,
                    'role' => $testUser->role,
                    'name' => $testUser->name
                ]);

                // 認証成功ログを追加
                Log::info('User access granted', [
                    'user_id' => $testUser->id,
                    'firebase_uid' => $testUser->firebase_uid,
                    'role' => $testUser->role
                ]);

                return $next($request);

            } catch (\Exception $e) {
                Log::error('❌ Development authentication failed', [
                    'error' => $e->getMessage()
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Development authentication failed'
                ], 500);
            }
        }

        // 本格的な Firebase 認証
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

            Log::info('🔥 Firebase token verified', [
                'firebase_uid' => $firebaseUid,
                'email' => $email,
            ]);

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

            Log::info('✅ Firebase user authenticated successfully', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);

        } catch (\Exception $e) {
            Log::error('🔥 Firebase authentication error', [
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