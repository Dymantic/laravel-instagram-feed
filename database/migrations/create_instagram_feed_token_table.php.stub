<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstagramFeedTokenTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('dymantic_instagram_feed_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('profile_id');
            $table->string('access_code');
            $table->string('username');
            $table->string('user_id');
            $table->string('user_fullname');
            $table->string('user_profile_picture');
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('dymantic_instagram_feed_tokens');
    }
}