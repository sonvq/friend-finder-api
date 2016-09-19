<?php

class PlusController extends BaseController {

	public $restful = true;       
    
    public function store() {
        $input = Input::all();
		$event = '';
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }
        
        // check if there is existing plus
        $plusCheck = Plus::where('user_id', $user->_id)->where('end_date', '>=', date("Y-m-d H:i:s"))->get();
        
        if (count($plusCheck) > 0) {
            return ApiResponse::errorValidation(Helper::failResponseFormat (array('Your previous plus has not end yet, you can choose to upgrade your plus instead!')));
        }
        $input['created_at'] = date("Y-m-d H:i:s");
        $input['user_id'] = $user->_id;
		$validator = Validator::make( $input, Plus::getCreateRules() );

		if ( $validator->passes() ) {

			$plus = new Plus();                     
        
			$plus->user_id = $user->_id;
            $plus->created_at = $input['created_at'];
            $plus->type = $input['type'];
            
            if ($input['type'] == '1month') {
                $plusMonth = '+1 month';  
            } else if ($input['type'] == '3month') {
                $plusMonth = '+3 months';  
            } else if ($input['type'] == '6month') {
                $plusMonth = '+6 months';  
            }
                      
            $plus->end_date = date("Y-m-d H:i:s", strtotime("$plusMonth"));
                        
			if ( !$plus->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
        $returnPlus = Plus::find($plus->_id);
        $returnPlus->user;
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnPlus->toArray()));
    }	

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}