<?php

class City extends BaseModel {

    protected $collection = 'cities';
    protected $table = 'cities';
    protected static $_table = 'cities';
    protected static $_model = 'City';
    public $timestamps = true;

    protected static $createRules = array(
        'city' => 'required',
        'country' => 'required'
    );
    
    protected static $updateRules = array(
        'city' => 'required',
        'country' => 'required'
    );

    public static function getCreateRules() {
        return self::$createRules;
    }

    public static function getUpdateRules() {
        return self::$updateRules;
    }
    
    public static function onPreQuery(\Illuminate\Database\Query\Builder $query, &$where = null) {
        $user = Token::userFor(Input::get('token'));

        // only get event of other users
        if (isset($where['token'])) {
            unset($where['token']);
        }
        
        if (isset($where['city'])) {
            $query->where('city', 'LIKE', '%' . $where['city'] . '%');
            unset($where['city']);
        }
    }

}
