<?php

class ConversationController extends BaseController {

	public $restful = true;

    public function store() {
        $input = Input::all();
		$conversation = '';
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }
       
        $input['creator_id'] = $user->_id;
        
        Validator::extend('check_accepted', function($attribute, $value, $parameters)
        {            
            $likeObjects =  Like::whereRaw("event_id = ? and status = 'like' and is_accepted = 1 and user_id = ?", 
                    array($value, $parameters[0]))->get();         
            if (count($likeObjects) > 0) {
                return true;
            }
            return false;
        });
        
        Validator::replacer('check_accepted', function($message, $attribute, $rule, $params) {
            return 'Like is not exist, or not accepted yet';
        });
        
		$validator = Validator::make( $input, Conversation::getCreateRules($input) );

		if ( $validator->passes() ) {

			$conversation = new Conversation();                     
        
			$conversation->creator_id   = $input['creator_id'];
			$conversation->event_id     = $input['event_id'];
			$conversation->joiner_id    = $input['joiner_id'];            
                    
			if ( !$conversation->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
        $returnConversation = Conversation::find($conversation->_id);
        
        $returnConversation->creator = User::find($conversation->creator_id);
        $returnConversation->joiner = User::find($conversation->joiner_id);
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnConversation->toArray()));
    }
    
	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}