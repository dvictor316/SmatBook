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
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $faker = FakerFactory::create();

        // Generate sample companies only for local/test environments.
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
