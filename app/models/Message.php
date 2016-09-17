<?php

class Message extends BaseModel {

    protected $collection = 'messages';
    protected $table = 'messages';
    public $timestamps = true;
    protected static $_table = 'messages';
    protected static $_model = 'Message';


    public static function getCreateRules($input) {
        $sender_id = null;
        
        if (isset($input['sender_id']) && !empty($input['sender_id'])) {
            $sender_id = $input['sender_id'];    
        } 
        
        return array(
            'sender_id' => 'required',
            'conversation_id'  => 'required|validate_sender_id:' . $sender_id,
            'content'   => 'required'
        );
    }
    
    public static function onPreQuery(\Illuminate\Database\Query\Builder  $query, &$where = null)
    {       
        
        // only get event of other users
        if (isset($where['token'])) {            
            unset($where['token']);
        }
        
    }
    
}
