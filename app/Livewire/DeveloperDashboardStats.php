<?php

namespace App\Livewire;

use App\Enums\Role;
use App\Filament\Resources\EducationalInstitutionResource;
use App\Filament\Resources\UserResource;
use App\Models\Classroom;
use App\Models\EducationalInstitution;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\PlatformSettingsService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class DeveloperDashboardStats extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'aggregator';

    #[Url(as: 'search')]
    public string $searchTerm = '';

    #[Url(as: 'stage')]
    public ?string $stageFilter = null;

    #[Url(as: 'status')]
    public ?string $statusFilter = null;

    #[Url(as: 'institution')]
    public ?int $selectedInstitutionId = null;

    public ?string $previewWilayaFilter = null;

    public ?string $previewStageFilter = null;

    public string $previewSearchTerm = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isDeveloper() || $user->role === Role::Admin), 403);

        $this->selectedInstitutionId = $this->selectedInstitutionId
            ?: (EducationalInstitution::getCurrent()?->id ?? EducationalInstitution::first()?->id);
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['aggregator', 'wilayas', 'active_school_preview', 'system_ops'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function selectInstitution(int $institutionId, string $targetTab = 'active_school_preview'): void
    {
        $this->selectedInstitutionId = $institutionId;
        $this->activeTab = $targetTab;

        $inst = EducationalInstitution::find($institutionId);
        if ($inst) {
            Notification::make()
                ->title(__('تم تحميل لوحة المؤسسة'))
                ->body(__('أنت الآن تعاين وتفحص لوحة وإحصائيات: :name', ['name' => $inst->name]))
                ->info()
                ->send();
        }
    }

    public function filterByWilayaAndPreview(string $wilaya): void
    {
        $this->previewWilayaFilter = $wilaya;
        $firstInWilaya = EducationalInstitution::where('wilaya', $wilaya)->first();
        if ($firstInWilaya) {
            $this->selectedInstitutionId = $firstInWilaya->id;
        }
        $this->activeTab = 'active_school_preview';
    }

    public function resetPreviewFilters(): void
    {
        $this->previewSearchTerm = '';
        $this->previewWilayaFilter = null;
        $this->previewStageFilter = null;
    }

    public function makeInstitutionCurrent(int $institutionId): void
    {
        $institution = EducationalInstitution::findOrFail($institutionId);
        $institution->makeCurrent();
        $this->selectedInstitutionId = $institution->id;

        Notification::make()
            ->title(__('تم تفعيل المؤسسة بنجاح'))
            ->body(__('أصبحت المؤسسة (:name) هي المؤسسة المعتمدة للنظام وتمت مزامنة كافة الوثائق الرسمية والتقارير مع هويتها.', [
                'name' => $institution->name,
            ]))
            ->success()
            ->send();
    }

    public function clearSystemCache(): void
    {
        $results = app(PlatformSettingsService::class)->clearAllCaches();

        Notification::make()
            ->title(__('تم تفريغ ذاكرة التخزين المؤقت'))
            ->body(implode(' | ', $results))
            ->success()
            ->send();
    }

    public function toggleMaintenance(): void
    {
        $setting = PlatformSetting::current();
        $newStatus = ! (bool) $setting->is_maintenance_mode;
        $setting->update(['is_maintenance_mode' => $newStatus]);
        PlatformSetting::flushCache();

        Notification::make()
            ->title($newStatus ? __('تم تفعيل وضع الصيانة') : __('تم إلغاء وضع الصيانة'))
            ->body($newStatus ? __('الموقع الآن في وضع الصيانة للمستخدمين العاديين.') : __('المنظومة متاحة للجميع كالمعتاد.'))
            ->warning()
            ->send();
    }

    public function resetFilters(): void
    {
        $this->searchTerm = '';
        $this->stageFilter = null;
        $this->statusFilter = null;
    }

    public function render()
    {
        $currentInstitution = EducationalInstitution::getCurrent();
        $platformSetting = PlatformSetting::current();
        $telemetry = app(PlatformSettingsService::class)->getSystemTelemetry();

        // Selected institution for inspection
        $selectedInstitution = EducationalInstitution::find($this->selectedInstitutionId)
            ?? $currentInstitution
            ?? EducationalInstitution::first();

        // Aggregated Metrics
        $totalInstitutions = EducationalInstitution::count();
        $activeInstitutions = EducationalInstitution::where('status', 'active')->count();
        $pendingInstitutions = EducationalInstitution::where('status', 'pending')->count();
        $suspendedInstitutions = EducationalInstitution::where('status', 'suspended')->count();

        $stageCounts = [
            'primary' => EducationalInstitution::where('stage', 'primary')->count(),
            'middle' => EducationalInstitution::where('stage', 'middle')->count(),
            'secondary' => EducationalInstitution::where('stage', 'secondary')->count(),
            'complex' => EducationalInstitution::where('stage', 'complex')->count(),
        ];

        $totalCapacity = (int) EducationalInstitution::sum('capacity');
        $enrolledStudentsInActiveSchool = Student::count();
        $activeClassrooms = Classroom::count();

        $usersCount = User::count();
        $principalsCount = User::where('role', Role::Principal)->count();
        $teachersCount = User::whereIn('role', [Role::Teacher, Role::HeadTeacher])->count();
        $censorsCount = User::where('role', Role::Censor)->count();
        $counselorsCount = User::where('role', Role::Counselor)->count();
        $supervisorsCount = User::whereIn('role', [Role::GeneralSupervisor, Role::EducationSupervisor])->count();

        // Filtered institutions query for Tab 1 (Aggregator)
        $institutionsQuery = EducationalInstitution::query();

        if ($this->searchTerm !== '') {
            $institutionsQuery->where(function ($query) {
                $query->where('name', 'like', "%{$this->searchTerm}%")
                    ->orWhere('code', 'like', "%{$this->searchTerm}%")
                    ->orWhere('wilaya', 'like', "%{$this->searchTerm}%")
                    ->orWhere('municipality', 'like', "%{$this->searchTerm}%")
                    ->orWhere('principal_name', 'like', "%{$this->searchTerm}%");
            });
        }

        if ($this->stageFilter) {
            $institutionsQuery->where('stage', $this->stageFilter);
        }

        if ($this->statusFilter) {
            $institutionsQuery->where('status', $this->statusFilter);
        }

        $institutions = $institutionsQuery->orderByDesc('is_current')->orderBy('name')->get();

        // Wilayas distribution for Tab 2
        $wilayasDistribution = EducationalInstitution::query()
            ->select('wilaya', DB::raw('count(*) as count'), DB::raw('sum(capacity) as total_capacity'))
            ->groupBy('wilaya')
            ->orderByDesc('count')
            ->get();

        // Institutions list for Tab 3 (Preview Selector)
        $previewInstitutionsQuery = EducationalInstitution::query();
        if ($this->previewSearchTerm !== '') {
            $previewInstitutionsQuery->where(function ($query) {
                $query->where('name', 'like', "%{$this->previewSearchTerm}%")
                    ->orWhere('code', 'like', "%{$this->previewSearchTerm}%")
                    ->orWhere('municipality', 'like', "%{$this->previewSearchTerm}%")
                    ->orWhere('wilaya', 'like', "%{$this->previewSearchTerm}%")
                    ->orWhere('principal_name', 'like', "%{$this->previewSearchTerm}%");
            });
        }
        if ($this->previewWilayaFilter) {
            $previewInstitutionsQuery->where('wilaya', $this->previewWilayaFilter);
        }
        if ($this->previewStageFilter) {
            $previewInstitutionsQuery->where('stage', $this->previewStageFilter);
        }
        $previewInstitutions = $previewInstitutionsQuery->orderByDesc('is_current')->orderBy('name')->get();

        $availableWilayas = EducationalInstitution::query()
            ->whereNotNull('wilaya')
            ->distinct()
            ->pluck('wilaya')
            ->filter()
            ->values();

        return view('livewire.developer-dashboard-stats', [
            'user' => auth()->user(),
            'currentInstitution' => $currentInstitution,
            'selectedInstitution' => $selectedInstitution,
            'platformSetting' => $platformSetting,
            'telemetry' => $telemetry,
            'totalInstitutions' => $totalInstitutions,
            'activeInstitutions' => $activeInstitutions,
            'pendingInstitutions' => $pendingInstitutions,
            'suspendedInstitutions' => $suspendedInstitutions,
            'stageCounts' => $stageCounts,
            'totalCapacity' => $totalCapacity,
            'enrolledStudentsInActiveSchool' => $enrolledStudentsInActiveSchool,
            'activeClassrooms' => $activeClassrooms,
            'usersCount' => $usersCount,
            'principalsCount' => $principalsCount,
            'teachersCount' => $teachersCount,
            'censorsCount' => $censorsCount,
            'counselorsCount' => $counselorsCount,
            'supervisorsCount' => $supervisorsCount,
            'institutions' => $institutions,
            'wilayasDistribution' => $wilayasDistribution,
            'previewInstitutions' => $previewInstitutions,
            'availableWilayas' => $availableWilayas,
            'createInstitutionUrl' => EducationalInstitutionResource::getUrl('create'),
            'registryUrl' => EducationalInstitutionResource::getUrl('index'),
            'usersUrl' => UserResource::getUrl('index'),
        ]);
    }
}
