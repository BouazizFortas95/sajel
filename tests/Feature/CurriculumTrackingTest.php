<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Filament\Pages\CurriculumProgress;
use App\Filament\Resources\CurriculumDomainResource\Pages\CreateCurriculumDomain;
use App\Filament\Resources\CurriculumKnowledgeResource\Pages\CreateCurriculumKnowledgeResource;
use App\Filament\Resources\CurriculumSequenceResource\Pages\CreateCurriculumSequence;
use App\Filament\Resources\DailyLogResource;
use App\Filament\Resources\DailyLogResource\Pages\CreateDailyLog;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\CurriculumProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurriculumTrackingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: CurriculumDomain, 1: CurriculumSequence, 2: CurriculumResource}
     */
    private function createPlan(Subject $subject, Level $level, string $resourceName = 'مورد معرفي'): array
    {
        $domain = CurriculumDomain::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $level->id,
        ]);

        $sequence = CurriculumSequence::factory()->create([
            'curriculum_domain_id' => $domain->id,
        ]);

        $resource = CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequence->id,
            'name' => $resourceName,
        ]);

        return [$domain, $sequence, $resource];
    }

    private function createSchedule(
        User $teacher,
        Classroom $classroom,
        Subject $subject,
        ?Group $group = null,
    ): Schedule {
        return Schedule::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $group ? null : $classroom->id,
            'group_id' => $group?->id,
            'subject_id' => $subject->id,
        ]);
    }

    private function createCompletedLog(
        Schedule $schedule,
        CurriculumResource $resource,
        string $date,
    ): DailyLog {
        return DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'curriculum_resource_id' => $resource->id,
            'date' => $date,
            'status' => DailyLogStatus::Completed,
        ]);
    }

    public function test_the_curriculum_hierarchy_relationships_and_ordering_work(): void
    {
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $domain = CurriculumDomain::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $level->id,
        ]);
        $secondSequence = CurriculumSequence::factory()->create([
            'curriculum_domain_id' => $domain->id,
            'sort_order' => 2,
        ]);
        $firstSequence = CurriculumSequence::factory()->create([
            'curriculum_domain_id' => $domain->id,
            'sort_order' => 1,
        ]);
        $resource = CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $firstSequence->id,
        ]);

        $this->assertTrue($domain->subject->is($subject));
        $this->assertTrue($domain->level->is($level));
        $this->assertTrue($secondSequence->domain->is($domain));
        $this->assertTrue($resource->sequence->is($firstSequence));
        $this->assertTrue($domain->resources()->get()->contains($resource));
        $this->assertSame(
            [$firstSequence->id, $secondSequence->id],
            $domain->sequences()->ordered()->pluck('id')->all(),
        );

        $this->assertStringContainsString($subject->name, $domain->display_name());
        $this->assertStringContainsString($level->name, $domain->display_name());
        $this->assertStringContainsString($domain->name, $resource->label());
        $this->assertStringContainsString($firstSequence->name, $resource->label());
        $this->assertStringContainsString($resource->name, $resource->label());
    }

    public function test_a_daily_log_belongs_to_a_curriculum_resource_and_mentions_it_in_the_register_summary(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        [, , $resource] = $this->createPlan($subject, $level);
        $schedule = $this->createSchedule($teacher, $classroom, $subject);
        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'curriculum_resource_id' => $resource->id,
            'title' => 'درس تجريبي',
            'content_data' => [],
        ]);

        $this->assertTrue($log->curriculumResource->is($resource));
        $this->assertTrue($resource->dailyLogs()->get()->contains($log));

        $summary = $log->generateRegisterSummary();

        $this->assertStringContainsString('المورد المعرفي:', $summary);
        $this->assertStringContainsString($resource->name, $summary);
    }

    public function test_an_admin_can_create_curriculum_domains_sequences_and_knowledge_resources(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreateCurriculumDomain::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'name' => 'ميدان الأعداد',
                'code' => 'D1',
                'description' => 'ميدان اختباري.',
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $domain = CurriculumDomain::query()->where('name', 'ميدان الأعداد')->firstOrFail();

        Livewire::test(CreateCurriculumSequence::class)
            ->fillForm([
                'curriculum_domain_id' => $domain->id,
                'name' => 'مقطع الأعداد الطبيعية',
                'code' => 'S1',
                'description' => 'مقطع اختباري.',
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $sequence = CurriculumSequence::query()
            ->where('name', 'مقطع الأعداد الطبيعية')
            ->firstOrFail();

        Livewire::test(CreateCurriculumKnowledgeResource::class)
            ->fillForm([
                'curriculum_sequence_id' => $sequence->id,
                'name' => 'مورد الأعداد النسبية',
                'code' => 'R1',
                'description' => 'مورد اختباري.',
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('curriculum_domains', [
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'name' => 'ميدان الأعداد',
        ]);
        $this->assertDatabaseHas('curriculum_sequences', [
            'curriculum_domain_id' => $domain->id,
            'name' => 'مقطع الأعداد الطبيعية',
        ]);
        $this->assertDatabaseHas('curriculum_resources', [
            'curriculum_sequence_id' => $sequence->id,
            'name' => 'مورد الأعداد النسبية',
        ]);
    }

    public function test_the_curriculum_catalog_is_admin_only(): void
    {
        $teacher = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $domain = CurriculumDomain::factory()->create();
        $sequence = CurriculumSequence::factory()->create([
            'curriculum_domain_id' => $domain->id,
        ]);
        $resource = CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequence->id,
        ]);

        $records = [
            'curriculum-domains' => $domain,
            'curriculum-sequences' => $sequence,
            'curriculum-knowledge-resources' => $resource,
        ];

        $this->actingAs($teacher);

        foreach ($records as $slug => $record) {
            $this->get("/en/auth/{$slug}")->assertForbidden();
            $this->get("/en/auth/{$slug}/create")->assertForbidden();
            $this->get("/en/auth/{$slug}/{$record->id}/edit")->assertForbidden();
        }

        $this->actingAs($admin);

        foreach ($records as $slug => $record) {
            $this->get("/en/auth/{$slug}")->assertOk();
            $this->get("/en/auth/{$slug}/create")->assertOk();
            $this->get("/en/auth/{$slug}/{$record->id}/edit")->assertOk();
        }
    }

    public function test_daily_log_curriculum_options_are_scoped_to_the_schedule_subject_and_level(): void
    {
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        [$domain, $sequence, $resource] = $this->createPlan($subject, $level, 'المورد الصحيح');

        $otherSubject = Subject::factory()->create();
        [, , $otherSubjectResource] = $this->createPlan($otherSubject, $level, 'مورد مادة أخرى');

        $otherLevel = Level::factory()->create();
        [, , $otherLevelResource] = $this->createPlan($subject, $otherLevel, 'مورد مستوى آخر');

        $teacher = User::factory()->create();
        $schedule = $this->createSchedule($teacher, $classroom, $subject);

        $options = DailyLogResource::curriculumResourceOptions($schedule->id);

        $this->assertArrayHasKey($resource->id, $options);
        $this->assertArrayNotHasKey($otherSubjectResource->id, $options);
        $this->assertArrayNotHasKey($otherLevelResource->id, $options);
        $this->assertStringContainsString($domain->name, $options[$resource->id]);
        $this->assertStringContainsString($sequence->name, $options[$resource->id]);
        $this->assertStringContainsString($resource->name, $options[$resource->id]);

        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        $groupSchedule = $this->createSchedule($teacher, $classroom, $subject, $group);

        $this->assertArrayHasKey(
            $resource->id,
            DailyLogResource::curriculumResourceOptions($groupSchedule->id),
        );
    }

    public function test_a_daily_log_rejects_a_resource_from_another_subject_or_level_and_accepts_a_matching_resource(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        [, , $resource] = $this->createPlan($subject, $level, 'المورد المطابق');
        $otherSubject = Subject::factory()->create();
        [, , $otherResource] = $this->createPlan($otherSubject, $level, 'مورد مادة أخرى');
        $schedule = $this->createSchedule($teacher, $classroom, $subject);

        $this->actingAs($teacher);

        $component = Livewire::withQueryParams([
            'schedule_id' => $schedule->id,
            'date' => today()->toDateString(),
        ])
            ->test(CreateDailyLog::class)
            ->fillForm([
                'title' => 'درس بربط خاطئ',
                'curriculum_resource_id' => $otherResource->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['curriculum_resource_id']);

        $component
            ->fillForm([
                'curriculum_resource_id' => $resource->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $log = DailyLog::query()->where('title', 'درس بربط خاطئ')->firstOrFail();

        $this->assertSame($resource->id, $log->curriculum_resource_id);
    }

    public function test_completing_a_mapped_log_advances_the_sequence_and_the_classroom_progress(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        [, $sequence, $firstResource] = $this->createPlan($subject, $level, 'المورد الأول');
        $secondResource = CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequence->id,
            'name' => 'المورد الثاني',
        ]);
        $schedule = $this->createSchedule($teacher, $classroom, $subject);
        $service = app(CurriculumProgressService::class);

        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'curriculum_resource_id' => $firstResource->id,
            'date' => today()->subDay()->toDateString(),
            'status' => DailyLogStatus::Planned,
        ]);

        $detail = $service->detail($classroom, $subject);

        $this->assertSame(0, $detail['percentage']);
        $this->assertSame(0, $detail['completed_resources']);
        $this->assertSame(2, $detail['total_resources']);
        $this->assertSame('not_started', $detail['domains'][0]['status']);
        $this->assertSame('not_started', $detail['domains'][0]['sequences'][0]['status']);

        $log->markAsCompleted();

        $detail = $service->detail($classroom, $subject);

        $this->assertSame(50, $detail['percentage']);
        $this->assertSame(1, $detail['completed_resources']);
        $this->assertSame(0, $detail['completed_sequences']);
        $this->assertSame('in_progress', $detail['domains'][0]['status']);
        $this->assertSame('in_progress', $detail['domains'][0]['sequences'][0]['status']);

        $this->createCompletedLog($schedule, $secondResource, today()->toDateString());

        $detail = $service->detail($classroom, $subject);

        $this->assertSame(100, $detail['percentage']);
        $this->assertSame(2, $detail['completed_resources']);
        $this->assertSame(1, $detail['completed_sequences']);
        $this->assertSame('completed', $detail['domains'][0]['status']);
        $this->assertSame('completed', $detail['domains'][0]['sequences'][0]['status']);
        $this->assertTrue($detail['domains'][0]['sequences'][0]['resources'][0]['completed']);
    }

    public function test_progress_counts_each_resource_once_even_when_it_has_multiple_completed_logs(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        [, $sequence, $firstResource] = $this->createPlan($subject, $level, 'المورد المكرر');
        CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequence->id,
            'name' => 'المورد الثاني',
        ]);
        $schedule = $this->createSchedule($teacher, $classroom, $subject);

        $this->createCompletedLog($schedule, $firstResource, today()->subDays(2)->toDateString());
        $this->createCompletedLog($schedule, $firstResource, today()->subDay()->toDateString());

        $detail = app(CurriculumProgressService::class)->detail($classroom, $subject);

        $this->assertSame(1, $detail['completed_resources']);
        $this->assertSame(2, $detail['total_resources']);
        $this->assertSame(50, $detail['percentage']);
    }

    public function test_progress_is_scoped_to_the_classroom_and_subject(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create([
            'level_id' => $level->id,
            'name' => 'أ 1',
        ]);
        $otherClassroom = Classroom::factory()->create([
            'level_id' => $level->id,
            'name' => 'ب 1',
        ]);
        [, $sequence, $firstResource] = $this->createPlan($subject, $level, 'المورد الأول');
        CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequence->id,
            'name' => 'المورد الثاني',
        ]);
        $schedule = $this->createSchedule($teacher, $classroom, $subject);
        $this->createCompletedLog($schedule, $firstResource, today()->toDateString());

        $otherSubject = Subject::factory()->create();
        [, , $otherSubjectResource] = $this->createPlan($otherSubject, $level, 'مورد مادة أخرى');
        $otherSubjectSchedule = $this->createSchedule($teacher, $classroom, $otherSubject);
        $this->createCompletedLog($otherSubjectSchedule, $otherSubjectResource, today()->toDateString());

        $service = app(CurriculumProgressService::class);

        $this->assertSame(50, $service->detail($classroom, $subject)['percentage']);
        $this->assertSame(0, $service->detail($otherClassroom, $subject)['percentage']);
        $this->assertSame(100, $service->detail($classroom, $otherSubject)['percentage']);
        $this->assertSame(50, $service->detail($classroom, $subject)['percentage']);
    }

    public function test_progress_counts_lessons_assigned_through_a_group(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        $group = Group::factory()->create(['classroom_id' => $classroom->id]);
        [, , $resource] = $this->createPlan($subject, $level);
        $schedule = $this->createSchedule($teacher, $classroom, $subject, $group);
        $this->createCompletedLog($schedule, $resource, today()->toDateString());

        $service = app(CurriculumProgressService::class);
        $detail = $service->detail($classroom, $subject);
        $overview = $service->overview($teacher);

        $this->assertSame(100, $detail['percentage']);
        $this->assertCount(1, $overview);
        $this->assertSame($classroom->id, $overview->first()['classroom']->id);
        $this->assertSame(100, $overview->first()['percentage']);
    }

    public function test_the_overview_is_scoped_to_a_teachers_classes_but_counts_the_whole_class_and_admins_see_every_pair(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();
        $teacherC = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $subjectA = Subject::factory()->create();
        $levelA = Level::factory()->create();
        $classroomA = Classroom::factory()->create(['level_id' => $levelA->id]);
        [, $sequenceA, $firstResource] = $this->createPlan($subjectA, $levelA, 'المورد الأول');
        CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $sequenceA->id,
            'name' => 'المورد الثاني',
        ]);
        $scheduleA = $this->createSchedule($teacherA, $classroomA, $subjectA);
        $scheduleB = $this->createSchedule($teacherB, $classroomA, $subjectA);
        $this->createCompletedLog($scheduleB, $firstResource, today()->toDateString());

        $subjectB = Subject::factory()->create();
        $levelB = Level::factory()->create();
        $classroomB = Classroom::factory()->create(['level_id' => $levelB->id]);
        [, , $resourceB] = $this->createPlan($subjectB, $levelB, 'مورد القسم الآخر');
        $scheduleC = $this->createSchedule($teacherC, $classroomB, $subjectB);
        $this->createCompletedLog($scheduleC, $resourceB, today()->toDateString());

        $service = app(CurriculumProgressService::class);
        $teacherOverview = $service->overview($teacherA)->keyBy(
            fn (array $row): string => $row['classroom']->id.':'.$row['subject']->id,
        );
        $adminOverview = $service->overview($admin)->keyBy(
            fn (array $row): string => $row['classroom']->id.':'.$row['subject']->id,
        );

        $this->assertCount(1, $teacherOverview);
        $this->assertArrayHasKey($classroomA->id.':'.$subjectA->id, $teacherOverview);
        $this->assertArrayNotHasKey($classroomB->id.':'.$subjectB->id, $teacherOverview);
        $this->assertSame(1, $teacherOverview->first()['completed_resources']);
        $this->assertSame(2, $teacherOverview->first()['total_resources']);
        $this->assertSame(50, $teacherOverview->first()['percentage']);

        $this->assertCount(2, $adminOverview);
        $this->assertArrayHasKey($classroomA->id.':'.$subjectA->id, $adminOverview);
        $this->assertArrayHasKey($classroomB->id.':'.$subjectB->id, $adminOverview);
        $this->assertSame(100, $adminOverview->get($classroomB->id.':'.$subjectB->id)['percentage']);
    }

    public function test_the_progress_page_defaults_teachers_to_their_class_and_hides_other_teachers_pairs(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create(['name' => 'المستوى الأول']);
        $classroom = Classroom::factory()->create([
            'level_id' => $level->id,
            'name' => 'قسم المعلم',
        ]);
        [, , $resource] = $this->createPlan($subject, $level);
        $schedule = $this->createSchedule($teacher, $classroom, $subject);
        $this->createCompletedLog($schedule, $resource, today()->toDateString());

        $otherSubject = Subject::factory()->create();
        $otherLevel = Level::factory()->create(['name' => 'المستوى الثاني']);
        $otherClassroom = Classroom::factory()->create([
            'level_id' => $otherLevel->id,
            'name' => 'قسم معلم آخر',
        ]);
        [, , $otherResource] = $this->createPlan($otherSubject, $otherLevel);
        $otherSchedule = $this->createSchedule($otherTeacher, $otherClassroom, $otherSubject);
        $this->createCompletedLog($otherSchedule, $otherResource, today()->toDateString());

        $this->actingAs($teacher);

        $component = Livewire::test(CurriculumProgress::class)
            ->assertOk()
            ->assertSet('subjectId', $subject->id)
            ->assertSet('classroomId', $classroom->id)
            ->assertSee($subject->name)
            ->assertSee($resource->name)
            ->assertSee('100%');

        $component
            ->call('showDetail', $otherSubject->id, $otherClassroom->id)
            ->assertDontSee($otherResource->name)
            ->assertDontSee($otherClassroom->display_name());
    }

    public function test_admins_see_all_pairs_on_the_progress_page_and_the_page_is_localized(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        [, , $resource] = $this->createPlan($subject, $level);
        $schedule = $this->createSchedule($teacher, $classroom, $subject);
        $this->createCompletedLog($schedule, $resource, today()->toDateString());

        $this->actingAs($admin);

        Livewire::test(CurriculumProgress::class)
            ->assertOk()
            ->assertSee($classroom->display_name())
            ->assertSee($subject->name);

        $this->get('/en/auth/curriculum-progress')
            ->assertOk()
            ->assertSee('Curriculum progress');

        $this->get('/ar/auth/curriculum-progress')
            ->assertOk()
            ->assertSee('تقدم المنهاج');

        $this->get('/fr/auth/curriculum-progress')
            ->assertOk()
            ->assertSee('Progression du programme');
    }

    public function test_the_progress_page_filters_and_sorts_the_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->create();
        $level = Level::factory()->create();

        $fixtures = [
            ['Alpha', 4],
            ['Beta', 1],
            ['Gamma', 0],
            ['Delta', 3],
        ];

        foreach ($fixtures as $index => [$name, $completed]) {
            $subject = Subject::factory()->create(['name' => $name]);
            $classroom = Classroom::factory()->create([
                'level_id' => $level->id,
                'name' => 'Class '.($index + 1),
            ]);

            [, $sequence, $firstResource] = $this->createPlan($subject, $level, $name.' resource 1');
            $resources = collect([$firstResource]);

            for ($position = 2; $position <= 4; $position++) {
                $resources->push(CurriculumResource::factory()->create([
                    'curriculum_sequence_id' => $sequence->id,
                    'name' => $name.' resource '.$position,
                ]));
            }

            $schedule = $this->createSchedule($teacher, $classroom, $subject);

            foreach ($resources->take($completed) as $resourceIndex => $resource) {
                $this->createCompletedLog(
                    $schedule,
                    $resource,
                    today()->subDays($resourceIndex)->toDateString(),
                );
            }
        }

        $this->actingAs($admin);

        $component = Livewire::test(CurriculumProgress::class);

        $this->assertSame(
            ['Class 1', 'Class 2', 'Class 3', 'Class 4'],
            $component->instance()->filteredOverview
                ->map(fn (array $row): string => $row['classroom']->name)
                ->all(),
        );

        $component->set('search', 'Delta');
        $this->assertSame(
            ['Delta'],
            $component->instance()->filteredOverview
                ->map(fn (array $row): string => $row['subject']->name)
                ->all(),
        );

        $component->set('search', 'Class 2');
        $this->assertSame(
            ['Beta'],
            $component->instance()->filteredOverview
                ->map(fn (array $row): string => $row['subject']->name)
                ->all(),
        );

        $component->set('search', '');

        $component->set('progressStatus', 'completed');
        $this->assertSame(
            ['Class 1'],
            $component->instance()->filteredOverview
                ->map(fn (array $row): string => $row['classroom']->name)
                ->all(),
        );

        $component->set('progressStatus', 'in_progress');
        $this->assertSame(
            ['Class 2', 'Class 4'],
            $component->instance()->filteredOverview
                ->map(fn (array $row): string => $row['classroom']->name)
                ->all(),
        );

        $component->set('progressStatus', 'not_started');
        $this->assertSame(
            ['Class 3'],
            $component->instance()->filteredOverview
                ->map(fn (array $row): string => $row['classroom']->name)
                ->all(),
        );

        $component->set('progressStatus', 'at_risk');
        $this->assertSame(
            ['Class 2', 'Class 3'],
            $component->instance()->filteredOverview
                ->map(fn (array $row): string => $row['classroom']->name)
                ->all(),
        );

        $component->set('progressStatus', 'all');
        $component->set('sortBy', 'percentage_desc');
        $this->assertSame(
            [100, 75, 25, 0],
            $component->instance()->filteredOverview
                ->pluck('percentage')
                ->all(),
        );

        $component->set('sortBy', 'percentage_asc');
        $this->assertSame(
            [0, 25, 75, 100],
            $component->instance()->filteredOverview
                ->pluck('percentage')
                ->all(),
        );

        $component->set('sortBy', 'completed_desc');
        $this->assertSame(
            [4, 3, 1, 0],
            $component->instance()->filteredOverview
                ->pluck('completed_resources')
                ->all(),
        );

        $component->set('sortBy', 'subject');
        $this->assertSame(
            ['Alpha', 'Beta', 'Delta', 'Gamma'],
            $component->instance()->filteredOverview
                ->map(fn (array $row): string => $row['subject']->name)
                ->all(),
        );
    }

    public function test_the_progress_page_paginates_without_scrolling_the_page(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $this->createPlan($subject, $level);

        foreach (range(1, 12) as $position) {
            $classroom = Classroom::factory()->create([
                'level_id' => $level->id,
                'name' => sprintf('Class %02d', $position),
            ]);

            $this->createSchedule($teacher, $classroom, $subject);
        }

        $this->actingAs($admin);

        $component = Livewire::test(CurriculumProgress::class);

        $paginator = $component->instance()->getOverviewPaginatorProperty();

        $this->assertSame(12, $paginator->total());
        $this->assertSame(10, $paginator->perPage());
        $this->assertCount(10, $paginator->items());

        $html = $component->html();

        $this->assertStringContainsString('fi-pagination', $html);
        $this->assertStringNotContainsString('scrollIntoView', $html);
        $this->assertStringContainsString('nextPage', $html);

        $component->call('gotoPage', 2);

        $paginator = $component->instance()->getOverviewPaginatorProperty();

        $this->assertSame(2, $paginator->currentPage());
        $this->assertCount(2, $paginator->items());

        $component->set('perPage', 25);

        $paginator = $component->instance()->getOverviewPaginatorProperty();

        $this->assertSame(1, $paginator->currentPage());
        $this->assertSame(25, $paginator->perPage());
        $this->assertCount(12, $paginator->items());
    }

    public function test_the_progress_page_tools_reset_and_export_a_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create([
            'level_id' => $level->id,
            'name' => 'قسم التصدير',
        ]);
        [, , $resource] = $this->createPlan($subject, $level);
        $schedule = $this->createSchedule($teacher, $classroom, $subject);
        $this->createCompletedLog($schedule, $resource, today()->toDateString());

        $this->actingAs($admin);

        $component = Livewire::test(CurriculumProgress::class)
            ->set('search', 'الرياضيات')
            ->set('progressStatus', 'completed')
            ->set('sortBy', 'percentage_desc')
            ->set('perPage', 25);

        $component->call('resetTableTools')
            ->assertSet('search', '')
            ->assertSet('progressStatus', 'all')
            ->assertSet('sortBy', 'classroom')
            ->assertSet('perPage', 10)
            ->assertSet('paginators.page', 1);

        $component
            ->set('subjectId', $subject->id)
            ->set('classroomId', $classroom->id)
            ->set('search', 'قسم التصدير')
            ->set('progressStatus', 'in_progress')
            ->call('clearFilters')
            ->assertSet('subjectId', null)
            ->assertSet('classroomId', null)
            ->assertSet('search', '')
            ->assertSet('progressStatus', 'all');

        $component->set('search', 'الرياضيات');

        $component->call('exportCsv')
            ->assertFileDownloaded('curriculum-progress-'.now()->format('Y-m-d').'.csv');

        $csv = base64_decode((string) data_get($component->effects, 'download.content'));

        $this->assertStringContainsString('قسم التصدير', $csv);
        $this->assertStringContainsString('الرياضيات', $csv);
        $this->assertStringContainsString('100%', $csv);
    }

    public function test_deleting_a_resource_nulls_the_daily_log_mapping_and_deleting_a_domain_cascades(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();
        $classroom = Classroom::factory()->create(['level_id' => $level->id]);
        [$domain, $sequence, $resource] = $this->createPlan($subject, $level);
        $schedule = $this->createSchedule($teacher, $classroom, $subject);
        $log = DailyLog::factory()->create([
            'schedule_id' => $schedule->id,
            'curriculum_resource_id' => $resource->id,
        ]);

        $resource->delete();

        $this->assertNull($log->refresh()->curriculum_resource_id);
        $this->assertDatabaseMissing('curriculum_resources', ['id' => $resource->id]);

        $secondDomain = CurriculumDomain::factory()->create();
        $secondSequence = CurriculumSequence::factory()->create([
            'curriculum_domain_id' => $secondDomain->id,
        ]);
        $secondResource = CurriculumResource::factory()->create([
            'curriculum_sequence_id' => $secondSequence->id,
        ]);

        $secondDomain->delete();

        $this->assertDatabaseMissing('curriculum_sequences', ['id' => $secondSequence->id]);
        $this->assertDatabaseMissing('curriculum_resources', ['id' => $secondResource->id]);
        $this->assertDatabaseMissing('curriculum_domains', ['id' => $secondDomain->id]);
    }
}
