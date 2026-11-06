<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\RakmanaSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Throwable;

class RakmanaSyncHub extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.rakmana-sync-hub';

    #[Url(as: 'tab')]
    public string $activeTab = 'import'; // 'import', 'grades', 'grade_import', 'deliberations', 'audit'

    #[Url(as: 'year')]
    public string $academicYear = '2026/2027';

    // Roster Import & Export state
    public $rosterFile;

    public string $rawCsvContent = '';

    public ?array $previewData = null;

    public bool $createMissingGroups = true;

    public bool $updateExisting = true;

    public ?int $exportClassroomId = null;

    public ?int $exportLevelId = null;

    // Grade export state
    public ?int $selectedClassroomId = null;

    public ?int $selectedSubjectId = null;

    public int $selectedTrimester = 1;

    // Grade import state (NEW)
    public $gradeFile;

    public string $gradeRawContent = '';

    public ?int $importClassroomId = null;

    public ?int $importSubjectId = null;

    public int $importTrimester = 1;

    public ?array $gradePreviewData = null;

    // Deliberation state (NEW)
    public ?int $deliberationClassroomId = null;

    public int $deliberationTrimester = 1;

    // Quick edit modal
    public ?int $editingStudentId = null;

    public ?string $editNin = '';

    public ?string $editFirstNameLatin = '';

    public ?string $editLastNameLatin = '';

    public function mount(): void
    {
        $setting = SchoolSetting::current();
        if ($setting?->academic_year) {
            $this->academicYear = str_replace('-', '/', $setting->academic_year);
        }

        $firstClassroom = Classroom::first();
        if ($firstClassroom) {
            $this->selectedClassroomId = $firstClassroom->id;
            $this->importClassroomId = $firstClassroom->id;
            $this->deliberationClassroomId = $firstClassroom->id;
        }

        $firstSubject = Subject::first();
        if ($firstSubject) {
            $this->selectedSubjectId = $firstSubject->id;
            $this->importSubjectId = $firstSubject->id;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher() || $user?->isEducationStaff();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة المدرسية والرقمنة والأرشيف');
    }

    public static function getNavigationLabel(): string
    {
        return __('مركز التكامل مع الرقمنة الوزارية');
    }

    public function getTitle(): string
    {
        return __('منظومة التكامل ومزامنة منصة الرقمنة الوزارية');
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('downloadSample')
                ->label(__('تحميل نموذج إكسل الرقمنة (CSV)'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('rakmana.sample-roster', ['locale' => app()->getLocale()]))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Process uploaded file or pasted text for preview.
     */
    public function parseFile(RakmanaSyncService $service): void
    {
        $content = '';

        if ($this->rosterFile) {
            try {
                $content = file_get_contents($this->rosterFile->getRealPath());
            } catch (Throwable $e) {
                Notification::make()
                    ->title(__('تعذّر قراءة الملف المرفوع'))
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                return;
            }
        } elseif (! empty(trim($this->rawCsvContent))) {
            $content = trim($this->rawCsvContent);
        } else {
            Notification::make()
                ->title(__('يرجى رفع ملف أو لصق بيانات القائمة أولاً'))
                ->warning()
                ->send();

            return;
        }

        $this->previewData = $service->previewImport($content);

        Notification::make()
            ->title(__('تمت قراءة البيانات بنجاح'))
            ->body(__('تم التعرف على :count سجل. يرجى معاينة القائمة أدناه وتأكيد الاستيراد.', [
                'count' => $this->previewData['total_count'],
            ]))
            ->success()
            ->send();
    }

    /**
     * Confirm and execute database import.
     */
    public function executeImport(RakmanaSyncService $service): void
    {
        if (empty($this->previewData['all_rows'])) {
            Notification::make()
                ->title(__('لا توجد بيانات جاهزة للاستيراد'))
                ->warning()
                ->send();

            return;
        }

        $result = $service->executeImport($this->previewData['all_rows'], [
            'create_missing_groups' => $this->createMissingGroups,
            'update_existing' => $this->updateExisting,
        ]);

        if ($result['success']) {
            Notification::make()
                ->title(__('تمت مزامنة واستيراد التلاميذ بنجاح'))
                ->body(__('تم إنشاء :created تلميذ جديد، وتحديث :updated تلميذ سابق.', [
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                ]))
                ->success()
                ->send();

            $this->previewData = null;
            $this->rosterFile = null;
            $this->rawCsvContent = '';
        } else {
            Notification::make()
                ->title(__('حدث خطأ أثناء الاستيراد'))
                ->body(implode("\n", $result['errors']))
                ->danger()
                ->send();
        }
    }

    public function clearPreview(): void
    {
        $this->previewData = null;
        $this->rosterFile = null;
        $this->rawCsvContent = '';
    }

    /**
     * Process uploaded ministerial grade booking file or pasted content.
     */
    public function parseGradeFile(RakmanaSyncService $service): void
    {
        if (! $this->importClassroomId || ! $this->importSubjectId) {
            Notification::make()
                ->title(__('يرجى تحديد القسم والمادة التعليمية أولاً'))
                ->warning()
                ->send();

            return;
        }

        $classroom = Classroom::find($this->importClassroomId);
        $subject = Subject::find($this->importSubjectId);

        if (! $classroom || ! $subject) {
            return;
        }

        $content = '';
        if ($this->gradeFile) {
            try {
                $content = file_get_contents($this->gradeFile->getRealPath());
            } catch (Throwable $e) {
                Notification::make()
                    ->title(__('تعذّر قراءة ملف حجز النقاط'))
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                return;
            }
        } elseif (! empty(trim($this->gradeRawContent))) {
            $content = trim($this->gradeRawContent);
        } else {
            Notification::make()
                ->title(__('يرجى رفع ملف حجز النقاط أو لصق المعطيات أولاً'))
                ->warning()
                ->send();

            return;
        }

        $this->gradePreviewData = $service->previewGradeImport(
            content: $content,
            classroom: $classroom,
            subject: $subject,
            trimester: $this->importTrimester,
            academicYear: $this->academicYear
        );

        Notification::make()
            ->title(__('تمت مطابقة ملف النقاط بنجاح'))
            ->body(__('تمت مطابقة :matched تلميذ من أصل :total سطر.', [
                'matched' => $this->gradePreviewData['matched_count'],
                'total' => $this->gradePreviewData['total_parsed_rows'],
            ]))
            ->success()
            ->send();
    }

    public function clearGradePreview(): void
    {
        $this->gradePreviewData = null;
        $this->gradeFile = null;
        $this->gradeRawContent = '';
    }

    /**
     * Commit parsed grades into database.
     */
    public function executeGradeImport(RakmanaSyncService $service): void
    {
        if (empty($this->gradePreviewData['matched_rows'])) {
            Notification::make()
                ->title(__('لا توجد نقاط مطابقة للحفظ'))
                ->warning()
                ->send();

            return;
        }

        $classroom = Classroom::findOrFail($this->importClassroomId);
        $subject = Subject::findOrFail($this->importSubjectId);

        $result = $service->executeGradeImport(
            matchedRows: $this->gradePreviewData['matched_rows'],
            classroom: $classroom,
            subject: $subject,
            trimester: $this->importTrimester,
            user: Auth::user(),
            academicYear: $this->academicYear
        );

        if ($result['success']) {
            Notification::make()
                ->title(__('تم حجز وتثبيت النقاط في قاعدة البيانات بنجاح'))
                ->body(__('تم حفظ :total نقطة لـ :students تلميذ (تقويم: :c، فروض: :m، اختبار: :e).', [
                    'total' => $result['total_grades_saved'],
                    'students' => $result['students_processed'],
                    'c' => $result['continuous_saved'],
                    'm' => $result['midterm_saved'],
                    'e' => $result['exam_saved'],
                ]))
                ->success()
                ->send();

            $this->clearGradePreview();
        } else {
            Notification::make()
                ->title(__('حدث خطأ أثناء حجز النقاط'))
                ->body($result['error'] ?? 'خطأ غير معروف')
                ->danger()
                ->send();
        }
    }

    public function saveStudentNin(): void
    {
        if (! $this->editingStudentId) {
            return;
        }

        $student = Student::findOrFail($this->editingStudentId);
        $student->national_id_number = trim((string) $this->editNin);
        if ($this->editFirstNameLatin !== null) {
            $student->first_name_latin = trim((string) $this->editFirstNameLatin);
        }
        if ($this->editLastNameLatin !== null) {
            $student->last_name_latin = trim((string) $this->editLastNameLatin);
        }
        $student->save();

        $this->editingStudentId = null;
        $this->editNin = '';
        $this->editFirstNameLatin = '';
        $this->editLastNameLatin = '';

        Notification::make()
            ->title(__('تم تحديث بيانات التلميذ الوزارية بنجاح'))
            ->success()
            ->send();
    }

    public function openEditNinModal(int $studentId, ?string $currentNin = null, ?string $firstLatin = null, ?string $lastLatin = null): void
    {
        $this->editingStudentId = $studentId;
        $this->editNin = $currentNin;
        $this->editFirstNameLatin = $firstLatin;
        $this->editLastNameLatin = $lastLatin;
    }

    public function getGradeMatrixProperty(): array
    {
        if (! $this->selectedClassroomId || ! $this->selectedSubjectId) {
            return ['rows' => []];
        }

        $classroom = Classroom::find($this->selectedClassroomId);
        $subject = Subject::find($this->selectedSubjectId);

        if (! $classroom || ! $subject) {
            return ['rows' => []];
        }

        return app(RakmanaSyncService::class)->generateGradeExportMatrix(
            classroom: $classroom,
            subject: $subject,
            trimester: $this->selectedTrimester,
            academicYear: $this->academicYear
        );
    }

    public function getDeliberationMatrixProperty(): array
    {
        if (! $this->deliberationClassroomId) {
            return ['rows' => [], 'subjects' => []];
        }

        $classroom = Classroom::find($this->deliberationClassroomId);
        if (! $classroom) {
            return ['rows' => [], 'subjects' => []];
        }

        return app(RakmanaSyncService::class)->generateClassCouncilDeliberationMatrix(
            classroom: $classroom,
            trimester: $this->deliberationTrimester,
            academicYear: $this->academicYear
        );
    }

    public function getAuditProperty(): array
    {
        return app(RakmanaSyncService::class)->auditReconciliation($this->academicYear);
    }
}
