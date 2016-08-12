<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelationForeignkeys extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tokens', function($table)
        {
            $table->foreign('user_id')->references('_id')->on('users');
        });

        Schema::table('reset_keys', function($table)
        {
            $table->foreign('user_id')->references('_id')->on('users');
        });
        
        Schema::table('photos', function($table)
        {
            $table->foreign('user_id')->references('_id')->on('users');
        });
        
        Schema::table('events', function($table)
        {
            $table->foreign('user_id')->references('_id')->on('users');
            $table->foreign('event_type')->references('_id')->on('event_types');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('reset_keys', function($table)
        {
            $table->dropForeign('reset_keys_user_id_foreign');
        });

        Schema::table('tokens', function($table)
        {
            $table->dropForeign('tokens_user_id_foreign');
        });
        
        Schema::table('photos', function($table)
        {
            $table->dropForeign('photos_user_id_foreign');
        });
        
        Schema::table('events', function($table)
        {
            $table->dropForeign('events_user_id_foreign');
            $table->dropForeign('events_event_type_foreign');
        });
	}

}
