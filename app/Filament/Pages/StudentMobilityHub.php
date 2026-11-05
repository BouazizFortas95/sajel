<?php

namespace App\Filament\Pages;

use App\Enums\TransferReason;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Services\StudentMobilityService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class StudentMobilityHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 87;

    protected static string $view = 'filament.pages.student-mobility-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'transfers';

    #[Url(as: 'year')]
    public string $academicYear = '2026/2027';

    #[Url(as: 'type')]
    public ?string $selectedType = null;

    #[Url(as: 'status')]
    public ?string $selectedStatus = null;

    #[Url(as: 'level')]
    public ?int $selectedLevelId = null;

    #[Url(as: 'classroom')]
    public ?int $selectedClassroomId = null;

    #[Url(as: 'q')]
    public string $searchQuery = '';

    public function mount(): void
    {
        $setting = SchoolSetting::current();
        if ($setting?->academic_year) {
            $this->academicYear = str_replace('-', '/', $setting->academic_year);
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
        return __('التشغيل اليومي');
    }

    public static function getNavigationLabel(): string
    {
        return __('حركية وتحويلات التلاميذ');
    }

    public function getTitle(): string
    {
        return __('إدارة حركية وتحويلات التلاميذ وسجل القيد العام');
    }

    public function getHeaderActions(): array
    {
        $actions = [];

        if (Auth::user()?->isAdmin()) {
            $actions[] = Action::make('registerTransfer')
                ->label(__('تسجيل تحويل تلميذ جديد'))
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->modalHeading(__('تسجيل حركة تحويل (وافد / مغادر)'))
                ->modalSubmitActionLabel(__('تأكيد وحفظ التحويل'))
                ->form([
                    Grid::make(2)->schema([
                        Select::make('student_id')
                            ->label(__('التلميذ المعني بالتحويل'))
                            ->options(function () {
                                return Student::orderBy('last_name')
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn (Student $s): array => [
                                        $s->id => "{$s->full_name} (".($s->group?->classroom?->name ?? '—').')',
                                    ]);
                            })
                            ->searchable()
                            ->required(),

                        Select::make('transfer_type')
                            ->label(__('نوع الحركة'))
                            ->options([
                                TransferType::Outward->value => TransferType::Outward->label(),
                                TransferType::Inward->value => TransferType::Inward->label(),
                            ])
                            ->default(TransferType::Outward->value)
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('other_school_name')
                            ->label(__('المؤسسة المقابلة (الأصلية / المستقبلة)'))
                            ->placeholder(__('مثال: متوسطة الإخوة بوحجة'))
                            ->required(),

                        TextInput::make('other_directorate_name')
                            ->label(__('مديرية التربية المعنية'))
                            ->placeholder(__('مثال: مديرية التربية لولاية الجزائر وسط')),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('reason')
                            ->label(__('سبب التحويل القانوني'))
                            ->options(collect(TransferReason::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()]))
                            ->default(TransferReason::ChangeOfResidence->value)
                            ->required(),

                        DatePicker::make('transfer_date')
                            ->label(__('تاريخ التحويل الفعلي'))
                            ->default(now()->toDateString())
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('reference_number')
                            ->label(__('رقم رخصة / مقرر التحويل'))
                            ->placeholder(__('مثال: ر.ت/2026/142')),

                        DatePicker::make('reference_date')
                            ->label(__('تاريخ رخصة التحويل'))
                            ->default(now()->toDateString()),
                    ]),

                    Grid::make(2)->schema([
                        Toggle::make('clearance_attached')
                            ->label(__('شهادة براءة الذمة (Quitus) مستوفاة ومرفقة'))
                            ->default(true),

                        Toggle::make('school_file_forwarded')
                            ->label(__('تم إرسال / استلام الملف المدرسي الأصلي'))
                            ->default(false),
                    ]),

                    Textarea::make('notes')
                        ->label(__('ملاحظات إضافية'))
                        ->rows(2)
                        ->placeholder(__('أي توضيحات أو قرارات مجالس التأديب...')),
                ])
                ->action(function (array $data, StudentMobilityService $service): void {
                    $student = Student::findOrFail($data['student_id']);
                    $service->registerTransfer($student, $data, Auth::user());

                    Notification::make()
                        ->title(__('تم تسجيل التحويل بنجاح'))
                        ->body(__('تم توثيق حركة التلميذ(ة) :name بنجاح.', ['name' => $student->full_name]))
                        ->success()
                        ->send();
                });

            $actions[] = Action::make('printLedger')
                ->label(__('سجل الحركة السنوي (A4)'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('print.student-transfer.ledger', [
                    'locale' => app()->getLocale(),
                    'year' => $this->academicYear,
                ]))
                ->openUrlInNewTab();
        }

        return $actions;
    }

    public function markFileForwarded(int $transferId, StudentMobilityService $service): void
    {
        $transfer = StudentTransfer::findOrFail($transferId);
        $service->markSchoolFileForwarded($transfer);

        Notification::make()
            ->title(__('تم توثيق إرسال الملف'))
            ->body(__('تم تأكيد إرسال الملف المدرسي الأصلي للتلميذ(ة) :name.', [
                'name' => $transfer->student?->full_name,
            ]))
            ->success()
            ->send();
    }

    public function approveTransfer(int $transferId, StudentMobilityService $service): void
    {
        $transfer = StudentTransfer::findOrFail($transferId);
        $service->updateTransferStatus($transfer, TransferStatus::Approved, Auth::user());

        Notification::make()
            ->title(__('تمت المصادقة على التحويل'))
            ->body(__('تمت المصادقة على التحويل وتحديث وضعية التلميذ في القيد العام.'))
            ->success()
            ->send();
    }

    public function cancelTransfer(int $transferId, StudentMobilityService $service): void
    {
        $transfer = StudentTransfer::findOrFail($transferId);
        $service->updateTransferStatus($transfer, TransferStatus::Cancelled, Auth::user());

        Notification::make()
            ->title(__('تم إلغاء التحويل'))
            ->body(__('تم إلغاء التحويل وإعادة التلميذ إلى قائمة المتمدرسين النشطين.'))
            ->warning()
            ->send();
    }

    public function getViewData(): array
    {
        $service = app(StudentMobilityService::class);
        $kpis = $service->getMobilityKpis($this->academicYear);

        // Filter transfers
        $query = StudentTransfer::with(['student.group.classroom.level', 'approvedBy'])
            ->forAcademicYear($this->academicYear);

        if ($this->selectedType) {
            $query->where('transfer_type', $this->selectedType);
        }

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        if (filled($this->searchQuery)) {
            $q = trim($this->searchQuery);
            $query->where(function ($sub) use ($q): void {
                $sub->whereHas('student', function ($sq) use ($q): void {
                    $sq->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('matricule_number', 'like', "%{$q}%")
                        ->orWhere('registration_number', 'like', "%{$q}%");
                })->orWhere('other_school_name', 'like', "%{$q}%")
                    ->orWhere('reference_number', 'like', "%{$q}%");
            });
        }

        $transfers = $query->orderByDesc('transfer_date')->paginate(15);

        // Matricule Register students
        $matriculeData = $service->assembleMatriculeRegister($this->selectedLevelId, $this->selectedClassroomId);

        return [
            'kpis' => $kpis,
            'transfers' => $transfers,
            'matriculeData' => $matriculeData,
            'levels' => Level::orderBy('grade_number')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
        ];
    }
}

