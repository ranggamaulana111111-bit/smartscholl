<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $orangTua;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['domain' => 'sma.test.id']);
        $this->orangTua = User::factory()->orangTua($this->tenant->id)->create();

        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenant->id]);
        $rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'name' => 'X-1',
        ]);
        $this->student = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $rombel->id,
            'nisn' => '0039111222',
        ]);

        StudentParent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->orangTua->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_parent_can_access_portal(): void
    {
        $this->actingAs($this->orangTua)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee($this->student->name);
    }

    public function test_non_parent_cannot_access_portal(): void
    {
        $guru = User::factory()->guru($this->tenant->id)->create();

        $this->actingAs($guru)
            ->get(route('parent.dashboard'))
            ->assertForbidden();
    }

    public function test_parent_only_sees_own_child(): void
    {
        $strangerParent = User::factory()->orangTua($this->tenant->id)->create();

        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenant->id]);
        $rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'name' => 'X-2',
        ]);
        $otherStudent = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $rombel->id,
            'nisn' => '0039333444',
        ]);
        StudentParent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $strangerParent->id,
            'student_id' => $otherStudent->id,
        ]);

        $this->actingAs($this->orangTua)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee($this->student->name)
            ->assertDontSee($otherStudent->name);
    }
}
