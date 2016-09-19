<?php

class Plus extends BaseModel {

    protected $collection = 'plus';
    protected $table = 'plus';
    public $timestamps = true;
    protected static $_table = 'plus';
    protected static $_model = 'Plus';
    
    public function user() {
        return $this->belongsTo('User');
    }    
    
    protected static $createRules = array(
        'type' => 'required|in:"1month","3month","6month"'    
    );

    public static function getCreateRules() {
        return self::$createRules;
    }
    
    
    public static function onPreQuery(\Illuminate\Database\Query\Builder  $query, &$where = null)
    {
        
    }   

}
