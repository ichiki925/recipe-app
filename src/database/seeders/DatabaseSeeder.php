<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('=== データベースシーダー開始 ===');

        $this->call([
            UserSeeder::class,
            AdminCodeSeeder::class,
            RecipeSeeder::class,
            RecipeLikesCommentsSeeder::class,
        ]);

        $this->command->info('=== データベースシーダー完了 ===');
        $this->command->info('');
        $this->command->info('テストデータの準備が整いました！');
        $this->command->info('');
        $this->command->info('作成されたデータ:');
        $this->command->info('管理者アカウント: 2件');
        $this->command->info('一般ユーザー: 5件');
        $this->command->info('アクティブなレシピ: 5件');
        $this->command->info('削除済みレシピ: 3件（復元・完全削除テスト用）');
        $this->command->info('いいね・コメント: ランダムに生成');
        $this->command->info('');
        $this->command->info('管理者情報:');
        $this->command->info('管理者コード: VANILLA_KITCHEN_ADMIN_2025');
        $this->command->info('Email: admin@test.com');
        $this->command->info('Firebase UID: test_admin_uid_001');
        $this->command->info('');
        $this->command->info('テスト用一般ユーザー:');
        $this->command->info('Email: test@example.com');
        $this->command->info('Firebase UID: test_user_uid_004');
    }
}