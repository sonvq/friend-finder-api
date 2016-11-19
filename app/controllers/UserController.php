<?php

use abeautifulsite\SimpleImage as SimpleImage;

class UserController extends BaseController {

	public $restful = true;

	public function index() {
		return ApiResponse::json(Helper::successResponseFormat(null, User::all()));
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

			if ( !$user->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			return ApiResponse::validation($validator);
		}
		//Log::info('<!> Created : '.$user);

		return ApiResponse::json($user);
	}
    
    public function update($user) {

		$input_token = Input::get('token');
		$token = Token::where('key', '=', $input_token)->first();

		if ( empty($token) ) {
            return ApiResponse::errorUnauthorized(Helper::failResponseFormat (array("No active session found.")));
        }

		if ( $token->user_id !== $user->_id ) {
            return ApiResponse::errorForbidden(Helper::failResponseFormat(array('You are not allowed to update')));
        }
        
		$input = Input::all();

		$validator = Validator::make( $input, User::getUpdateRules() );

		if ( $validator->passes() ) {
			
			$user->longitude            = Input::has('longitude')? $input['longitude'] : null;
			$user->latitude 			= Input::has('latitude')? $input['latitude'] : null;

            if (isset($input['name']) && !empty($input['name'])) {
                $user->name = $input['name'];
            }
            
            if (isset($input['about']) && !empty($input['about'])) {
                $user->about = $input['about'];
            }
            
			if ( !$user->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
		//Log::info('<!> Updated : '.$user);

		$user->photos;
        $user->short_interests = Interest::where('user_id', $user->_id)->take(4)->get();
		return ApiResponse::json(Helper::successResponseFormat(null, $user->toArray()));
	}
    
    public function updateUser($user) {

		$input_token = Input::get('token');
		$token = Token::where('key', '=', $input_token)->first();

		if ( empty($token) ) {
            return ApiResponse::errorUnauthorized(Helper::failResponseFormat (array("No active session found.")));
        }

		if ( $token->user_id !== $user->_id ) {
            return ApiResponse::errorForbidden(Helper::failResponseFormat(array('You are not allowed to update')));
        }
        
		$input = Input::all();

		$validator = Validator::make( $input, User::getUpdateRules() );

		if ( $validator->passes() ) {
			
			$user->longitude            = Input::has('longitude')? $input['longitude'] : null;
			$user->latitude 			= Input::has('latitude')? $input['latitude'] : null;

            if (isset($input['name']) && !empty($input['name'])) {
                $user->name = $input['name'];
            }
            
            if (isset($input['about']) && !empty($input['about'])) {
                $user->about = $input['about'];
            }
            
			if ( !$user->save() ) {
				return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
		//Log::info('<!> Updated : '.$user);

		$user->photos;
        $user->short_interests = Interest::where('user_id', $user->_id)->take(4)->get();
		return ApiResponse::json(Helper::successResponseFormat(null, $user->toArray()));
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

				//Log::info('<!> Device Token Received : '. $device_token .' - Device ID Received : '. $device_id .' for user id: '.$token->user_id);
				//Log::info('<!> Logged : '.$token->user_id.' on '.$token->device_os.'['.$token->device_id.'] with token '.$token->key);
				
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
            
			if ( is_array($profile) && isset($profile['error']) ) {
				$error = array($profile['error']);
                return ApiResponse::errorValidation(Helper::failResponseFormat($error));
            }
            
			//Log::info( json_encode( $profile->asArray() ) );
            if (isset($profile->asArray()['photos'])) {
                return ApiResponse::json(Helper::successResponseFormat(null, $profile->asArray()['photos']));    
            } else {
                return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no images found')));   
            }
			
            
        } else {
            $error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
        }
    }
    

    public function mutual() {
        $input = Input::all();
        
        if ( !Input::has('token') ) {
            return ApiResponse::errorUnauthorized(Helper::failResponseFormat (array("No token given.")));
        }

		$user = Token::userFor ( Input::get('token') );

		if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));   
        }
        
        if (!Input::has('user_id')) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat (array("user_id field is required!")));
        }
        
        if ($user->_id == $input['user_id']) {
            return ApiResponse::errorForbidden(Helper::failResponseFormat (array("Given user_id cannot be the same with user_id from token")));
        }

        // Get friends of first user
        $friendFirstUser = Friend::where('user_id', $user->_id)->get();
        
        // Get friends of second user
        $friendSecondUser = Friend::where('user_id', $input['user_id'])->get();
        
        $friendMutuals = array();
        $arrayFriendFirstUser = array();
        $arrayFriendSecondUser = array();
        
        if (count($friendFirstUser) > 0 && count($friendSecondUser) > 0) {
            foreach ($friendFirstUser as $singleFriend) {
                $arrayFriendFirstUser[$singleFriend->friend_id] = $singleFriend->friend_name;
            }
            
            foreach ($friendSecondUser as $singleFriend) {
                $arrayFriendSecondUser[$singleFriend->friend_id] = $singleFriend->friend_name;
            }
            
            $sameFriend = array_intersect($arrayFriendFirstUser, $arrayFriendSecondUser);
            $dataArray = array();
            if (count($sameFriend) > 0) {
                foreach ($sameFriend as $key => $value) {
                    $object = new stdClass();
                    $object->id = $key;
                    $object->name = $value;
                    $dataArray[] = $object;
                }
                $friendMutuals['data'] = $dataArray;
            } else {
                $friendMutuals['data'] = array();
            }            
        } else {
            // return empty $friendMutuals
            $friendMutuals['data'] = array();
        }
        return ApiResponse::json(Helper::successResponseFormat(null, $friendMutuals));
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
            Log::info( 'A user call signed in with input: ' . json_encode( $input ) );
            /*
             * Scope email => email
             * Scope user_photos => select 4 images
             * Scope user_work_history => for work info
             * Scope user_education_history => for education info
             * Scope user_about_me => for about info
             * Scope user_birthday => for birthday and age
             * me?fields=id,email,first_name,last_name,name,middle_name,work,education,about,birthday,gender,albums{type,name,id},likes.limit(250){category,id,name}
             */
            $fields = 'id,email,first_name,last_name,name,middle_name,work,education,about,birthday,gender,albums{type,name,id},likes.limit(250){category,id,name}';
			$profile = $facebook->getMe(array(
                'fields' => $fields)
            );
            
			if ( is_array($profile) && isset($profile['error']) ) {
                $error = array($profile['error']);
                return ApiResponse::errorValidation(Helper::failResponseFormat($error));
            }

			//Log::info( json_encode( $profile->asArray() ) );

			$user = User::where('facebook_id', '=', $profile->getId() )->first();
			
			if ( !($user instanceof User) )
				$user = User::where('email', '=', $profile->getProperty('email') )->first();

			if ( !($user instanceof User) ){                                
				// Create an account if none is found
				$user = new User();
                $user->facebook_id = $profile->getId();
				$user->firstname = $profile->getFirstName();
				$user->lastname = $profile->getLastName();
                
                $facebookEmailById = $profile->getProperty('id') . '@pickmefirst.co';
                
				$user->email = !empty($profile->getProperty('email')) ? $profile->getProperty('email') : $facebookEmailById;
				$user->password = Hash::make( uniqid() );
                
                $user->name = !empty($profile->getName()) ? $profile->getName() : null;
                
                $user->middlename = !empty($profile->getMiddleName()) ? $profile->getMiddleName() : null;                                
                
                $workArray = array();
                
                if (!empty($profile->getProperty('work'))) {
                    $workArray = $profile->getProperty('work')->asArray();    
                }
                                
                $lastWorkObject = null;
                if (is_array($workArray) && count($workArray) > 0) {
                    $lastWorkObject = array_values($workArray)[0]; 
                }

                $user->work = !empty($lastWorkObject) ? $lastWorkObject->employer->name : null;
                
                $educationArray = array();
                
                if (!empty($profile->getProperty('education'))) {
                    $educationArray = $profile->getProperty('education')->asArray();
                }
                
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
                             
                $user->age = null;
                if (!empty($profile->getProperty('birthday'))) {
                    if (strpos($profile->getProperty('birthday'), '/') !== false) {
                        $user->age = $this->caculateAgeFromBirthday($profile->getProperty('birthday'));
                    }
                }
                                
                $user->about = (!empty($profile->getProperty('about'))) ? $profile->getProperty('about') : null;
                $user->gender = (!empty($profile->getGender())) ? $profile->getGender() : null;
                
                $user->longitude = null;
                $user->latitude = null;
                        
                $user->save(); 
                
                if (!empty($profile->getProperty('likes'))) {
                    $likesArray = $profile->getProperty('likes')->asArray();
                    $interestArray = array();
                    if (is_array($likesArray) && count($likesArray) > 0) {
                        $likesArrayData = $likesArray['data'];
                        foreach ($likesArrayData as $singleLike) {   
                            $interestObject = new Interest();
                            $interestObject->user_id = $user->_id;
                            $interestObject->page_category = $singleLike->category;
                            $interestObject->page_id = $singleLike->id;
                            $interestObject->page_name = $singleLike->name;
                            $interestObject->save();
                        }
                    }                   
                }

                $imageLink = 'http://graph.facebook.com/' . $profile->getId() . '/picture?width=9999';
                $this->getAndCropImageFromLink($user, $imageLink, 1, true);
                
                // Get id of profile picture album
                $profilePictureId = '';
                
                $albumArray = array();
                if (!empty($profile->getProperty('albums'))) {
                    $albumArray = $profile->getProperty('albums')->asArray();
                }
                
                if (is_array($albumArray) && count($albumArray) > 0) {
                    $dataAlbumArray = $albumArray['data'];
                    if (is_array($dataAlbumArray) && count($dataAlbumArray) > 0) {
                        foreach ($dataAlbumArray as $singleAlbum) {
                            if ($singleAlbum->type == "profile" && $singleAlbum->name == 'Profile Pictures') {
                                $profilePictureId = $singleAlbum->id;
                            }
                        }
                    }
                }
                
                // If successfully get profile picture album id, get 4 photos
                if (!empty($profilePictureId)) {
                    $fields = 'photos.limit(4){id,images}';
                    $profilePhotos = $facebook->makeRequest('GET', '/' . $profilePictureId, array(
                        'fields' => $fields)
                    )->execute()->getGraphObject();

                    if (is_array($profilePhotos) && isset($profilePhotos['error'])) {
                        $error = array($profilePhotos['error']);
                        return ApiResponse::errorValidation(Helper::failResponseFormat($error));
                    }

                    //Log::info(json_encode($profile->asArray()));

                    $profilePhotosArray = $profilePhotos->getProperty('photos')->asArray();

                    if (is_array($profilePhotosArray) && count($profilePhotosArray) > 0) {
                        $profilePhotoData = $profilePhotosArray['data'];

                        if (is_array($profilePhotoData) && count($profilePhotoData) > 0) {
                            foreach ($profilePhotoData as $key => $singleProfilePhoto) {
                                $allImageLinks = $singleProfilePhoto->images;
                                $chosenImageLinkObject = $allImageLinks[0]; // the largest size
                                $imageLink = $chosenImageLinkObject->source;                                
                                if ($key != 0) {
                                    $this->getAndCropImageFromLink($user, $imageLink, 0);                               
                                }
                            }
                        }
                    }
                }                
            }       
            
            // Get user mutual friends
            try {
                $context = $facebook->makeRequest('GET', '/' . $profile->getId(), array('fields' => 'context{mutual_friends}'), 'v2.7')->execute()->getGraphObject();

                if (is_array($context) && isset($context['error'])) {
                    $error = array($context['error']);
                    //return ApiResponse::errorValidation(Helper::failResponseFormat($error));
                } else {
                    $contextId = $context->getProperty('context')->getProperty('id');
                    $friendMutuals = $facebook->makeRequest('GET', '/' . $contextId . '/mutual_friends?limit=5000')->execute()->getGraphObject()->asArray();
                    if (isset($friendMutuals['data']) && !empty($friendMutuals['data'])) {
                        $arrayFriends = $friendMutuals['data'];
                        if (count($arrayFriends) > 0) {
                            DB::transaction(function() use ($arrayFriends, $user) {
                                $existingFriends = Friend::where('user_id', $user->_id)->delete();
                                foreach ($arrayFriends as $singleFriend) {
                                    $friendObject = new Friend();
                                    $friendObject->user_id = $user->_id;
                                    $friendObject->friend_id = $singleFriend->id;
                                    $friendObject->friend_name = $singleFriend->name;
                                    $friendObject->save();                                    
                                }
                            });
                        }                        
                    }
                    
                }
            } catch (Exception $ex) {                
                //return ApiResponse::errorValidation(Helper::failResponseFormat($ex->getMessage()));
            }

            $user->photos;
            $user->short_interests = Interest::where('user_id', $user->_id)->take(4)->get();

			$device_id = Input::has('device_id')? $input['device_id'] : '';
			$device_type = Input::has('device_type')? $input['device_type'] : '';
			$device_token = Input::has('device_token')? $input['device_token'] : '';

			$token = $user->login( $device_id, $device_type, $device_token );
			
			//Log::info('<!> Device Token Received : '. $device_token .' - Device ID Received : '. $device_id .' for user id: '.$token->user_id);
			//Log::info('<!> FACEBOOK Logged : '.$token->user_id.' on '.$token->device_os.'['.$token->device_id.'] with token '.$token->token);

			$token = $token->toArray();                                          
            
			$token['user'] = $user->toArray();

			//Log::info( json_encode($token) );
			
			return ApiResponse::json(Helper::successResponseFormat(null, $token));
		}
		else {
            $error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
	}
    
    protected function getAndCropImageFromLink ($user, $imageLink, $is_profile = 0, $removeOld = false) {
        $profileImagepath = '/profile_image/';

        if (!is_dir(public_path() . $profileImagepath)) {
            mkdir(public_path() . $profileImagepath, 0777, true);
        }

        $userProfileImagePath = $profileImagepath . 'user_' . $user->_id . '/';
        if (!is_dir(public_path() . $userProfileImagePath)) {
            mkdir(public_path() . $userProfileImagePath, 0777, true);
        }

        if ($removeOld) {
            // Remove old files                
            $files = glob(public_path() . $userProfileImagePath . '*'); // get all file names                
            foreach ($files as $file) { // iterate files
                if (is_file($file))
                    unlink($file); // delete file
            }
        }

        $profileImageSaveLink = $userProfileImagePath . 'image_' . uniqid() . '_' . time() . '.jpg';
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
                        
        
        $profileImageSaveLinkFull = public_path() . $profileImageSaveLink;
        
        // Do not use file put content and file get content anymore, using guzzleHttp instead
//        file_put_contents($profileImageSaveLinkFull, file_get_contents($imageLink, false, stream_context_create($arrContextOptions)));

        $client = new \GuzzleHttp\Client();
        try {
            $client->request('GET', $imageLink, ['sink' => $profileImageSaveLinkFull]);
        } catch (Exception $ex) {
            
        }
        
        $downloadedImageSize = getimagesize($profileImageSaveLinkFull);

        if ($downloadedImageSize[0] > 1200 || $downloadedImageSize[1] > 1200) {
            try {
                $img = new SimpleImage($profileImageSaveLinkFull);
                $img->best_fit(1200, 1200)->save($profileImageSaveLinkFull);
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }

        $profileImageThumb = $userProfileImagePath . 'thumb_' . uniqid() . '_' . time() . '.jpg';
        $profileImageThumbFull = public_path() . $profileImageThumb;
        // Save thumbnail image
        try {
            $img = new SimpleImage($profileImageSaveLinkFull);
            $img->best_fit(300, 300)->save($profileImageThumbFull);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

        $photoProfile = new Photo();
        $photoProfile->image = Config::get('app.public_url') . $profileImageSaveLink;
        $photoProfile->thumb = Config::get('app.public_url') . $profileImageThumb;
        $photoProfile->user_id = $user->_id;
        
        if ($is_profile) {
            $photoProfile->is_profile = 1;
        }
        $photoProfile->save();
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
			//Log::info('<!> Logged out from : '.$input_token );
			return ApiResponse::json('User logged out successfully.', '202');
		}	
		else
			return ApiResponse::errorInternal('User could not log out. Please try again.');

	}

	/**
	 *	Show all active sessions for the specified user, check access rights
	 */
	public function sessions() {

		if ( !Input::has('token') ) {
            return ApiResponse::errorUnauthorized(Helper::failResponseFormat (array("No token given.")));
        }

		$user = Token::userFor ( Input::get('token') );

		if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));   
        }

		$user->sessions;
        $user->photos;
        $user->instagrams;
        $user->short_interests = Interest::where('user_id', $user->_id)->take(4)->get();
        
        // Check is plus user
        $plus = Plus::where('user_id', $user->_id)->where('end_date', '>=', date("Y-m-d H:i:s"))->first();
        $userHasPlus = 0;
        if ($plus) {
            $userHasPlus = 1;    
        }
        $user->is_plus = $userHasPlus;
        
        // get rating for userObject
        $allRating = Rating::where('receiver_id', $user->_id)->get();
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

        $user->average_rating = (double)$ratingNumber;


		return ApiResponse::json(Helper::successResponseFormat(null, $user->toArray()));
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
				//Log::info($reset);
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
        if ( !Input::has('token') ) {
            return ApiResponse::errorUnauthorized(Helper::failResponseFormat (array("No token given.")));
        }

        $currentLoggedInUser = Token::userFor ( Input::get('token') );
        
        if ( empty($currentLoggedInUser) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }
        
        $currentLoggedInUserInterest = $currentLoggedInUser->interests;        
        $currentLoggedInUserInterestArray = $currentLoggedInUserInterest->toArray();        		
        $userObjectInterest = $user->interests;
        $userObjectInterestArray = $userObjectInterest->toArray();
        
        // Check common interest
        $arrayInterest = array();
            
        if ($currentLoggedInUser->_id != $user->_id) {            

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
        }
        
        unset($user->interests);
        $user->common_interests = $arrayInterest;
        
        $user->photos;
        $user->instagrams;
        $user->short_interests = Interest::where('user_id', $user->_id)->take(4)->get();
        
        // Check is plus user
        $plus = Plus::where('user_id', $user->_id)->where('end_date', '>=', date("Y-m-d H:i:s"))->first();
        $userHasPlus = 0;
        if ($plus) {
            $userHasPlus = 1;    
        }
        $user->is_plus = $userHasPlus;
        
        // get rating for userObject
        $allRating = Rating::where('receiver_id', $user->_id)->get();
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

        $user->average_rating = (double)$ratingNumber;
        
		return ApiResponse::json(Helper::successResponseFormat(null, $user->toArray()));
	}

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));   
	}

}