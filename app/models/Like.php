<?php

class Like extends BaseModel {

    protected $collection = 'likes';
    protected $table = 'likes';
    protected static $_table = 'likes';
    protected static $_model = 'Like';
    public $timestamps = true;
    

    protected static $updateRules = array(
        'user_id' => 'required',
        'is_profile' => 'required|in:0,1'
    );

    public static function getCreateRules($input) {
        return array(
            'user_id' => 'required',
            'event_id' => 'required|exists:events,_id|unique:likes,event_id,NULL,_id,user_id,' . $input['user_id'],
            'status' => 'required|in:like,unlike'
        );
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
