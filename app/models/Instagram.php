<?php

class Instagram extends BaseModel {

    protected $collection = 'instagrams';
    protected $table = 'instagrams';
    public $timestamps = true;
    protected static $_table = 'instagrams';
    protected static $_model = 'Instagram';

    public function user() {
        return $this->belongsTo('User');
    }

    protected static $updateAccessTokenRules = array(
        'user_id'       => 'required',
        'access_token'  => 'required'        
    );

    public static function getUpdateAccessTokenRules() {
        return self::$updateAccessTokenRules;
    }
    
}
