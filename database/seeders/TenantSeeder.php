<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::updateOrCreate(
            ['domain' => 'sman1.smartschool.id'],
            ['name' => 'SMA Nusantara 1', 'status' => 'active'],
        );

        Tenant::updateOrCreate(
            ['domain' => 'smpharapan.smartschool.id'],
            ['name' => 'SMP Harapan Bangsa', 'status' => 'active'],
        );
    }
}
