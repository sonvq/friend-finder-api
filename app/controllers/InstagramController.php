<?php

use MetzWeb\Instagram\Instagram as InstagramConnection;

class InstagramController extends BaseController {

	public $restful = true;
    
    public function connect() {
        Log::info('<***> Instagram connect called');
        sleep(1);
        $input = Input::all();
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }

        $input['user_id'] = $user->_id;
		$validator = Validator::make( $input, Instagram::getUpdateAccessTokenRules() );

		if ( $validator->passes() ) {

			$user->instagram_access_token = $input['access_token'];
            $user->instagram_connected = 1;
            
            $instagram = new InstagramConnection(array(
                'apiKey'      => '3b6fdca485af4018825be1b9ed12ac42',
                'apiSecret'   => '0ccbe3cc1cca4bb88615c5dc6b12b10c',
                'apiCallback' => 'http://pickmefirst.co'
            ));

            $instagram->setAccessToken($input['access_token']);
            
            $userInstagram = $instagram->getUser();
            
            $user->instagram_username = $userInstagram->data->username;            

			if ( !$user->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }
            
            // Delete old instagram images of $user->_id
            $affected = Instagram::where('user_id', '=', $user->_id)->delete();
            
            sleep(1);
            
            $userInstagramMedia = $instagram->getUserMedia('self', 24);
            
            if (is_array($userInstagramMedia->data) && count($userInstagramMedia->data) > 0) {
                $arrayImages = $userInstagramMedia->data;
                foreach ($arrayImages as $singleImage) {
                    $imagesSizes = $singleImage->images;
                    
                    $newInstagram = new Instagram();
                    $newInstagram->user_id = $user->_id;
                    $newInstagram->low_resolution = $imagesSizes->low_resolution->url;
                    $newInstagram->thumbnail = $imagesSizes->thumbnail->url;
                    $newInstagram->standard_resolution = $imagesSizes->standard_resolution->url;
                    
                    try {
                        $newInstagram->save();
                    } catch (Illuminate\Database\QueryException $e){
                        $errorCode = $e->errorInfo[1];
                        if($errorCode == 1062){
                            // echo 'hehe';
                            // houston, we have a duplicate entry problem
                        }
                    }
                }
            }
		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}        
        sleep(1);
		return ApiResponse::json(Helper::successResponseFormat(null, $user->toArray()));
    }
    
    public function disconnect() {
        Log::info('<***> Instagram disconnect called');
        sleep(1);
        $input = Input::all();
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }

        $user->instagram_access_token = null;
        $user->instagram_connected = 0;
        $user->instagram_username = null;
        
        if ( !$user->save() ) {
            return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
        }

        // Remove all instagram photos
        $affected = Instagram::where('user_id', '=', $user->_id)->delete();
        
        sleep(1);
        
		return ApiResponse::json(Helper::successResponseFormat(null, $user->toArray()));
    }
	    
	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}