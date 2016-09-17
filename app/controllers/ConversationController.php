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
    
    public function index() {
        
        $query = $this->processInput();               

        $currentUser = Token::userFor(Input::get('token'));

        $result = Conversation::getAll($query['where'], $query['sort'], $query['limit'], $query['offset']);                
                
        if (count($result) > 0) {
            // Add User info to event list
            foreach ($result as $id=>$object) {                
                $creatorObject = User::find($object->creator_id);
                $creatorObject->photos;                
                $object->creator = $creatorObject->toArray();
                
                $joinerObject = User::find($object->joiner_id);
                $joinerObject->photos;                
                $object->joiner = $joinerObject->toArray();
                
                // get the last message from conversation
                $lastMessage = Message::where('conversation_id', $object->_id)->orderBy('created_at', 'desc')->first();
                
                if ($lastMessage) {
                    if ($lastMessage->sender_id == $currentUser->_id) {
                        $lastMessage->is_new_message = 0;
                    } else {
                        $lastMessage->is_new_message = $lastMessage->is_new;
                    } 
                }
                
                $object->last_message = $lastMessage;
                
//                $object->like_count = count(Like::where('event_id', $object->_id)->where('status', 'like')->get());
//                
//                $object->accepted_count = count(Like::where('event_id', $object->_id)->where('is_accepted', 1)->get());
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
    
    public function notification() {
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }
        
        $countNotification = 0;
        // get conversation list by user id
        $conversationList = Conversation::where('creator_id', $user->_id)->orWhere('joiner_id', $user->_id)->get();
        
        if (count($conversationList) > 0) {
            foreach($conversationList as $singleConversation) {
                // get new message by conversation id
                $listNewMessage = Message::where('conversation_id', $singleConversation->_id)->where('sender_id', '!=', $user->_id)->where('is_new', 1)->get();
                if (count($listNewMessage) > 0) {
                    $countNotification++;
                }
            }
        }
        
        $result = array();
        
        $result['notification_count'] = $countNotification;
        return ApiResponse::json(Helper::successResponseFormat(null, $result));
    }
    
	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}