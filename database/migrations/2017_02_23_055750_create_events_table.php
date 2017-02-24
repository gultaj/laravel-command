<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wp_id');
            $table->integer('user_id');
            $table->integer('event_place_id')->default(1);
            $table->integer('event_type_id')->default(1);
            $table->string('title');
            $table->date('startDate')->nullable();
            $table->date('endDate')->nullable();
            $table->string('price')->nullable();
            $table->text('schedule')->nullable();
            $table->text('content');
            $table->text('meta')->nullable();
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
        Schema::dropIfExists('events');
    }
}
