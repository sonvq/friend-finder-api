<?php

use abeautifulsite\SimpleImage as SimpleImage;

class PhotoController extends BaseController {

	public $restful = true;
    
    public function index() {
        
        $query = $this->processInput();               

        $result = Photo::getAll($query['where'], $query['sort'], $query['limit'], $query['offset']);        
        
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
  
    public function store() {
        $input = Input::all();
		$photo = '';
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        }        
        
        $input['user_id'] = $user->_id;
		$validator = Validator::make( $input, Photo::getCreateRules() );

		if ( $validator->passes() ) {
            $photo = new Photo();
            $photo->user_id = $input['user_id'];
            $photo->is_profile = $input['is_profile'];

            if (!$photo->save()) {
                return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }

            if ($input::hasFile('photo')) {

                $avatarArray = $this->getAndCropImagePhoto ($user, $input::file('photo'));

                $photo->image = $avatarArray['image'];
                $photo->thumb = $avatarArray['thumb'];

                if (!$photo->save()) {
                    return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
                }
            }   			       

		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
		Log::info('<!> Created : '.$photo);
        $returnPhoto = Photo::find($photo->_id);
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnPhoto->toArray()));
    }
    
    protected function getAndCropImagePhoto ($user, $imageFile, $removeOld = false) {
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
        
        $extension = $imageFile->getClientOriginalExtension();
        
        $imageSize = getimagesize($imageFile);

        $imgName = 'image_' . uniqid() . '_' . time() . '.' . $extension;

        $imageFile->move(public_path() . $userAvatarPath, $imgName);
        

        $downloadedImageSize = getimagesize($profileImageSaveLinkFull);

        if ($imageSize[0] > 1200 || $imageSize[1] > 1200) {
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

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}