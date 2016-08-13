<?php

class EventTypesController extends BaseController {

	public $restful = true;

	public function index() {
		return ApiResponse::json(Helper::successResponseFormat(null, EventType::all()));
	}
	
	/**
	 *	
	 *	@param $event_type EventType
	 */
	public function show($event_type) {  
        $eventTypeObject = EventType::find($event_type);
        if (empty($eventTypeObject)) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no record found')));
        }
//		$user->sessions;
		// Log::info('<!> Showing : '.$user );
		return ApiResponse::json(Helper::successResponseFormat(null, $eventTypeObject->toArray()));
	}

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}