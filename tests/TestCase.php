<?php

namespace Tests;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Rute produksi memakai dua lapis gerbang, `role:` dan `permission:`.
        // Tanpa baris ini setiap pengujian peran non-Super Admin akan tersandung
        // 403 padahal tabel permissions-nya memang belum diisi, sehingga gate
        // izin yang baru dipasang tidak pernah ikut teruji.
        //
        // Hanya untuk test yang benar-benar menjalankan migrasi: test seperti
        // ExampleTest tidak punya tabel sama sekali.
        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->seed(PermissionSeeder::class);
        }
    }
}
