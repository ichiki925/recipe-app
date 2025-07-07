<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipeLikesTable extends Migration
{

    public function up()
    {
        Schema::create('recipe_likes', function (Blueprint $table) { // テーブル名変更
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // 複合ユニークキー
            $table->unique(['user_id', 'recipe_id']);
            
            // インデックス追加
            $table->index('user_id');
            $table->index('recipe_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipe_likes');
    }
}
