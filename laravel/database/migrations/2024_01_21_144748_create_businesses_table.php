<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('tax_id');
            $table->string('logo_path')->nullable();
            $table->unsignedBigInteger('id_country')->nullable();
            $table->float('longitude')->nullable();
            $table->float('latitude')->nullable();
            $table->string('directions');
            $table->unsignedBigInteger('id_currency');
            $table->foreign('id_country')->references('id')->on('countries');
            $table->foreign('id_currency')->references('id')->on('currencies');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('businesses');
    }
};