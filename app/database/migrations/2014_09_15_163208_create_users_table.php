<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
        {
            $table->increments('_id');
            $table->string('email');
            $table->string('firstname');
            $table->string('name')->nullable();
            $table->string('middlename')->nullable();
            $table->string('work')->nullable();
            $table->string('education')->nullable();
            $table->string('lastname');
            $table->string('password');
            $table->string('facebook_id')->nullable();
            $table->string('about')->nullable();
            $table->string('birthday')->nullable();
            $table->integer('age')->nullable();
            $table->string('gender')->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->decimal('latitude', 8, 6)->nullable();
            $table->softDeletes();
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
		Schema::drop('users');
	}

}
