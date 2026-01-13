<?php

namespace Database\Seeders;

use App\Models\InsuranceCompany;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InsuranceCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Prudential Assurance Uganda Limited
        $prudential = InsuranceCompany::create([
            'name' => 'Prudential Assurance Uganda Limited',
            'code' => 'PRUUG',
            'slug' => 'prudential-uganda',
            'head_office_address' => '10TH FLOOR ZEBRA PLAZA, PLOT 23 KAMPALA ROAD',
            'postal_address' => 'P. O. BOX 2660 KAMPALA',
            'phone' => '+256 14 3434 897/909',
            'email' => 'prumedfamily@prudential.ug',
            'website' => 'www.prudential.ug',
            'description' => 'PRUMED+ Health Insurance for Individuals, Families & SMEs',
            'is_active' => true,
        ]);

        // Create a login user for Prudential
        User::create([
            'name' => 'Prudential Admin',
            'username' => 'prudential',
            'email' => 'admin@prudential.ug',
            'password' => Hash::make('password'),
            'insurance_company_id' => $prudential->id,
        ]);

        // Create additional dummy insurance companies
        $companies = [
            [
                'name' => 'AAR Health Insurance',
                'code' => 'AARUG',
                'slug' => 'aar-health',
                'head_office_address' => 'Plot 4, Bugolobi, Kampala',
                'postal_address' => 'P.O. Box 1234, Kampala',
                'phone' => '+256 312 345678',
                'email' => 'info@aaruganda.co.ug',
                'website' => 'www.aaruganda.co.ug',
                'description' => 'Comprehensive Health Insurance Solutions',
                'is_active' => true,
            ],
            [
                'name' => 'Liberty Health Insurance',
                'code' => 'LIBUG',
                'slug' => 'liberty-health',
                'head_office_address' => 'Plot 15, Nakasero, Kampala',
                'postal_address' => 'P.O. Box 5678, Kampala',
                'phone' => '+256 414 234567',
                'email' => 'info@libertyuganda.com',
                'website' => 'www.libertyuganda.com',
                'description' => 'Liberty Health Insurance Services',
                'is_active' => true,
            ],
            [
                'name' => 'Jubilee Insurance Uganda',
                'code' => 'JUBUG',
                'slug' => 'jubilee-uganda',
                'head_office_address' => 'Plot 10, Kololo, Kampala',
                'postal_address' => 'P.O. Box 9012, Kampala',
                'phone' => '+256 312 456789',
                'email' => 'info@jubileeuganda.com',
                'website' => 'www.jubileeuganda.com',
                'description' => 'Jubilee Health Insurance Products',
                'is_active' => true,
            ],
        ];

        foreach ($companies as $companyData) {
            $company = InsuranceCompany::create($companyData);
            
            // Create a login user for each company
            $username = strtolower($companyData['code']);
            User::create([
                'name' => $companyData['name'] . ' Admin',
                'username' => $username,
                'email' => 'admin@' . strtolower($companyData['code']) . '.com',
                'password' => Hash::make('password'),
                'insurance_company_id' => $company->id,
            ]);
        }

        if ($this->command) {
            $this->command->info('Insurance companies and users created successfully!');
            $this->command->info('Login credentials:');
            $this->command->info('  Prudential: username: prudential, password: password');
            $this->command->info('  AAR: username: aarug, password: password');
            $this->command->info('  Liberty: username: libug, password: password');
            $this->command->info('  Jubilee: username: jubug, password: password');
        }
    }
}
