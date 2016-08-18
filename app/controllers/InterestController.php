<?php

class InterestController extends BaseController {

	public $restful = true;

	public function index() {
		$query = $this->processInput();               

        $result = Interest::getAll($query['where'], $query['sort'], $query['limit'], $query['offset']);
        
        
        if (count($result) > 0) {            
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