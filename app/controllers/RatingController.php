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
            return 'This is not a valid receiver_id user or you cannot rate yourself';
        });
        
        Validator::extend('already_exist_rating', function($attribute, $value, $parameters)
        {
            if (!empty($parameters[0]) && !empty($value)) {
                $user = Token::userFor ( Input::get('token') );
                $ratingObject = Rating::whereRaw('sender_id = ? and receiver_id = ? and event_id = ?', array($user->_id, $value, $parameters[0]))->get();
                if (count($ratingObject) > 0) {                    
                    return false;
                }
            }
                
            return true;            
        });
        
        Validator::replacer('already_exist_rating', function($message, $attribute, $rule, $params) {
            return 'You have already rated this user before!';
        });
        
        Validator::extend('liked_each_other', function($attribute, $value, $parameters)
        {
            if (!empty($parameters[0]) && !empty($value)) {
                $user = Token::userFor ( Input::get('token') );
                $ratingObject = Conversation::whereRaw('event_id = ? and creator_id = ? and joiner_id = ?', array($value, $user->_id, $parameters[0]))
                        ->orWhereRaw('event_id = ? and creator_id = ? and joiner_id = ?', array($value, $parameters[0], $user->_id))->get();
                if (count($ratingObject) == 0) {
                    return false;
                }
            }
                
            return true;            
        });
        
        Validator::replacer('liked_each_other', function($message, $attribute, $rule, $params) {
            return 'You can not rating because you have not liked each other yet!';
        });
        
        Validator::extend('event_has_finished', function($attribute, $value, $parameters)
        {
            if (!empty($value)) {
                $event = EventModel::where('_id', $value)->first();
                if ($event) {
                    if ($event->end_date <= date("Y-m-d H:i:s")) {
                        return true;
                    }
                }
            }
            return false;
        });
        
        Validator::replacer('event_has_finished', function($message, $attribute, $rule, $params) {
            return 'Event has not finished yet, and you can not rate, wait until the event finishes';
        });
                
        
		$validator = Validator::make( $input, Rating::getCreateRules($input) );

		if ( $validator->passes() ) {

			$rating = new Rating();                     
        
			$rating->sender_id = $user->_id;
            $rating->receiver_id = $input['receiver_id'];
            $rating->rating = $input['rating'];
            $rating->event_id = $input['event_id'];

            if ( !$rating->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}

        $returnRating = Rating::find($rating->_id);
        $returnRating->sender = User::find($rating->sender_id);
        $returnRating->receiver = User::find($rating->receiver_id);
        $returnRating->event = EventModel::find($rating->event_id);
        
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