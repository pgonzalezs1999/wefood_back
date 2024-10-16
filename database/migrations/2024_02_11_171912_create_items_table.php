<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table -> id();
            $table -> unsignedBigInteger('id_product');
            $table -> date('date');
            $table -> foreign('id_product') -> references('id') -> on('products');
            $table -> timestamps();
            $table -> softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('items');
    }
};
