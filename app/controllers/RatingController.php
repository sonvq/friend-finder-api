<?php

class RatingController extends BaseController {

	public $restful = true;

	public function index() {
                
	}
    
    public function store() {
        $input = Input::all();
		$rating = '';
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }
        
        $input['sender_id'] = $user->_id;
               
        Validator::extend('valid_user', function($attribute, $value, $parameters)
        {
            $userObject = User::find($value);
            if (empty($userObject)) {
                return false;
            }
            
            $user = Token::userFor ( Input::get('token') );
            
            if ($user->_id == $userObject->_id) {
                return false;
            }
            return true;            
        });
        
        Validator::replacer('valid_user', function($message, $attribute, $rule, $params) {
            return 'This is not a valid user or you cannot rate yourself';
        });
        
        Validator::extend('already_exist_rating', function($attribute, $value, $parameters)
        {
            $user = Token::userFor ( Input::get('token') );
            $ratingObject = Rating::whereRaw('sender_id = ? and receiver_id = ?', array($user->_id, $value))->get();
            if (count($ratingObject) > 0) {
                return false;
            }
            return true;            
        });
        
        Validator::replacer('already_exist_rating', function($message, $attribute, $rule, $params) {
            return 'You have already rated this user before!';
        });
        
		$validator = Validator::make( $input, Rating::getCreateRules() );

		if ( $validator->passes() ) {

			$rating = new Rating();                     
        
			$rating->sender_id                  = $user->_id;
			$rating->receiver_id                = $input['receiver_id'];
			$rating->rating                     = $input['rating'];
            
			if ( !$rating->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
		Log::info('<!> Created : '.$rating);
        $returnRating = Rating::find($rating->_id);
        $returnRating->sender = User::find($rating->sender_id);
        $returnRating->receiver = User::find($rating->receiver_id);
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnRating->toArray()));
    }
	/**
	 *	
	 */
	public function show($event) {  

	}

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}