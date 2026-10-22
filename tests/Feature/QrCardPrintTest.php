<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCardPrintTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['domain' => 'sma.test.id']);
        $this->admin = User::factory()->adminSekolah($this->tenant->id)->create();
        $this->student = Student::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    /**
     * Chromium mengabaikan @page yang ditulis di dalam @media print, sehingga
     * ukuran halaman cetak jatuh ke kertas bawaan printer. Aturan @page wajib
     * berada di luar blok media.
     */
    private function assertPageRuleIsTopLevel(string $html, string $expectedSize): void
    {
        $this->assertStringContainsString('@page', $html, 'Aturan @page tidak ditemukan.');

        $pageAt = strpos($html, '@page');
        $mediaAt = strpos($html, '@media print');

        $this->assertNotFalse($pageAt);
        $this->assertNotFalse($mediaAt);
        $this->assertLessThan(
            $mediaAt,
            $pageAt,
            'Aturan @page harus berada sebelum blok @media print, di luar media query.'
        );
        $this->assertStringContainsString($expectedSize, $html);
    }

    public function test_single_card_page_rule_is_top_level_and_exact_card_size(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('attendance.qrcode', $this->student))
            ->assertOk()
            ->getContent();

        $this->assertPageRuleIsTopLevel((string) $html, 'size: 85.6mm 53.98mm');
    }

    public function test_bulk_sheet_page_rule_is_top_level_and_a4(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('attendance.qrcodes'))
            ->assertOk()
            ->getContent();

        $this->assertPageRuleIsTopLevel((string) $html, 'size: 210mm 297mm');
    }

    /**
     * Tanpa ini, topbar aplikasi ikut tercetak dan min-h-screen (100vh)
     * memecah kartu melintasi halaman-halaman kosong.
     */
    public function test_print_css_hides_app_chrome_and_locks_the_page_box(): void
    {
        $html = (string) $this->actingAs($this->admin)
            ->get(route('attendance.qrcode', $this->student))
            ->getContent();

        $this->assertStringContainsString('.site-header', $html);
        $this->assertStringContainsString('.app-shell { min-height: 0 !important; }', $html);
        $this->assertStringContainsString('.app-content { margin-left: 0 !important; }', $html);
        $this->assertStringContainsString('height: 53.98mm', $html);
    }

    public function test_app_layout_exposes_print_hooks(): void
    {
        $this->assertStringContainsString('app-shell', (string) file_get_contents(resource_path('views/layouts/app.blade.php')));
        $this->assertStringContainsString('app-content', (string) file_get_contents(resource_path('views/layouts/app.blade.php')));
    }
}
