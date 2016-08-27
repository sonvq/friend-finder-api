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
            $photo->is_profile = Input::has('is_profile')? $input['is_profile'] : 0;

            if (isset($input['is_profile']) && $input['is_profile'] == 1) {
                // Remove old profile image if exist
                $matchThese = ['user_id' => $user->_id, 'is_profile' => 1];

                $profileImage = Photo::where($matchThese)->first();
                if ($profileImage) {
                    $profileImage->is_profile = 0;
                    $profileImage->save();
                } 
            }
            if (Input::hasFile('photo')) {
                $avatarArray = $this->getAndCropImagePhoto ($user, Input::file('photo'));

                $photo->image = $avatarArray['image'];
                $photo->thumb = $avatarArray['thumb'];                
            }   			       

            if (!$photo->save()) {
                return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
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
    
    public function update($id) {
        $input = Input::all();
        $photo = Photo::where('_id', '=', $id)->first();
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        } 
        
        if ( empty($photo) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Photo not found.')));
        } 
        
        if ($user->_id != $photo->user_id) {
            return ApiResponse::errorForbidden(Helper::failResponseFormat(array('You donot own this record')));
        }                
        
        $input['user_id'] = $user->_id;
		$validator = Validator::make( $input, Photo::getUpdateRules() );

		if ( $validator->passes() ) {            
            $photo->is_profile = Input::has('is_profile')? $input['is_profile'] : 0;
         
            if (isset($input['is_profile']) && $input['is_profile'] == 1) {
                // Remove old profile image if exist
                $matchThese = ['user_id' => $user->_id, 'is_profile' => 1];

                $profileImage = Photo::where($matchThese)->first();
                if ($profileImage) {
                    $profileImage->is_profile = 0;
                    $profileImage->save();
                } 
            }
            
            if (!$photo->save()) {
                return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
            }
		}
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
		Log::info('<!> Updated : '.$photo);
        $returnPhoto = Photo::find($photo->_id);
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnPhoto->toArray()));
    }
    
    public function destroy($id) {
        $input = Input::all();
        $photo = Photo::where('_id', '=', $id)->first();
        
        $user = Token::userFor ( Input::get('token') );
        if ( empty($user) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('User not found.')));
        } 
        
        if ( empty($photo) ) {
            return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Photo not found.')));
        } 
        
        if ($user->_id != $photo->user_id) {
            return ApiResponse::errorForbidden(Helper::failResponseFormat(array('You donot own this record')));
        }
        
        // unlink image files
        $profileImagepath = '/profile_image/';
        $userPhotoImagePath = $profileImagepath . 'user_' . $user->_id . '/';
        
        if (!is_dir(public_path() . $profileImagepath)) {
            mkdir(public_path() . $profileImagepath, 0777, true);
        } else {
            chmod(public_path() . $profileImagepath, 0777);
        }

        $userPhotoImagePath = $profileImagepath . 'user_' . $user->_id . '/';
        if (!is_dir(public_path() . $userPhotoImagePath)) {
            mkdir(public_path() . $userPhotoImagePath, 0777, true);
        } else {
            chmod(public_path() . $userPhotoImagePath, 0777);
        }
        
        $fileImageInfo = pathinfo($photo->image);
        $fileThumbInfo = pathinfo($photo->thumb);
        
        $fileImage = public_path() . $userPhotoImagePath . $fileImageInfo['basename'];
        $fileThumb = public_path() . $userPhotoImagePath . $fileThumbInfo['basename'];                
                
        if (is_file($fileImage)) {
            unlink($fileImage); 
        }
        
        if (is_file($fileThumb)) {
            unlink($fileThumb); 
        }     
        
        if (!$photo->delete()) {
            return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
        }                           
        
        return ApiResponse::json(Helper::successResponseFormat(null, array('Successfully delete image')));
    }
    
    protected function getAndCropImagePhoto ($user, $imageFile) {
        $profileImagepath = '/profile_image/';

        if (!is_dir(public_path() . $profileImagepath)) {
            mkdir(public_path() . $profileImagepath, 0777, true);
        }

        $userPhotoImagePath = $profileImagepath . 'user_' . $user->_id . '/';
        if (!is_dir(public_path() . $userPhotoImagePath)) {
            mkdir(public_path() . $userPhotoImagePath, 0777, true);
        }
        
        $extension = $imageFile->getClientOriginalExtension();
        
        $imageSize = getimagesize($imageFile);

        $imgName = 'image_' . uniqid() . '_' . time() . '.' . $extension;
        $photoImage = $userPhotoImagePath . $imgName;

        $imageFile->move(public_path() . $userPhotoImagePath, $imgName);         

        if ($imageSize[0] > 1200 || $imageSize[1] > 1200) {
            try {
                $img = new SimpleImage(public_path() . $photoImage);
                $img->best_fit(1200, 1200)->save(public_path() . $photoImage);
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }

        $photoThumb = $userPhotoImagePath . 'thumb_' . uniqid() . '_' . time() . '.jpg';
        $photoThumbFull = public_path() . $photoThumb;
        // Save thumbnail image
        try {
            $img = new SimpleImage(public_path() . $photoImage);
            $img->best_fit(300, 300)->save($photoThumbFull);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

        $arrayResult = array();
        $arrayResult['image'] = Config::get('app.public_url') . $photoImage;
        $arrayResult['thumb'] = Config::get('app.public_url') . $photoThumb;
      
        return $arrayResult;
    }

	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}