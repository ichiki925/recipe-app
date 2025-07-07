<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipesTable extends Migration
{

    public function up()
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('genre');
            $table->enum('servings', ['1人分', '2人分', '3人分', '4人分', '5人分以上']);
            $table->text('ingredients');
            $table->text('instructions');
            $table->string('image_url', 500)->nullable(); // image_pathをimage_urlに変更
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_published')->default(true); // 公開状態追加
            $table->integer('views_count')->default(0); // 閲覧数追加
            $table->integer('likes_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            
            // インデックス追加
            $table->index('admin_id');
            $table->index('genre');
            $table->index('is_published');
            $table->index('created_at');
            $table->index('likes_count');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipes');
    }
}
