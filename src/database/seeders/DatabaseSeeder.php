<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('=== データベースシーダー開始 ===');

        // 実行順序が重要：外部キー制約があるため
        $this->call([
            UserSeeder::class,              // 1. ユーザー（管理者含む）
            AdminCodeSeeder::class,         // 2. 管理者コード情報表示
            RecipeSeeder::class,            // 3. レシピ（admin_idが必要）
            RecipeLikesCommentsSeeder::class, // 4. いいねとコメント（user_id, recipe_idが必要）
        ]);

        $this->command->info('=== データベースシーダー完了 ===');
        $this->command->info('');
        $this->command->info('🎉 テストデータの準備が整いました！');
        $this->command->info('');
        $this->command->info('📋 作成されたデータ:');
        $this->command->info('   ✅ 管理者アカウント: 2件');
        $this->command->info('   ✅ 一般ユーザー: 5件');
        $this->command->info('   ✅ アクティブなレシピ: 5件');
        $this->command->info('   ✅ 削除済みレシピ: 3件（復元・完全削除テスト用）');
        $this->command->info('   ✅ いいね・コメント: ランダムに生成');
        $this->command->info('');
        $this->command->info('🔐 管理者情報:');
        $this->command->info('   管理者コード: VANILLA_KITCHEN_ADMIN_2025');
        $this->command->info('   Email: admin@test.com');
        $this->command->info('   Firebase UID: test_admin_uid_001');
        $this->command->info('');
        $this->command->info('🧪 テスト用一般ユーザー:');
        $this->command->info('   Email: test@example.com');
        $this->command->info('   Firebase UID: test_user_uid_004');
    }
}