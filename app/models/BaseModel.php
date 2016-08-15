<?php

class BaseModel extends SmartLoquent {
      
    public static function getAll (array $where = array(), array $sort = array(), $limit = 10, $offset = 0) {
        $query = DB::table(static::$_table . ' as r');
        
        $query->select('r.*');
        
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
        
    }

}
