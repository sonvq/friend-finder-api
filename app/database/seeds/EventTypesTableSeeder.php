<?php

class EventTypesTableSeeder extends Seeder {

    public function run() {
        DB::table('event_types')->delete();
        $folderImagePath = public_path() . '/event_types_icon/';

        $event1 = EventType::create(array('name'    => 'Go to Beach', 'icon'    => $folderImagePath . 'icon-go-to-beach.png'));
        
        $event2 = EventType::create(array('name'    => 'Drink tea', 'icon'      => $folderImagePath . 'icon-drink-tea.png'));
        
        $event3 = EventType::create(array('name'    => 'Watch football', 'icon' => $folderImagePath . 'icon-watch-football.png'));
        
        $event4 = EventType::create(array('name'    => 'Drink Coffee', 'icon'   => $folderImagePath . 'icon-drink-coffee.png'));
        
        $event5 = EventType::create(array('name'    => 'Walking', 'icon'        => $folderImagePath . 'icon-walking.png'));
        
        $event6 = EventType::create(array('name'    => 'Rolik', 'icon'          => $folderImagePath . 'icon-rolik.png'));
        
        $event7 = EventType::create(array('name'    => 'Ice cream', 'icon'      => $folderImagePath . 'icon-ice-cream.png'));
        
        $event8 = EventType::create(array('name'    => 'Eating', 'icon'         => $folderImagePath . 'icon-eating.png'));
        
        $event9 = EventType::create(array('name'    => 'Go to GYM', 'icon'      => $folderImagePath . 'icon-go-to-gym.png'));
        
        $event10 = EventType::create(array('name'   => 'Flirting', 'icon'       => $folderImagePath . 'icon-flirting.png'));
        
        $event11 = EventType::create(array('name'   => 'Traveling', 'icon'      => $folderImagePath . 'icon-travelling.png'));
        
        $event12 = EventType::create(array('name'   => 'Ride bicycle', 'icon'   => $folderImagePath . 'icon-ride-bicycle.png'));
        
        $event13 = EventType::create(array('name'   => 'Go to cinema', 'icon'   => $folderImagePath . 'icon-go-to-cinema.png'));
        
        $event14 = EventType::create(array('name'   => 'Fishing', 'icon'        => $folderImagePath . 'icon-fishing.png'));
        
        $event15 = EventType::create(array('name'   => 'Go to pool', 'icon'     => $folderImagePath . 'icon-go-pool.png'));
        
    }

}
