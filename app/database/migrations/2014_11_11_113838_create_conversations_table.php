<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('conversations', function(Blueprint $table) {
            $table->increments('_id');
            $table->integer('event_id')->unsigned()->index();
            $table->integer('creator_id');
            $table->integer('joiner_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('conversations');
    }

}
