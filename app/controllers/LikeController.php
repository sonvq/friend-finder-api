<?php


class LikeController extends BaseController {

	public $restful = true;
        
    public function store() {
        $input = Input::all();
		$like = '';
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }                        
        
        $input['user_id'] = $user->_id;
		$validator = Validator::make( $input, Like::getCreateRules($input) );

		if ( $validator->passes() ) {
            $like = new Like();
            $like->user_id = $input['user_id'];
            $like->status = $input['status'];
            $like->event_id = $input['event_id'];

            if (!$like->save()) {
                return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }
		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}

        $returnLike = Like::find($like->_id);
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnLike->toArray()));
    }           
    
    public function updateLike($id) {
        $input = Input::all();
        $like = Like::where('_id', '=', $id)->first();
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        } 
        
        if ( empty($like) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Like not found.')));
        } 
        
        $event = EventModel::where('_id', '=', $like->event_id)->first();
        
        if ( empty($event) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Event not found.')));
        } 
        
        $input['user_id'] = $user->_id;
        
        if (isset($input['is_accepted'])) {
            if ($user->_id != $event->user_id) {
                return ApiResponse::errorForbidden(Helper::failResponseFormat(array('You donot own this record')));
            }                            
        }
        
        if (isset($input['status'])) {
            if ($user->_id != $like->user_id) {
                return ApiResponse::errorForbidden(Helper::failResponseFormat(array('You donot own this like')));
            }
        }
                            
        $validator = Validator::make( $input, Like::getUpdateRules() );

        $conversation = null;
		if ( $validator->passes() ) {
            if (isset($input['is_accepted'])) {
                $like->is_accepted = $input['is_accepted'];
                
                // checking if is_accepted = 1 then create new conversation
                if ($input['is_accepted'] == 1) {
                    
                    // check if the conversation is already existed
                    $conversationCheck = Conversation::where('creator_id', $user->_id)->where('event_id', $like->event_id)->where('joiner_id', $like->user_id)->first();
                    
                    if (!$conversationCheck) {                        
                        $conversation = new Conversation();                     

                        $conversation->creator_id   = $user->_id;
                        $conversation->event_id     = $like->event_id;
                        $conversation->joiner_id    = $like->user_id;            


                        if ( !$conversation->save() ) {
                            return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
                        }
                        
                        $conversation->creator = User::find($conversation->creator_id);
                        $conversation->joiner = User::find($conversation->joiner_id);
        
                        $conversation = $conversation->toArray();
                    }
                    
                }
            }
            
            if (isset($input['status'])) {
                $like->status = $input['status'];
            }                    
            
            if (!$like->save()) {
                return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }
		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}

        $returnLike = Like::find($like->_id);
        $returnLike->conversation = $conversation;
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnLike->toArray()));
    }
    
    public function update($id) {
        $input = Input::all();
        $like = Like::where('_id', '=', $id)->first();
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        } 
        
        if ( empty($like) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Like not found.')));
        } 
        
        $event = EventModel::where('_id', '=', $like->event_id)->first();
        
        if ( empty($event) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Event not found.')));
        } 
        
        $input['user_id'] = $user->_id;
        
        if (isset($input['is_accepted'])) {
            if ($user->_id != $event->user_id) {
                return ApiResponse::errorForbidden(Helper::failResponseFormat(array('You donot own this record')));
            }                            
        }
        
        if (isset($input['status'])) {
            if ($user->_id != $like->user_id) {
                return ApiResponse::errorForbidden(Helper::failResponseFormat(array('You donot own this like')));
            }
        }
                            
        $validator = Validator::make( $input, Like::getUpdateRules() );

        $conversation = null;
		if ( $validator->passes() ) {
            if (isset($input['is_accepted'])) {
                $like->is_accepted = $input['is_accepted'];
                
                // checking if is_accepted = 1 then create new conversation
                if ($input['is_accepted'] == 1) {
                    
                    // check if the conversation is already existed
                    $conversationCheck = Conversation::where('creator_id', $user->_id)->where('event_id', $like->event_id)->where('joiner_id', $like->user_id)->first();
                    
                    if (!$conversationCheck) {                        
                        $conversation = new Conversation();                     

                        $conversation->creator_id   = $user->_id;
                        $conversation->event_id     = $like->event_id;
                        $conversation->joiner_id    = $like->user_id;            


                        if ( !$conversation->save() ) {
                            return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
                        }
                        
                        $conversation->creator = User::find($conversation->creator_id);
                        $conversation->joiner = User::find($conversation->joiner_id);
        
                        $conversation = $conversation->toArray();
                    }
                    
                }
            }
            
            if (isset($input['status'])) {
                $like->status = $input['status'];
            }                    
            
            if (!$like->save()) {
                return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }
		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}

        $returnLike = Like::find($like->_id);
        $returnLike->conversation = $conversation;
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnLike->toArray()));
    }

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}