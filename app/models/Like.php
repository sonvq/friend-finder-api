<?php

class Like extends BaseModel {

    protected $collection = 'likes';
    protected $table = 'likes';
    protected static $_table = 'likes';
    protected static $_model = 'Like';
    public $timestamps = true;
    

    protected static $updateRules = array(
        'user_id' => 'required',
        'is_accepted' => 'in:-1,0,1',
        'status' => 'in:like,unlike'
    );

    public static function getCreateRules($input) {
        
        $user_id = null;
        
        if (isset($input['user_id']) && !empty($input['user_id'])) {
            $user_id = $input['user_id'];    
        } 
        
        return array(
            'user_id' => 'required',
            'event_id' => 'required|exists:events,_id|unique:likes,event_id,NULL,_id,user_id,' . $user_id,
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
