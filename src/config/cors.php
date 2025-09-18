<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'auth/*',     // 追加
        'admin/*', 
        'user/*',           // ← 追加：お気に入りAPI用
        'recipes/*',        // ← 追加：レシピAPI用
        'sanctum/csrf-cookie'
    ],

    'allowed_methods' => ['*'],

    // 'allowed_origins' => [
    //     'http://localhost:3000',  
    //     'http://127.0.0.1:3000',
    // ],
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // 'supports_credentials' => false,
    'supports_credentials' => true,

];