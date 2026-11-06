<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Models\InspectionReport;
use App\Models\Level;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalInspectionService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class PedagogicalInspectorHub extends Page
{
    protected static ?string $slug = 'pedagogical-inspector';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.pedagogical-inspector-hub';

    protected ?string $maxWidth = 'full';

    #[Url(as: 'subject')]
    public ?int $selectedSubjectId = null;

    #[Url(as: 'level')]
    public ?int $selectedLevelId = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'velocity';

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('فضاء المفتش التربوي');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإشراف والمراقبة الإدارية');
    }

    public function getTitle(): string
    {
        return __('فضاء المفتش التربوي ومتابعة المنهاج');
    }

    public function mount(): void
    {
        if (! $this->selectedSubjectId) {
            $this->selectedSubjectId = Subject::orderBy('id')->value('id');
        }

        if (! $this->selectedLevelId) {
            $this->selectedLevelId = Level::orderBy('id')->value('id');
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('createInspection')
                ->label(__('تسجيل زيارة تفتيشية جديدة'))
                ->icon('heroicon-o-plus-circle')
                ->color('amber')
                ->modalHeading(__('تسجيل تقرير زيارة وتفتيش بيداغوجي'))
                ->modalSubmitActionLabel(__('حفظ التقرير الرسمي'))
                ->form([
                    Grid::make(2)->schema([
                        Select::make('teacher_id')
                            ->label(__('الأستاذ(ة) المزار'))
                            ->options(fn () => User::where('role', 'teacher')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),

                        TextInput::make('inspector_name')
                            ->label(__('اسم المشرف / المفتش'))
                            ->default(fn () => Auth::user()?->name ?? 'مفتش المادة')
                            ->required(),

                        Select::make('inspector_role')
                            ->label(__('الصفة الرتبوية للمشرف'))
                            ->options([
                                'inspector' => __('مفتش التربية الوطنية'),
                                'principal' => __('مدير المتوسطة'),
                            ])
                            ->default('inspector')
                            ->required(),

                        Select::make('subject_id')
                            ->label(__('المادة التعليمية'))
                            ->options(fn () => Subject::pluck('name', 'id'))
                            ->default(fn () => $this->selectedSubjectId)
                            ->required(),

                        Select::make('classroom_id')
                            ->label(__('القسم / الفوج'))
                            ->options(fn () => Classroom::pluck('name', 'id'))
                            ->required(),

                        DatePicker::make('visit_date')
                            ->label(__('تاريخ الزيارة'))
                            ->default(today())
                            ->required(),

                        TextInput::make('lesson_topic')
                            ->label(__('موضوع الحصة / المورد المعرفي'))
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('pedagogical_score')
                            ->label(__('النقطة التربوية (من 20)'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->step(0.25)
                            ->placeholder('16.00'),

                        Select::make('pedagogical_appreciation')
                            ->label(__('التقدير العام'))
                            ->options([
                                'excellent' => __('ممتاز'),
                                'very_good' => __('جيد جداً'),
                                'good' => __('جيد'),
                                'acceptable' => __('مقبول'),
                                'needs_improvement' => __('يحتاج إلى متابعة'),
                            ])
                            ->default('very_good')
                            ->required(),

                        Textarea::make('strengths')
                            ->label(__('نقاط القوة والملاحظات الإيجابية'))
                            ->placeholder(__('سجل الملاحظات الإيجابية ونقاط تميز الأستاذ...'))
                            ->rows(3)
                            ->columnSpan(2),

                        Textarea::make('areas_for_improvement')
                            ->label(__('التوجيهات ومجالات التحسين البيداغوجي'))
                            ->placeholder(__('سجل التوجيهات البيداغوجية لمرافقة الأستاذ...'))
                            ->rows(3)
                            ->columnSpan(2),
                    ]),
                ])
                ->action(function (array $data) {
                    InspectionReport::create($data);

                    Notification::make()
                        ->title(__('تم تسجيل تقرير الزيارة التفتيشية بنجاح'))
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $service = app(PedagogicalInspectionService::class);

        $metrics = $service->getInspectionMetrics();
        $velocity = ($this->selectedSubjectId && $this->selectedLevelId)
            ? $service->getCurriculumVelocity($this->selectedSubjectId, $this->selectedLevelId)
            : null;

        $subjects = Subject::orderBy('name')->get();
        $levels = Level::with('educationPhase')->orderBy('grade_number')->get();

        $teachers = User::where('role', 'teacher')
            ->with(['primarySubject', 'secondarySubject'])
            ->orderBy('name')
            ->get();

        $teacherScorecards = [];
        foreach ($teachers as $teacher) {
            $teacherScorecards[] = $service->getTeacherPedagogicalScorecard($teacher);
        }

        $reports = InspectionReport::with(['teacher', 'subject', 'classroom'])
            ->orderByDesc('visit_date')
            ->get();

        return [
            'metrics' => $metrics,
            'velocity' => $velocity,
            'subjects' => $subjects,
            'levels' => $levels,
            'teacherScorecards' => $teacherScorecards,
            'reports' => $reports,
        ];
    }
}
