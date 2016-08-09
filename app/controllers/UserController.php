<?php

class UserController extends BaseController {

	public $restful = true;

	public function index() {
		return User::all();
	}
	
	/**
	 *	Create a new user account
	 */
	public function store() {

		$input = Input::all();
		$user = '';

		$validator = Validator::make( $input, User::getCreateRules() );

		if ( $validator->passes() ) {

			$user = new User();
			$user->email 				= Input::has('email')? $input['email'] : '';
			$user->firstname 			= Input::has('firstname')? $input['firstname'] : '';
			$user->lastname 			= Input::has('lastname')? $input['lastname'] : '';
			$user->password 			= Hash::make( $input['password'] );

			if ( !$user->save() )
				$user = ApiResponse::errorInternal('An error occured. Please, try again.');

		}
		else {
			return ApiResponse::validation($validator);
		}
		Log::info('<!> Created : '.$user);

		return ApiResponse::json($user);
	}
    
    public function update($user) {

		$input_token = Input::get('token');
		$token = Token::where('key', '=', $input_token)->first();

		if ( empty($token) ) return ApiResponse::json('No active session found.');

		if ( $token->user_id !== $user->_id ) return ApiResponse::errorForbidden('You are not allowed to update');
        
		$input = Input::all();

		$validator = Validator::make( $input, User::getUpdateRules() );

		if ( $validator->passes() ) {
			
			$user->longitude            = Input::has('longitude')? $input['longitude'] : null;
			$user->latitude 			= Input::has('latitude')? $input['latitude'] : null;

			if ( !$user->save() )
				$user = ApiResponse::errorInternal('An error occured. Please, try again.');

		}
		else {
			return ApiResponse::validation($validator);
		}
		Log::info('<!> Updated : '.$user);

		return ApiResponse::json($user);
	}

	/**
	 *	Authenticate a registered user, with its email and password
	 */
	public function authenticate() {

		$input = Input::all();
		$validator = Validator::make( $input, User::getAuthRules() );

		if ( $validator->passes() ){

			$user = User::where('email', '=', $input['email'])->first();
			if ( !($user instanceof User) ) {
				return ApiResponse::json("User is not registered.");
			}
			
			if ( Hash::check( $input['password'] , $user->password) ) {

				$device_id = Input::has('device_id')? $input['device_id'] : '';
				$device_type = Input::has('device_type')? $input['device_type'] : '';
				$device_token = Input::has('device_token')? $input['device_token'] : '';

				$token = $user->login( $device_id, $device_type, $device_token );

				Log::info('<!> Device Token Received : '. $device_token .' - Device ID Received : '. $device_id .' for user id: '.$token->user_id);
				Log::info('<!> Logged : '.$token->user_id.' on '.$token->device_os.'['.$token->device_id.'] with token '.$token->key);
				
				$token->user = $user->toArray();
				$token = ApiResponse::json($token, '202');
			}
			else $token = ApiResponse::json("Incorrect password.", '412');
			
			return $token;
		}
		else {
			return ApiResponse::validation($validator);
		}
	}
    
    protected function caculateAgeFromBirthday ($birthDay) {
        //explode the date to get month, day and year
        $birthDate = explode("/", $birthDay);
        //get age from date or birthdate
        $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md")
          ? ((date("Y") - $birthDate[2]) - 1)
          : (date("Y") - $birthDate[2]));
        
