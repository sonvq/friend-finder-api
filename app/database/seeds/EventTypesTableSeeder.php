<?php

class EventTypesTableSeeder extends Seeder {

    public function run() {
        DB::table('event_types')->delete();

        $event1 = EventType::create(array('name' => 'Go to Beach', 'icon' => 'icon go here'));
    }

}
