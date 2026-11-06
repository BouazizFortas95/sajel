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
    public string $activeTab = 'import'; // 'import', 'grades', 'audit'

    #[Url(as: 'year')]
    public string $academicYear = '2026/2027';

    // Import state
    public $rosterFile;

    public string $rawCsvContent = '';

    public ?array $previewData = null;

    public bool $createMissingGroups = true;

    public bool $updateExisting = true;

    // Grade export state
    public ?int $selectedClassroomId = null;

    public ?int $selectedSubjectId = null;

    public int $selectedTrimester = 1;

    // Quick edit modal
    public ?int $editingStudentId = null;

    public ?string $editNin = '';

    public function mount(): void
    {
        $setting = SchoolSetting::current();
        if ($setting?->academic_year) {
            $this->academicYear = str_replace('-', '/', $setting->academic_year);
        }

        $firstClassroom = Classroom::first();
        if ($firstClassroom) {
            $this->selectedClassroomId = $firstClassroom->id;
        }

        $firstSubject = Subject::first();
        if ($firstSubject) {
            $this->selectedSubjectId = $firstSubject->id;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isTeacher();
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

    public function saveStudentNin(): void
    {
        if (! $this->editingStudentId) {
            return;
        }

        $student = Student::findOrFail($this->editingStudentId);
        $student->national_id_number = trim((string) $this->editNin);
        $student->save();

        $this->editingStudentId = null;
        $this->editNin = '';

        Notification::make()
            ->title(__('تم تحديث رقم التعريف الوطني بنجاح'))
            ->success()
            ->send();
    }

    public function openEditNinModal(int $studentId, ?string $currentNin = null): void
    {
        $this->editingStudentId = $studentId;
        $this->editNin = $currentNin;
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

    public function getAuditProperty(): array
    {
        return app(RakmanaSyncService::class)->auditReconciliation($this->academicYear);
    }
}
