<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInterestsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('interests', function(Blueprint $table) {
            $table->increments('_id');
            $table->integer('user_id')->unsigned()->index();
            $table->bigInteger('page_id');
            $table->string('page_name');
            $table->string('page_category');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('interests');
    }

}
