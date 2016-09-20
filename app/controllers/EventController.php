<?php

class EventController extends BaseController {

	public $restful = true;

    public function myEvent() {
        $query = $this->processInput();               

        $result = EventModel::getAllMyEvents($query['where'], $query['sort'], $query['limit'], $query['offset']);
        
        
        if (count($result) > 0) {
            // Add User info to event list
            foreach ($result as $id=>$object) {                
                $userObject = User::find($object->user_id);
                $userObject->photos;
                
                // get rating for userObject
                $allRating = Rating::where('receiver_id', $userObject->_id)->get();
                $ratingNumber = 0;
                if (count($allRating) > 0) {
                    $sumRating = 0;
                    $count = 0;
                    foreach($allRating as $singleRating) {
                        $sumRating = $sumRating + $singleRating->rating;
                        $count++;
                    }
                    $ratingNumber = $sumRating/$count;
                } 
                
                $userObject->average_rating = (double)$ratingNumber;
                
                $object->user = $userObject->toArray();
                $object->event_type_details = EventType::find($object->event_type)->toArray();
                
                // Lấy về my event các like mà có status = like và is_accepted != -1
                $allLikeObjects = Like::where('event_id', $object->_id)->where('status', 'like')->where('is_accepted', '!=' , -1)->get();
                
                if (count($allLikeObjects) > 0) {
                    foreach ($allLikeObjects as $singleLike) {
                        $userObject = User::find($singleLike->user_id);
                        $userObject->photos;
                        $singleLike->user = $userObject->toArray();
                    }
                    
                    $object->event_like_details = $allLikeObjects->toArray();
                } else {
                    $object->event_like_details = $allLikeObjects->toArray();
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
    
	public function index() {
        
        $query = $this->processInput();               

        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }
       
        // Check user is account plus or not
        $plus = Plus::where('user_id', $user->_id)->where('end_date', '>=', date("Y-m-d H:i:s"))->first();
        $userHasPlus = false;
        if ($plus) {
            $userHasPlus = true;    
        }
        
        $input = Input::all();
        
        if ($userHasPlus == false) {
            if (isset($input['city_id'])) {
                return ApiResponse::errorForbidden(Helper::failResponseFormat (array('Normal user can not search event by city, upgrade your account to plus!')));
            }
        }
        
        $result = EventModel::getAll($query['where'], $query['sort'], $query['limit'], $query['offset']);
        
        
        if (count($result) > 0) {
            // Add User info to event list
            foreach ($result as $id=>$object) {                
                $userObject = User::find($object->user_id);
                $userObject->photos;
                
                // get rating for userObject
                $allRating = Rating::where('receiver_id', $userObject->_id)->get();
                $ratingNumber = 0;
                if (count($allRating) > 0) {
                    $sumRating = 0;
                    $count = 0;
                    foreach($allRating as $singleRating) {
                        $sumRating = $sumRating + $singleRating->rating;
                        $count++;
                    }
                    $ratingNumber = $sumRating/$count;
                } 
                
                $userObject->average_rating = (double)$ratingNumber;
                
                $object->user = $userObject->toArray();
                $object->event_type_details = EventType::find($object->event_type)->toArray();
                
                $object->like_count = count(Like::where('event_id', $object->_id)->where('status', 'like')->get());
                
                $object->accepted_count = count(Like::where('event_id', $object->_id)->where('is_accepted', 1)->get());
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
    
    public function store() {
        $input = Input::all();
		$event = '';
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }
        
        // Check user is account plus or not
        $plus = Plus::where('user_id', $user->_id)->where('end_date', '>=', date("Y-m-d H:i:s"))->first();
        $userHasPlus = false;
        if ($plus) {
            $userHasPlus = true;    
        }

        $countEventCreated = EventModel::where('user_id', $user->_id)->where('created_at', '>=', date("Y-m-d 00:00:00"))->where('created_at', '<=', date("Y-m-d 23:59:59"))->get();
        
        if ($userHasPlus == false) {
            if (count($countEventCreated) >= 1) {
                return ApiResponse::errorForbidden(Helper::failResponseFormat (array('Normal user can only create 1 event per day, upgrade your account to plus!')));
            }
        }
        
        Validator::extend('greater_than', function($attribute, $value, $parameters)
        {
            $other = Input::get($parameters[0]);

            return isset($other) and intval($value) > intval($other);
        });
        
        Validator::replacer('greater_than', function($message, $attribute, $rule, $params) {
            return str_replace('_', ' ' , 'The '. $attribute .' must be greater than the ' .$params[0]);
        });
        
        Validator::extend('valid_event_type', function($attribute, $value, $parameters)
        {
            $eventTypeObject = EventType::find($value);
            if (empty($eventTypeObject)) {
                return false;
            }
            return true;            
        });
        
        Validator::replacer('valid_event_type', function($message, $attribute, $rule, $params) {
            return str_replace('_', ' ' , 'The '. $attribute .' does not exist');
        });
        
        Validator::extend('no_exist_event_running', function($attribute, $value, $parameters)
        {
            $user = Token::userFor ( Input::get('token') );
            
            $eventObjects =  EventModel::whereRaw('user_id = ? and end_date >= ?', 
                    array($user->_id, $value))->get();         
            if (count($eventObjects) > 0) {
                return false;
            }
            return true;
        });
        
        Validator::replacer('no_exist_event_running', function($message, $attribute, $rule, $params) {
            return 'Only one active event are allowed at a time';
        });
        

        $input['created_at'] = date("Y-m-d H:i:s");
        $input['user_id'] = $user->_id;
		$validator = Validator::make( $input, EventModel::getCreateRules() );

		if ( $validator->passes() ) {

			$event = new EventModel();                     
        
			$event->user_id             = $user->_id;
			$event->gender              = $input['gender'];
			$event->period              = $input['period'];
            $event->age_start           = $input['age_start'];
            $event->age_end             = $input['age_end'];            
            $event->event_type          = $input['event_type'];  
            $event->created_at          = $input['created_at'];
            $event->latitude            = $input['latitude'];
            $event->longitude           = $input['longitude'];
            $event->city_id             = $input['city_id'];
            
            $plusMinutes = '+' . $event->period * 60;            
            $event->end_date = date("Y-m-d H:i:s", strtotime("$plusMinutes minutes"));


			if ( !$event->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
		Log::info('<!> Created : '.$event);
        $returnEvent = EventModel::find($event->_id);
        $returnEvent->user;
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnEvent->toArray()));
    }
	/**
	 *	
	 *	@param $event Event
	 */
	public function show($event) {  
        
        $currentLoggedInUser = Token::userFor ( Input::get('token') );
        $currentLoggedInUserInterest = $currentLoggedInUser->interests;
        $currentLoggedInUserInterestArray = $currentLoggedInUserInterest->toArray();
                
        $eventObject = EventModel::find($event);
        if (empty($eventObject)) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no record found')));
        }
        
        $userObject = User::find($eventObject->user_id);
        $userObjectInterest = $userObject->interests;
        $userObjectInterestArray = $userObjectInterest->toArray();
        
        // Check common interest
        $arrayInterest = array();
        
        if (count($currentLoggedInUserInterestArray) > 0 &&
                count($userObjectInterestArray) > 0) {
            foreach ($currentLoggedInUserInterestArray as $singleInterest) {
                foreach ($userObjectInterestArray as $singleUserInterest) {
                    if ($singleInterest['page_id'] == $singleUserInterest['page_id']) {
                        $arrayInterest[] = $singleUserInterest;
                    }
                }
            }
        }
        $userObject->photos;
        $userObject->instagrams;

        unset($userObject->interests);
        $eventObject->user = $userObject->toArray();
        $eventObject->common_interests = $arrayInterest;
                
        //$object->event_type_details = EventType::find($object->event_type)->toArray();
                
        return ApiResponse::json(Helper::successResponseFormat(null, $eventObject->toArray()));
	}

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}