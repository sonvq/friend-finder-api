<?php


/** ------------------------------------------
 *  Route model binding
 *  ------------------------------------------
 *	Models are bson encoded objects (mongoDB)
 */
Route::model('users', 'User');

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::pattern('id', '[0-9]+');

Route::get('/', function()
{
	return View::make('hello');
});


Route::group(array('prefix' => 'v1'), function(){

    /**
     *  Allows the api to receive the push
     */
    Route::post('emails/queue/push', function() {
        Log::info('<!> RECEIVED THE PUSH !');
        return Queue::marshal();
    });

    /**
     * Password reset flow:
     * 1. /forget : user request for a reset token, a link will be sent by email
     * 2. /reset/key : link on the email redirects to the form on the mobile app
     * 3. /reset : form on the mobile app defines the new password
     */
    Route::post('users/forgot',         array('as' => 'v1.users.forgot',    'uses' => 'UserController@forgot') );
    Route::get('users/reset/{key}', function($key){
        return ApiResponse::toApplication('reset/'.$key);
    });
    Route::post('users/reset',          array('as' => 'v1.users.reset',     'uses' => 'UserController@resetPassword') );
    
    Route::post('users/auth',           array('as' => 'v1.users.auth',          'uses' => 'UserController@authenticate') );
    Route::post('users/auth/facebook',  array('as' => 'v1.users.auth.facebook', 'uses' => 'UserController@authenticateFacebook') );

    Route::get('users/facebook/photos',  array('as' => 'v1.users.facebook.photos', 'uses' => 'UserController@facebookPhotos') );
    
    Route::resource('users', 'UserController', array('only' => array('store')) );
    
    Route::post('users/mutual_friend', array('as' => 'v1.users.mutual_friend', 'uses' => 'UserController@mutual'));

    //	user needs to have a registered and active token
    Route::group(array('before' => 'logged_in'), function() {
        
        Route::get('places/search',  array('as' => 'v1.places.search', 'uses' => 'PlaceController@search') );

        Route::get('events/my_event',  array('as' => 'v1.events.my_event', 'uses' => 'EventController@myEvent') );
        
        Route::resource('events', 'EventController');                
        
        Route::resource('photos', 'PhotoController');
        
        Route::resource('cities', 'CityController');
        
        Route::post('photos/{id}/update', array('as' => 'v1.photos.update', 'uses' => 'PhotoController@updatePhoto'));

        Route::resource('interests', 'InterestController');
        
        Route::resource('messages', 'MessageController');
        
        Route::resource('ratings', 'RatingController');
        
        Route::get('conversations/notification',  array('as' => 'v1.conversations.notification', 'uses' => 'ConversationController@notification') );
        
        Route::resource('conversations', 'ConversationController');        
        
        Route::resource('likes', 'LikeController');
        Route::post('likes/{id}/update', array('as' => 'v1.likes.update', 'uses' => 'LikeController@updateLike'));
        
        Route::resource('plus', 'PlusController');
        
        Route::resource('event_types', 'EventTypesController', array('only' => array('index')) );
        Route::group(array('prefix' => 'event_types/{event_type}'), function() {
            Route::get('show',          array('as' => 'v1.event_types.show',      'uses' => 'EventTypesController@show') );
        });
        
        Route::get('users/sessions',    array('as' => 'v1.users.sessions',      'uses' => 'UserController@sessions') );
        
        Route::post('instagrams/connect', array('as' => 'v1.instagrams.connect', 'uses' => 'InstagramController@connect') );
        Route::post('instagrams/disconnect', array('as' => 'v1.instagrams.disconnect', 'uses' => 'InstagramController@disconnect') );
        
        Route::resource('users', 'UserController', array('only' => array('update', 'index')) );        
        Route::group(array('prefix' => 'users/{users}'), function() {

            Route::get('show',          array('as' => 'v1.users.show',      'uses' => 'UserController@show') );
            Route::post('logout',       array('as' => 'v1.users.logout',    'uses' => 'UserController@logout') );
            Route::post('update',       array('as' => 'v1.users.update',    'uses' => 'UserController@updateUser') );

        });

    });
    

});