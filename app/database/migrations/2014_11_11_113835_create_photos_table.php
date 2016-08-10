<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotosTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('photos', function(Blueprint $table) {
            $table->increments('_id');
            $table->integer('user_id')->unsigned()->index();
            $table->string('url');
            $table->tinyInteger('is_removed')->default(0);
            $table->string('is_profile')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('photos');
    }

}
