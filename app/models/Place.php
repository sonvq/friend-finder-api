<?php

class Place extends BaseModel {

    public $timestamps = true;

    public function user() {
        return $this->belongsTo('User');
    }

    protected static $searchRules = array (
        'keyword' => 'required'
    );   

    public static function getSearchRules() {
        return self::$searchRules;
    }

}
