<?php

use Database\Seeders\StatesSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         $this->call([
             AdminRoleTable::class,
             AdminTable::class,
             StatesSeeder::class,
             SellerTableSeeder::class
         ]);
    }
}
