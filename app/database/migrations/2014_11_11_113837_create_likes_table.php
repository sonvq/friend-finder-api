<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLikesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('likes', function(Blueprint $table) {
            $table->increments('_id');
            $table->integer('user_id')->unsigned()->index();
            $table->string('event_id');
            $table->enum('status', array('like', 'unlike'));
            $table->tinyInteger('is_accepted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('likes');
    }

}
