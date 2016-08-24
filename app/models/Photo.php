<?php

class Photo extends BaseModel {

    protected $collection = 'photos';
    protected $table = 'photos';
    protected static $_table = 'photos';
    protected static $_model = 'Photo';
    public $timestamps = true;

    public function user() {
        return $this->belongsTo('User');
    }

    protected static $createRules = array(
        'user_id' => 'required',
        'photo' => 'required|mimes:jpeg,bmp,png'       
    );

    public static function getCreateRules() {
        return self::$createRules;
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
