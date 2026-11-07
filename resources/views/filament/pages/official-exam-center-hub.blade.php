<x-filament-panels::page>
@php
  $viewData = $this->getViewData();
  $centers = $viewData['centers'];
  $selectedCenter = $viewData['selectedCenter'];
  $telemetry = $viewData['telemetry'];
  $rooms = $viewData['rooms'];
  $supervisors = $viewData['supervisors'];
  $incidents = $viewData['incidents'];
  $teachers = $viewData['teachers'];

  $selectClasses = 'block w-full rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white dark:focus:border-primary-400';
@endphp

<div class="space-y-6">

  {{-- Top Center Selector & Action Bar --}}
  <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div class="flex-1 max-w-md">
        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مركز إجراء الامتحان المختار') }}</label>
        <select wire:model.live="selectedCenterId" class="{{ $selectClasses }}">
          @forelse($centers as $c)
            <option value="{{ $c->id }}">
              {{ $c->center_name }} ({{ $c->exam_type->label() }} - {{ $c->session_year }})
            </option>
          @empty
            <option value="">{{ __('لا يوجد مراكز مسجلة حتى الآن') }}</option>
          @endforelse
        </select>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        @if($selectedCenter)
          <a
            href="{{ route('print.official-exam.center-roster', ['locale' => app()->getLocale(), 'center' => $selectedCenter->id]) }}"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
            <x-heroicon-o-printer class="w-4 h-4 text-gray-500" />
            {{ __('مخطط التأطير والقاعات') }}
          </a>

          <a
            href="{{ route('print.official-exam.center-roster.pdf', ['locale' => app()->getLocale(), 'center' => $selectedCenter->id]) }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-primary-600" />
            PDF
          </a>
        @endif

        <button
          type="button"
          wire:click="openCenterModal"
          class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition-colors shadow-sm">
          <x-heroicon-o-plus class="w-4 h-4" />
          {{ __('إنشاء مركز امتحان رسمي') }}
        </button>
      </div>
    </div>
  </div>

  @if($selectedCenter)
  {{-- Live KPI Telemetry Banner --}}
  <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-primary-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('قاعات الامتحان') }}</div>
      <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $telemetry['totalRooms'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('قاعات مجهزة بالمركز') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-emerald-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('إجمالي المترشحين') }}</div>
      <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $telemetry['totalCandidates'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('مترشح مسجل رسمياً') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-sky-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('الأساتذة الحراس') }}</div>
      <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ $telemetry['roomSupervisorsCount'] + $telemetry['roomChiefsCount'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('تأطير القاعات المباشر') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-indigo-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('نسبة تغطية الحراسة') }}</div>
      <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $telemetry['coveragePercentage'] }}%</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('من مجمل الحراس المطلوبين') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-rose-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('المحاضر والوقائع') }}</div>
      <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $telemetry['incidentsCount'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('محاضر أظرفة وحالات غش') }}</div>
    </div>
  </div>

  {{-- Navigation Tabs --}}
  <div class="border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex gap-6" aria-label="Tabs">
      <button
        wire:click="$set('activeTab', 'centers_and_rooms')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'centers_and_rooms' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-home-modern class="w-5 h-5" />
        {{ __('قاعات الامتحان وتوزيع المترشحين') }}
      </button>

      <button
        wire:click="$set('activeTab', 'supervisors')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'supervisors' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-users class="w-5 h-5" />
        {{ __('تأطير الحراسة والأمانة العامة') }}
      </button>

      <button
        wire:click="$set('activeTab', 'incidents')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'incidents' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-shield-exclamation class="w-5 h-5" />
        {{ __('محاضر الأظرفة ووقائع الامتحان (20-241)') }}
      </button>
    </nav>
  </div>

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 1: ROOMS & CANDIDATE ALLOCATION                                     --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'centers_and_rooms')
  <div class="space-y-6">

    <div class="flex justify-between items-center">
      <div>
        <h3 class="font-bold text-sm text-gray-900 dark:text-white">{{ __('خريطة قاعات مركز الإجراء وتوزيع أرقام التسجيل') }}</h3>
        <p class="text-xs text-gray-500 mt-0.5">{{ __('ضبط القاعات النموذجية (20 مترشح) وتحديد أرقام التسجيل من... إلى...') }}</p>
      </div>

      <button
        type="button"
        wire:click="openGenerateRoomsModal"
        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition-colors shadow-sm">
        <x-heroicon-o-sparkles class="w-4 h-4" />
        {{ __('توليد وتوزيع القاعات تلقائياً') }}
      </button>
    </div>

    {{-- Rooms Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="p-3">{{ __('رقم القاعة') }}</th>
            <th class="p-3">{{ __('موقع الحجرة والجناح') }}</th>
            <th class="p-3 text-center">{{ __('مجال أرقام التسجيل') }}</th>
            <th class="p-3 text-center">{{ __('عدد المترشحين') }}</th>
            <th class="p-3">{{ __('رئيس القاعة') }}</th>
            <th class="p-3 text-center">{{ __('عدد الحراس المعينين') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
          @forelse($rooms as $room)
          @php
            $chief = $room->supervisors->firstWhere('duty_role', \App\Enums\OfficialExamDutyRole::RoomChief);
            $supervisorsCount = $room->supervisors->count();
          @endphp
          <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <td class="p-3 font-bold text-primary-600 dark:text-primary-400">{{ $room->room_label }}</td>
            <td class="p-3 text-gray-700 dark:text-gray-300">{{ $room->physical_location ?? 'الجناح الرئيسي' }}</td>
            <td class="p-3 text-center font-mono text-[11px] text-gray-600 dark:text-gray-400">
              @if($room->candidate_start_number)
                {{ $room->candidate_start_number }} ⟵ {{ $room->candidate_end_number }}
              @else
                ---
              @endif
            </td>
            <td class="p-3 text-center font-bold text-gray-900 dark:text-white">{{ $room->candidate_count }}</td>
            <td class="p-3 font-medium text-gray-800 dark:text-gray-200">
              {{ $chief?->supervisor_name ?? __('لم يُعيّن بعد') }}
            </td>
            <td class="p-3 text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $supervisorsCount >= $room->supervisors_required ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                {{ $supervisorsCount }} / {{ $room->supervisors_required }}
              </span>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
              <x-heroicon-o-home-modern class="w-10 h-10 mx-auto text-gray-400 mb-2" />
              <div>{{ __('لا توجد قاعات مولدة لهذا المركز حتى الآن.') }}</div>
              <div class="text-xs text-gray-400 mt-1">{{ __('انقر على "توليد وتوزيع القاعات تلقائياً" لإعداد حجرات الامتحان وتوزيع المترشحين بنقرة واحدة.') }}</div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 2: SUPERVISORY ROSTER                                               --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'supervisors')
  <div class="space-y-6">

    <div class="flex justify-between items-center">
      <div>
        <h3 class="font-bold text-sm text-gray-900 dark:text-white">{{ __('سجل الأساتذة الحراس وأعضاء الأمانة العامة') }}</h3>
        <p class="text-xs text-gray-500 mt-0.5">{{ __('تسخير وتوزيع الأساتذة الحراس على القاعات والممرات ومتابعة الحضور والغياب') }}</p>
      </div>

      <button
        type="button"
        wire:click="openSupervisorModal"
        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition-colors shadow-sm">
        <x-heroicon-o-user-plus class="w-4 h-4" />
        {{ __('إضافة وتعيين أستاذ حارس') }}
      </button>
    </div>

    {{-- Supervisors Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="p-3">{{ __('اسم الأستاذ المؤطر') }}</th>
            <th class="p-3">{{ __('المؤسسة الأصلية') }}</th>
            <th class="p-3">{{ __('المهمة والرتبة بالمركز') }}</th>
            <th class="p-3">{{ __('القاعة المعينة') }}</th>
            <th class="p-3">{{ __('رقم الاستدعاء') }}</th>
            <th class="p-3 text-center">{{ __('حالة الحضور') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
          @forelse($supervisors as $s)
          <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <td class="p-3 font-semibold text-gray-900 dark:text-white">{{ $s->supervisor_name }}</td>
            <td class="p-3 text-gray-600 dark:text-gray-400">{{ $s->origin_institution ?? '---' }}</td>
            <td class="p-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">
                {{ $s->duty_role->label() }}
              </span>
            </td>
            <td class="p-3 font-medium text-gray-800 dark:text-gray-200">
              {{ $s->room?->room_label ?? __('غير مخصص لقاعة محددة') }}
            </td>
            <td class="p-3 font-mono text-[11px] text-gray-600 dark:text-gray-400">{{ $s->convocation_reference ?? '---' }}</td>
            <td class="p-3 text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $s->attendance_status === 'present' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' }}">
                {{ $s->attendance_status === 'present' ? __('حاضر') : __('غائب / معوض') }}
              </span>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
              <x-heroicon-o-users class="w-10 h-10 mx-auto text-gray-400 mb-2" />
              <div>{{ __('لا يوجد مؤطرون أو حراس مسجلون بهذا المركز حتى الآن.') }}</div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 3: OFFICIAL MINUTES & INCIDENTS (DECREE 20-241)                     --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'incidents')
  <div class="space-y-6">

    <div class="flex justify-between items-center">
      <div>
        <h3 class="font-bold text-sm text-gray-900 dark:text-white">{{ __('محاضر فتح الأظرفة وإثبات الوقائع ومحاولات الغش (المرسوم 20-241)') }}</h3>
        <p class="text-xs text-gray-500 mt-0.5">{{ __('توثيق سلامة مواضيع الامتحانات الرسمية ومحاضر الغش والإقصاء الانضباطي') }}</p>
      </div>

      <button
        type="button"
        wire:click="openIncidentModal"
        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition-colors shadow-sm">
        <x-heroicon-o-document-plus class="w-4 h-4" />
        {{ __('تحرير محضر رسمي جديد') }}
      </button>
    </div>

    {{-- Incidents Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="p-3">{{ __('رقم المحضر') }}</th>
            <th class="p-3">{{ __('نوع المحضر') }}</th>
            <th class="p-3">{{ __('التاريخ والجلسة') }}</th>
            <th class="p-3">{{ __('المادة') }}</th>
            <th class="p-3">{{ __('القاعة') }}</th>
            <th class="p-3">{{ __('المترشح المعني') }}</th>
            <th class="p-3 text-center">{{ __('إجراءات وطباعة') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
          @forelse($incidents as $inc)
          <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <td class="p-3 font-mono font-bold text-primary-600 dark:text-primary-400">{{ $inc->incident_number }}</td>
            <td class="p-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $inc->incident_type === \App\Enums\OfficialExamIncidentType::EnvelopeOpening ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300' : 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' }}">
                {{ $inc->incident_type->label() }}
              </span>
            </td>
            <td class="p-3 text-gray-600 dark:text-gray-400">
              {{ $inc->incident_date?->format('Y-m-d') }} ({{ $inc->session_time === 'morning' ? __('صباحاً') : __('مساءً') }})
            </td>
            <td class="p-3 font-semibold text-gray-900 dark:text-white">{{ $inc->subject_name }}</td>
            <td class="p-3 text-gray-700 dark:text-gray-300">{{ $inc->room?->room_label ?? '---' }}</td>
            <td class="p-3 text-gray-700 dark:text-gray-300">
              @if($inc->candidate_name)
                <div><strong>{{ $inc->candidate_name }}</strong></div>
                <div class="font-mono text-[10px] text-gray-500">{{ $inc->candidate_registration_number }}</div>
              @else
                <span class="text-gray-400">---</span>
              @endif
            </td>
            <td class="p-3 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($inc->incident_type === \App\Enums\OfficialExamIncidentType::EnvelopeOpening)
                  <a
                    href="{{ route('print.official-exam.envelope-pv', ['locale' => app()->getLocale(), 'incident' => $inc->id]) }}"
                    target="_blank"
                    title="{{ __('طباعة محضر فتح الأظرفة') }}"
                    class="p-1.5 rounded text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                    <x-heroicon-o-printer class="w-4 h-4" />
                  </a>
                  <a
                    href="{{ route('print.official-exam.envelope-pv.pdf', ['locale' => app()->getLocale(), 'incident' => $inc->id]) }}"
                    title="PDF"
                    class="p-1.5 rounded text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-950/40">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                  </a>
                @else
                  <a
                    href="{{ route('print.official-exam.incident-pv', ['locale' => app()->getLocale(), 'incident' => $inc->id]) }}"
                    target="_blank"
                    title="{{ __('طباعة محضر الغش / الواقعة') }}"
                    class="p-1.5 rounded text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                    <x-heroicon-o-printer class="w-4 h-4" />
                  </a>
                  <a
                    href="{{ route('print.official-exam.incident-pv.pdf', ['locale' => app()->getLocale(), 'incident' => $inc->id]) }}"
                    title="PDF"
                    class="p-1.5 rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                  </a>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="p-8 text-center text-gray-500 dark:text-gray-400">
              <x-heroicon-o-document-text class="w-10 h-10 mx-auto text-gray-400 mb-2" />
              <div>{{ __('لا توجد محاضر فتح أظرفة أو وقائع مسجلة لهذا المركز.') }}</div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
  @endif

  @else
  <div class="rounded-xl border border-gray-200 bg-white p-12 text-center dark:border-white/10 dark:bg-gray-800">
    <x-heroicon-o-academic-cap class="w-12 h-12 mx-auto text-gray-400 mb-3" />
    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('لا يوجد مركز امتحان رسمي محدد') }}</h3>
    <p class="text-xs text-gray-500 max-w-md mx-auto mt-1 mb-4">{{ __('قم بإنشاء مركز إجراء جديد لامتحانات BEM أو BAC لإدارة القاعات وتأطير الحراسة ومحاضر فتح الأظرفة.') }}</p>
    <button
      type="button"
      wire:click="openCenterModal"
      class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
      <x-heroicon-o-plus class="w-4 h-4" />
      {{ __('إنشاء مركز امتحان رسمي') }}
    </button>
  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- MODALS                                                                   --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}

  {{-- Modal 1: Create Center --}}
  @if($isCenterModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إنشاء مركز إجراء امتحان رسمي جديد') }}</h3>
        <button wire:click="closeCenterModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع الامتحان') }}</label>
            <select wire:model="centerExamType" class="{{ $selectClasses }}">
              <option value="bem">{{ __('شهادة التعليم المتوسط (BEM)') }}</option>
              <option value="bac">{{ __('شهادة البكالوريا (BAC)') }}</option>
              <option value="primary_eval">{{ __('تقييم مكتسبات الابتدائي') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('دورة السنة') }}</label>
            <input type="number" wire:model="centerSessionYear" class="{{ $selectClasses }}" min="2020" max="2035" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم مركز الإجراء الرسمي') }}</label>
          <input type="text" wire:model="centerName" placeholder="{{ __('مثال: مركز الإجراء متوسطة الشهيد العربي بن مهيدي') }}" class="{{ $selectClasses }}" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نائب رئيس المركز') }}</label>
            <input type="text" wire:model="centerViceHeadName" placeholder="{{ __('اسم النائب') }}" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رئيس الأمانة') }}</label>
            <input type="text" wire:model="centerSecretariatChief" placeholder="{{ __('اسم رئيس الأمانة') }}" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ انطلاق الامتحان') }}</label>
            <input type="date" wire:model="centerStartDate" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ اختتام الامتحان') }}</label>
            <input type="date" wire:model="centerEndDate" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات وتنظيمات المركز') }}</label>
          <textarea wire:model="centerNotes" rows="2" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeCenterModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="saveCenter" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('إنشاء المركز') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 2: Generate Rooms --}}
  @if($isGenerateRoomsModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('توليد وتوزيع قاعات الامتحان') }}</h3>
        <button wire:click="closeGenerateRoomsModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('عدد القاعات المراد تجهيزها') }}</label>
          <input type="number" wire:model="roomsToGenerate" min="1" max="50" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('عدد المترشحين في كل قاعة (النموذجي: 20)') }}</label>
          <input type="number" wire:model="candidatesPerRoom" min="5" max="35" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('بداية أرقام التسجيل (اختياري)') }}</label>
          <input type="number" wire:model="startRegistrationNumber" placeholder="26001001" class="{{ $selectClasses }}" />
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeGenerateRoomsModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="executeGenerateRooms" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('توليد القاعات') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 3: Assign Supervisor --}}
  @if($isSupervisorModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تسجيل وتعيين أستاذ حارس / مؤطر') }}</h3>
        <button wire:click="closeSupervisorModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم الأستاذ المؤطر') }}</label>
          <input type="text" wire:model="supervisorName" placeholder="{{ __('الاسم واللقب') }}" class="{{ $selectClasses }}" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المؤسسة الأصلية') }}</label>
            <input type="text" wire:model="supervisorOrigin" placeholder="{{ __('مثال: متوسطة بن باديس') }}" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم الاستدعاء الرسمي') }}</label>
            <input type="text" wire:model="supervisorConvocation" placeholder="{{ __('مثال: CONV/2026/102') }}" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المهمة والرتبة') }}</label>
            <select wire:model="supervisorDutyRole" class="{{ $selectClasses }}">
              <option value="room_chief">{{ __('رئيس القاعة المسؤول') }}</option>
              <option value="room_supervisor">{{ __('أستاذ حارس بالقاعة') }}</option>
              <option value="corridor_guard">{{ __('حارس ممر وجناح') }}</option>
              <option value="secretariat">{{ __('عضو أمانة المركز') }}</option>
              <option value="reserve">{{ __('حارس احتياطي') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('القاعة المعينة') }}</label>
            <select wire:model="supervisorRoomId" class="{{ $selectClasses }}">
              <option value="">{{ __('غير محدد / عام بالمركز') }}</option>
              @foreach($rooms as $r)
                <option value="{{ $r->id }}">{{ $r->room_label }} ({{ $r->physical_location }})</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeSupervisorModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="saveSupervisor" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تسجيل المؤطر') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 4: Add Incident / PV --}}
  @if($isIncidentModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تحرير محضر رسمي / إثبات واقعة') }}</h3>
        <button wire:click="closeIncidentModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع المحضر') }}</label>
            <select wire:model="incidentType" class="{{ $selectClasses }}">
              <option value="envelope_opening">{{ __('فتح أظرفة المواضيع وتأكيد سلامتها') }}</option>
              <option value="cheating_attempt">{{ __('إثبات محاولة غش (المرسوم 20-241)') }}</option>
              <option value="candidate_absence">{{ __('تسجيل غياب مترشح عن الجلسة') }}</option>
              <option value="medical_incident">{{ __('حالة صحية طارئة وإسعاف') }}</option>
              <option value="disciplinary_exclusion">{{ __('إقصاء انضباطي وإخلال بالنظام') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('القاعة المعنية') }}</label>
            <select wire:model="incidentRoomId" class="{{ $selectClasses }}">
              <option value="">{{ __('القاعة المركزية / عام') }}</option>
              @foreach($rooms as $r)
                <option value="{{ $r->id }}">{{ $r->room_label }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('التاريخ') }}</label>
            <input type="date" wire:model="incidentDate" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الفترة') }}</label>
            <select wire:model="incidentSessionTime" class="{{ $selectClasses }}">
              <option value="morning">{{ __('صباحية') }}</option>
              <option value="afternoon">{{ __('مسائية') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مادة الامتحان') }}</label>
            <input type="text" wire:model="incidentSubject" placeholder="{{ __('مثال: الرياضيات') }}" class="{{ $selectClasses }}" />
          </div>
        </div>

        @if($incidentType !== 'envelope_opening')
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم المترشح (إن وجد)') }}</label>
            <input type="text" wire:model="incidentCandidateName" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم التسجيل') }}</label>
            <input type="text" wire:model="incidentCandidateReg" class="{{ $selectClasses }}" />
          </div>
        </div>
        @endif

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('حيثيات وتفاصيل الواقعة') }}</label>
          <textarea wire:model="incidentDescription" rows="2" placeholder="{{ __('شرح ملابسات فتح الظرف أو واقعة الغش...') }}" class="{{ $selectClasses }}"></textarea>
        </div>

        @if($incidentType === 'cheating_attempt')
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المحجوزات ووسائل الغش المضبوطة') }}</label>
          <input type="text" wire:model="incidentSeizedEvidence" placeholder="{{ __('مثال: هاتف ذكي، سماعات بلوتوث، قصاصات...') }}" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الإجراءات المتخذة فورا') }}</label>
          <input type="text" wire:model="incidentMeasuresTaken" placeholder="{{ __('مثال: سحب وسيلة الغش وإشعار رئيس المركز...') }}" class="{{ $selectClasses }}" />
        </div>
        @endif
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeIncidentModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="saveIncident" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('حفظ وتأكيد المحضر') }}
        </button>
      </div>
    </div>
  </div>
  @endif

</div>
</x-filament-panels::page>
