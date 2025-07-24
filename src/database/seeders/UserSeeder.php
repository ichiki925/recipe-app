<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // テスト用管理者ユーザー（管理者コード対応）
        User::create([
            'firebase_uid' => 'test_admin_uid_001',
            'name' => 'テスト管理者',
            'email' => 'admin@test.com',
            'username' => 'admin_user',
            'avatar_url' => null,
            'role' => 'admin',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // サブ管理者（予備）
        User::create([
            'firebase_uid' => 'test_admin_uid_002', 
            'name' => 'サブ管理者',
            'email' => 'subadmin@test.com',
            'username' => 'sub_admin',
            'avatar_url' => null,
            'role' => 'admin',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 一般ユーザー（テスト用）
        $users = [
            [
                'firebase_uid' => 'test_user_uid_001',
                'name' => '田中太郎',
                'email' => 'tanaka@example.com',
                'username' => 'tanaka_taro',
                'avatar_url' => null,
                'role' => 'user',
                'email_verified_at' => now(),
            ],
            [
                'firebase_uid' => 'test_user_uid_002',
                'name' => '佐藤花子',
                'email' => 'sato@example.com',
                'username' => 'sato_hanako',
                'avatar_url' => null,
                'role' => 'user',
                'email_verified_at' => now(),
            ],
            [
                'firebase_uid' => 'test_user_uid_003',
                'name' => '山田次郎',
                'email' => 'yamada@example.com',
                'username' => 'yamada_jiro',
                'avatar_url' => null,
                'role' => 'user',
                'email_verified_at' => now(),
            ],
            [
                'firebase_uid' => 'test_user_uid_004',
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'username' => 'test_user',
                'avatar_url' => null,
                'role' => 'user',
                'email_verified_at' => now(),
            ],
            [
                'firebase_uid' => 'test_user_uid_005',
                'name' => '鈴木一郎',
                'email' => 'suzuki@example.com',
                'username' => 'suzuki_ichiro',
                'avatar_url' => null,
                'role' => 'user',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('ユーザーのシーダーが完了しました！');
        $this->command->info('- 管理者: 2名');
        $this->command->info('- 一般ユーザー: ' . count($users) . '名');
        $this->command->info('');
        $this->command->info('🔐 管理者情報:');
        $this->command->info('管理者コード: VANILLA_KITCHEN_ADMIN_2025');
        $this->command->info('Email: admin@test.com');
        $this->command->info('Firebase UID: test_admin_uid_001');
        $this->command->info('');
        $this->command->info('🧪 テスト用一般ユーザー:');
        $this->command->info('Email: test@example.com');
        $this->command->info('Firebase UID: test_user_uid_004');
    }
}