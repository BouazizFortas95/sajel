<?php

namespace App\Filament\Pages;

use App\Models\SchoolSetting;
use App\Services\SchoolBackupService;
use App\Services\SchoolRestoreService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ManageSchoolSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 27;

    protected static string $view = 'filament.pages.manage-school-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('إعدادات المؤسسة التربوية');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التخطيط والمناهج');
    }

    public function getTitle(): string
    {
        return __('هوية المؤسسة التربوية');
    }

    public function mount(): void
    {
        $setting = SchoolSetting::current();

        $this->form->fill([
            'school_name' => $setting->school_name,
            'directorate_name' => $setting->directorate_name,
            'municipality_name' => $setting->municipality_name,
            'academic_year' => $setting->academic_year,
            'principal_name' => $setting->principal_name,
            'counselor_name' => $setting->counselor_name,
            'school_code' => $setting->school_code,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('البيانات الرسمية للمؤسسة'))
                    ->description(__('تظهر هذه البيانات تلقائياً في كل الوثائق المطبوعة وملفات PDF الرسمية.'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('school_name')
                            ->label(__('اسم المؤسسة (المتوسطة)'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('directorate_name')
                            ->label(__('مديرية التربية'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('municipality_name')
                            ->label(__('البلدية / الدائرة'))
                            ->maxLength(255),

                        TextInput::make('academic_year')
                            ->label(__('السنة الدراسية'))
                            ->required()
                            ->maxLength(20)
                            ->placeholder('2026-2027'),

                        TextInput::make('principal_name')
                            ->label(__('اسم مدير المتوسطة'))
                            ->maxLength(255),

                        TextInput::make('counselor_name')
                            ->label(__('اسم مستشار التربية'))
                            ->maxLength(255),

                        TextInput::make('school_code')
                            ->label(__('الرقم الميكانيكي للمؤسسة'))
                            ->maxLength(50),
                    ]),
            ])
            ->statePath('data');
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('downloadBackup')
                ->label(__('تحميل نسخة احتياطية للمؤسسة (JSON)'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('amber')
                ->visible(fn () => Auth::user()?->isAdmin() ?? false)
                ->action(fn () => app(SchoolBackupService::class)->streamDownload()),

            Action::make('restoreBackup')
                ->label(__('استرجاع نسخة احتياطية (JSON)'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => Auth::user()?->isAdmin() ?? false)
                ->modalHeading(__('استرجاع بيانات المؤسسة من نسخة احتياطية'))
                ->modalDescription(__('قم برفع ملف النسخة الاحتياطية بصيغة JSON. يمكنك اختيار المعاينة أولاً للتحقق من سلامة البيانات قبل تطبيق الاسترجاع.'))
                ->modalSubmitActionLabel(__('بدء المعالجة'))
                ->form([
                    FileUpload::make('backup_file')
                        ->label(__('ملف النسخة الاحتياطية (JSON)'))
                        ->acceptedFileTypes(['application/json', 'text/plain', 'text/json'])
                        ->required()
                        ->disk('local')
                        ->directory('temp-backups'),

                    Toggle::make('dry_run')
                        ->label(__('معاينة تجريبية فقط (Dry Run)'))
                        ->helperText(__('فحص الملف ومحاكاة عملية الاسترجاع دون تعديل قاعدة البيانات الحالية.'))
                        ->default(true),

                    Toggle::make('wipe_existing')
                        ->label(__('مسح البيانات الحالية قبل الاسترجاع (Clean Restore)'))
                        ->helperText(__('حذف السجلات السابقة لإعادة بناء المؤسسة من الصفر بناءً على ملف النسخ.'))
                        ->default(false),
                ])
                ->action(function (array $data) {
                    $disk = Storage::disk('local');
                    $filePath = $disk->path($data['backup_file']);
                    $service = app(SchoolRestoreService::class);

                    $result = $service->restoreFromFile(
                        $filePath,
                        dryRun: (bool) ($data['dry_run'] ?? true),
                        wipeExisting: (bool) ($data['wipe_existing'] ?? false)
                    );

                    $disk->delete($data['backup_file']);

                    if (! ($result['success'] ?? false)) {
                        Notification::make()
                            ->title(__('فشل فحص أو استرجاع النسخة الاحتياطية'))
                            ->body(implode("\n", $result['errors'] ?? [__('حدث خطأ غير متوقع')]))
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if ($result['dry_run'] ?? false) {
                        $simulatedCount = array_sum($result['simulated_counts'] ?? []);
                        Notification::make()
                            ->title(__('اكتملت المعاينة التجريبية بنجاح'))
                            ->body(__('تم التحقق من سلامة البيانات بنجاح. عدد السجلات الجاهزة للاسترجاع: :count سجلاً.', ['count' => $simulatedCount]))
                            ->info()
                            ->persistent()
                            ->send();
                    } else {
                        $totalRestored = $result['total_restored'] ?? 0;
                        Notification::make()
                            ->title(__('تم استرجاع النسخة الاحتياطية بنجاح'))
                            ->body(__('تم استرجاع :count سجلاً وتحديث إعدادات المؤسسة.', ['count' => $totalRestored]))
                            ->success()
                            ->send();

                        $this->mount();
                    }
                }),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $setting = SchoolSetting::query()->orderBy('id')->first() ?? new SchoolSetting;
        $setting->fill($data)->save();

        SchoolSetting::flushCache();

        Notification::make()
            ->title(__('تم حفظ إعدادات المؤسسة بنجاح'))
            ->success()
            ->send();
    }
}
