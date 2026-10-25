<?php

namespace App\Filament\Pages;

use App\Models\SchoolSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

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
