<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        \App\Models\User::factory()->create([
            'name' => 'appAdmin',
            'email' => 'admin@toplive.click',
            'password' => bcrypt('admin!123!!')
        ]);


        \App\Models\User::factory(20)->create();
    }
}
