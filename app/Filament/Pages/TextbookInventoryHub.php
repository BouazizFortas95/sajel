<?php

namespace App\Filament\Pages;

use App\Enums\TextbookDistributionType;
use App\Enums\TextbookReturnCondition;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentTextbookLoan;
use App\Models\Textbook;
use App\Services\TextbookInventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class TextbookInventoryHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.textbook-inventory-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'catalog';

    #[Url(as: 'level')]
    public ?int $selectedLevelId = null;

    #[Url(as: 'classroom')]
    public ?int $selectedClassroomId = null;

    #[Url(as: 'year')]
    public string $academicYear = '2026/2027';

    // Modal state for returning a textbook
    public bool $isReturnModalOpen = false;

    public ?int $returningLoanId = null;

    public string $returnCondition = 'good';

    public ?string $returnNotes = null;

    public function mount(): void
    {
        $school = SchoolSetting::current();
        $this->academicYear = $school?->academic_year ?? '2026/2027';

        if (! $this->selectedLevelId) {
            $this->selectedLevelId = Level::first()?->id;
        }

        if (! $this->selectedClassroomId) {
            $this->selectedClassroomId = Classroom::where('level_id', $this->selectedLevelId)->first()?->id
                ?? Classroom::first()?->id;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('الكتاب المدرسي وبراءة الذمة');
    }

    public function getTitle(): string
    {
        return __('إدارة الكتاب المدرسي وحصص التضامن وبراءة الذمة');
    }

    public function getHeaderActions(): array
    {
        $actions = [];

        if (Auth::user()?->isAdmin()) {
            $actions[] = Action::make('seedCatalog')
                ->label(__('توليد دليل الكتب الوطني (ONPS)'))
                ->icon('heroicon-o-sparkles')
                ->color('amber')
                ->requiresConfirmation()
                ->modalHeading(__('توليد دليل الكتب المدرسية المعتمدة للتعليم المتوسط'))
                ->modalDescription(__('سيقوم النظام بمطابقة وتوليد عناوين الكتب المقررة لجميع المستويات (1 م إلى 4 م) بأسعار الديوان الوطني الرسمية.'))
                ->modalSubmitActionLabel(__('تأكيد وتوليد الدليل'))
                ->action(function (TextbookInventoryService $service): void {
                    $count = $service->seedStandardAlgerianCatalog();

                    Notification::make()
                        ->title(__('تم تحديث دليل الكتب المدرسية'))
                        ->body(__('تمت إضافة / مطابقة :count كتاباً مدرسياً بنجاح.', ['count' => $count]))
                        ->success()
                        ->send();
                });

            $actions[] = Action::make('batchDistribute')
                ->label(__('توزيع حزمة القسم'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('emerald')
                ->modalHeading(__('توزيع حزم الكتب المقررة على تلاميذ القسم'))
                ->modalSubmitActionLabel(__('تأكيد التوزيع الجماعي'))
                ->form([
                    Grid::make(2)->schema([
                        Select::make('classroom_id')
                            ->label(__('القسم المستهدف'))
                            ->options(fn () => Classroom::orderBy('name')->pluck('name', 'id'))
                            ->default(fn () => $this->selectedClassroomId)
                            ->required(),

                        Select::make('distribution_type')
                            ->label(__('طبيعة الاستفادة'))
                            ->options(TextbookDistributionType::options())
                            ->default(TextbookDistributionType::Loan->value)
                            ->required(),
                    ]),
                ])
                ->action(function (array $data, TextbookInventoryService $service): void {
                    $classroom = Classroom::find($data['classroom_id']);
                    if (! $classroom) {
                        return;
                    }

                    $type = TextbookDistributionType::from($data['distribution_type']);
                    $count = $service->batchDistributeToClassroom($classroom, null, $type, Auth::user());

                    Notification::make()
                        ->title(__('تم التوزيع الجماعي بنجاح'))
                        ->body(__('تم تسليم :count نسخة من الكتب المدرسية لتلاميذ :classroom.', [
                            'count' => $count,
                            'classroom' => $classroom->name,
                        ]))
                        ->success()
                        ->send();

                    $this->selectedClassroomId = $classroom->id;
                    $this->selectedLevelId = $classroom->level_id;
                    $this->activeTab = 'distribution';
                });

            $actions[] = Action::make('printInventory')
                ->label(__('كشف الجرد السنوي'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('print.textbook.annual-inventory', [
                    'locale' => app()->getLocale(),
                    'year' => $this->academicYear,
                ]))
                ->openUrlInNewTab();

            $actions[] = Action::make('pdfInventory')
                ->label(__('تحميل الجرد (PDF)'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn (): string => route('print.textbook.annual-inventory.pdf', [
                    'locale' => app()->getLocale(),
                    'year' => $this->academicYear,
                ]))
                ->openUrlInNewTab();
        }

        return $actions;
    }

    public function openReturnModal(int $loanId): void
    {
        $this->returningLoanId = $loanId;
        $this->returnCondition = 'good';
        $this->returnNotes = null;
        $this->isReturnModalOpen = true;
    }

    public function closeReturnModal(): void
    {
        $this->isReturnModalOpen = false;
        $this->returningLoanId = null;
        $this->returnCondition = 'good';
        $this->returnNotes = null;
    }

    public function confirmReturn(TextbookInventoryService $service): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        if (! $this->returningLoanId) {
            return;
        }

        $loan = StudentTextbookLoan::find($this->returningLoanId);
        if (! $loan) {
            $this->closeReturnModal();

            return;
        }

        $condition = TextbookReturnCondition::from($this->returnCondition);
        $service->processReturn($loan, $condition, $this->returnNotes);

        Notification::make()
            ->title(__('تم تسجيل استرجاع الكتاب'))
            ->body(__('تم تحديث حالة الكتاب (:title) إلى :condition.', [
                'title' => $loan->textbook?->title,
                'condition' => $condition->label(),
            ]))
            ->success()
            ->send();

        $this->closeReturnModal();
    }

    public function grantClearance(int $studentId, TextbookInventoryService $service): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $student = Student::find($studentId);
        if ($student) {
            $service->grantStudentClearance($student, $this->academicYear);

            Notification::make()
                ->title(__('تم منح براءة الذمة التامة'))
                ->body(__('تمت تبرئة ذمة التلميذ(ة) :name لاستلام وثائقه المدرسية.', ['name' => $student->full_name]))
                ->success()
                ->send();
        }
    }

    public function addReceivedStock(int $textbookId, int $additionalQuantity): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $textbook = Textbook::find($textbookId);
        if ($textbook && $additionalQuantity > 0) {
            $textbook->increment('total_received', $additionalQuantity);

            Notification::make()
                ->title(__('تم تحديث الرصيد المستلم'))
                ->body(__('أضيفت :count نسخة جديدة من :title للمخزن.', [
                    'count' => $additionalQuantity,
                    'title' => $textbook->title,
                ]))
                ->success()
                ->send();
        }
    }

    /**
     * Get view data for rendering the hub.
     *
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $service = app(TextbookInventoryService::class);
        $user = Auth::user();

        // 1. Textbooks Catalog
        $catalogQuery = Textbook::where('is_active', true)->with(['level', 'subject']);
        if ($this->selectedLevelId) {
            $catalogQuery->where('level_id', $this->selectedLevelId);
        }
        $textbooks = $catalogQuery->get();

        // 2. Classrooms for filter
        $classrooms = Classroom::orderBy('name')->get();
        $selectedClassroom = Classroom::find($this->selectedClassroomId) ?? $classrooms->first();

        // 3. Students in selected classroom with textbook loans
        $students = collect();
        if ($selectedClassroom) {
            $students = Student::whereHas('group', fn ($q) => $q->where('classroom_id', $selectedClassroom->id))
                ->with(['loans' => fn ($q) => $q->where('academic_year', $this->academicYear)->with('textbook.subject')])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        // 4. Inventory KPIs
        $kpis = $service->getInventoryKpis($this->academicYear);

        return [
            'textbooks' => $textbooks,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'students' => $students,
            'kpis' => $kpis,
            'levels' => Level::orderBy('grade_number')->get(),
            'isAdmin' => $user?->isAdmin() ?? false,
        ];
    }
}
