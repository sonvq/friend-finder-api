<?php

use MetzWeb\Instagram\Instagram as InstagramConnection;

class InstagramController extends BaseController {

	public $restful = true;
    
    public function connect() {
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
                'apiKey'      => '84f93341d98d477ba833141bc91dc3c6',
                'apiSecret'   => '48dea9d8659843c390a8ec191522cf4a',
                'apiCallback' => 'http://pickmefirst.co'
            ));

            $instagram->setAccessToken($input['access_token']);
            
            $userInstagram = $instagram->getUser();
            
            $user->instagram_username = $userInstagram->data->username;            

			if ( !$user->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }
            
            $existingInstagramPhotos = Instagram::where('user_id', '=', $user->_id)->get();
            
            if (count($existingInstagramPhotos) > 0) {
                foreach ($existingInstagramPhotos as $singlePhoto) {
                    $singlePhoto->delete();
                }
            }
            
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

                    $newInstagram->save();
                }
            }
		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}        
        
		return ApiResponse::json(Helper::successResponseFormat(null, $user->toArray()));
    }
    
    public function disconnect() {
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
        $existingInstagramPhotos = Instagram::where('user_id', '=', $user->_id)->get();

        if (count($existingInstagramPhotos) > 0) {
            foreach ($existingInstagramPhotos as $singlePhoto) {
                $singlePhoto->delete();
            }
        }
        
		return ApiResponse::json(Helper::successResponseFormat(null, $user->toArray()));
    }
	    
	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}