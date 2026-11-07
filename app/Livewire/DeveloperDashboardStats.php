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

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isDeveloper() || $user->role === Role::Admin), 403);
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['aggregator', 'wilayas', 'active_school_preview', 'system_ops'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function makeInstitutionCurrent(int $institutionId): void
    {
        $institution = EducationalInstitution::findOrFail($institutionId);
        $institution->makeCurrent();

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

        // Filtered institutions query
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

        // Wilayas distribution
        $wilayasDistribution = EducationalInstitution::query()
            ->select('wilaya', DB::raw('count(*) as count'), DB::raw('sum(capacity) as total_capacity'))
            ->groupBy('wilaya')
            ->orderByDesc('count')
            ->get();

        return view('livewire.developer-dashboard-stats', [
            'user' => auth()->user(),
            'currentInstitution' => $currentInstitution,
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
            'createInstitutionUrl' => EducationalInstitutionResource::getUrl('create'),
            'registryUrl' => EducationalInstitutionResource::getUrl('index'),
            'usersUrl' => UserResource::getUrl('index'),
        ]);
    }
}
