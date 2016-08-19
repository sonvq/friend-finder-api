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
        
        if (isset($where['nearby']) && $where['nearby'] > 0) {
            if (!empty($user->longitude) && !empty($user->latitude)) {
                $longitude = $user->longitude;
                $latitude = $user->latitude;
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
            }
            unset($where['nearby']);
        }
        
        if (isset($where['age_start']) && $where['age_start'] >= 0 
                && isset($where['age_end']) && $where['age_end'] >= 0 
                && $where['age_start'] <= $where['age_end']) {
            $ageStart = $where['age_start'];
            $ageEnd = $where['age_end'];
            $query->whereRaw("($ageStart < r.age_start < $ageEnd) OR ($ageStart < r.age_end < $ageEnd) OR (r.age_start = $ageStart) OR (r.age_start = $ageEnd) OR (r.age_end = $ageStart) OR (r.age_end = $ageEnd)");
//            $query->where(function($query) use ($where) {
//                $query->where(function($query) use ($where) {
//                    $query->where('r.age_start', '>', $where['age_start'])->where('r.age_start', '<', $where['age_end']);
//                })
//                ->orWhere(function($query) use ($where) {
//                    $query->where('r.age_end', '>', $where['age_start'])->where('r.age_end', '<', $where['age_end']);
//                })
//                ->orWhere('r.age_start', '=', $where['age_start'])
//                ->orWhere('r.age_start', '=', $where['age_end'])
//                ->orWhere('r.age_end', '=', $where['age_start'])
//                ->orWhere('r.age_end', '=', $where['age_end']);
//            });
            
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
    }

}
