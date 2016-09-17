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
    
    public function index() {
        $input = Input::all();
        
        $query = $this->processInput();               

        $currentUser = Token::userFor(Input::get('token'));
        
        if (!isset($input['conversation_id']) || empty($input['conversation_id'])) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Conversation found!')));
        }
        
        $conversation = Conversation::where('_id', $input['conversation_id'])->first();
        if (empty($conversation)) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Conversation found!')));
        }
        
        if (($conversation->creator_id != $currentUser->_id) && ($conversation->joiner_id != $currentUser->_id)) {
            return ApiResponse::errorForbidden(Helper::failResponseFormat(array('Permission denied!')));
        }

        $result = Message::getAll($query['where'], $query['sort'], $query['limit'], $query['offset']);                
                
        if (count($result) > 0) {
            // Add User info to event list
            foreach ($result as $id=>$object) {                
                $sender = User::find($object->sender_id);
                $sender->photos;
                
                $object->sender = $sender->toArray();
                
                // mark message is_new = 0 if sender_id != current user id
                $message = Message::find($object->_id);
                if ($message->sender_id != $currentUser->_id) {
                    $message->is_new = 0;
                    $message->save();
                }
            }
            
            // TODO: optimize
            foreach ($result as $id=>$object) {
                if(!empty($query['fields'])) {
                    foreach ($object as $key=>$value) {
                        if(in_array($key, $query['fields'])) {
                            continue;
                        } else {
                            unset($object->$key);
                        }
                    }
                }                
            }
                        
        }

        return ApiResponse::json(Helper::successResponseFormat(null, $result));

	}
    
	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}