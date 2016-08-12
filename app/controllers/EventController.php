<?php

class EventController extends BaseController {

	public $restful = true;

	public function index() {
		return EventModel::all();
	}
	
    public function store() {
        $input = Input::all();
		$event = '';

        Validator::extend('greater_than', function($attribute, $value, $parameters)
        {
            $other = Input::get($parameters[0]);

            return isset($other) and intval($value) > intval($other);
        });
        
        Validator::replacer('greater_than', function($message, $attribute, $rule, $params) {
            return str_replace('_', ' ' , 'The '. $attribute .' must be greater than the ' .$params[0]);
        });

		$validator = Validator::make( $input, EventModel::getCreateRules() );

		if ( $validator->passes() ) {

			$event = new EventModel();
            $user = Token::userFor ( Input::get('token') );
            if ( empty($user) ) return ApiResponse::json('User not found.');
            
            $event_type = EventType::find($input['event_type']);
            if(empty($event_type)) {
                return ApiResponse::errorNotFound('Event Type does not exist');
            }
        
			$event->user_id              = $user->_id;
			$event->gender               = $input['gender'];
			$event->period               = $input['period'];
            $event->age_start            = $input['age_start'];
            $event->age_end              = $input['age_end'];            
            $event->event_type           = $input['event_type'];  
            
            $plusMinutes = '+' . $event->period * 60;            
            $event->end_date = date("Y-m-d H:i:s", strtotime("$plusMinutes minutes"));


			if ( !$event->save() )
				$event = ApiResponse::errorInternal('An error occured. Please, try again.');

		}
		else {
			return ApiResponse::validation($validator);
		}
		Log::info('<!> Created : '.$event);
        $returnEvent = EventModel::find($event->_id);
        $returnEvent->user;
        
		return ApiResponse::json($returnEvent->toArray());
    }
	/**
	 *	
	 *	@param $event Event
	 */
	public function show($event) {  
        $eventObject = EventModel::find($event);
        if (empty($eventObject)) {
            return ApiResponse::errorNotFound('Sorry, no record found');
        }
//		$user->sessions;
		// Log::info('<!> Showing : '.$user );
		return $eventObject->toArray();
	}

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound('Sorry, no method found');
	}

}