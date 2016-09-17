<?php

class MessageController extends BaseController {

	public $restful = true;

    public function store() {
        $input = Input::all();
		$message = '';
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }
       
        $input['sender_id'] = $user->_id;
        
        Validator::extend('validate_sender_id', function($attribute, $value, $parameters)
        {           
            if (!empty($parameters[0]) && !empty($value)) {
                $conversationObject =  Conversation::where('_id', $value)->first();
                if ($conversationObject) {
                    if (($conversationObject->creator_id == $parameters[0]) 
                            || ($conversationObject->joiner_id == $parameters[0]))
                    return true;
                }    
            }
            
            return false;
        });
        
        Validator::replacer('validate_sender_id', function($message, $attribute, $rule, $params) {
            return 'You are not in this conversation and are not allowed to post message';
        });
        
		$validator = Validator::make( $input, Message::getCreateRules($input) );

		if ( $validator->passes() ) {

			$message = new Message();                     
        
			$message->sender_id = $input['sender_id'];
            $message->conversation_id = $input['conversation_id'];
            $message->content = $input['content'];
            $message->is_new = 1;

            if ( !$message->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
        $returnMessage = Message::find($message->_id);
        
        $returnMessage->sender = User::find($message->sender_id);
        $returnMessage->conversation = Conversation::find($message->conversation_id);
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnMessage->toArray()));
    }
    
	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}