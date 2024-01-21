<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('real_name')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('email')->unique();
            $table->integer('phone')->nullable();
            $table->integer('phone_prefix')->nullable();
            $table->char('sex')->nullable();
            $table->float('last_latitude')->nullable();
            $table->float('last_longitude')->nullable();
            $table->dateTime('last_login_date')->nullable();
            $table->unsignedBigInteger('id_business')->nullable();
            $table->foreign('id_business')->references('id')->on('businesses');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
