<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signed_url_renders_portal(): void
    {
        $student = Student::factory()->create();
        $url = URL::temporarySignedRoute('parent.student-portal', now()->addDays(90), [
            'locale' => 'ar',
            'student' => $student,
        ]);

        $this->get($url)->assertOk();
    }

    public function test_expired_url_rejected(): void
    {
        $student = Student::factory()->create();
        $url = URL::temporarySignedRoute('parent.student-portal', now()->subDays(1), [
            'locale' => 'ar', 'student' => $student,
        ]);
        $this->get($url)->assertStatus(403);
    }

    public function test_teacher_generates_portal_for_own_student(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);
        $student = Student::factory()->create();

        $this->get(route('parent.student-portal', ['locale' => 'ar', 'student' => $student]))
            ->assertStatus(403);
    }
}
