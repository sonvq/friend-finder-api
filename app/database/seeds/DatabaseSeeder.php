<?php

class DatabaseSeeder extends Seeder {

    public function run() {
        Eloquent::unguard();
        
        $this->call('EventTypesTableSeeder');

        $this->command->info('Event Types table seeded!');
    }

}
