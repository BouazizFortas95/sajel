<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use App\Services\PlatformSettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DeveloperSettingsHub extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.developer-settings-hub';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->canManagePlatformSettings() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('إعدادات المنصة والمطور');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة المدرسية والرقمنة والأرشيف');
    }

    public function getTitle(): string
    {
        return __('إعدادات المنصة والتحكم التقني للمطور');
    }

    public function mount(): void
    {
        $setting = PlatformSetting::current();

        $this->form->fill([
            'platform_name' => $setting->platform_name,
            'platform_slogan' => $setting->platform_slogan,
            'meta_description' => $setting->meta_description,
            'logo_path' => $setting->logo_path,
            'dark_logo_path' => $setting->dark_logo_path,
            'favicon_path' => $setting->favicon_path,
            'primary_color' => $setting->primary_color ?? '#d97706',
            'developer_name' => $setting->developer_name,
            'developer_organization' => $setting->developer_organization,
            'developer_website' => $setting->developer_website,
            'developer_support_email' => $setting->developer_support_email,
            'developer_support_phone' => $setting->developer_support_phone,
            'version_tag' => $setting->version_tag,
            'footer_copyright_text' => $setting->footer_copyright_text,
            'is_maintenance_mode' => (bool) $setting->is_maintenance_mode,
            'maintenance_message' => $setting->maintenance_message,
            'maintenance_allowed_ips' => $setting->maintenance_allowed_ips,
            'enable_public_registration' => (bool) $setting->enable_public_registration,
            'announcement_active' => (bool) $setting->announcement_active,
            'system_announcement' => $setting->system_announcement,
            'announcement_type' => $setting->announcement_type ?? 'info',
            'enable_ai_assistant' => (bool) $setting->enable_ai_assistant,
            'ai_default_model' => $setting->ai_default_model ?? 'gemini-2.0-flash',
            'max_file_upload_mb' => $setting->max_file_upload_mb ?? 20,
            'session_lifetime_minutes' => $setting->session_lifetime_minutes ?? 120,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('DeveloperSettingsTabs')
                    ->tabs([
                        // Tab 1: Branding & Visual Identity
                        Tab::make('branding')
                            ->label(__('هوية المنصة والشعارات'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('platform_name')
                                        ->label(__('اسم المنصة البرمجية'))
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('سَجِّل — Sajel'),

                                    TextInput::make('platform_slogan')
                                        ->label(__('الشعار اللفظي للمنصة (Slogan)'))
                                        ->maxLength(255)
                                        ->placeholder(__('المنظومة الرقمية الشاملة لإدارة وتسيير المؤسسات التربوية')),
                                ]),

                                Textarea::make('meta_description')
                                    ->label(__('الوصف التعريفي العام (Meta Description)'))
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->columnSpanFull(),

                                Grid::make(3)->schema([
                                    FileUpload::make('logo_path')
                                        ->label(__('شعار المنصة الرسمي (Logo)'))
                                        ->image()
                                        ->disk('public')
                                        ->directory('platform')
                                        ->helperText(__('الشعار الافتراضي للمنصة في الوضع الفاتح.')),

                                    FileUpload::make('dark_logo_path')
                                        ->label(__('شعار الوضع الليلي (Dark Logo)'))
                                        ->image()
                                        ->disk('public')
                                        ->directory('platform')
                                        ->helperText(__('شعار مخصص للوضع الليلي المظلم.')),

                                    FileUpload::make('favicon_path')
                                        ->label(__('أيقونة المتصفح (Favicon)'))
                                        ->image()
                                        ->disk('public')
                                        ->directory('platform')
                                        ->helperText(__('الأيقونة المصغرة لشريط المتصفح (ICO / PNG / SVG).')),
                                ]),

                                Grid::make(2)->schema([
                                    ColorPicker::make('primary_color')
                                        ->label(__('اللون الرئيسي المميز للمنصة'))
                                        ->default('#d97706'),
                                ]),
                            ]),

                        // Tab 2: Developer & Software Information
                        Tab::make('developer')
                            ->label(__('بيانات المطور وحقوق الملكية'))
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('developer_name')
                                        ->label(__('اسم المطور أو المبرمج المسؤول'))
                                        ->required()
                                        ->maxLength(150),

                                    TextInput::make('developer_organization')
                                        ->label(__('الشركة المطورة أو المؤسسة التقنية'))
                                        ->maxLength(150),

                                    TextInput::make('developer_website')
                                        ->label(__('موقع المطور / محفظة الأعمال (URL)'))
                                        ->url()
                                        ->maxLength(255)
                                        ->placeholder('https://sajel.dz'),

                                    TextInput::make('developer_support_email')
                                        ->label(__('بريد الدعم الفني للمطور'))
                                        ->email()
                                        ->maxLength(150),

                                    TextInput::make('developer_support_phone')
                                        ->label(__('هاتف الدعم الفني البرمجي'))
                                        ->tel()
                                        ->maxLength(50),

                                    TextInput::make('version_tag')
                                        ->label(__('رقم الإصدار البرمجي للمنصة'))
                                        ->required()
                                        ->maxLength(50)
                                        ->placeholder('v3.0.0-LTS'),
                                ]),

                                TextInput::make('footer_copyright_text')
                                    ->label(__('نص حقوق النشر بالتذييل (Footer Credits)'))
                                    ->maxLength(300)
                                    ->columnSpanFull(),
                            ]),

                        // Tab 3: Maintenance & Access Control
                        Tab::make('maintenance')
                            ->label(__('وضع الصيانة والأمان والوصول'))
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Grid::make(2)->schema([
                                    Toggle::make('is_maintenance_mode')
                                        ->label(__('تفعيل وضع الصيانة التقنية للمنصة'))
                                        ->helperText(__('عند التفعيل يظهر تنبيه صيانة فوري للمستخدمين مع تعطيل العمليات الحساسة.')),

                                    Toggle::make('enable_public_registration')
                                        ->label(__('السماح بالتسجيل الذاتي للمستخدمين الجدد'))
                                        ->helperText(__('تعطيل هذا الخيار يحصر إنشاء الحسابات بمسؤول النظام والمكلف بالرقمنة.')),
                                ]),

                                Textarea::make('maintenance_message')
                                    ->label(__('رسالة الصيانة المخصصة للمستخدمين'))
                                    ->rows(3)
                                    ->visible(fn (Get $get): bool => (bool) $get('is_maintenance_mode'))
                                    ->columnSpanFull(),

                                TextInput::make('maintenance_allowed_ips')
                                    ->label(__('عناوين IP المستثناة من الصيانة (Whitelist)'))
                                    ->placeholder('127.0.0.1, 192.168.1.100')
                                    ->visible(fn (Get $get): bool => (bool) $get('is_maintenance_mode'))
                                    ->columnSpanFull(),

                                Grid::make(2)->schema([
                                    Toggle::make('announcement_active')
                                        ->label(__('تفعيل شريط التنبيهات والإعلانات العام'))
                                        ->helperText(__('يظهر شريط التنبيه فوراً أعلى لوحة التحكم لجميع المستخدمين النشطين.')),

                                    Select::make('announcement_type')
                                        ->label(__('نوع التنبيه والشريط'))
                                        ->options([
                                            'info' => __('إشعار معلوماتي عام (أزرق)'),
                                            'warning' => __('تنبيه هام (أصفر)'),
                                            'danger' => __('طارئ ومستعجل (أحمر)'),
                                            'success' => __('إعلان إيجابي / تهنئة (أخضر)'),
                                        ])
                                        ->visible(fn (Get $get): bool => (bool) $get('announcement_active')),
                                ]),

                                TextInput::make('system_announcement')
                                    ->label(__('نص الإعلان أو التنبيه العام'))
                                    ->visible(fn (Get $get): bool => (bool) $get('announcement_active'))
                                    ->maxLength(300)
                                    ->columnSpanFull(),
                            ]),

                        // Tab 4: AI & System Engine Controls
                        Tab::make('engine')
                            ->label(__('محرك الذكاء الاصطناعي وحدود النظام'))
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Grid::make(2)->schema([
                                    Toggle::make('enable_ai_assistant')
                                        ->label(__('تفعيل ميزات الذكاء الاصطناعي (AI Engine)'))
                                        ->helperText(__('تفعيل التوليد الذكي للتقارير التربوية والمذكرات وتحليل الأداء.')),

                                    Select::make('ai_default_model')
                                        ->label(__('الموديل الافتراضي للذكاء الاصطناعي'))
                                        ->options([
                                            'gemini-2.0-flash' => 'Google Gemini 2.0 Flash (موصى به - فائق السرعة)',
                                            'gemini-1.5-pro' => 'Google Gemini 1.5 Pro (تحليلي متقدم)',
                                            'gemini-1.5-flash' => 'Google Gemini 1.5 Flash (اقتصادي سريع)',
                                            'gpt-4o-mini' => 'OpenAI GPT-4o Mini (محرك بديل)',
                                        ]),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('max_file_upload_mb')
                                        ->label(__('الحد الأقصى لحجم رفع الملفات (MB)'))
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(500),

                                    TextInput::make('session_lifetime_minutes')
                                        ->label(__('مدة صلاحية جلسة تسجيل الدخول (بالدقائق)'))
                                        ->numeric()
                                        ->minValue(15)
                                        ->maxValue(1440),
                                ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function getHeaderActions(): array
    {
        $telemetry = app(PlatformSettingsService::class)->getSystemTelemetry();

        return [
            Action::make('clearCache')
                ->label(__('مسح وتحديث ذاكرة التخزين المؤقت'))
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('مسح ذاكرة التخزين المؤقت للنظام'))
                ->modalDescription(__('سيتم تفريغ كاش التطبيق وقوالب Blade والمسارات والإعدادات لضمان تطبيق كل التعديلات فوراً.'))
                ->modalSubmitActionLabel(__('تأكيد المسح الآن'))
                ->action(function () {
                    $results = app(PlatformSettingsService::class)->clearAllCaches();

                    Notification::make()
                        ->title(__('تم تفريغ ذاكرة التخزين المؤقت بنجاح'))
                        ->body(implode(' | ', $results))
                        ->success()
                        ->send();

                    $this->mount();
                }),

            Action::make('systemTelemetry')
                ->label(__('تشخيص النظام وموارد الخادم'))
                ->icon('heroicon-o-server-stack')
                ->color('info')
                ->modalHeading(__('تقرير التشخيص والموارد الفنية للخادم'))
                ->modalSubmitActionLabel(__('إغلاق'))
                ->modalDescription(__('إحصائيات فورية حول بيئة التشغيل وقاعدة البيانات والذاكرة المتاحة.'))
                ->form([
                    Grid::make(3)->schema([
                        TextInput::make('php_version')
                            ->label(__('إصدار PHP'))
                            ->default($telemetry['php_version'])
                            ->disabled(),

                        TextInput::make('laravel_version')
                            ->label(__('إصدار Laravel'))
                            ->default($telemetry['laravel_version'])
                            ->disabled(),

                        TextInput::make('db_driver')
                            ->label(__('محرك قاعدة البيانات'))
                            ->default(strtoupper($telemetry['db_driver']).' ('.$telemetry['db_tables_count'].' جدول)')
                            ->disabled(),

                        TextInput::make('server_os')
                            ->label(__('نظام تشغيل الخادم'))
                            ->default($telemetry['server_os'])
                            ->disabled(),

                        TextInput::make('memory_usage_mb')
                            ->label(__('استهلاك الذاكرة الحالي'))
                            ->default($telemetry['memory_usage_mb'].' MB (حد: '.$telemetry['memory_limit'].')')
                            ->disabled(),

                        TextInput::make('upload_limit')
                            ->label(__('الحد الأقصى لرفع الملفات (PHP)'))
                            ->default($telemetry['upload_max_filesize'])
                            ->disabled(),
                    ]),
                ]),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(PlatformSettingsService::class)->updateSettings($data);

        Notification::make()
            ->title(__('تم حفظ وتحديث إعدادات المنصة بنجاح'))
            ->body(__('تم تطبيق التغييرات فورياً وتحديث ذاكرة التخزين المؤقت للنظام.'))
            ->success()
            ->send();

        $this->mount();
    }
}
