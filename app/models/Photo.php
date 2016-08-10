<?php

class Photo extends SmartLoquent {

    protected $collection = 'photos';
    public $timestamps = true;

    public function user() {
        return $this->belongsTo('User');
    }

}
