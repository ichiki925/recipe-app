<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // テスト用管理者ユーザー作成
        User::factory()->admin()->create([
            'firebase_uid' => 'test_admin_uid_001',
            'name' => 'テスト管理者',
            'email' => 'admin@test.com',
            'username' => 'test_admin',
        ]);

        // 追加の管理者ユーザー
        User::factory()->admin()->create([
            'firebase_uid' => 'test_admin_uid_002',
            'name' => 'サブ管理者',
            'email' => 'subadmin@test.com',
            'username' => 'sub_admin',
        ]);
    }
}