<?php

class EventModel extends BaseModel {

    protected $collection = 'events';
    protected $table = 'events';
    public $timestamps = true;
    protected static $_table = 'events';
    protected static $_model = 'EventModel';
    
    protected $with = array('event_type_details');
    
    public function user() {
        return $this->belongsTo('User');
    }
    
    public function event_type_details() {
        return $this->hasOne('EventType', '_id', 'event_type');
    }
    
    protected static $createRules = array(
        'period'        => 'required|numeric',
        'city_id'       => 'required',
        'age_start'     => 'required|integer|min:18|max:55',
        'age_end'       => 'required|integer|min:18|max:55|greater_than:age_start',
        'gender'        => 'required|in:male,female,both',
        'event_type'    => 'required|integer|valid_event_type',
        'created_at'    => 'no_exist_event_running',
        'latitude'		=>	['required', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
		'longitude'		=>	['required', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],        
    );

    public static function getCreateRules() {
        return self::$createRules;
    }
    
    
    public static function onPreQuery(\Illuminate\Database\Query\Builder  $query, &$where = null)
    {
        $user = Token::userFor ( Input::get('token') );
        
        $query->addSelect(DB::raw('(SELECT count(likes._id) FROM likes WHERE likes.event_id = r._id AND likes.user_id = ' . $user->_id . ') as user_liked_disliked_event'));
        
        if (isset($where['nearby']) && $where['nearby'] > 0 ) {            
            if (isset($where['user_longitude']) && !empty($where['user_longitude'])) {
                if (isset($where['user_latitude']) && !empty($where['user_latitude'])) {
                    $longitude = $where['user_longitude'];
                    $latitude = $where['user_latitude'];
                    $query->addSelect(
                        DB::raw("(
                            6371 * acos (
                            cos ( radians($latitude) )
                            * cos( radians( r.latitude ) )
                            * cos( radians( r.longitude ) - radians($longitude) )
                            + sin ( radians($latitude) )
                            * sin( radians( r.latitude ) )
                        )) AS distance")
                    );        
                    $query->having('distance', '<', $where['nearby']);
                    unset($where['user_latitude']);
                }
                unset($where['user_longitude']);
            }                
            unset($where['nearby']);            
        }
        
        if (isset($where['age_start']) && ($where['age_start'] >= 0)
                && isset($where['age_end']) && ($where['age_end'] >= 0)
                && ($where['age_start'] <= $where['age_end'])) {
            $query->where(function($query) use ($where) {            
                $query->whereBetween('r.age_start', array($where['age_start'], $where['age_end']));
                $query->orWhereBetween('r.age_end', array($where['age_start'], $where['age_end']));
            });
            unset($where['age_start']);
            unset($where['age_end']);
        }
        // only get event of other users
        if (isset($where['token'])) {
            if (!empty($where['token'])) {
                $user = Token::userFor ( $where['token'] );                
                $query->whereNotIn('r.user_id', array($user->_id));
            }
            unset($where['token']);
        }
        
        // only get active event
        $query->where('r.end_date', '>', date("Y-m-d H:i:s"));
        $query->having('user_liked_disliked_event', '=', 0);
        
    }
    
    public static function getAllMyEvents (array $where = array(), array $sort = array(), $limit = 10, $offset = 0) {        
        
        $query = DB::table(static::$_table . ' as r');
        
        $query->select('r.*');
                
        if (isset($where['token'])) {
            if (!empty($where['token'])) {
                $user = Token::userFor ( $where['token'] );                
                $query->where('r.user_id', $user->_id);
            }
            unset($where['token']);
        }
        
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
    

}
