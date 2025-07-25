<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $admin = User::create([
            'full_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'ADMIN',
            'status' => 'Active',
        ]);

        Profile::create([
            'user_id' => $admin->id,
        ]);

        $planList = ['Free plan', 'Basic plan', 'Standard plan', 'Premium plan'];
        $quoteList = [2, 5, 10, 50];
        $priceList = [0.00, 0.99, 1.99, 5.99];

        for ($i = 0; $i < count($planList); $i++) {
            Subscription::create([
                'plan_name' => $planList[$i],
                'number_of_quotes' => $quoteList[$i],
                'price' => $priceList[$i],
            ]);
        }

    }
}
