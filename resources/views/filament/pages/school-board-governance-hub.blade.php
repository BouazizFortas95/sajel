<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Top Header & Tab Navigation -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 rounded-xl">
                    <x-heroicon-o-building-library class="w-7 h-7" />
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ __('مجلس التربية والتسيير ومشروع المؤسسة (CEG / COG)') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('الهيئة العليا للتسيير، مداولات المجلس، عقد أهداف مشروع المؤسسة، والنظام الداخلي والشراكة مع جمعية الأولياء') }}
                    </p>
                </div>
            </div>

            <!-- Tab Buttons -->
            <div class="flex flex-wrap items-center gap-2 bg-gray-100 dark:bg-gray-800/80 p-1 rounded-xl">
                <button
                    wire:click="setTab('board_sessions')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'board_sessions' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-document-text class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('دورات ومحاضر المجلس') }}
                </button>
                <button
                    wire:click="setTab('members')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'members' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-users class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('أعضاء المجلس والهيئة') }}
                </button>
                <button
                    wire:click="setTab('school_project')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'school_project' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-chart-bar-square class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('مشروع المؤسسة والنجاعة') }}
                </button>
                <button
                    wire:click="setTab('parents_and_regulations')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition {{ $activeTab === 'parents_and_regulations' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    <x-heroicon-m-user-group class="w-4 h-4 inline-block mr-1 -mt-0.5" />
                    {{ __('جمعية الأولياء والنظام الداخلي') }}
                </button>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 1: BOARD SESSIONS (CEG / COG)                         -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'board_sessions')
            <!-- Telemetry Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('إجمالي دورات المجلس') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->telemetry['sessions_count'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ __('سنة') }} {{ $academicYear }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('دورات تمت المداولة عليها') }}</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $this->telemetry['deliberated_sessions'] }}</div>
                    <div class="text-[11px] text-blue-500/80 mt-1">{{ __('مستوفية للنصاب القانوني') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('أعضاء المجلس المقيدون') }}</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $this->telemetry['members_count'] }}</div>
                    <div class="text-[11px] text-green-500/80 mt-1">{{ __('بحكم المنصب والمنتخبون') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('تقدم مشروع المؤسسة') }}</div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $this->telemetry['project_completion_rate'] }}%</div>
                    <div class="text-[11px] text-purple-500/80 mt-1">{{ __('عقد النجاعة 2026-2029') }}</div>
                </div>
            </div>

            <!-- Toolbar & Actions -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <x-heroicon-m-magnifying-glass class="w-4 h-4 absolute top-3 right-3 text-gray-400" />
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="{{ __('بحث برقم الدورة، جدول الأعمال...') }}"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 pr-9 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <select wire:model.live="boardTypeFilter" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <option value="all">{{ __('جميع المجالس (CEG / COG)') }}</option>
                        <option value="ceg">{{ __('مجلس التربية والتسيير (متوسط)') }}</option>
                        <option value="cog">{{ __('مجلس التوجيه والتسيير (ثانوي)') }}</option>
                    </select>

                    <select wire:model.live="sessionTermFilter" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <option value="all">{{ __('جميع الدورات') }}</option>
                        <option value="term_1_opening">{{ __('الدورة الأولى: الافتتاح ومشروع المؤسسة') }}</option>
                        <option value="term_2_budget">{{ __('الدورة الثانية: الميزانية والوقاية') }}</option>
                        <option value="term_3_bilan">{{ __('الدورة الثالثة: الحصيلة السنوية') }}</option>
                        <option value="extraordinary">{{ __('دورة استثنائية') }}</option>
                    </select>
                </div>

                <button
                    wire:click="openSessionModal"
                    class="w-full md:w-auto px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition flex items-center justify-center gap-1.5">
                    <x-heroicon-m-plus-circle class="w-4 h-4" />
                    {{ __('تسجيل دورة مجلس جديدة') }}
                </button>
            </div>

            <!-- Sessions Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                            <tr>
                                <th class="p-3 text-start">{{ __('رمز الدورة') }}</th>
                                <th class="p-3 text-start">{{ __('طبيعة الدورة والمجلس') }}</th>
                                <th class="p-3 text-start">{{ __('تاريخ ومكان الانعقاد') }}</th>
                                <th class="p-3 text-start">{{ __('النصاب والحضور') }}</th>
                                <th class="p-3 text-start">{{ __('حالة المحضر') }}</th>
                                <th class="p-3 text-center">{{ __('الإجراءات والطباعة') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                            @forelse($this->sessions as $session)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                        {{ $session->session_code }}
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ $session->session_term?->label() ?? $session->session_term }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 font-medium">
                                            {{ $session->board_type?->label() ?? $session->board_type }}
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <div>{{ $session->session_date?->format('Y-m-d') }} ({{ $session->session_time }})</div>
                                        <div class="text-[11px] text-gray-500">{{ $session->meeting_place }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center gap-1 font-semibold text-[11px] {{ $session->is_quorum_reached ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            <x-heroicon-m-check-badge class="w-4 h-4" />
                                            {{ $session->members_present_count }} / {{ $session->quorum_required }} {{ __('حاضرون') }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                                            {{ match($session->status?->value) {
                                                'deliberated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                                'submitted_to_directorate' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300'
                                            } }}">
                                            {{ $session->status?->label() ?? $session->status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a
                                                href="{{ route('print.governance.board-session-pv', ['locale' => app()->getLocale(), 'session' => $session->id]) }}"
                                                target="_blank"
                                                class="p-1 text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                                title="{{ __('معاينة وطباعة محضر المداولات') }}">
                                                <x-heroicon-o-printer class="w-4 h-4" />
                                            </a>
                                            <a
                                                href="{{ route('print.governance.board-session-pv.pdf', ['locale' => app()->getLocale(), 'session' => $session->id]) }}"
                                                target="_blank"
                                                class="p-1 text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                                title="{{ __('تحميل نسخة PDF للمحضر') }}">
                                                <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                                            </a>
                                            @if($session->status?->value === 'deliberated')
                                                <button
                                                    wire:click="submitSessionToDirectorate({{ $session->id }})"
                                                    class="p-1 text-gray-600 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400"
                                                    title="{{ __('إرسال المحضر لمديرية التربية') }}">
                                                    <x-heroicon-o-paper-airplane class="w-4 h-4" />
                                                </button>
                                            @elseif($session->status?->value === 'submitted_to_directorate')
                                                <button
                                                    wire:click="approveSession({{ $session->id }})"
                                                    class="p-1 text-gray-600 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400"
                                                    title="{{ __('المصادقة والتأشير النهائي') }}">
                                                    <x-heroicon-o-check-circle class="w-4 h-4" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500">
                                        {{ __('لا توجد دورات مسجلة للمجلس مطابقة لمعايير البحث الحالية.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 2: BOARD MEMBERS & MANDATES                           -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'members')
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تشكيلة أعضاء مجلس التربية والتسيير (الهيئة التداولية)') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('تركيبة قانونية وفقاً للمرسوم التنفيذي 90-12 (الأعضاء بحكم المنصب والأعضاء المنتخبون لمدة 3 سنوات)') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        wire:click="bootstrapDefaultMembers"
                        class="px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 rounded-lg transition flex items-center gap-1.5">
                        <x-heroicon-m-arrow-path class="w-4 h-4" />
                        {{ __('التهيئة التلقائية القانونية') }}
                    </button>
                    <button
                        wire:click="openMemberModal"
                        class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition flex items-center gap-1.5">
                        <x-heroicon-m-user-plus class="w-4 h-4" />
                        {{ __('إضافة عضو للمجلس') }}
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                            <tr>
                                <th class="p-3 text-start">{{ __('اسم العضو') }}</th>
                                <th class="p-3 text-start">{{ __('الفئة والصفة في المجلس') }}</th>
                                <th class="p-3 text-start">{{ __('الوظيفة بالمؤسسة') }}</th>
                                <th class="p-3 text-start">{{ __('فترة العهدة الانتدابية') }}</th>
                                <th class="p-3 text-start">{{ __('حالة العضوية') }}</th>
                                <th class="p-3 text-start">{{ __('رقم الهاتف') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                            @forelse($this->members as $member)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">
                                        {{ $member->member_name }}
                                    </td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                                            {{ match($member->member_category?->value) {
                                                'statutory' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                                'elected_teacher' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                                'elected_staff' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                                default => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300'
                                            } }}">
                                            {{ $member->member_category?->label() ?? $member->member_category }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-700 dark:text-gray-300 font-medium">
                                        {{ $member->institution_role }}
                                    </td>
                                    <td class="p-3 text-gray-500 font-mono text-[11px]">
                                        {{ $member->mandate_start_date?->format('Y-m-d') }} — {{ $member->mandate_end_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="p-3">
                                        @if($member->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                                {{ __('عهدة سارية') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300">
                                                {{ __('منتهية') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 font-mono text-gray-600 dark:text-gray-400">
                                        {{ $member->phone_number ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500">
                                        {{ __('لا يوجد أعضاء مقيدون حالياً في المجلس.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 3: SCHOOL PROJECT (PROJET D'ÉTABLISSEMENT)            -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'school_project')
            @php $project = $this->schoolProject; @endphp
            <div class="space-y-6">
                <!-- Project Summary Card -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-teal-600 dark:text-teal-400 text-sm">{{ $project?->project_code ?? 'PRJ-2026-01' }}</span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                    {{ $project?->status?->label() ?? __('قيد التنفيذ') }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white mt-1">{{ $project?->title ?? 'مشروع المؤسسة' }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('الفترة الزمنية للتعاقد:') }} {{ $project?->start_year }} — {{ $project?->end_year }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($project)
                                <a
                                    href="{{ route('print.governance.school-project', ['locale' => app()->getLocale(), 'project' => $project->id]) }}"
                                    target="_blank"
                                    class="px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 rounded-lg transition flex items-center gap-1.5">
                                    <x-heroicon-o-printer class="w-4 h-4" />
                                    {{ __('طباعة بطاقة المشروع') }}
                                </a>
                                <a
                                    href="{{ route('print.governance.school-project.pdf', ['locale' => app()->getLocale(), 'project' => $project->id]) }}"
                                    target="_blank"
                                    class="px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 rounded-lg transition flex items-center gap-1.5">
                                    <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                                    {{ __('تحميل PDF') }}
                                </a>
                            @endif
                            <button
                                wire:click="openProjectModal"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-teal-600 hover:bg-teal-700 rounded-lg shadow-sm transition flex items-center gap-1.5">
                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                {{ __('تحيين أهداف المشروع') }}
                            </button>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ __('نسبة الإنجاز المحققة من الأهداف المسطرة:') }}</span>
                            <span class="font-bold text-teal-600 dark:text-teal-400 font-mono">{{ $project?->completion_rate_percentage ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-teal-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $project?->completion_rate_percentage ?? 0 }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Strategic Axes & Targets Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Strategic Axes -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5">
                            <x-heroicon-m-arrows-pointing-out class="w-4 h-4 text-teal-600" />
                            {{ __('المحاور الاستراتيجية المعتمدة للمشروع (Axes stratégiques)') }}
                        </h4>
                        <ul class="space-y-2 text-xs">
                            @if(is_array($project?->strategic_axes))
                                @foreach($project->strategic_axes as $idx => $axis)
                                    <li class="flex items-start gap-2 bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                                        <span class="font-bold text-teal-600 dark:text-teal-400 mt-0.5">{{ $idx + 1 }}.</span>
                                        <span class="text-gray-800 dark:text-gray-200">{{ $axis }}</span>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>

                    <!-- Quantitative Targets -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5">
                            <x-heroicon-m-chart-bar class="w-4 h-4 text-blue-600" />
                            {{ __('الأهداف والمؤشرات الكمية المستهدفة (Indicateurs & Cibles)') }}
                        </h4>
                        <div class="space-y-2 text-xs">
                            @if(is_array($project?->quantitative_targets))
                                @foreach($project->quantitative_targets as $target => $val)
                                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                                        <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $target }}</span>
                                        <strong class="text-teal-600 dark:text-teal-400 font-mono text-sm">{{ $val }}</strong>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- TAB 4: PARENTS ASSOCIATION & INTERNAL REGULATIONS         -->
        <!-- ═════════════════════════════════════════════════════════ -->
        @if($activeTab === 'parents_and_regulations')
            @php
                $ape = $this->parentsAssociation;
                $reg = $this->regulations;
            @endphp
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Right: Parents Association Profile -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 rounded-lg">
                                    <x-heroicon-m-user-group class="w-4 h-4" />
                                </span>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('جمعية أولياء التلاميذ (APE)') }}</h4>
                            </div>
                            <button
                                wire:click="openApeModal"
                                class="text-[11px] font-semibold text-green-600 hover:text-green-700 dark:text-green-400">
                                {{ __('تعديل البيانات') }}
                            </button>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('اسم الجمعية:') }}</span>
                                <strong class="text-gray-900 dark:text-white">{{ $ape?->association_name }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('رقم وصل الإيداع / الاعتماد:') }}</span>
                                <span class="font-mono">{{ $ape?->registration_number ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('رئيس الجمعية المنتخب:') }}</span>
                                <strong class="text-green-600 dark:text-green-400">{{ $ape?->president_name }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('رقم هاتف الاتصال:') }}</span>
                                <span class="font-mono">{{ $ape?->president_phone ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('الأولياء المنخرطون:') }}</span>
                                <strong class="text-gray-900 dark:text-white">{{ $ape?->registered_parents_count }} {{ __('ولياً') }}</strong>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-[11px] text-gray-500">{{ __('حالة الاعتماد القانوني:') }}</span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                {{ __('مكتب معتمد ومجدد') }}
                            </span>
                        </div>
                    </div>

                    <!-- Left: School Internal Regulations -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded-lg">
                                    <x-heroicon-m-book-open class="w-4 h-4" />
                                </span>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('النظام الداخلي النموذجي للمؤسسة') }}</h4>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                {{ __('القرار الوزاري 65/771') }}
                            </span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('عنوان النظام:') }}</span>
                                <strong class="text-gray-900 dark:text-white">{{ $reg?->title }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('تاريخ المصادقة بالمجلس:') }}</span>
                                <span>{{ $reg?->adoption_date?->format('Y-m-d') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('تعهدات الأولياء الموقعة والمسترجعة:') }}</span>
                                <strong class="text-blue-600 dark:text-blue-400 font-mono text-sm">{{ $reg?->signed_undertakings_count }} {{ __('تعهداً') }}</strong>
                            </div>
                        </div>

                        <div class="mt-3 text-[11px] text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                            <strong>{{ __('الملخص التوجيهي للبنود:') }}</strong>
                            {{ $reg?->key_rules_summary }}
                        </div>
                    </div>
                </div>

                <!-- APE Meetings Ledger -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white">
                                {{ __('سجل محاضر الجمعيات العامة والاجتماعات التنسيقية مع جمعية الأولياء') }}
                            </h4>
                            <p class="text-[11px] text-gray-500">{{ __('محاضر رسمية لجلسات تجديد المكتب والشراكة في دعم الحياة المدرسية') }}</p>
                        </div>
                        <button
                            wire:click="openParentsMeetingModal"
                            class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition flex items-center gap-1.5">
                            <x-heroicon-m-plus-circle class="w-4 h-4" />
                            {{ __('تسجيل جمعية عامة / جلسة تنسيقية') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-start text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                                <tr>
                                    <th class="p-3 text-start">{{ __('رمز المحضر') }}</th>
                                    <th class="p-3 text-start">{{ __('نوع الجلسة / الجمعية') }}</th>
                                    <th class="p-3 text-start">{{ __('تاريخ الانعقاد') }}</th>
                                    <th class="p-3 text-start">{{ __('الأولياء الحاضرون') }}</th>
                                    <th class="p-3 text-start">{{ __('جدول الأعمال') }}</th>
                                    <th class="p-3 text-center">{{ __('الطباعة والإجراءات') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                                @forelse($ape?->meetings ?? [] as $m)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                        <td class="p-3 font-mono font-bold text-green-600 dark:text-green-400">
                                            {{ $m->meeting_code }}
                                        </td>
                                        <td class="p-3 font-medium">
                                            {{ $m->meeting_type?->label() ?? $m->meeting_type }}
                                        </td>
                                        <td class="p-3 text-gray-500">
                                            {{ $m->meeting_date?->format('Y-m-d') }}
                                        </td>
                                        <td class="p-3 font-semibold text-gray-900 dark:text-white">
                                            {{ $m->attendees_count }} {{ __('ولياً') }}
                                        </td>
                                        <td class="p-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                            {{ $m->agenda_topics }}
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a
                                                    href="{{ route('print.governance.parents-assembly-pv', ['locale' => app()->getLocale(), 'meeting' => $m->id]) }}"
                                                    target="_blank"
                                                    class="p-1 text-gray-600 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400"
                                                    title="{{ __('طباعة محضر الجمعية') }}">
                                                    <x-heroicon-o-printer class="w-4 h-4" />
                                                </a>
                                                <a
                                                    href="{{ route('print.governance.parents-assembly-pv.pdf', ['locale' => app()->getLocale(), 'meeting' => $m->id]) }}"
                                                    target="_blank"
                                                    class="p-1 text-gray-600 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400"
                                                    title="{{ __('تحميل PDF') }}">
                                                    <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-gray-500">
                                            {{ __('لم يتم تسجيل اجتماعات أو جمعيات عامة حتى الآن لهذه السنة الدراسية.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 1: RECORD BOARD SESSION                             -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isSessionModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-2xl w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-document-text class="w-5 h-5 text-blue-600" />
                        {{ __('تسجيل دورة مجلس التربية والتسيير ومحضر المداولات') }}
                    </h3>
                    <button wire:click="closeSessionModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('طبيعة المجلس:') }}</label>
                        <select wire:model="newBoardType" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="ceg">{{ __('مجلس التربية والتسيير (متوسط - CEM)') }}</option>
                            <option value="cog">{{ __('مجلس التوجيه والتسيير (ثانوي - Lycée)') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('طبيعة الدورة:') }}</label>
                        <select wire:model="newSessionTerm" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="term_1_opening">{{ __('الدورة العادية الأولى: افتتاح السنة ومشروع المؤسسة') }}</option>
                            <option value="term_2_budget">{{ __('الدورة العادية الثانية: الميزانية والوقاية') }}</option>
                            <option value="term_3_bilan">{{ __('الدورة العادية الثالثة: الحصيلة السنوية والدخول') }}</option>
                            <option value="extraordinary">{{ __('دورة استثنائية طارئة') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('تاريخ الانعقاد:') }}</label>
                        <input type="date" wire:model="newSessionDate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('توقيت الجلسة ومكانها:') }}</label>
                        <div class="grid grid-cols-2 gap-1">
                            <input type="text" wire:model="newSessionTime" placeholder="14:30" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <input type="text" wire:model="newMeetingPlace" placeholder="{{ __('قاعة الاجتماعات') }}" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('رئيس المجلس (المدير):') }}</label>
                        <input type="text" wire:model="presidentName" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('مقرر الجلسة (الناظر / مستشار التربية):') }}</label>
                        <input type="text" wire:model="rapporteurName" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('النصاب القانوني المطلوب:') }}</label>
                        <input type="number" wire:model="quorumRequired" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('عدد الأعضاء الحاضرين فعلياً:') }}</label>
                        <input type="number" wire:model="membersPresentCount" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('جدول أعمال الدورة:') }}</label>
                        <textarea wire:model="agendaItems" rows="3" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('وقائع المداولات والمناقشات:') }}</label>
                        <textarea wire:model="deliberationDetails" rows="3" placeholder="{{ __('ملخص ما دار في الجلسة من مناقشات...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('القرارات والتوصيات المصادق عليها:') }}</label>
                        <textarea wire:model="decisionsSummary" rows="2" placeholder="{{ __('القرارات المتخذة بإجماع أو أغلبية الأعضاء...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeSessionModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitSession" type="button" class="px-5 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                        {{ __('اعتماد وحفظ محضر المداولة') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 2: ADD BOARD MEMBER                                 -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isMemberModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 text-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-user-plus class="w-5 h-5 text-blue-600" />
                        {{ __('إضافة عضو لمجلس التربية والتسيير') }}
                    </h3>
                    <button wire:click="closeMemberModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('فئة العضوية:') }}</label>
                        <select wire:model="memberCategory" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="statutory">{{ __('عضو بحكم المنصب (Membre de droit)') }}</option>
                            <option value="elected_teacher">{{ __('أستاذ منتخب (Enseignant élu)') }}</option>
                            <option value="elected_staff">{{ __('موظف / عامل منتخب') }}</option>
                            <option value="elected_parent">{{ __('ممثل أولياء التلاميذ') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الاسم واللقب الكامل:') }}</label>
                        <input type="text" wire:model="memberName" placeholder="{{ __('الاسم الكامل') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الصفة الوظيفية بالمؤسسة:') }}</label>
                        <input type="text" wire:model="institutionRole" placeholder="{{ __('أستاذ مادة الرياضيات، مقتصد، عون خدمة...') }}" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('بداية العهدة:') }}</label>
                            <input type="date" wire:model="mandateStartDate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('نهاية العهدة:') }}</label>
                            <input type="date" wire:model="mandateEndDate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('رقم الهاتف:') }}</label>
                        <input type="text" wire:model="memberPhone" placeholder="06XXXXXXXX" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeMemberModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitMember" type="button" class="px-5 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                        {{ __('قيد العضو في المجلس') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 3: EDIT SCHOOL PROJECT                              -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isProjectModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-lg w-full p-6 text-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-chart-bar-square class="w-5 h-5 text-teal-600" />
                        {{ __('تحيين عقد أهداف مشروع المؤسسة') }}
                    </h3>
                    <button wire:click="closeProjectModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('عنوان المشروع:') }}</label>
                        <input type="text" wire:model="projectTitle" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('سنة البداية:') }}</label>
                            <input type="text" wire:model="projectStartYear" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('سنة النهاية:') }}</label>
                            <input type="text" wire:model="projectEndYear" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('نسبة الإنجاز المحققة (%):') }}</label>
                        <input type="number" step="0.1" min="0" max="100" wire:model="projectCompletionRate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('خطة العمل والأنشطة الإجرائية:') }}</label>
                        <textarea wire:model="projectActionPlan" rows="3" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('ملاحظات التقييم الدوري للحصيلة:') }}</label>
                        <textarea wire:model="projectEvaluationNotes" rows="2" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeProjectModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitProject" type="button" class="px-5 py-2 rounded-lg bg-teal-600 text-white font-semibold hover:bg-teal-700">
                        {{ __('حفظ التحيين') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 4: RECORD PARENTS MEETING                           -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isParentsMeetingModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-lg w-full p-6 text-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-user-group class="w-5 h-5 text-green-600" />
                        {{ __('تسجيل جمعية عامة لأولياء التلاميذ (APE)') }}
                    </h3>
                    <button wire:click="closeParentsMeetingModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('طبيعة الجلسة / الاجتماع:') }}</label>
                        <select wire:model="parentsMeetingType" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="ordinary_general_assembly">{{ __('جمعية عامة عادية وانتخاب المكتب') }}</option>
                            <option value="extraordinary_general_assembly">{{ __('جمعية عامة استثنائية') }}</option>
                            <option value="joint_school_meeting">{{ __('جلسة تنسيقية مشتركة مع إدارة المؤسسة') }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('تاريخ الانعقاد:') }}</label>
                            <input type="date" wire:model="parentsMeetingDate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('عدد الأولياء الحاضرين:') }}</label>
                            <input type="number" wire:model="parentsAttendeesCount" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('جدول الأعمال:') }}</label>
                        <textarea wire:model="parentsAgendaTopics" rows="2" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('وقائع المداولات والمناقشات:') }}</label>
                        <textarea wire:model="parentsDeliberationNarrative" rows="2" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('التوصيات وقرارات الشراكة والتعاون:') }}</label>
                        <textarea wire:model="parentsRecommendations" rows="2" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeParentsMeetingModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitParentsMeeting" type="button" class="px-5 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700">
                        {{ __('اعتماد وحفظ المحضر') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ═════════════════════════════════════════════════════════ -->
    <!-- MODAL 5: UPDATE APE PROFILE                               -->
    <!-- ═════════════════════════════════════════════════════════ -->
    @if($isApeModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 text-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-m-pencil-square class="w-5 h-5 text-green-600" />
                        {{ __('تعديل ملف جمعية أولياء التلاميذ (APE)') }}
                    </h3>
                    <button wire:click="closeApeModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('اسم الجمعية:') }}</label>
                        <input type="text" wire:model="apeName" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('رقم وصل الإيداع:') }}</label>
                            <input type="text" wire:model="apeRegNumber" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('تاريخ الاعتماد:') }}</label>
                            <input type="date" wire:model="apeAccreditationDate" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('رئيس الجمعية المنتخب:') }}</label>
                        <input type="text" wire:model="apePresidentName" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('رقم الهاتف:') }}</label>
                            <input type="text" wire:model="apePresidentPhone" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ __('الأولياء المنخرطون:') }}</label>
                            <input type="number" wire:model="apeRegisteredParents" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                    <button wire:click="closeApeModal" type="button" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('إلغاء') }}
                    </button>
                    <button wire:click="submitApeProfile" type="button" class="px-5 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700">
                        {{ __('حفظ التعديلات') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

