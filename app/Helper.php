<?php

class Helper {

	public static function getErrorMessageValidation($validator){
        $validateMessageArray = $validator->messages()->toArray();
        $returnMessageArray = array();
        
        if (count($validateMessageArray) > 0) {
            foreach ($validateMessageArray as $singleErrorArray) {
                $returnMessageArray = array_merge($returnMessageArray, $singleErrorArray);
            }
        }
        
		return $returnMessageArray;
	}
    
    public static function responseFormat($status = null, $error = null, $data = null) {        
        return [
            'status' => $status,
            'error' => $error,
            'data' => $data
        ];
    }
    
    public static function failResponseFormat($error = null, $data = null) {        
        return [
            'status' => 0,
            'error' => $error,
            'data' => $data
        ];
    }
    
    public static function successResponseFormat($error = null, $data = null) {        
        return [
            'status' => 1,
            'error' => $error,
            'data' => $data
        ];
    }

}
