<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('events', function(Blueprint $table)
        {
            $table->increments('_id');
            $table->integer('user_id')->unsigned()->index();
            $table->float('period');
            $table->integer('age_start');
            $table->integer('age_end');
            $table->string('gender');
            $table->dateTime('end_date');
            $table->decimal('longitude', 9, 6);
            $table->decimal('latitude', 8, 6);
            $table->integer('event_type')->unsigned()->index();
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
		//
        Schema::drop('events');
	}

}