        return $age;
    }
    
    protected function postProcessUserArray(User $userObject) {
        $userArray = $userObject->toArray();
        
        if (isset($userArray['profile_image']) && $userArray['profile_image']) {
            $userArray['profile_image_square'] = $userArray['profile_image'] . '?type=square';
            $userArray['profile_image_small'] = $userArray['profile_image'] . '?type=small';
            $userArray['profile_image_normal'] = $userArray['profile_image'] . '?type=normal';
            $userArray['profile_image_large'] = $userArray['profile_image'] . '?type=large';
        }
        if (isset($userArray['birthday']) && $userArray['birthday']) {
            if (strpos($userArray['birthday'], '/') !== false) {
                $userArray['age'] = $this->caculateAgeFromBirthday($userArray['birthday']);    
            }            
        }
        
        return $userArray;
    }

    public function facebookPhotos() {
        $input = Input::all();
        $validator = Validator::make( $input, User::getFBPhotosRules() );

		if ( $validator->passes() ){
            $facebook = new FacebookWrapper();
			$facebook->loginAsUser( $input['access_token'] );

            /*
             * Scope email => email
             * Scope user_photos => select 4 images
             * Scope user_work_history => for work info
             * Scope user_education_history => for education info
             * Scope user_about_me => for about info
             * Scope user_birthday => for birthday and age
             * me?fields=photos.limit(4){images,name}
             */
            $fields = 'photos.limit(10){images,name}';
			$profile = $facebook->getMe(array(
                'fields' => $fields)
            );
            
			if ( is_array($profile) && isset($profile['error']) )
				return json_encode($profile);

			Log::info( json_encode( $profile->asArray() ) );
            if (isset($profile->asArray()['photos'])) {
                return ApiResponse::json($profile->asArray()['photos']);    
            } else {
                return ApiResponse::errorNotFound('Sorry, no images found');   
            }
			
            
        } else {
			return ApiResponse::validation($validator);		
        }
    }
    
    /**
	 *	Authenticate a user based on Facebook access token. If the email address from facebook is already in the database, 
	 *	the facebook user id will be added. 
	 *	If not, a new user will be created with a random password and user info from facebook.
	 */
	public function authenticateFacebook() {

		$input = Input::all();
		$validator = Validator::make( $input, User::getAuthFBRules() );

		if ( $validator->passes() ){

			$facebook = new FacebookWrapper();
			$facebook->loginAsUser( $input['access_token'] );

            /*
             * Scope email => email
             * Scope user_photos => select 4 images
             * Scope user_work_history => for work info
             * Scope user_education_history => for education info
             * Scope user_about_me => for about info
             * Scope user_birthday => for birthday and age
             * me?fields=id,email,first_name,last_name,name,middle_name,work,education,about,birthday,gender
             */
            $fields = 'id,email,first_name,last_name,name,middle_name,work,education,about,birthday,gender';
			$profile = $facebook->getMe(array(
                'fields' => $fields)
            );
            
			if ( is_array($profile) && isset($profile['error']) )
				return json_encode($profile);

			Log::info( json_encode( $profile->asArray() ) );

			$user = User::where('facebook_id', '=', $profile->getId() )->first();
			
			if ( !($user instanceof User) )
				$user = User::where('email', '=', $profile->getProperty('email') )->first();

			if ( !($user instanceof User) ){
				// Create an account if none is found
				$user = new User();
				$user->firstname = $profile->getFirstName();
				$user->lastname = $profile->getLastName();
				$user->email = $profile->getProperty('email');
				$user->password = Hash::make( uniqid() );
                
                $user->name = !empty($profile->getName()) ? $profile->getName() : null;
                
                $user->middlename = !empty($profile->getMiddleName()) ? $profile->getMiddleName() : null;
                
                $user->profile_image = 'http://graph.facebook.com/' . $profile->getId() . '/picture';
                
                $workArray = $profile->getProperty('work')->asArray();                
                $lastWorkObject = null;
                if (is_array($workArray) && count($workArray) > 0) {
                    $lastWorkObject = array_values($workArray)[0]; 
                }

                $user->work = !empty($lastWorkObject) ? $lastWorkObject->employer->name : null;
                
                $educationArray = $profile->getProperty('education')->asArray();
                
                $choosedHighschool = false;
                $choosedCollege = false;
                $highschool = '';
                $college = '';
                if (is_array($educationArray) && count($educationArray) > 0) {
                    // Has at least highschool or college                    
                    foreach($educationArray as $key => $singleObject) {
                        if (($singleObject->type == 'High School') && ($choosedHighschool == false)) {
                            $highschool = $singleObject->school->name;
                            $choosedHighschool = true;
                        }
                        if (($singleObject->type == 'College') && ($choosedCollege == false)) {
                            $college = $singleObject->school->name;
                            $choosedCollege = true;
                        }
                    }
                }
                
                if ($choosedCollege == true) {
                    $user->education = $college;
                } else {
                    $user->education = ($choosedHighschool == true) ? $highschool : null;                    
                }
                
                $user->birthday = (!empty($profile->getProperty('birthday'))) ? $profile->getProperty('birthday') : null;                                
                $user->about = (!empty($profile->getProperty('about'))) ? $profile->getProperty('about') : null;
                $user->gender = (!empty($profile->getGender())) ? $profile->getGender() : null;
                
                $user->longitude = null;
                $user->latitude = null;
            }                

			$user->facebook_id = $profile->getId();
			$user->save();
            
			$device_id = Input::has('device_id')? $input['device_id'] : '';
			$device_type = Input::has('device_type')? $input['device_type'] : '';
			$device_token = Input::has('device_token')? $input['device_token'] : '';

			$token = $user->login( $device_id, $device_type, $device_token );
			
			Log::info('<!> Device Token Received : '. $device_token .' - Device ID Received : '. $device_id .' for user id: '.$token->user_id);
			Log::info('<!> FACEBOOK Logged : '.$token->user_id.' on '.$token->device_os.'['.$token->device_id.'] with token '.$token->token);

			$token = $token->toArray();                        
            
            $userArray = $this->postProcessUserArray($user);            
            
			$token['user'] = $userArray;

			Log::info( json_encode($token) );
			
			return ApiResponse::json($token);
		}
		else {
			return ApiResponse::validation($validator);
		}
	}

	/**
	 *	Logout a user: remove the specified active token from the database
	 *	@param user User
	 */
	public function logout( $user ) {

		if ( !Input::has('token') ) return ApiResponse::json('No token given.');

		$input_token = Input::get('token');
		$token = Token::where('key', '=', $input_token)->first();

		if ( empty($token) ) return ApiResponse::json('No active session found.');

		if ( $token->user_id !== $user->_id ) return ApiResponse::errorForbidden('You do not own this token.');

		if ( $token->delete() ){
			Log::info('<!> Logged out from : '.$input_token );
			return ApiResponse::json('User logged out successfully.', '202');
		}	
		else
			return ApiResponse::errorInternal('User could not log out. Please try again.');

	}

	/**
	 *	Show all active sessions for the specified user, check access rights
	 */
	public function sessions() {

		if ( !Input::has('token') ) return ApiResponse::json('No token given.');

		$user = Token::userFor ( Input::get('token') );

		if ( empty($user) ) return ApiResponse::json('User not found.');

		$user->sessions;

		return ApiResponse::json( $user );
	}

	/**
	 *	Initiate request for new password
	 */
	public function forgot() {
		$input = Input::all();
		$validator = Validator::make( $input, User::getForgotRules() );

		if ( $validator->passes() ) {

			$user = User::where('email', '=', $input['email'])->first();
			$reset = $user->generateResetKey();

			$sent = false;

			if ( $reset->save() ){
				Log::info($reset);
				$sent = EmailTrigger::send( 'lost_password', $reset );
			}

			if ( $sent )
				return ApiResponse::json('Email sent successfully.');
			else
				return ApiResponse::json('An error has occured, the Email was not sent.', 500);
		}
		else {
			return ApiResponse::validation($validator);
		}
	}

	/**
	 *	Send reset password form with KEY
	 */
	public function resetPassword() {
		$input = Input::all();
		$validator = Validator::make( $input, User::getResetRules() );

		if ( $validator->passes() ) {
			$reset = ResetKey::where('key', $input['key'])->first();
			$user = User::where('email', $input['email'])->first();

			if ( !($reset instanceof ResetKey) )
				return ApiResponse::errorUnauthorized("Invalid reset key.");

			if ( $reset->user_id != $user->_id )
				return ApiResponse::errorUnauthorized("Reset key does not belong to this user.");

			if ( $reset->isExpired() ) {
				$reset->delete();
				return ApiResponse::errorUnauthorized("Reset key is expired.");
			}

			$user = $reset->user;

			$user->password = Hash::make($input['password']);
			$user->save();

			$reset->delete();

			return ApiResponse::json('Password reset successfully!');
		}
		else {
			return ApiResponse::validation($validator);
		}
	}

	/**
	 *	Show all active sessions for the specified user, no access right check
	 *	@param user User
	 */
	public function show($user) {        
//		$user->sessions;
		// Log::info('<!> Showing : '.$user );
		return $user;
	}

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound('Sorry, no method found');
	}

}