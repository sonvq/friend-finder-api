<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRatingsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('ratings', function(Blueprint $table) {
            $table->increments('_id');
            $table->integer('event_id')->unsigned()->index();
            $table->integer('sender_id');
            $table->integer('receiver_id');            
            $table->integer('rating');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('ratings');
    }

}
