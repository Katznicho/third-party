<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Insurance Companies first
        $this->call([
            InsuranceCompanySeeder::class,
        ]);

        // Create a generic admin user (without insurance company)
        User::factory()->create([
            'name' => 'System Admin',
            'username' => 'admin',
            'email' => 'admin@system.com',
            'password' => Hash::make('password'),
            'insurance_company_id' => null,
        ]);
    }
}
