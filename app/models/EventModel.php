<?php

class EventModel extends SmartLoquent {

    protected $collection = 'events';
    protected $table = 'events';
    public $timestamps = true;
    protected static $_table = 'events';
    
    protected $with = array('event_type_details');
    
    public function user() {
        return $this->belongsTo('User');
    }
    
    public function event_type_details() {
        return $this->hasOne('EventType', '_id', 'event_type');
    }
    
    protected static $createRules = array(
        'period'        => 'required|numeric',
        'age_start'     => 'required|integer|min:18|max:55',
        'age_end'       => 'required|integer|min:18|max:55|greater_than:age_start',
        'gender'        => 'required|in:male,female,both',
        'event_type'    => 'required|integer|valid_event_type',
        'created_at'    => 'no_exist_event_running'
    );

    public static function getCreateRules() {
        return self::$createRules;
    }
    
    public static function getAll (array $where = array(), array $sort = array(), $limit = 10, $offset = 0) {
        $query = DB::table(static::$_table . ' as r');
        
        static::onPreQuery($query, $where);
        
        foreach ($where as $key => $value) {
            if (is_array($value)) {
                $query->whereIn('r.' . $key, $value);    
            } else {
                $query->where('r.' . $key, $value);
            }
        }
        
        foreach ($sort as $key => $value) {
            $query->orderBy('r.' . $key, $value);    
        }
        
        if ($limit) {
            $query->skip($offset);
            $query->take($limit);
        }
        
        return $query->get();
    }
    
    public static function onPreQuery(\Illuminate\Database\Query\Builder  $query, &$where = null)
    {
        if (isset($where['token'])) {
            if (!empty($where['token'])) {
                $user = Token::userFor ( $where['token'] );                
                $query->whereNotIn('r.user_id', array($user->_id));
            }
            unset($where['token']);
        }
    }

}
