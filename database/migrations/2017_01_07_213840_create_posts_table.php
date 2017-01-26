<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wp_id')->unsigned();
            $table->integer('user_id')->unsigned()->index();
            $table->string('title');
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->enum('status', ['public', 'private', 'draft', 'trash'])->default('public');
            $table->string('slug', 200);
            $table->boolean('allow_comments')->default(true);
            $table->text('thumbnail')->nullable();
            $table->timestamps();

            //$table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
