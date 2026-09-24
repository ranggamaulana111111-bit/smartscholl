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
    }
}
