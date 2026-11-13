<?php

namespace App\Filament\Pages;

use App\Enums\DailyLogStatus;
use App\Exceptions\AiLessonPlannerException;
use App\Filament\Resources\PedagogicalMemoResource;
use App\Models\ActiveLearningStrategy;
use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\DailyLog;
use App\Models\PedagogicalMemo;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\AiLessonPlannerService;
use App\Services\CurriculumPlanGeneratorService;
use App\Services\CurriculumProgressService;
use Carbon\Carbon;
use Database\Seeders\InformaticsCurriculumSeeder;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CurriculumProgress extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.curriculum-progress';

    #[Url(as: 'subject')]
    public ?int $subjectId = null;

    #[Url(as: 'classroom')]
    public ?int $classroomId = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'progress')]
    public string $progressStatus = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'classroom';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    #[Url(as: 'view')]
    public string $curriculumView = 'annual';

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('تقدم المنهاج');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج والتفتيش');
    }

    public function getTitle(): string
    {
        return __('تقدم المنهاج');
    }

    /**
     * Start teachers on their first scheduled class instead of an empty page.
     * Admins start with the full school overview.
     */
    public function mount(): void
    {
        $user = Auth::user();

        if (! $user?->isTeacher()) {
            return;
        }

        $schedule = Schedule::query()
            ->forTeacher($user->id)
            ->with(['group'])
            ->first();

        if (! $schedule) {
            app(CurriculumPlanGeneratorService::class)->generateForTeacher($user);

            $schedule = Schedule::query()
                ->forTeacher($user->id)
                ->with(['group'])
                ->first();
        }

        if (! $schedule) {
            return;
        }

        $this->subjectId = $schedule->subject_id;
        $this->classroomId = $schedule->classroom_id ?? $schedule->group?->classroom_id;
    }

    public function showDetail(int $subjectId, int $classroomId): void
    {
        $this->subjectId = $subjectId;
        $this->classroomId = $classroomId;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->subjectId = null;
        $this->classroomId = null;
        $this->resetTableTools();
    }

    /**
     * Keep the table on the first page whenever a tool changes the result set.
     */
    public function updated(string $property): void
    {
        if (in_array($property, [
            'subjectId',
            'classroomId',
            'search',
            'progressStatus',
            'sortBy',
            'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    public function resetTableTools(): void
    {
        $this->search = '';
        $this->progressStatus = 'all';
        $this->sortBy = 'classroom';
        $this->perPage = 10;
        $this->resetPage();
    }

    /**
     * Export every row that matches the current tools, not just the visible
     * page. Rows are written with a UTF-8 BOM so Arabic text opens cleanly in
     * Excel.
     */
    public function exportCsv(): StreamedResponse
    {
        $rows = $this->filteredOverview;
        $filename = 'curriculum-progress-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                __('القسم'),
                __('المادة'),
                __('المستوى'),
                __('الحالة'),
                __('المقاطع المكتملة'),
                __('الموارد المنجزة'),
                __('نسبة التقدم'),
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['classroom']->display_name(),
                    $row['subject']->name,
                    $row['level']?->display_name() ?? '',
                    $this->rowStatusLabel($row),
                    $row['completed_sequences'].' / '.$row['total_sequences'],
                    $row['completed_resources'].' / '.$row['total_resources'],
                    $row['percentage'].'%',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function getStatusFilterOptionsProperty(): array
    {
        return [
            'all' => __('كل الحالات'),
            'completed' => __('مكتمل'),
            'in_progress' => __('قيد الإنجاز'),
            'not_started' => __('لم يبدأ'),
            'at_risk' => __('في خطر'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getSortOptionsProperty(): array
    {
        return [
            'classroom' => __('القسم أولاً'),
            'subject' => __('المادة أولاً'),
            'percentage_desc' => __('التقدم: الأعلى أولاً'),
            'percentage_asc' => __('التقدم: الأدنى أولاً'),
            'completed_desc' => __('الموارد: الأكثر إنجازاً'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getPerPageOptionsProperty(): array
    {
        return [
            10 => '10',
            25 => '25',
            50 => '50',
            100 => '100',
        ];
    }

    /**
     * The scope is everything the current user may see, narrowed only by the
     * subject/classroom selectors. KPIs stay stable while search and status
     * filters change the table below them.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getScopedOverviewProperty(): Collection
    {
        return $this->overview
            ->when(
                filled($this->subjectId),
                fn (Collection $rows): Collection => $rows
                    ->filter(fn (array $row): bool => $row['subject']->id === (int) $this->subjectId),
            )
            ->when(
                filled($this->classroomId),
                fn (Collection $rows): Collection => $rows
                    ->filter(fn (array $row): bool => $row['classroom']->id === (int) $this->classroomId),
            )
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getFilteredOverviewProperty(): Collection
    {
        $rows = $this->scopedOverview;
        $search = Str::lower(trim($this->search));

        if ($search !== '') {
            $rows = $rows->filter(function (array $row) use ($search): bool {
                $haystack = implode(' ', array_filter([
                    $row['subject']->name,
                    $row['classroom']->name,
                    $row['classroom']->display_name(),
                    $row['level']?->name,
                ]));

                return Str::contains(Str::lower($haystack), $search);
            });
        }

        $status = $this->normalizedProgressStatus();

        if ($status !== 'all') {
            $rows = $rows->filter(fn (array $row): bool => $status === 'at_risk'
                ? $this->isAtRisk($row)
                : $this->rowStatus($row) === $status);
        }

        return $this->sortRows($rows)->values();
    }

    public function getOverviewPaginatorProperty(): LengthAwarePaginator
    {
        $rows = $this->filteredOverview;
        $perPage = $this->normalizedPerPage();
        $total = $rows->count();

        $page = max(1, (int) $this->getPage());
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @return array{
     *     pairs: int,
     *     average: int,
     *     completed_pairs: int,
     *     at_risk: int,
     *     completed_resources: int,
     *     total_resources: int
     * }
     */
    public function getStatsProperty(): array
    {
        $rows = $this->scopedOverview;
        $total = $rows->count();

        return [
            'pairs' => $total,
            'average' => $total > 0 ? (int) round((float) $rows->avg('percentage')) : 0,
            'completed_pairs' => $rows->filter(
                fn (array $row): bool => $this->rowStatus($row) === 'completed',
            )->count(),
            'at_risk' => $rows->filter(fn (array $row): bool => $this->isAtRisk($row))->count(),
            'completed_resources' => (int) $rows->sum('completed_resources'),
            'total_resources' => (int) $rows->sum('total_resources'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowStatus(array $row): string
    {
        if ($row['total_resources'] === 0 || $row['completed_resources'] === 0) {
            return 'not_started';
        }

        return $row['completed_resources'] >= $row['total_resources']
            ? 'completed'
            : 'in_progress';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowStatusLabel(array $row): string
    {
        return match ($this->rowStatus($row)) {
            'completed' => __('مكتمل'),
            'in_progress' => __('قيد الإنجاز'),
            default => __('لم يبدأ'),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowStatusColor(array $row): string
    {
        return match ($this->rowStatus($row)) {
            'completed' => 'success',
            'in_progress' => 'warning',
            default => 'gray',
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function isAtRisk(array $row): bool
    {
        return $row['total_resources'] > 0 && $row['percentage'] < 50;
    }

    private function sortRows(Collection $rows): Collection
    {
        return match ($this->normalizedSortBy()) {
            'subject' => $rows->sort(fn (array $a, array $b): int => [
                $a['subject']->name,
                $a['classroom']->name,
            ] <=> [
                $b['subject']->name,
                $b['classroom']->name,
            ]),
            'percentage_desc' => $rows->sort(fn (array $a, array $b): int => [
                $b['percentage'],
                $a['classroom']->name,
                $a['subject']->name,
            ] <=> [
                $a['percentage'],
                $b['classroom']->name,
                $b['subject']->name,
            ]),
            'percentage_asc' => $rows->sort(fn (array $a, array $b): int => [
                $a['percentage'],
                $a['classroom']->name,
                $a['subject']->name,
            ] <=> [
                $b['percentage'],
                $b['classroom']->name,
                $b['subject']->name,
            ]),
            'completed_desc' => $rows->sort(fn (array $a, array $b): int => [
                $b['completed_resources'],
                $b['percentage'],
                $a['classroom']->name,
                $a['subject']->name,
            ] <=> [
                $a['completed_resources'],
                $a['percentage'],
                $b['classroom']->name,
                $b['subject']->name,
            ]),
            default => $rows->sort(fn (array $a, array $b): int => [
                $a['classroom']->name,
                $a['subject']->name,
            ] <=> [
                $b['classroom']->name,
                $b['subject']->name,
            ]),
        };
    }

    private function normalizedProgressStatus(): string
    {
        return array_key_exists($this->progressStatus, $this->statusFilterOptions)
            ? $this->progressStatus
            : 'all';
    }

    private function normalizedSortBy(): string
    {
        return array_key_exists($this->sortBy, $this->sortOptions)
            ? $this->sortBy
            : 'classroom';
    }

    private function normalizedPerPage(): int
    {
        return array_key_exists($this->perPage, $this->perPageOptions)
            ? $this->perPage
            : 10;
    }

    /**
     * @return array<int, string>
     */
    public function getSubjectOptionsProperty(): array
    {
        $query = Subject::query()->orderBy('name');

        $user = Auth::user();

        if ($user?->isTeacher()) {
            $query->whereIn(
                'id',
                Schedule::query()
                    ->forTeacher($user->id)
                    ->select('subject_id'),
            );
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    public function getClassroomOptionsProperty(): array
    {
        $query = Classroom::query()->with('level')->orderBy('name');

        $user = Auth::user();

        if ($user?->isTeacher()) {
            $schedules = Schedule::query()
                ->forTeacher($user->id)
                ->with(['group'])
                ->get(['classroom_id', 'group_id']);

            $classroomIds = $schedules
                ->map(fn (Schedule $schedule): ?int => $schedule->classroom_id ?? $schedule->group?->classroom_id)
                ->filter()
                ->unique()
                ->values();

            $query->whereIn('id', $classroomIds);
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->id => $classroom->display_name(),
            ])
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getOverviewProperty(): Collection
    {
        return app(CurriculumProgressService::class)->overview(Auth::user());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetailProperty(): ?array
    {
        if (blank($this->subjectId) || blank($this->classroomId)) {
            return null;
        }

        $classroom = Classroom::query()->with('level')->find($this->classroomId);
        $subject = Subject::query()->find($this->subjectId);

        if (! $classroom || ! $subject) {
            return null;
        }

        $user = Auth::user();

        if ($user?->isTeacher() && ! $this->teacherCanAccess($user, $classroom->id, $subject->id)) {
            return null;
        }

        $detail = app(CurriculumProgressService::class)->detail($classroom, $subject);

        if (empty($detail['domains'])) {
            if (str_contains($subject->name, 'إعلام') || str_contains($subject->name, 'معلوماتية')) {
                app(InformaticsCurriculumSeeder::class)->run();
            } else {
                app(CurriculumPlanGeneratorService::class)->generateForSubjectAndLevel($subject, $classroom->level);
            }
            $detail = app(CurriculumProgressService::class)->detail($classroom, $subject);
        }

        return $detail;
    }

    /**
     * Generate or re-generate official Algerian curriculum and annual plan for current subject & classroom.
     */
    public function generateCurriculumPlan(): void
    {
        $classroom = Classroom::query()->with('level')->find($this->classroomId);
        $subject = Subject::find($this->subjectId);

        if (! $classroom || ! $subject) {
            Notification::make()
                ->title(__('تعذر التوليد'))
                ->body(__('يرجى تحديد المادة والقسم أولاً.'))
                ->warning()
                ->send();

            return;
        }

        $res = app(CurriculumPlanGeneratorService::class)->generateForSubjectAndLevel($subject, $classroom->level, true);

        Notification::make()
            ->title(__('تم توليد واعتماد المنهاج والتوزيع السنوي بنجاح'))
            ->body(__('تم إنشاء :domains ميادين و :sequences مقاطع و :resources موارد تعليمية رسمية لمادة :subject.', [
                'domains' => $res['domains_count'] ?? 0,
                'sequences' => $res['sequences_count'] ?? 0,
                'resources' => $res['resources_count'] ?? 0,
                'subject' => $subject->name,
            ]))
            ->success()
            ->send();
    }

    private function teacherCanAccess(User $teacher, int $classroomId, int $subjectId): bool
    {
        return Schedule::query()
            ->forTeacher($teacher->id)
            ->where('subject_id', $subjectId)
            ->where(function (Builder $query) use ($classroomId): void {
                $query->where('classroom_id', $classroomId)
                    ->orWhereHas('group', fn (Builder $query) => $query->where('classroom_id', $classroomId));
            })
            ->exists();
    }

    public function setCurriculumView(string $view): void
    {
        $this->curriculumView = in_array($view, ['annual', 'monthly'], true) ? $view : 'annual';
    }

    protected function getActions(): array
    {
        return [
            $this->generateAiMemoAction(),
        ];
    }

    /**
     * Open the AI memo generation modal for a specific curriculum resource.
     */
    public function openAiMemoModal(int $resourceId): void
    {
        $resource = CurriculumResource::with(['sequence.domain'])->find($resourceId);

        if (! $resource) {
            return;
        }

        $this->mountAction('generateAiMemo', [
            'resource_id' => $resource->id,
            'lesson_title' => $resource->name,
            'phase' => $resource->sequence?->name ?? '',
        ]);
    }

    /**
     * Filament action for generating a full Algerian Pedagogical Memo using AI.
     */
    public function generateAiMemoAction(): Action
    {
        return Action::make('generateAiMemo')
            ->label(__('إنجاز مذكرة الدرس بالذكاء الاصطناعي'))
            ->modalHeading(__('إعداد مذكرة الدرس بالذكاء الاصطناعي'))
            ->modalDescription(__('توليد مذكرة بيداغوجية جزائرية رسمية وفق المقاربة بالكفاءات واستراتيجيات التعلم النشط وحفظها مباشرة في بنك المذكرات.'))
            ->modalSubmitActionLabel(__('توليد وحفظ المذكرة'))
            ->modalIcon('heroicon-o-sparkles')
            ->form([
                Hidden::make('resource_id'),

                TextInput::make('lesson_title')
                    ->label(__('عنوان الدرس / المورد المعرفي'))
                    ->required()
                    ->maxLength(255)
                    ->helperText(__('عنوان الحصة أو المورد التعليمي كما سيظهر في المذكرة الرسمية.')),

                TextInput::make('phase')
                    ->label(__('المقطع التعلمي'))
                    ->maxLength(255)
                    ->helperText(__('المقطع البيداغوجي الذي يندرج ضمنه هذا الدرس.')),

                Select::make('preferred_strategy')
                    ->label(__('استراتيجية التعلم النشط المفضلة'))
                    ->options(fn (): array => ActiveLearningStrategy::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->pluck('name', 'name')
                        ->all())
                    ->searchable()
                    ->placeholder(__('اختيار تلقائي من الذكاء الاصطناعي وفق طبيعة الدرس'))
                    ->helperText(__('يمكنك تحديد استراتيجية معينة (مثل: التفكير — المشاركة — العرض، جيكسو، بطاقة الخروج، حل المشكلات...) أو ترك الاختيار للذكاء الاصطناعي.')),

                Textarea::make('custom_instructions')
                    ->label(__('توجيهات أو إضافات خاصة بالأستاذ (اختياري)'))
                    ->placeholder(__('مثال: التركيز على العمل الفوجي التعاوني، إدماج الوسائل الرقمية، مراعاة الفروق الفردية، وضعية تقويمية هادفة...'))
                    ->rows(2),
            ])
            ->fillForm(fn (array $arguments): array => [
                'resource_id' => $arguments['resource_id'] ?? null,
                'lesson_title' => $arguments['lesson_title'] ?? '',
                'phase' => $arguments['phase'] ?? '',
            ])
            ->action(function (array $data, AiLessonPlannerService $planner): void {
                $classroom = Classroom::query()->with('level')->find($this->classroomId);
                $subject = Subject::find($this->subjectId);

                if (! $classroom || ! $subject) {
                    Notification::make()
                        ->warning()
                        ->title(__('معلومات ناقصة'))
                        ->body(__('يرجى تحديد المادة والقسم أولاً قبل توليد المذكرة.'))
                        ->send();

                    return;
                }

                try {
                    $plan = $planner->generatePedagogicalMemo(
                        topic: $data['lesson_title'],
                        subject: $subject->name,
                        level: $classroom->level?->display_name() ?? 'التعليم المتوسط',
                        phase: filled($data['phase'] ?? null) ? $data['phase'] : null,
                        locale: app()->getLocale(),
                        preferredStrategy: filled($data['preferred_strategy'] ?? null) ? (string) $data['preferred_strategy'] : null,
                        customInstructions: filled($data['custom_instructions'] ?? null) ? (string) $data['custom_instructions'] : null,
                    );

                    $memo = PedagogicalMemo::create([
                        'user_id' => Auth::id(),
                        'subject_id' => $subject->id,
                        'level_id' => $classroom->level_id,
                        'title' => $data['lesson_title'],
                        'phase' => $data['phase'] ?? null,
                        'competence' => $plan['competence'],
                        'starting_situation' => $plan['starting_situation'],
                        'activities' => $plan['activities'],
                        'resources' => $plan['resources'],
                        'evaluation' => $plan['evaluation'],
                        'notes' => $plan['notes'],
                        'is_template' => false,
                        'is_archived' => false,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('تم توليد وحفظ المذكرة البيداغوجية بنجاح'))
                        ->body(__('تم إنشاء المذكرة البيداغوجية: «:title» وإضافتها إلى بنك مذكراتك.', ['title' => $memo->title]))
                        ->actions([
                            NotificationAction::make('print')
                                ->label(__('معاينة وطباعة المذكرة'))
                                ->icon('heroicon-o-printer')
                                ->color('primary')
                                ->url(route('print.pedagogical-memo', ['locale' => app()->getLocale(), 'memo' => $memo->id]))
                                ->openUrlInNewTab(),
                            NotificationAction::make('edit')
                                ->label(__('تعديل في بنك المذكرات'))
                                ->icon('heroicon-o-pencil-square')
                                ->color('gray')
                                ->url(PedagogicalMemoResource::getUrl('edit', ['record' => $memo->id]))
                                ->openUrlInNewTab(),
                        ])
                        ->persistent()
                        ->send();
                } catch (AiLessonPlannerException $exception) {
                    Notification::make()
                        ->danger()
                        ->title(__('تعذر توليد المذكرة بالذكاء الاصطناعي'))
                        ->body($exception->getMessage())
                        ->persistent()
                        ->send();
                } catch (\Throwable $exception) {
                    report($exception);
                    Notification::make()
                        ->danger()
                        ->title(__('حدث خطأ غير متوقع'))
                        ->body(__('تعذر إتمام عملية التوليد. حاول مرة أخرى لاحقاً.'))
                        ->persistent()
                        ->send();
                }
            });
    }

    /**
     * Map existing teacher pedagogical memos for this subject & level.
     *
     * @return Collection<string, PedagogicalMemo>
     */
    public function getTeacherMemosProperty(): Collection
    {
        if (blank($this->subjectId) || blank($this->classroomId)) {
            return collect();
        }

        $classroom = Classroom::find($this->classroomId);

        if (! $classroom) {
            return collect();
        }

        $user = Auth::user();
        $query = PedagogicalMemo::query()
            ->where('subject_id', (int) $this->subjectId)
            ->where('level_id', (int) $classroom->level_id);

        if ($user?->isTeacher()) {
            $query->where('user_id', $user->id);
        }

        return $query->get()->keyBy(fn (PedagogicalMemo $memo): string => Str::lower(trim($memo->title)));
    }

    /**
     * Find an existing memo matching a curriculum resource.
     */
    public function findMemoForResource(CurriculumResource $resource): ?PedagogicalMemo
    {
        $memos = $this->teacherMemos;
        $cleanName = Str::lower(trim($resource->name));

        if ($memos->has($cleanName)) {
            return $memos->get($cleanName);
        }

        return $memos->first(function (PedagogicalMemo $memo) use ($cleanName): bool {
            $memoTitle = Str::lower(trim($memo->title));

            return str_contains($memoTitle, $cleanName) || str_contains($cleanName, $memoTitle);
        });
    }

    /**
     * Monthly pacing distribution schedule (سبتمبر — ماي).
     *
     * @return array<string, array{
     *     month_name: string,
     *     month_num: string,
     *     term: string,
     *     badge_color: string,
     *     total: int,
     *     completed_count: int,
     *     resources: list<array{
     *         resource: CurriculumResource,
     *         sequence: CurriculumSequence|null,
     *         domain: CurriculumDomain|null,
     *         completed: bool,
     *         completed_date: string|null,
     *         memo: PedagogicalMemo|null
     *     }>
     * }>
     */
    public function getMonthlyScheduleProperty(): array
    {
        $detail = $this->detail;

        if (! $detail) {
            return [];
        }

        $classroom = Classroom::find($this->classroomId);
        $subject = Subject::find($this->subjectId);

        if (! $classroom || ! $subject) {
            return [];
        }

        $completedLogs = DailyLog::query()
            ->where('status', DailyLogStatus::Completed->value)
            ->whereNotNull('curriculum_resource_id')
            ->whereHas('schedule', function ($query) use ($classroom, $subject): void {
                $query->where('subject_id', $subject->id)
                    ->where(function ($query) use ($classroom): void {
                        $query->where('classroom_id', $classroom->id)
                            ->orWhereHas('group', fn ($query) => $query->where('classroom_id', $classroom->id));
                    });
            })
            ->pluck('date', 'curriculum_resource_id');

        $allFlatResources = [];

        foreach ($detail['domains'] as $domainRow) {
            foreach ($domainRow['sequences'] as $sequenceRow) {
                foreach ($sequenceRow['resources'] as $resRow) {
                    $res = $resRow['resource'];
                    $completed = (bool) $resRow['completed'];
                    $date = $completedLogs->get($res->id);

                    $allFlatResources[] = [
                        'resource' => $res,
                        'sequence' => $sequenceRow['sequence'],
                        'domain' => $domainRow['domain'],
                        'completed' => $completed,
                        'completed_date' => $date ? Carbon::parse($date)->format('d/m/Y') : null,
                        'completed_month' => $date ? Carbon::parse($date)->format('m') : null,
                        'memo' => $this->findMemoForResource($res),
                    ];
                }
            }
        }

        $monthsDef = [
            '09_10' => ['month_name' => __('سبتمبر — أكتوبر'), 'term' => __('الفصل الأول'), 'badge_color' => 'info'],
            '11' => ['month_name' => __('نوفمبر'), 'term' => __('الفصل الأول'), 'badge_color' => 'info'],
            '12' => ['month_name' => __('ديسمبر'), 'term' => __('الفصل الأول'), 'badge_color' => 'info'],
            '01' => ['month_name' => __('جانفي'), 'term' => __('الفصل الثاني'), 'badge_color' => 'warning'],
            '02' => ['month_name' => __('فيفري'), 'term' => __('الفصل الثاني'), 'badge_color' => 'warning'],
            '03' => ['month_name' => __('مارس'), 'term' => __('الفصل الثاني'), 'badge_color' => 'warning'],
            '04' => ['month_name' => __('أفريل'), 'term' => __('الفصل الثالث'), 'badge_color' => 'success'],
            '05' => ['month_name' => __('ماي'), 'term' => __('الفصل الثالث'), 'badge_color' => 'success'],
        ];

        $monthKeys = array_keys($monthsDef);
        $totalResources = count($allFlatResources);
        $schedule = [];

        foreach ($monthsDef as $key => $meta) {
            $schedule[$key] = [
                'month_name' => $meta['month_name'],
                'month_num' => $key,
                'term' => $meta['term'],
                'badge_color' => $meta['badge_color'],
                'total' => 0,
                'completed_count' => 0,
                'resources' => [],
            ];
        }

        if ($totalResources === 0) {
            return $schedule;
        }

        $perMonth = max(1, (int) ceil($totalResources / count($monthKeys)));

        foreach ($allFlatResources as $index => $item) {
            $assignedMonth = $item['completed_month'];
            if ($assignedMonth === '09' || $assignedMonth === '10') {
                $assignedMonth = '09_10';
            }

            if (! $assignedMonth || ! isset($schedule[$assignedMonth])) {
                $monthIdx = (int) floor($index / $perMonth);
                $monthIdx = min($monthIdx, count($monthKeys) - 1);
                $assignedMonth = $monthKeys[$monthIdx];
            }

            $schedule[$assignedMonth]['resources'][] = $item;
            $schedule[$assignedMonth]['total']++;

            if ($item['completed']) {
                $schedule[$assignedMonth]['completed_count']++;
            }
        }

        return $schedule;
    }
}
