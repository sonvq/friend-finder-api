<?php

class Friend extends BaseModel {

    protected $collection = 'friends';
    protected $table = 'friends';
    protected static $_table = 'friends';
    protected static $_model = 'Friend';
    public $timestamps = true;

    public function user() {
        return $this->belongsTo('User');
    }

    protected static $createRules = array(
        'user_id' => 'required',
        'friend_name' => 'required',
        'friend_id' => 'required'
    );
    
    protected static $updateRules = array(
        'user_id' => 'required',
        'friend_name' => 'required',
        'friend_id' => 'required'
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
            if (!empty($where['token'])) {
                $user = Token::userFor($where['token']);
                $where['user_id'] = $user->_id;
            }
            unset($where['token']);
        }
    }

}
