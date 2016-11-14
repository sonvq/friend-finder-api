<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFriendsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('friends', function(Blueprint $table) {
            $table->increments('_id');
            $table->integer('user_id')->unsigned()->index();
            $table->string('friend_name')->nullable();
            $table->string('friend_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('friends');
    }

}
