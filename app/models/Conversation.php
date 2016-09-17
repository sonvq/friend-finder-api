<?php

class Conversation extends BaseModel {

    protected $collection = 'conversations';
    protected $table = 'conversations';
    public $timestamps = true;
    protected static $_table = 'conversations';
    protected static $_model = 'Conversation';


    public static function getCreateRules($input) {
        $event_id = null;
        
        if (isset($input['event_id']) && !empty($input['event_id'])) {
            $event_id = $input['event_id'];    
        } 
        
        $joiner_id = null;
        
        if (isset($input['joiner_id']) && !empty($input['joiner_id'])) {
            $joiner_id = $input['joiner_id'];    
        } 
        
        return array(
            'creator_id' => 'required',
            'joiner_id'  => 'required|unique:conversations,joiner_id,NULL,_id,event_id,' . $event_id,
            'event_id'   => 'required|exists:events,_id|check_accepted:' . $joiner_id
        );
    }
    
    public static function onPreQuery(\Illuminate\Database\Query\Builder  $query, &$where = null)
    {       
        
        // only get event of other users
        if (isset($where['token'])) {
            if (!empty($where['token'])) {
                $user = Token::userFor ( $where['token'] );                
                $query->where('r.creator_id', $user->_id);
                $query->orWhere('r.joiner_id', $user->_id);
            }
            unset($where['token']);
        }
        
    }
    
}
