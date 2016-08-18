<?php

class Interest extends BaseModel {

    protected $collection = 'interests';
    protected $table = 'interests';
    public $timestamps = true;
    protected static $_table = 'interests';
    protected static $_model = 'Interest';

    public function user() {
        return $this->belongsTo('User');
    }

    public static function onPreQuery(\Illuminate\Database\Query\Builder  $query, &$where = null)
    {       
        
        // only get event of other users
        if (isset($where['token'])) {            
            unset($where['token']);
        }
        
    }
    
}
