<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\User;
use Carbon\Carbon;

class RecipeSeeder extends Seeder
{
    public function run()
    {
        // 管理者ユーザーを取得（存在しない場合は作成）
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => '管理者',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        // 通常のアクティブなレシピ
        $activeRecipes = [
            [
                'title' => '基本のハンバーグ',
                'genre' => '肉料理',
                'servings' => '4人分',
                'ingredients' => "牛ひき肉 400g\n玉ねぎ 1個\n卵 1個\nパン粉 1/2カップ\n牛乳 大さじ2\n塩こしょう 適量\nナツメグ 少々",
                'instructions' => "1. 玉ねぎをみじん切りにして炒め、冷ましておく\n2. ボウルにひき肉、卵、パン粉、牛乳を入れて混ぜる\n3. 炒めた玉ねぎ、塩こしょう、ナツメグを加えてよく混ぜる\n4. 4等分して楕円形に成形する\n5. フライパンで両面を焼き、蓋をして中まで火を通す",
                'image_url' => '/images/recipes/hamburger.jpg',
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 156,
                'likes_count' => 23,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(8),
            ],
            [
                'title' => 'チキンカレー',
                'genre' => 'カレー',
                'servings' => '3人分',
                'ingredients' => "鶏もも肉 400g\n玉ねぎ 2個\nにんじん 1本\nじゃがいも 2個\nトマト缶 1缶\nカレールー 1/2箱\n水 400ml\nサラダ油 大さじ1",
                'instructions' => "1. 鶏肉を一口大に切る\n2. 野菜を食べやすい大きさに切る\n3. 鍋で鶏肉を炒め、色が変わったら野菜を加える\n4. 水とトマト缶を加えて煮込む\n5. 野菜が柔らかくなったらカレールーを溶かし入れる\n6. 10分程度煮込んで完成",
                'image_url' => '/images/recipes/chicken-curry.jpg',
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 203,
                'likes_count' => 35,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(12),
            ],
            [
                'title' => '和風パスタ',
                'genre' => '麺類',
                'servings' => '2人分',
                'ingredients' => "スパゲッティ 200g\nしめじ 1パック\nベーコン 3枚\n大葉 5枚\n醤油 大さじ2\nバター 15g\n塩こしょう 適量",
                'instructions' => "1. パスタを茹でる\n2. ベーコンを切って炒める\n3. しめじを加えて炒める\n4. 茹で上がったパスタを加える\n5. 醤油とバターで味付けし、大葉をトッピング",
                'image_url' => '/images/recipes/wafu-pasta.jpg',
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 89,
                'likes_count' => 12,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(5),
            ],
            [
                'title' => 'チョコレートケーキ',
                'genre' => 'デザート',
                'servings' => '5人分以上',
                'ingredients' => "薄力粉 100g\nココアパウダー 30g\n卵 2個\n砂糖 80g\nバター 50g\n牛乳 50ml\nベーキングパウダー 小さじ1",
                'instructions' => "1. オーブンを180度に予熱する\n2. バターを溶かす\n3. 卵と砂糖を混ぜる\n4. 粉類をふるって加える\n5. バターと牛乳を加えて混ぜる\n6. 型に入れて30分焼く",
                'image_url' => '/images/recipes/chocolate-cake.jpg',
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 167,
                'likes_count' => 28,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(18),
            ],
            [
                'title' => '野菜炒め',
                'genre' => '野菜料理',
                'servings' => '2人分',
                'ingredients' => "キャベツ 1/4個\nにんじん 1/2本\nピーマン 2個\nもやし 1袋\n豚こま肉 150g\n醤油 大さじ1\n塩こしょう 適量\nごま油 大さじ1",
                'instructions' => "1. 野菜を食べやすい大きさに切る\n2. フライパンで豚肉を炒める\n3. 野菜を加えて炒める\n4. 醤油と塩こしょうで味付け\n5. 最後にごま油を回しかける",
                'image_url' => '/images/recipes/vegetable-stir-fry.jpg',
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 78,
                'likes_count' => 9,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(2),
            ],
        ];

        // 削除済みレシピ（論理削除テスト用）
        $deletedRecipes = [
            [
                'title' => '古いレシピ1',
                'genre' => '和食',
                'servings' => '2人分',
                'ingredients' => "材料A 100g\n材料B 200g\n調味料C 適量",
                'instructions' => "1. 材料Aを準備する\n2. 材料Bと混ぜる\n3. 調味料Cで味付けする",
                'image_url' => null,
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 45,
                'likes_count' => 3,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(25),
                'deleted_at' => now()->subDays(5), // 5日前に削除
            ],
            [
                'title' => '削除テスト用レシピ',
                'genre' => '中華',
                'servings' => '3人分',
                'ingredients' => "テスト材料1 150g\nテスト材料2 1個\nテスト調味料 大さじ1",
                'instructions' => "1. テスト手順1を実行\n2. テスト手順2を実行\n3. 完成",
                'image_url' => null,
                'admin_id' => $admin->id,
                'is_published' => false,
                'views_count' => 12,
                'likes_count' => 1,
                'created_at' => now()->subDays(14),
                'updated_at' => now()->subDays(10),
                'deleted_at' => now()->subDays(3), // 3日前に削除
            ],
            [
                'title' => '非公開だったレシピ',
                'genre' => 'イタリアン',
                'servings' => '1人分',
                'ingredients' => "パスタ 100g\nトマトソース 適量\nチーズ 少々",
                'instructions' => "1. パスタを茹でる\n2. ソースと和える\n3. チーズをかける",
                'image_url' => '/images/recipes/test-pasta.jpg',
                'admin_id' => $admin->id,
                'is_published' => false,
                'views_count' => 5,
                'likes_count' => 0,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(6),
                'deleted_at' => now()->subDay(), // 1日前に削除
            ],
        ];

        // アクティブなレシピを作成
        foreach ($activeRecipes as $recipeData) {
            Recipe::create($recipeData);
        }

        // 削除済みレシピを作成
        foreach ($deletedRecipes as $recipeData) {
            Recipe::create($recipeData);
        }

        $this->command->info('レシピのシーダーが完了しました！');
        $this->command->info('- アクティブなレシピ: ' . count($activeRecipes) . '件');
        $this->command->info('- 削除済みレシピ: ' . count($deletedRecipes) . '件');
    }
}