<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSongsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->increments('id');

            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('album')->nullable();
            $table->string('genre')->nullable();
            $table->string('lyrics')->nullable();
            $table->integer('duration')->nullable();
            $table->string('accompaniment_path')->nullable();
            $table->string('vocals_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('songs');
    }
}
