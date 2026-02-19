<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;

class CompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = FakerFactory::create();

        // Generate 10 sample companies
        for ($i = 0; $i < 10; $i++) {
            DB::table('companies')->insert([
                'name' => $faker->company(),
                'email' => $faker->unique()->companyEmail(),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->address(),
                'status' => $faker->randomElement(['active', 'inactive']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}