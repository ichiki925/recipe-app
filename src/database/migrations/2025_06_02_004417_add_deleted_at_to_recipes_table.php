<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtToRecipesTable extends Migration
{
    
    public function up()
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->softDeletes(); // ← これだけ！
        });
    }

    
    public function down()
    {
        Schema::table('recipes', function (Blueprint $table) {
            //
        });
    }
}
