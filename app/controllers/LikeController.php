<?php


class LikeController extends BaseController {

	public $restful = true;
        
    public function store() {
        $input = Input::all();
		$photo = '';
        
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

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}