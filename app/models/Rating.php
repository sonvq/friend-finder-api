<?php

class Rating extends BaseModel {

    protected $collection = 'ratings';
    protected $table = 'ratings';
    public $timestamps = true;
    protected static $_table = 'ratings';
    protected static $_model = 'Rating';
    
    public function user() {
        return $this->belongsTo('User');
    }    
    
    protected static $createRules = array(
        'receiver_id'       => 'required|numeric|already_exist_rating|valid_user',
        'sender_id'         => 'required|numeric',
        'rating'            => 'required|integer|min:1|max:5'
    );

    public static function getCreateRules() {
        return self::$createRules;
    }
    
    
    public static function onPreQuery(\Illuminate\Database\Query\Builder  $query, &$where = null)
    {
        
    }

}
