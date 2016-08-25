<?php

class PlaceController extends BaseController {

	public $restful = true;
    
    public function search() {
        
        $input = Input::all();

		$validator = Validator::make( $input, Place::getSearchRules() );

		if ( $validator->passes() ) {            
            $client = new \GuzzleHttp\Client();
            $baseGoogleApiURL = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?types=(cities)&key=AIzaSyBGxgM6ybCCTFm-x37ByIawkmuizYNOpOc';
            $GoogleApiURL = '';
            
            if (!empty($input['keyword'])) {
                $GoogleApiURL = $baseGoogleApiURL . '&input=' . $input['keyword'];
            }
            try {
                $response = $client->request('GET', $GoogleApiURL);
                if ($response->getStatusCode() != 200) {
                    return ApiResponse::errorInternal(Helper::failResponseFormat (array('An error occured. Please, try again.')));
                }
                $body = $response->getBody();
                
                $bodyDecoded = json_decode($body);
                
                return ApiResponse::json(Helper::successResponseFormat(null, $bodyDecoded->predictions));
            } catch (Exception $ex) {
                return ApiResponse::errorInternal(Helper::failResponseFormat(array($ex->getMessage())));
            }
            
            var_dump($response);die;
        }
		else {
			$error = Helper::getErrorMessageValidation($validator);
			return ApiResponse::errorValidation(Helper::failResponseFormat($error));
		}
		
        $returnPhoto = Photo::find($photo->_id);
        
		return ApiResponse::json(Helper::successResponseFormat(null, $returnPhoto->toArray()));

	}
     
	public function missingMethod( $parameters = array() )
	{
	    return ApiResponse::errorNotFound(Helper::failResponseFormat(array('Sorry, no method found')));
	}

}