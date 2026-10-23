<?php

namespace Tests\Feature;

use App\Filament\Resources\PedagogicalMemoResource;
use App\Filament\Resources\PedagogicalMemoResource\Pages\CreatePedagogicalMemo;
use App\Filament\Resources\PedagogicalMemoResource\Pages\EditPedagogicalMemo;
use App\Filament\Resources\PedagogicalMemoResource\Pages\ListPedagogicalMemos;
use App\Models\Level;
use App\Models\PedagogicalMemo;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalMemoPrintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class PedagogicalMemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_memo_belongs_to_a_teacher_subject_and_level_and_casts_activities(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create(['name' => 'الرياضيات']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);

        $memo = PedagogicalMemo::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'activities' => [
                ['name' => 'التمهيد', 'duration' => 5, 'content' => 'طرح الوضعية.'],
            ],
        ]);

        $this->assertTrue($memo->user->is($teacher));
        $this->assertTrue($memo->subject->is($subject));
        $this->assertTrue($memo->level->is($level));
        $this->assertIsArray($memo->activities);
        $this->assertSame(1, $memo->activityCount());
        $this->assertFalse($memo->is_template);
        $this->assertFalse($memo->is_archived);
    }

    public function test_teachers_only_see_their_own_memos_in_the_resource(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $ownMemo = $this->createMemo($teacher);
        $otherMemo = $this->createMemo($otherTeacher);

        $this->actingAs($teacher);

        $this->assertSame(
            [$ownMemo->id],
            PedagogicalMemoResource::getEloquentQuery()->pluck('id')->all(),
        );

        Livewire::test(ListPedagogicalMemos::class)
            ->assertCanSeeTableRecords([$ownMemo])
            ->assertCanNotSeeTableRecords([$otherMemo]);
    }

    public function test_admins_see_every_teachers_memos(): void
    {
        $admin = User::factory()->admin()->create();
        $firstMemo = $this->createMemo(User::factory()->create());
        $secondMemo = $this->createMemo(User::factory()->create());

        $this->actingAs($admin);

        Livewire::test(ListPedagogicalMemos::class)
            ->assertCanSeeTableRecords([$firstMemo, $secondMemo]);
    }

    public function test_a_teacher_can_create_a_memo_and_ownership_is_forced(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $level = Level::factory()->create(['name' => 'السنة الثانية متوسط']);

        $this->actingAs($teacher);

        Livewire::test(CreatePedagogicalMemo::class)
            ->fillForm([
                'title' => 'مذكرة الخوارزميات',
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'phase' => 'المقطع الأول',
                'competence' => 'يحلّ التلميذ مسألة بسيطة.',
                'starting_situation' => 'وضعية انطلاقية من الحياة اليومية.',
                'activities' => [
                    [
                        'name' => 'التمهيد',
                        'duration' => 5,
                        'strategy' => 'حوار',
                        'content' => 'طرح الوضعية.',
                    ],
                ],
                'resources' => 'السبورة والبطاقات.',
                'evaluation' => 'تمرين تطبيقي.',
                'notes' => 'مراعاة الفروق الفردية.',
                'is_template' => true,
                'user_id' => $otherTeacher->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $memo = PedagogicalMemo::query()
            ->where('title', 'مذكرة الخوارزميات')
            ->firstOrFail();

        $this->assertSame($teacher->id, $memo->user_id);
        $this->assertSame($subject->id, $memo->subject_id);
        $this->assertSame($level->id, $memo->level_id);
        $this->assertSame('المقطع الأول', $memo->phase);
        $this->assertSame(1, $memo->activityCount());
        $this->assertTrue($memo->is_template);
    }

    public function test_an_admin_can_assign_a_memo_to_a_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreatePedagogicalMemo::class)
            ->fillForm([
                'title' => 'مذكرة مسندة',
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'competence' => 'كفاءة مستهدفة.',
                'user_id' => $teacher->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $memo = PedagogicalMemo::query()
            ->where('title', 'مذكرة مسندة')
            ->firstOrFail();

        $this->assertSame($teacher->id, $memo->user_id);
    }

    public function test_an_admin_must_choose_a_teacher_when_creating_a_memo(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create();
        $level = Level::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreatePedagogicalMemo::class)
            ->fillForm([
                'title' => 'مذكرة بدون أستاذ',
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'competence' => 'كفاءة مستهدفة.',
            ])
            ->call('create')
            ->assertHasFormErrors(['user_id' => 'required']);
    }

    public function test_a_teacher_can_edit_their_own_memo(): void
    {
        $teacher = User::factory()->create();
        $memo = $this->createMemo($teacher);

        $this->actingAs($teacher);

        Livewire::test(EditPedagogicalMemo::class, ['record' => $memo->getRouteKey()])
            ->fillForm([
                'title' => 'مذكرة محدثة',
                'competence' => 'كفاءة محدثة بعد المراجعة.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $memo->refresh();

        $this->assertSame('مذكرة محدثة', $memo->title);
        $this->assertSame('كفاءة محدثة بعد المراجعة.', $memo->competence);
    }

    public function test_a_memo_can_be_duplicated_into_the_current_teachers_bank(): void
    {
        $teacher = User::factory()->create();
        $memo = $this->createMemo($teacher, [
            'title' => 'مذكرة الأصل',
            'is_template' => true,
            'is_archived' => true,
        ]);

        $this->actingAs($teacher);

        Livewire::test(ListPedagogicalMemos::class)
            ->callTableAction('duplicate', $memo)
            ->assertNotified(__('تم نسخ المذكرة'));

        $copy = PedagogicalMemo::query()
            ->where('title', 'مذكرة الأصل — '.__('نسخة'))
            ->firstOrFail();

        $this->assertSame($teacher->id, $copy->user_id);
        $this->assertSame($memo->subject_id, $copy->subject_id);
        $this->assertSame($memo->level_id, $copy->level_id);
        $this->assertSame($memo->activities, $copy->activities);
        $this->assertFalse($copy->is_template);
        $this->assertFalse($copy->is_archived);
    }

    public function test_a_memo_can_be_archived_and_restored_from_the_table(): void
    {
        $teacher = User::factory()->create();
        $memo = $this->createMemo($teacher);

        $this->actingAs($teacher);

        Livewire::test(ListPedagogicalMemos::class)
            ->callTableAction('archive', $memo)
            ->assertNotified(__('تمت أرشفة المذكرة'));

        $this->assertTrue($memo->refresh()->is_archived);

        Livewire::test(ListPedagogicalMemos::class)
            ->callTableAction('restore', $memo)
            ->assertNotified(__('تمت استعادة المذكرة'));

        $this->assertFalse($memo->refresh()->is_archived);
    }

    public function test_memos_can_be_filtered_by_template_and_archive_state(): void
    {
        $teacher = User::factory()->create();
        $standardMemo = $this->createMemo($teacher);
        $templateMemo = $this->createMemo($teacher, ['is_template' => true]);
        $archivedMemo = $this->createMemo($teacher, ['is_archived' => true]);

        $this->actingAs($teacher);

        Livewire::test(ListPedagogicalMemos::class)
            ->assertCanSeeTableRecords([$standardMemo, $templateMemo, $archivedMemo])
            ->filterTable('is_template', true)
            ->assertCanSeeTableRecords([$templateMemo])
            ->assertCanNotSeeTableRecords([$standardMemo, $archivedMemo])
            ->removeTableFilter('is_template')
            ->filterTable('is_archived', true)
            ->assertCanSeeTableRecords([$archivedMemo])
            ->assertCanNotSeeTableRecords([$standardMemo, $templateMemo]);
    }

    public function test_teachers_cannot_view_edit_or_print_another_teachers_memo_but_admins_can(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $memo = $this->createMemo($otherTeacher);

        $this->assertFalse($teacher->can('view', $memo));
        $this->assertFalse($teacher->can('update', $memo));
        $this->assertFalse($teacher->can('delete', $memo));
        $this->assertFalse($teacher->can('duplicate', $memo));
        $this->assertFalse($teacher->can('archive', $memo));
        $this->assertFalse($teacher->can('print', $memo));
        $this->assertFalse($teacher->can('downloadPdf', $memo));

        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('view', $memo));
        $this->assertTrue($admin->can('update', $memo));
        $this->assertTrue($admin->can('print', $memo));
        $this->assertTrue($admin->can('downloadPdf', $memo));

        $this->actingAs($teacher)
            ->get("/en/auth/pedagogical-memos/{$memo->id}/edit")
            ->assertNotFound();

        $this->actingAs($teacher)
            ->get(route('print.pedagogical-memo', [
                'locale' => 'en',
                'memo' => $memo,
            ]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('print.pedagogical-memo', [
                'locale' => 'en',
                'memo' => $memo,
            ]))
            ->assertOk();
    }

    public function test_localized_memo_pages_render_the_same_sidebar_and_title_label(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->get('/ar/auth/pedagogical-memos')
            ->assertOk()
            ->assertSee('المذكرات البيداغوجية');

        $this->actingAs($teacher)
            ->get('/en/auth/pedagogical-memos')
            ->assertOk()
            ->assertSee('Pedagogical memos');

        $this->actingAs($teacher)
            ->get('/fr/auth/pedagogical-memos')
            ->assertOk()
            ->assertSee('Mémos pédagogiques');
    }

    public function test_unlocalized_memo_url_redirects_to_the_session_locale(): void
    {
        $teacher = User::factory()->create();

        $this->actingAs($teacher)
            ->withSession(['locale' => 'ar'])
            ->get('/auth/pedagogical-memos')
            ->assertRedirect(route('filament.auth.resources.pedagogical-memos.index', [
                'locale' => 'ar',
            ]));

        $this->actingAs($teacher)
            ->withSession(['locale' => 'fr'])
            ->get('/auth/pedagogical-memos')
            ->assertRedirect(route('filament.auth.resources.pedagogical-memos.index', [
                'locale' => 'fr',
            ]));
    }

    public function test_teacher_can_print_own_memo_as_a_standalone_a4_document(): void
    {
        $teacher = User::factory()->create(['name' => 'الأستاذة أمينة']);
        $subject = Subject::factory()->create(['name' => 'الإعلام الآلي']);
        $level = Level::factory()->create(['name' => 'السنة الأولى متوسط']);

        $memo = PedagogicalMemo::factory()->create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'title' => 'مذكرة الخوارزميات',
            'phase' => 'المقطع الأول',
            'competence' => 'يحلّ التلميذ مسألة بسيطة.',
            'starting_situation' => 'وضعية انطلاقية من الحياة اليومية.',
            'activities' => [
                [
                    'name' => 'التمهيد',
                    'duration' => 5,
                    'strategy' => 'حوار',
                    'content' => 'طرح الوضعية.',
                ],
                [
                    'name' => 'التقويم',
                    'duration' => 10,
                    'strategy' => 'عمل فردي',
                    'content' => 'تمرين تطبيقي.',
                ],
            ],
            'resources' => 'السبورة والبطاقات.',
            'evaluation' => 'تمرين فردي.',
            'notes' => 'مراعاة الفروق الفردية.',
        ]);

        $this->actingAs($teacher)
            ->get(route('print.pedagogical-memo', [
                'locale' => 'ar',
                'memo' => $memo,
            ]))
            ->assertOk()
            ->assertSessionHas('locale', 'ar')
            ->assertSee('dir="rtl"', false)
            ->assertSee('size: A4', false)
            ->assertSee('مذكرة بيداغوجية')
            ->assertSee('مذكرة الخوارزميات')
            ->assertSee('الأستاذة أمينة')
            ->assertSee('الإعلام الآلي')
            ->assertSee('السنة الأولى متوسط')
            ->assertSee('المقطع الأول')
            ->assertSee('يحلّ التلميذ مسألة بسيطة.')
            ->assertSee('وضعية انطلاقية من الحياة اليومية.')
            ->assertSee('التمهيد')
            ->assertSee('طرح الوضعية.')
            ->assertSee('السبورة والبطاقات.')
            ->assertSee('تمرين فردي.')
            ->assertSee('مراعاة الفروق الفردية.')
            ->assertSee('window.print()', false);
    }

    public function test_english_and_french_print_views_use_ltr_layout_and_localized_labels(): void
    {
        $teacher = User::factory()->create();
        $memo = $this->createMemo($teacher);

        $this->actingAs($teacher)
            ->get(route('print.pedagogical-memo', [
                'locale' => 'en',
                'memo' => $memo,
            ]))
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('size: A4', false)
            ->assertSee('Pedagogical memo');

        $this->actingAs($teacher)
            ->get(route('print.pedagogical-memo', [
                'locale' => 'fr',
                'memo' => $memo,
            ]))
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('size: A4', false)
            ->assertSee('Mémo pédagogique');
    }

    public function test_guests_are_redirected_to_the_localized_login_page(): void
    {
        $memo = PedagogicalMemo::factory()->create();

        $this->get(route('print.pedagogical-memo', [
            'locale' => 'ar',
            'memo' => $memo,
        ]))->assertRedirect(route('filament.auth.auth.login', [
            'locale' => 'ar',
        ]));
    }

    public function test_print_route_rejects_an_unsupported_locale(): void
    {
        $teacher = User::factory()->create();
        $memo = $this->createMemo($teacher);

        $this->actingAs($teacher)
            ->get("/de/print/pedagogical-memos/{$memo->id}")
            ->assertNotFound();
    }

    public function test_print_and_pdf_actions_are_available_on_the_list_and_edit_pages(): void
    {
        $teacher = User::factory()->create();
        $memo = $this->createMemo($teacher);

        $printUrl = route('print.pedagogical-memo', [
            'locale' => app()->getLocale(),
            'memo' => $memo,
        ]);
        $pdfUrl = route('print.pedagogical-memo.pdf', [
            'locale' => app()->getLocale(),
            'memo' => $memo,
        ]);

        Livewire::actingAs($teacher)
            ->test(ListPedagogicalMemos::class)
            ->assertTableActionExists('print')
            ->assertTableActionHasUrl('print', $printUrl, $memo)
            ->assertTableActionShouldOpenUrlInNewTab('print', $memo)
            ->assertTableActionExists('downloadPdf')
            ->assertTableActionHasUrl('downloadPdf', $pdfUrl, $memo)
            ->assertTableActionShouldOpenUrlInNewTab('downloadPdf', $memo)
            ->assertTableActionExists('duplicate')
            ->assertTableActionExists('archive');

        Livewire::actingAs($teacher)
            ->test(EditPedagogicalMemo::class, ['record' => $memo->getRouteKey()])
            ->assertActionExists('print')
            ->assertActionExists('downloadPdf')
            ->assertActionExists('duplicate')
            ->assertActionExists('archive');
    }

    public function test_teacher_can_download_own_memo_pdf(): void
    {
        $teacher = User::factory()->create();
        $memo = $this->createMemo($teacher);

        $this->mock(PedagogicalMemoPrintService::class, function (MockInterface $mock) use ($memo): void {
            $mock->shouldReceive('pdfResponse')
                ->once()
                ->withArgs(fn (PedagogicalMemo $argument): bool => $argument->is($memo))
                ->andReturn(response()->streamDownload(
                    fn () => print 'memo-pdf-bytes',
                    'memo.pdf',
                    ['Content-Type' => 'application/pdf'],
                ));
        });

        $this->actingAs($teacher)
            ->get(route('print.pedagogical-memo.pdf', [
                'locale' => 'en',
                'memo' => $memo,
            ]))
            ->assertOk()
            ->assertDownload('memo.pdf');
    }

    public function test_teacher_cannot_download_another_teachers_memo_pdf(): void
    {
        $teacher = User::factory()->create();
        $memo = $this->createMemo(User::factory()->create());

        $this->actingAs($teacher)
            ->get(route('print.pedagogical-memo.pdf', [
                'locale' => 'en',
                'memo' => $memo,
            ]))
            ->assertForbidden();
    }

    public function test_memo_labels_match_between_navigation_breadcrumb_and_title(): void
    {
        $labels = [
            'ar' => 'المذكرات البيداغوجية',
            'en' => 'Pedagogical memos',
            'fr' => 'Mémos pédagogiques',
        ];

        $previousLocale = app()->getLocale();

        try {
            foreach ($labels as $locale => $label) {
                app()->setLocale($locale);

                $this->assertSame($label, PedagogicalMemoResource::getNavigationLabel());
                $this->assertSame($label, PedagogicalMemoResource::getPluralModelLabel());
                $this->assertSame($label, PedagogicalMemoResource::getBreadcrumb());

                $this->assertSame(
                    $label,
                    Livewire::actingAs(User::factory()->create())
                        ->test(ListPedagogicalMemos::class)
                        ->instance()
                        ->getTitle(),
                );
            }
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    private function createMemo(User $teacher, array $attributes = []): PedagogicalMemo
    {
        return PedagogicalMemo::factory()->create([
            'user_id' => $teacher->id,
            ...$attributes,
        ]);
    }
}
