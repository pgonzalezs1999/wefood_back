<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->float('price');
            $table->integer('amount');
            $table->dateTime('ending_date')->nullable();
            $table->boolean('vegetarian')->nullable();
            $table->boolean('mediterranean')->nullable();
            $table->boolean('dessert')->nullable();
            $table->boolean('junk')->nullable();
            $table->boolean('workingOnMonday')->nullable();
            $table->boolean('workingOnTuesday')->nullable();
            $table->boolean('workingOnWednesday')->nullable();
            $table->boolean('workingOnThursday')->nullable();
            $table->boolean('workingOnFriday')->nullable();
            $table->boolean('workingOnSaturday')->nullable();
            $table->boolean('workingOnSunday')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};