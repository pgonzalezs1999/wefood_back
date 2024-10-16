<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('accepted_currencies', function (Blueprint $table) {
            $table -> id();
            $table -> unsignedBigInteger('id_business');
            $table -> unsignedBigInteger('id_currency');
            $table -> foreign('id_business') -> references('id') -> on('businesses');
            $table -> foreign('id_currency') -> references('id') -> on('currencies');
            $table -> timestamps();
            $table -> softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('accepted_currencies');
    }
};