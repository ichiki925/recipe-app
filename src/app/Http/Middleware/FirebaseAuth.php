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

            // 環境変数の詳細チェック
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

            // 開発環境での暫定対応
            if (config('app.env') === 'local' || config('app.debug')) {
                Log::info('🧪 Development environment detected - skipping Firebase init');
                $this->auth = null;
                return;
            }

            // 設定ファイルの確認
            $credentials = config('firebase.credentials');
            
            Log::info('🔍 Firebase config check', [
                'config_exists' => !empty($credentials),
                'config_project_id' => $credentials['project_id'] ?? 'NOT_SET',
                'config_client_email' => $credentials['client_email'] ?? 'NOT_SET',
                'config_has_private_key' => !empty($credentials['private_key'])
            ]);

            if (!$credentials || empty($credentials['project_id']) || empty($credentials['client_email'])) {
                throw new \Exception('Firebase credentials incomplete: missing project_id or client_email');
            }

            if (empty($credentials['private_key'])) {
                throw new \Exception('Firebase credentials incomplete: missing private_key');
            }

            // Firebase初期化
            $factory = (new Factory)->withServiceAccount($credentials);
            $this->auth = $factory->createAuth();

            Log::info('✅ Firebase auth initialized successfully');

        } catch (\Exception $e) {
            $this->initializationError = $e;
            Log::error('❌ Firebase initialization failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // 開発環境では致命的エラーにしない
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
                    ['email' => 'test@example.com'],
                    [
                        'name' => 'Test Admin',
                        'firebase_uid' => 'test-uid-development',
                        'role' => 'admin',
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
                throw new \Exception('Firebase auth not initialized: ' . ($this->initializationError ? $this->initializationError->getMessage() : 'unknown error'));
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
                'name' => $name
            ]);

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

                Log::info('👤 New user created via Firebase', [
                    'user_id' => $user->id,
                    'firebase_uid' => $firebaseUid
                ]);
            } else {
                // 既存ユーザーの情報を更新
                $user->update([
                    'name' => $name ?? $user->name,
                    'email' => $email ?? $user->email,
                    'avatar' => $avatar ?? $user->avatar,
                ]);

                Log::info('👤 Existing user updated via Firebase', [
                    'user_id' => $user->id,
                    'firebase_uid' => $firebaseUid
                ]);
            }

            // リクエストにユーザー情報を添付
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            Log::info('✅ Firebase user authenticated successfully', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);

        } catch (\Kreait\Firebase\Exception\Auth\InvalidToken $e) {
            Log::error('🔥 Invalid Firebase token', [
                'error' => $e->getMessage(),
                'token_preview' => substr($idToken, 0, 20) . '...'
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Invalid authentication token'
            ], 401);

        } catch (\Exception $e) {
            Log::error('🔥 Firebase authentication error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed: ' . $e->getMessage()
            ], 500);
        }

        return $next($request);
    }
}