<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Inpatient',
                'code' => 'INP',
                'description' => 'Inpatient medical services',
                'is_mandatory' => true,
                'requires_maternity_wait' => false,
                'requires_optical_dental_pair' => false,
                'waiting_period_days' => 0,
                'sort_order' => 1,
            ],
            [
                'name' => 'Outpatient',
                'code' => 'OUT',
                'description' => 'Outpatient medical services',
                'is_mandatory' => false,
                'requires_maternity_wait' => false,
                'requires_optical_dental_pair' => false,
                'waiting_period_days' => 0,
                'sort_order' => 2,
            ],
            [
                'name' => 'Maternity',
                'code' => 'MAT',
                'description' => 'Maternity services',
                'is_mandatory' => false,
                'requires_maternity_wait' => true,
                'requires_optical_dental_pair' => false,
                'waiting_period_days' => 365,
                'sort_order' => 3,
            ],
            [
                'name' => 'Optical',
                'code' => 'OPT',
                'description' => 'Optical services',
                'is_mandatory' => false,
                'requires_maternity_wait' => false,
                'requires_optical_dental_pair' => true,
                'waiting_period_days' => 0,
                'sort_order' => 4,
            ],
            [
                'name' => 'Dental',
                'code' => 'DEN',
                'description' => 'Dental services',
                'is_mandatory' => false,
                'requires_maternity_wait' => false,
                'requires_optical_dental_pair' => true,
                'waiting_period_days' => 0,
                'sort_order' => 5,
            ],
            [
                'name' => 'Funeral Expenses',
                'code' => 'FUN',
                'description' => 'Funeral expenses coverage',
                'is_mandatory' => false,
                'requires_maternity_wait' => false,
                'requires_optical_dental_pair' => false,
                'waiting_period_days' => 0,
                'sort_order' => 6,
            ],
            [
                'name' => 'Hospital Cash',
                'code' => 'HOS',
                'description' => 'Hospital cash per day benefit',
                'is_mandatory' => false,
                'requires_maternity_wait' => false,
                'requires_optical_dental_pair' => false,
                'waiting_period_days' => 0,
                'sort_order' => 7,
            ],
            [
                'name' => 'Life Cover',
                'code' => 'LIF',
                'description' => 'Life insurance coverage',
                'is_mandatory' => false,
                'requires_maternity_wait' => false,
                'requires_optical_dental_pair' => false,
                'waiting_period_days' => 0,
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $categoryData) {
            $existing = ServiceCategory::where('code', $categoryData['code'])->first();
            
            if (!$existing) {
                ServiceCategory::create([
                    'name' => $categoryData['name'],
                    'slug' => \Illuminate\Support\Str::slug($categoryData['name']),
                    'code' => $categoryData['code'],
                    'description' => $categoryData['description'],
                    'is_mandatory' => $categoryData['is_mandatory'],
                    'requires_maternity_wait' => $categoryData['requires_maternity_wait'],
                    'requires_optical_dental_pair' => $categoryData['requires_optical_dental_pair'],
                    'waiting_period_days' => $categoryData['waiting_period_days'],
                    'is_active' => true,
                    'sort_order' => $categoryData['sort_order'],
                ]);
                $this->command->info("Created service category: {$categoryData['name']}");
            } else {
                $this->command->warn("Service category {$categoryData['name']} already exists. Skipping...");
            }
        }

        $this->command->info('Service category seeding completed!');
    }
}
