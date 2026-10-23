<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Filament\Resources\AssessmentResource;
use App\Filament\Resources\AssessmentResource\Pages\EditAssessment;
use App\Filament\Resources\AssessmentResource\Pages\ListAssessments;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_labels_match_between_navigation_breadcrumb_and_title(): void
    {
        $labels = [
            'ar' => 'التقييمات',
            'en' => 'Assessments',
            'fr' => 'Évaluations',
        ];

        $previousLocale = app()->getLocale();

        try {
            foreach ($labels as $locale => $label) {
                app()->setLocale($locale);

                $this->assertSame($label, AssessmentResource::getNavigationLabel());
                $this->assertSame($label, AssessmentResource::getPluralModelLabel());
                $this->assertSame($label, AssessmentResource::getBreadcrumb());

                $this->assertSame(
                    $label,
                    Livewire::actingAs(User::factory()->create())
                        ->test(ListAssessments::class)
                        ->instance()
                        ->getTitle(),
                );
            }
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    public function test_localized_assessment_pages_render_the_same_sidebar_and_title_label(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->get('/ar/auth/assessments')
            ->assertOk()
            ->assertSee('التقييمات')
            ->assertDontSee('دفتر العلامات');

        $this->actingAs($teacher)
            ->get('/en/auth/assessments')
            ->assertOk()
            ->assertSee('Assessments');

        $this->actingAs($teacher)
            ->get('/fr/auth/assessments')
            ->assertOk()
            ->assertSee('Évaluations');
    }

    public function test_unlocalized_assessment_url_redirects_to_the_session_locale(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->withSession(['locale' => 'ar'])
            ->get('/auth/assessments')
            ->assertRedirect(route('filament.auth.resources.assessments.index', [
                'locale' => 'ar',
            ]));

        $this->actingAs($teacher)
            ->withSession(['locale' => 'fr'])
            ->get('/auth/assessments')
            ->assertRedirect(route('filament.auth.resources.assessments.index', [
                'locale' => 'fr',
            ]));
    }

    public function test_unlocalized_assessment_url_falls_back_to_the_application_locale(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->get('/auth/assessments')
            ->assertRedirect(route('filament.auth.resources.assessments.index', [
                'locale' => config('app.locale'),
            ]));
    }

    public function test_teacher_can_print_own_assessment_gradebook_with_a4_rtl_layout(): void
    {
        $teacher = User::factory()->create(['name' => 'الأستاذة أمينة']);
        $classroom = Classroom::factory()->create([
            'name' => '1 م 1',
            'academic_year' => '2025/2026',
        ]);
        $group = Group::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => 'المجموعة أ',
        ]);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);

        $firstStudent = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'أمين',
            'last_name' => 'بلقاسم',
            'registration_number' => '001',
        ]);
        $secondStudent = Student::factory()->create([
            'group_id' => $group->id,
            'first_name' => 'سارة',
            'last_name' => 'مرابط',
            'registration_number' => '002',
        ]);

        $assessment = $this->createAssessment($teacher, $classroom, $group, $subject, [
            'title' => 'الفرض الأول',
            'type' => AssessmentType::Midterm,
            'date' => '2026-10-20',
            'max_score' => 20,
        ]);

        $url = route('print.assessment', [
            'locale' => 'ar',
            'assessment' => $assessment,
        ]);

        $this->actingAs($teacher)
            ->get($url)
            ->assertOk()
            ->assertSessionHas('locale', 'ar')
            ->assertSee('dir="rtl"', false)
            ->assertSee('size: A4', false)
            ->assertSee('كشف نقاط التقييم')
            ->assertSee('الفرض الأول')
            ->assertSee('الأستاذة أمينة')
            ->assertSee('1 م 1')
            ->assertSee('الإعلام الآلي')
            ->assertSee('أمين بلقاسم')
            ->assertSee('سارة مرابط')
            ->assertSee('window.print()', false);

        // Opening the print view materializes the roster, including students
        // who joined after the assessment was created.
        $this->assertSame(2, $assessment->grades()->count());

        $assessment->grades()
            ->where('student_id', $firstStudent->id)
            ->firstOrFail()
            ->update([
                'score' => 15.5,
                'remarks' => 'ممتاز',
            ]);

        $this->actingAs($teacher)
            ->get($url)
            ->assertOk()
            ->assertSee('15.50')
            ->assertSee('ممتاز')
            ->assertSee('نسبة التصحيح')
            ->assertSee('50%')
            ->assertSee('أعلى علامة')
            ->assertSee('أدنى علامة');

        $this->assertDatabaseHas('grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $secondStudent->id,
            'score' => null,
        ]);
    }

    public function test_teacher_cannot_print_another_teachers_assessment_but_admin_can(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $assessment = Assessment::factory()->create([
            'schedule_id' => Schedule::factory()->create([
                'user_id' => $otherTeacher->id,
            ])->id,
        ]);

        $url = route('print.assessment', [
            'locale' => 'en',
            'assessment' => $assessment,
        ]);

        $this->actingAs($teacher)->get($url)->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get($url)
            ->assertOk();
    }

    public function test_guests_are_redirected_to_the_localized_login_page(): void
    {
        $assessment = Assessment::factory()->create();

        $this->get(route('print.assessment', [
            'locale' => 'ar',
            'assessment' => $assessment,
        ]))->assertRedirect(route('filament.auth.auth.login', [
            'locale' => 'ar',
        ]));
    }

    public function test_print_route_rejects_an_unsupported_locale(): void
    {
        $teacher = User::factory()->create();
        $assessment = Assessment::factory()->create();

        $this->actingAs($teacher)
            ->get("/de/print/assessments/{$assessment->id}")
            ->assertNotFound();
    }

    public function test_print_actions_are_available_on_the_assessment_list_page(): void
    {
        $teacher = User::factory()->create();
        $assessment = $this->createAssessment(
            $teacher,
            Classroom::factory()->create(),
            null,
            Subject::factory()->create(),
        );

        $expectedUrl = route('print.assessment', [
            'locale' => app()->getLocale(),
            'assessment' => $assessment,
        ]);

        $component = Livewire::actingAs($teacher)->test(ListAssessments::class);

        $component
            ->assertActionExists('printGradebook')
            ->assertTableActionExists('printGradebook')
            ->assertTableActionHasUrl('printGradebook', $expectedUrl, $assessment)
            ->assertTableActionShouldOpenUrlInNewTab('printGradebook', $assessment);

        $component
            ->callAction('printGradebook', ['assessment_id' => $assessment->id])
            ->assertRedirect($expectedUrl);

        Livewire::actingAs($teacher)
            ->test(EditAssessment::class, ['record' => $assessment->getRouteKey()])
            ->assertActionExists('printGradebook');
    }

    public function test_header_print_action_cannot_select_another_teachers_assessment(): void
    {
        $teacher = User::factory()->create();
        $otherAssessment = Assessment::factory()->create();

        Livewire::actingAs($teacher)
            ->test(ListAssessments::class)
            ->callAction('printGradebook', ['assessment_id' => $otherAssessment->id])
            ->assertNotified(__('التقييم غير متاح'));
    }

    private function createAssessment(
        User $teacher,
        Classroom $classroom,
        ?Group $group,
        Subject $subject,
        array $attributes = [],
    ): Assessment {
        $schedule = Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'group_id' => $group?->id,
            'subject_id' => $subject->id,
        ]);

        return Assessment::factory()->create([
            ...$attributes,
            'schedule_id' => $schedule->id,
        ]);
    }
}
