<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wp_id')->unsigned();
            $table->integer('parent')->unsigned()->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('post_categories', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('wp_post_id')->unsigned();
            $table->integer('post_id')->unsigned()->index()->default(0);
            $table->integer('category_id')->unsigned()->index();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_categories');
        Schema::dropIfExists('categories');
    }
}
