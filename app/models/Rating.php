<?php

class Rating extends BaseModel {

    protected $collection = 'ratings';
    protected $table = 'ratings';
    public $timestamps = true;
    protected static $_table = 'ratings';
    protected static $_model = 'Rating';
    
    public function user() {
        return $this->belongsTo('User');
    }        

    public static function getCreateRules($input) {
        $event_id = null;
        
        if (isset($input['event_id']) && !empty($input['event_id'])) {
            $event_id = $input['event_id'];    
        } 
        
        $receiver_id = null;
        
        if (isset($input['receiver_id']) && !empty($input['receiver_id'])) {
            $receiver_id = $input['receiver_id'];    
        }        
        
        return array(
            'receiver_id' => 'required|numeric|already_exist_rating:' . $event_id. '|valid_user',
            'sender_id' => 'required|numeric',
            'rating' => 'required|integer|in:1,2,3,4,5',
            'event_id' => 'required|event_has_finished|liked_each_other:' . $receiver_id
        );
    }
    
    
    public static function onPreQuery(\Illuminate\Database\Query\Builder  $query, &$where = null)
    {
        
    }

}
