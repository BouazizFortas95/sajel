<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Top Header & Tab Navigation -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 rounded-xl">
                    <x-heroicon-o-sparkles class="w-7 h-7" />
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ __('التعاونية المدرسية، الحديقة البيداغوجية والنوادي الخضراء') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('مشاريع التضامن المدرسي، الحديقة البيداغوجية والمشتلة، مبادرات التشجير والرسكلة، وصندوق مالية التعاونية') }}
                    </p>
                </div>
            </div>

            <!-- Tab Buttons -->
            <div class="flex flex-wrap items-center gap-2 bg-gray-100 dark:bg-gray-800/80 p-1 rounded-xl">
                <button
                    wire:click="switchTab('projects')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'projects' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-clipboard-document-list class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('مشاريع ومبادرات التعاونية') }}
                </button>
                <button
                    wire:click="switchTab('garden')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'garden' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-squares-plus class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('الحديقة البيداغوجية والمشتلة') }}
                </button>
                <button
                    wire:click="switchTab('eco_club')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'eco_club' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-globe-americas class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('النادي الأخضر والبيئة المدرسية') }}
                </button>
                <button
                    wire:click="switchTab('treasury')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'treasury' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-banknotes class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('صندوق المالية والتضامن') }}
                </button>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 1: COOPERATIVE PROJECTS & SOLIDARITY INITIATIVES      -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'projects')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي مشاريع التعاونية') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['totalProjectsCount'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('مشاريع مسجلة لسنة') }} {{ $academicYear }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('المشاريع النشطة والجارية') }}</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $this->telemetry['activeProjectsCount'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('قيد التنفيذ والتأطير') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('المشاريع المكتملة المنجزة') }}</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $this->telemetry['completedProjectsCount'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('أهداف محققة بنجاح') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('التلاميذ المنخرطون بالتعاونية') }}</div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $this->telemetry['totalMembersCount'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('تلميذاً وتلميذة') }}</div>
                </div>
            </div>

            <!-- Action Bar & Filters -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="{{ __('بحث برمز المشروع أو العنوان...') }}"
                        class="text-sm px-3.5 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 w-64"
                    />
                    <select
                        wire:model.live="projectTypeFilter"
                        class="text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="all">{{ __('جميع مجالات المشاريع') }}</option>
                        @foreach(App\Enums\CooperativeProjectType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <select
                        wire:model.live="projectStatusFilter"
                        class="text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="all">{{ __('جميع حالات المشاريع') }}</option>
                        @foreach(App\Enums\CooperativeProjectStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        wire:click="openProjectModal"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('اعتماد مشروع / مبادرة جديدة') }}
                    </button>
                </div>
            </div>

            <!-- Projects Ledger Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-700 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-4 py-3">{{ __('رمز المشروع') }}</th>
                                <th class="px-4 py-3">{{ __('عنوان المشروع والمبادرة') }}</th>
                                <th class="px-4 py-3">{{ __('المجال والتصنيف') }}</th>
                                <th class="px-4 py-3">{{ __('الجمهور المستهدف') }}</th>
                                <th class="px-4 py-3">{{ __('الفترة الزمنية') }}</th>
                                <th class="px-4 py-3">{{ __('الميزانية المخصصة') }}</th>
                                <th class="px-4 py-3">{{ __('الحالة') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('المطبوعات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->projects as $project)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $project->project_code }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $project->title }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                            {{ $project->project_type?->label() ?? $project->project_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $project->target_audience ?: 'تلاميذ المؤسسة' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $project->start_date?->format('Y-m-d') }}
                                        @if($project->end_date)
                                            <span class="text-gray-400">←</span> {{ $project->end_date->format('Y-m-d') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-800 dark:text-gray-200">
                                        {{ number_format($project->budget_allocated, 2) }} دج
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                            {{ $project->status?->label() ?? $project->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a
                                                href="{{ route('print.cooperative.project-fiche', ['locale' => app()->getLocale(), 'project' => $project->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-xs font-medium rounded text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30">
                                                {{ __('معاينة') }}
                                            </a>
                                            <a
                                                href="{{ route('print.cooperative.project-fiche.pdf', ['locale' => app()->getLocale(), 'project' => $project->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-xs font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد مشاريع مسجلة وفق معايير البحث المحددة.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 2: PEDAGOGICAL GARDEN & BOTANICAL PLOTS               -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'garden')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('أحواض الحديقة البيداغوجية') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['gardenPlotsCount'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('أحواض ورقع نباتية مجهزة') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('المساحة المزروعة الإجمالية') }}</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($this->telemetry['totalGardenAreaSqm'], 0) }} م²</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('مساحة خضراء بيداغوجية') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('الأصناف والمغروسات الكلية') }}</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $this->telemetry['totalPlantsCount'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('نبتة وشجيرة مسجلة بالجرد') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('أنظمة السقي المعتمدة') }}</div>
                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400 mt-1">{{ __('تقطير ورش مقتصد') }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('محافظة على الثروة المائية') }}</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('الأحواض البيداغوجية وجرد الأصناف النباتية') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('تسيير ورعاية الأحواض النباتية، أنظمة السقي، وتأطير الأفواج التربوية') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        wire:click="openPlantModal"
                        class="px-3.5 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300 font-medium text-xs rounded-lg transition flex items-center gap-1.5">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('إضافة صنف نباتي للحوض') }}
                    </button>
                    <button
                        wire:click="openPlotModal"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('تهيئة حوض بيداغوجي جديد') }}
                    </button>
                </div>
            </div>

            <!-- Garden Plots Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($this->gardenPlots as $plot)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm space-y-4">
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        {{ $plot->plot_code }}
                                    </span>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $plot->plot_name }}</h4>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $plot->location_in_school ?: 'فناء المؤسسة' }}</p>
                            </div>
                            <div class="text-left">
                                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ number_format($plot->surface_area_sqm, 1) }} م²</span>
                                <div class="text-[11px] text-gray-400">{{ $plot->irrigation_type?->label() ?? $plot->irrigation_type }}</div>
                            </div>
                        </div>

                        <!-- Plot Details -->
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded">
                                <span class="text-gray-500">{{ __('الفوج المكلف:') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white mr-1">{{ $plot->assignedClassroom?->name ?: 'عام لجميع الأفواج' }}</span>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded">
                                <span class="text-gray-500">{{ __('الأستاذ المشرف:') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white mr-1">{{ $plot->supervisingTeacher?->name ?: 'أستاذ العلوم' }}</span>
                            </div>
                        </div>

                        <!-- Plants mini-table -->
                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                <span>{{ __('الأصناف المغروسة بالحوض (:count)', ['count' => $plot->plants->count()]) }}</span>
                                <button
                                    wire:click="openPlantModal({{ $plot->id }})"
                                    class="text-emerald-600 hover:underline text-[11px]">
                                    + {{ __('إضافة نبتة') }}
                                </button>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden text-xs">
                                <table class="w-full text-right">
                                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 text-[11px]">
                                        <tr>
                                            <th class="p-2">{{ __('الاسم') }}</th>
                                            <th class="p-2">{{ __('التصنيف') }}</th>
                                            <th class="p-2">{{ __('العدد') }}</th>
                                            <th class="p-2">{{ __('الحالة') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @forelse($plot->plants as $plant)
                                            <tr>
                                                <td class="p-2 font-medium">{{ $plant->common_name_ar }}</td>
                                                <td class="p-2 text-gray-500">{{ $plant->plant_category?->label() ?? $plant->plant_category }}</td>
                                                <td class="p-2 font-bold">{{ $plant->quantity_planted }}</td>
                                                <td class="p-2 text-emerald-600">{{ $plant->health_status?->label() ?? $plant->health_status }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="p-3 text-center text-gray-400">{{ __('لا توجد نباتات مسجلة بعد بالحوض') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Plot Footer Actions -->
                        <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800">
                            <span class="text-xs text-gray-400">{{ $plot->soil_type ?: 'تربة زراعية مخصبة' }}</span>
                            <div class="flex items-center gap-1.5">
                                <a
                                    href="{{ route('print.cooperative.garden-fiche', ['locale' => app()->getLocale(), 'plot' => $plot->id]) }}"
                                    target="_blank"
                                    class="px-2.5 py-1 text-xs font-medium rounded text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30">
                                    {{ __('معاينة البطاقة') }}
                                </a>
                                <a
                                    href="{{ route('print.cooperative.garden-fiche.pdf', ['locale' => app()->getLocale(), 'plot' => $plot->id]) }}"
                                    target="_blank"
                                    class="px-2.5 py-1 text-xs font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30">
                                    PDF
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 text-center py-12 text-gray-500">
                        {{ __('لا توجد أحواض بيداغوجية معتمدة. يمكنك تهيئة أول حوض الآن.') }}
                    </div>
                @endforelse
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 3: ECO-SCHOOL GREEN CLUB & AFFORESTATION              -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'eco_club')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('مبادرات النادي الأخضر') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['ecoActionsCount'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('نشاطاً بيئياً منجزاً') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('أشجار وشجيرات مغروسة') }}</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $this->telemetry['treesPlanted'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('حملات التشجير المدرسي') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('نفايات مجمعة للرسكلة') }}</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($this->telemetry['wasteRecycledKg'], 1) }} كغ</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('فرز انتقائي للورق والبلاستيك') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('التلاميذ والمؤطرون المشاركون') }}</div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $this->telemetry['ecoParticipants'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('تلميذاً في الحملات الميدانية') }}</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('سجل حملات ومبادرات النادي الأخضر والبيئة') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('حملات غرس الأشجار، الفرز الانتقائي للنفايات، وترشيد استهلاك الماء والطاقة') }}</p>
                </div>
                <div>
                    <button
                        wire:click="openEcoActionModal"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('تسجيل نشاط بيئي للنادي الأخضر') }}
                    </button>
                </div>
            </div>

            <!-- Eco Actions Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-700 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-4 py-3">{{ __('رمز النشاط') }}</th>
                                <th class="px-4 py-3">{{ __('عنوان المبادرة البيئية') }}</th>
                                <th class="px-4 py-3">{{ __('المجال البيئي') }}</th>
                                <th class="px-4 py-3">{{ __('تاريخ التنفيذ') }}</th>
                                <th class="px-4 py-3">{{ __('المشاركون') }}</th>
                                <th class="px-4 py-3">{{ __('أشجار مغروسة') }}</th>
                                <th class="px-4 py-3">{{ __('نفايات مرسكلة (كغ)') }}</th>
                                <th class="px-4 py-3">{{ __('الشريك / الهيئة') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('المطبوعات') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->ecoActions as $action)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $action->action_code }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $action->action_title }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                            {{ $action->action_category?->label() ?? $action->action_category }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $action->execution_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-bold text-gray-800 dark:text-gray-200">
                                        {{ $action->participants_count }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-bold text-emerald-600">
                                        {{ $action->trees_planted_count > 0 ? $action->trees_planted_count : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-bold text-blue-600">
                                        {{ $action->waste_collected_kg > 0 ? number_format($action->waste_collected_kg, 1) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $action->partner_organization ?: 'داخلي للمؤسسة' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a
                                                href="{{ route('print.cooperative.eco-report', ['locale' => app()->getLocale(), 'action' => $action->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-xs font-medium rounded text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30">
                                                {{ __('معاينة') }}
                                            </a>
                                            <a
                                                href="{{ route('print.cooperative.eco-report.pdf', ['locale' => app()->getLocale(), 'action' => $action->id]) }}"
                                                target="_blank"
                                                class="px-2 py-1 text-xs font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد مبادرات بيئية مسجلة لهذه السنة الدراسية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 4: SOLIDARITY FUND & TREASURY CASHBOOK                -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'treasury')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('الرصيد الصافي المتاح بالصندوق') }}</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($this->telemetry['treasuryBalanceDzd'], 2) }} دج</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('صندوق التعاونية والتضامن') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي المداخيل المحصلة') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->telemetry['incomeTotalDzd'], 2) }} دج</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('اشتراكات، تبرعات، ومبيعات الحديقة') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي المصاريف والمساعدات') }}</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ number_format($this->telemetry['expenseTotalDzd'], 2) }} دج</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('إعانة المعوزين وبذور وبستنة') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('الحصيلة السنوية الرسمية') }}</div>
                    <div class="mt-2">
                        <a
                            href="{{ route('print.cooperative.annual-bilan', ['locale' => app()->getLocale(), 'academicYear' => str_replace('/', '-', $academicYear)]) }}"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg transition shadow-sm">
                            <x-heroicon-m-printer class="w-4 h-4" />
                            {{ __('طباعة الحصيلة السنوية') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('دفتر حركة صندوق التعاونية المدرسية (مداخيل ومصاريف)') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('تقييد اشتراكات التلاميذ، مداخيل معارض الرسم والبستنة، ومصاريف التضامن الموجهة للمعوزين') }}</p>
                </div>
                <div>
                    <button
                        wire:click="openTransactionModal"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ __('قيد عملية مالية بالصندوق') }}
                    </button>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-700 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-4 py-3">{{ __('المرجع') }}</th>
                                <th class="px-4 py-3">{{ __('التاريخ') }}</th>
                                <th class="px-4 py-3">{{ __('نوع العملية') }}</th>
                                <th class="px-4 py-3">{{ __('البيان والشرح') }}</th>
                                <th class="px-4 py-3">{{ __('المصدر / الجهة المستفيدة') }}</th>
                                <th class="px-4 py-3">{{ __('طريقة الدفع') }}</th>
                                <th class="px-4 py-3">{{ __('المبلغ (دج)') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($this->transactions as $tx)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $tx->transaction_reference }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $tx->transaction_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $tx->transaction_type?->isIncome() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' }}">
                                            {{ $tx->transaction_type?->label() ?? $tx->transaction_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-medium text-gray-900 dark:text-white">
                                        {{ $tx->description ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                        {{ $tx->beneficiary_or_source }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $tx->payment_method }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold {{ $tx->transaction_type?->isIncome() ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $tx->transaction_type?->isIncome() ? '+' : '-' }}{{ number_format($tx->amount_dzd, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        {{ __('لا توجد حركات مالية مسجلة لهذه السنة الدراسية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- MODAL 1: ADD COOPERATIVE PROJECT                          -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($isProjectModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-xl w-full p-6 space-y-4 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('اعتماد مشروع / مبادرة جديدة للتعاونية') }}</h3>
                        <button wire:click="closeProjectModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('عنوان المشروع أو المبادرة *') }}</label>
                            <input type="text" wire:model="projectTitle" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" placeholder="{{ __('مثال: معرض المشغولات اليدوية والرسم، حملة كسوة الشتاء...') }}" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('طبيعة وتصنيف المشروع *') }}</label>
                                <select wire:model="projectType" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                    @foreach(App\Enums\CooperativeProjectType::cases() as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الميزانية المخصصة (دج) *') }}</label>
                                <input type="number" step="100" wire:model="budgetAllocated" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الانطلاق *') }}</label>
                                <input type="date" wire:model="startDate" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الاختتام (اختياري)') }}</label>
                                <input type="date" wire:model="endDate" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الجمهور المستهدف') }}</label>
                            <input type="text" wire:model="targetAudience" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الفريق التلميذي القيادي والمهام') }}</label>
                            <textarea wire:model="studentLeadsSummary" rows="2" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button wire:click="closeProjectModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ __('إلغاء') }}</button>
                        <button wire:click="saveProject" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">{{ __('حفظ المشروع') }}</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- MODAL 2: ADD GARDEN PLOT                                  -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($isPlotModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-xl w-full p-6 space-y-4 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('تهيئة حوض بيداغوجي بالحديقة') }}</h3>
                        <button wire:click="closePlotModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم الحوض البيداغوجي *') }}</label>
                            <input type="text" wire:model="plotName" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" placeholder="{{ __('مثال: حوض النباتات العطرية، مشتلة الحمضيات...') }}" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المساحة (م²) *') }}</label>
                                <input type="number" step="0.5" wire:model="surfaceArea" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نظام السقي المعتمد *') }}</label>
                                <select wire:model="irrigationType" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                    @foreach(App\Enums\GardenIrrigationType::cases() as $irr)
                                        <option value="{{ $irr->value }}">{{ $irr->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('موقع الحوض بالمؤسسة') }}</label>
                                <input type="text" wire:model="locationInSchool" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الفوج التربوي المكلف') }}</label>
                                <select wire:model="assignedClassroomId" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                    <option value="">{{ __('مشترك عام لجميع الأفواج') }}</option>
                                    @foreach($this->classrooms as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع وطبيعة التربة') }}</label>
                            <input type="text" wire:model="soilType" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات وتوجيهات الرعاية') }}</label>
                            <textarea wire:model="plotNotes" rows="2" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button wire:click="closePlotModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ __('إلغاء') }}</button>
                        <button wire:click="savePlot" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">{{ __('حفظ الحوض') }}</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- MODAL 3: ADD BOTANICAL PLANT                              -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($isPlantModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-xl w-full p-6 space-y-4 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('إضافة صنف نباتي للحوض البيداغوجي') }}</h3>
                        <button wire:click="closePlantModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اختر الحوض البيداغوجي المستهدف *') }}</label>
                            <select wire:model="selectedPlotId" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                @foreach($this->gardenPlots as $plot)
                                    <option value="{{ $plot->id }}">{{ $plot->plot_code }} — {{ $plot->plot_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الاسم الشائع بالعربية *') }}</label>
                                <input type="text" wire:model="commonNameAr" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" placeholder="{{ __('مثال: إكليل الجبل، النعناع...') }}" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الاسم العلمي / اللاتيني') }}</label>
                                <input type="text" wire:model="botanicalName" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" placeholder="{{ __('مثال: Rosmarinus officinalis') }}" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('التصنيف النباتي *') }}</label>
                                <select wire:model="plantCategory" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                    @foreach(App\Enums\BotanicalPlantCategory::cases() as $cat)
                                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('العدد المغروس *') }}</label>
                                <input type="number" min="1" wire:model="quantityPlanted" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الحالة الصحية') }}</label>
                                <select wire:model="healthStatus" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                    @foreach(App\Enums\PlantHealthStatus::cases() as $h)
                                        <option value="{{ $h->value }}">{{ $h->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الغرس') }}</label>
                            <input type="date" wire:model="plantingDate" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button wire:click="closePlantModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ __('إلغاء') }}</button>
                        <button wire:click="savePlant" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">{{ __('حفظ النبتة') }}</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- MODAL 4: RECORD ECO ACTION                                -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($isEcoActionModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-xl w-full p-6 space-y-4 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('تسجيل نشاط بيئي للنادي الأخضر') }}</h3>
                        <button wire:click="closeEcoActionModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('عنوان النشاط البيئي *') }}</label>
                            <input type="text" wire:model="ecoTitle" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" placeholder="{{ __('مثال: غرس 50 شجرة بالتعاون مع محافظة الغابات...') }}" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المجال البيئي *') }}</label>
                                <select wire:model="ecoCategory" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                    @foreach(App\Enums\EcoActionCategory::cases() as $ecoCat)
                                        <option value="{{ $ecoCat->value }}">{{ $ecoCat->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ التنفيذ *') }}</label>
                                <input type="date" wire:model="executionDate" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('عدد المشاركين *') }}</label>
                                <input type="number" min="1" wire:model="participantsCount" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('أشجار مغروسة') }}</label>
                                <input type="number" min="0" wire:model="treesPlantedCount" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نفايات للرسكلة (كغ)') }}</label>
                                <input type="number" step="0.5" min="0" wire:model="wasteCollectedKg" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مكان النشاط بالمؤسسة') }}</label>
                                <input type="text" wire:model="locationDetails" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الشريك أو الهيئة المدعمة') }}</label>
                                <input type="text" wire:model="partnerOrganization" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملخص الأثر والتقرير الميداني') }}</label>
                            <textarea wire:model="impactSummary" rows="2" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button wire:click="closeEcoActionModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ __('إلغاء') }}</button>
                        <button wire:click="saveEcoAction" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">{{ __('حفظ النشاط البيئي') }}</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- MODAL 5: RECORD FINANCIAL TRANSACTION                     -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($isTransactionModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-xl w-full p-6 space-y-4 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('قيد عملية مالية بصندوق التعاونية') }}</h3>
                        <button wire:click="closeTransactionModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع العملية المالية *') }}</label>
                                <select wire:model="transactionType" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                    @foreach(App\Enums\CooperativeTransactionType::cases() as $tType)
                                        <option value="{{ $tType->value }}">{{ $tType->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المبلغ (دج) *') }}</label>
                                <input type="number" step="50" min="1" wire:model="transactionAmount" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ العملية *') }}</label>
                                <input type="date" wire:model="transactionDate" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('طريقة الدفع *') }}</label>
                                <select wire:model="paymentMethod" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                                    <option value="Cash">{{ __('نقداً (Espèces)') }}</option>
                                    <option value="PostalCCP">{{ __('صك بريدي / تحويل CCP') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المصدر أو الجهة المستفيدة *') }}</label>
                                <input type="text" wire:model="beneficiaryOrSource" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" placeholder="{{ __('مثال: اشتراكات الفوج، مورد بذور...') }}" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم الوصل أو الفاتورة') }}</label>
                                <input type="text" wire:model="receiptNumber" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('البيان والشرح') }}</label>
                            <textarea wire:model="transactionDescription" rows="2" class="w-full text-sm border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button wire:click="closeTransactionModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ __('إلغاء') }}</button>
                        <button wire:click="saveTransaction" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">{{ __('قيد العملية') }}</button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
