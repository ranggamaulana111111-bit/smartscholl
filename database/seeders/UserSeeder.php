<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenantA = Tenant::where('domain', 'sman1.smartschool.id')->first();

        User::updateOrCreate(['email' => 'superadmin@smartschool.id'], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'tenant_id' => null,
        ]);

        User::updateOrCreate(['email' => 'admin@sman1.smartschool.id'], [
            'name' => 'Admin SMA Nusantara',
            'password' => Hash::make('password'),
            'role' => 'admin_sekolah',
            'tenant_id' => $tenantA?->id,
        ]);

        User::updateOrCreate(['email' => 'guru@sman1.smartschool.id'], [
            'name' => 'Guru Budi',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'tenant_id' => $tenantA?->id,
        ]);

        User::updateOrCreate(['email' => 'andi@sman1.smartschool.id'], [
            'name' => 'Siswa Andi',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'tenant_id' => $tenantA?->id,
        ]);
    }
}
