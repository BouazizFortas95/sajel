<x-filament-panels::page>
@php
  $telemetry = $this->telemetry;
  $rooms = $this->rooms;
  $selectedRoom = $this->selectedRoom;
  $nightSessions = $this->nightSessions;
  $exitPasses = $this->exitPasses;
  $busRoutes = $this->busRoutes;
  $busSubscriptions = $this->busSubscriptions;
  $studentsList = $this->studentsList;

  $selectClasses = 'block w-full rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white dark:focus:border-primary-400';
@endphp

<div class="space-y-6">

  {{-- Top Navigation Tabs --}}
  <div class="border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex flex-wrap gap-4 sm:gap-6" aria-label="Tabs">
      <button
        wire:click="$set('activeTab', 'rooms')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'rooms' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-home-modern class="w-5 h-5" />
        {{ __('المراقد وتوزيع الأسرّة والخزانات') }}
      </button>

      <button
        wire:click="$set('activeTab', 'night_sessions')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'night_sessions' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-moon class="w-5 h-5" />
        {{ __('المداومة والمذاكرة الليلية') }}
      </button>

      <button
        wire:click="$set('activeTab', 'exit_passes')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'exit_passes' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-ticket class="w-5 h-5" />
        {{ __('رخص الخروج الأسبوعي والعطل') }}
      </button>

      <button
        wire:click="$set('activeTab', 'transport')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'transport' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-truck class="w-5 h-5" />
        {{ __('حافلات وخطوط النقل المدرسي') }}
      </button>
    </nav>
  </div>

  {{-- Telemetry KPI Banner --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-primary-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('طاقة استيعاب المراقد') }}</div>
      <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $telemetry['totalBedCapacity'] }} {{ __('سرير') }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ $telemetry['totalRooms'] }} {{ __('مرقد إيواء إجمالي') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-emerald-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('الأسرّة المشغولة فعلياً') }}</div>
      <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $telemetry['occupiedBeds'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ $telemetry['activeBoardersCount'] }} {{ __('تلميذ نزيل مسجل') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-sky-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('الأسرّة الشاغرة المتاحة') }}</div>
      <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ $telemetry['availableBeds'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('نسبة الإشغال:') }} {{ $telemetry['occupancyRate'] }}%</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-amber-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('رخص الخروج السارية') }}</div>
      <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $telemetry['activeExitPassesCount'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('عطل نهاية أسبوع ورخص') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-indigo-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('المستفيدون من النقل المدرسي') }}</div>
      <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $telemetry['totalSubscribedBusStudents'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ $telemetry['activeBusRoutesCount'] }} {{ __('خطوط حافلات معتمدة') }}</div>
    </div>
  </div>

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 1: DORMS & BED ALLOCATIONS                                          --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'rooms')
  <div class="space-y-6">

    {{-- Filter & Actions Bar --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('تصفية الجناح:') }}</span>
        <button
          wire:click="$set('wingFilter', 'all')"
          class="px-3 py-1 text-xs font-semibold rounded-lg transition-colors {{ $wingFilter === 'all' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300' }}">
          {{ __('جميع الأجنحة') }}
        </button>
        <button
          wire:click="$set('wingFilter', 'boys')"
          class="px-3 py-1 text-xs font-semibold rounded-lg transition-colors {{ $wingFilter === 'boys' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300' }}">
          {{ __('جناح الذكور (Garçons)') }}
        </button>
        <button
          wire:click="$set('wingFilter', 'girls')"
          class="px-3 py-1 text-xs font-semibold rounded-lg transition-colors {{ $wingFilter === 'girls' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300' }}">
          {{ __('جناح الإناث (Filles)') }}
        </button>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        @if($selectedRoom)
          <a href="{{ route('print.boarding.room-roster', ['locale' => app()->getLocale(), 'room' => $selectedRoom->id]) }}"
             target="_blank"
             class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <x-heroicon-o-printer class="w-4 h-4 text-primary-600" />
            {{ __('طباعة مخطط المرقد') }}
          </a>

          <a href="{{ route('print.boarding.room-roster.pdf', ['locale' => app()->getLocale(), 'room' => $selectedRoom->id]) }}"
             target="_blank"
             class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-primary-600" />
            {{ __('تحميل PDF') }}
          </a>
        @endif

        <button
          wire:click="openAllocationModal"
          class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 shadow-sm">
          <x-heroicon-o-user-plus class="w-4 h-4" />
          {{ __('تخصيص سرير لتلميذ') }}
        </button>

        <button
          wire:click="openRoomModal"
          class="inline-flex items-center gap-1 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 shadow-sm">
          <x-heroicon-o-plus class="w-4 h-4" />
          {{ __('إضافة مرقد جديد') }}
        </button>
      </div>
    </div>

    {{-- Dormitory Rooms Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      @forelse($rooms as $r)
        <div
          wire:click="$set('selectedRoomId', {{ $r->id }})"
          class="cursor-pointer rounded-xl border p-4 shadow-sm transition-all {{ $selectedRoomId === $r->id ? 'border-primary-500 ring-2 ring-primary-500/20 bg-primary-50/20 dark:bg-primary-950/20 dark:border-primary-400' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-white/10 dark:bg-gray-800' }}">
          <div class="flex justify-between items-start">
            <span class="px-2 py-0.5 rounded text-xs font-bold font-mono bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
              {{ $r->room_code }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $r->wing->value === 'boys' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
              {{ $r->wing->shortLabel() }}
            </span>
          </div>

          <div class="mt-2 font-bold text-sm text-gray-900 dark:text-white">{{ $r->room_name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ __('الطابق:') }} {{ $r->floor_level }}</div>

          <div class="mt-3">
            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
              <span>{{ __('الإشغال:') }} {{ $r->active_occupants_count }} / {{ $r->bed_capacity }}</span>
              <span>{{ $r->bed_capacity > 0 ? round(($r->active_occupants_count / $r->bed_capacity) * 100) : 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 overflow-hidden">
              <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ $r->bed_capacity > 0 ? min(100, ($r->active_occupants_count / $r->bed_capacity) * 100) : 0 }}%"></div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-span-full p-8 text-center text-gray-500 bg-white rounded-xl border border-gray-200 dark:border-white/10 dark:bg-gray-800">
          {{ __('لا توجد مراقد مسجلة في هذا الجناح. اضغط على "إضافة مرقد جديد" لتهيئة جناح الإيواء.') }}
        </div>
      @endforelse
    </div>

    {{-- Selected Room Details & Occupants Table --}}
    @if($selectedRoom)
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
        <div>
          <h3 class="text-sm font-bold text-gray-900 dark:text-white">
            {{ __('نزلاء المرقد:') }} {{ $selectedRoom->room_name }} ({{ $selectedRoom->room_code }})
          </h3>
          <div class="text-xs text-gray-500 mt-0.5">
            {{ __('المشرف المسؤول:') }} {{ $selectedRoom->supervisor_in_charge ?: __('مساعد التربية للجناح') }} | {{ __('الأسرّة المتاحة:') }} {{ max(0, $selectedRoom->bed_capacity - $selectedRoom->active_occupants_count) }} {{ __('سرير شاغر') }}
          </div>
        </div>

        <button
          wire:click="openAllocationModal({{ $selectedRoom->id }})"
          class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          <x-heroicon-o-plus class="w-4 h-4" />
          {{ __('إضافة نزيل للمرقد') }}
        </button>
      </div>

      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
          <tr>
            <th class="p-3">{{ __('رقم السرير') }}</th>
            <th class="p-3">{{ __('رقم الخزانة') }}</th>
            <th class="p-3">{{ __('لقب واسم التلميذ النزيل') }}</th>
            <th class="p-3">{{ __('رقم التعريف المدرسي') }}</th>
            <th class="p-3">{{ __('القسم') }}</th>
            <th class="p-3">{{ __('تاريخ الدخول') }}</th>
            <th class="p-3">{{ __('استلام الفراش') }}</th>
            <th class="p-3 text-center">{{ __('الإجراءات') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @forelse($selectedRoom->activeAllocations as $alloc)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
              <td class="p-3 font-bold text-primary-600">{{ $alloc->bed_number }}</td>
              <td class="p-3 font-mono">{{ $alloc->locker_number ?: '—' }}</td>
              <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $alloc->student?->full_name }}</td>
              <td class="p-3 font-mono">{{ $alloc->student?->matricule_number ?? $alloc->student?->registration_number ?? '—' }}</td>
              <td class="p-3">{{ $alloc->student?->group?->classroom?->name ?? '—' }}</td>
              <td class="p-3">{{ $alloc->check_in_date->format('Y-m-d') }}</td>
              <td class="p-3">
                @if($alloc->linen_received)
                  <span class="text-emerald-600 font-semibold">✓ {{ __('مستلم') }}</span>
                @else
                  <span class="text-rose-600 font-semibold">✗ {{ __('غير مستلم') }}</span>
                @endif
              </td>
              <td class="p-3 text-center">
                <button
                  wire:click="vacateBed({{ $alloc->id }})"
                  wire:confirm="{{ __('هل أنت متأكد من إخلاء السرير واستلام المفاتيح والأفرشة من التلميذ؟') }}"
                  title="{{ __('إخلاء السرير') }}"
                  class="px-2 py-1 text-xs font-semibold rounded bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400">
                  {{ __('إخلاء السرير') }}
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="p-6 text-center text-gray-500">{{ __('لا يوجد نزلاء مخصصون لهذا المرقد حالياً.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @endif

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 2: NIGHT SESSIONS & STUDY                                           --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'night_sessions')
  <div class="space-y-4">

    <div class="flex justify-between items-center">
      <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('سجل المداومة والمذاكرة الليلية وحراسة المراقد') }}</h3>
      <button
        wire:click="openNightSessionModal"
        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 shadow-sm">
        <x-heroicon-o-plus class="w-4 h-4" />
        {{ __('تسجيل محضر مداومة ليلية جديدة') }}
      </button>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
          <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            <tr>
              <th class="p-3">{{ __('تاريخ المداومة') }}</th>
              <th class="p-3">{{ __('طبيعة الحصة') }}</th>
              <th class="p-3">{{ __('المشرف المناوب') }}</th>
              <th class="p-3 text-center">{{ __('الحاضرون') }}</th>
              <th class="p-3 text-center">{{ __('الغيابات') }}</th>
              <th class="p-3 text-center">{{ __('العيادة') }}</th>
              <th class="p-3 text-center">{{ __('رخص الخروج') }}</th>
              <th class="p-3">{{ __('وقائع وأحداث المداومة') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($nightSessions as $session)
              <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                <td class="p-3 font-bold font-mono">{{ $session->session_date->format('Y-m-d') }}</td>
                <td class="p-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                    {{ $session->session_type->label() }}
                  </span>
                </td>
                <td class="p-3 font-medium">{{ $session->supervisor_name }}</td>
                <td class="p-3 text-center font-bold text-emerald-600">{{ $session->boarders_present_count }}</td>
                <td class="p-3 text-center font-bold text-rose-600">{{ $session->boarders_absent_count }}</td>
                <td class="p-3 text-center font-bold text-amber-600">{{ $session->sickbay_count }}</td>
                <td class="p-3 text-center font-bold text-sky-600">{{ $session->authorized_leave_count }}</td>
                <td class="p-3 max-w-xs truncate text-gray-600 dark:text-gray-400">
                  {{ $session->incident_report ?: __('سير عادي وهادئ للمذاكرة والمراقد') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="p-6 text-center text-gray-500">{{ __('لم يتم تسجيل أي محاضر مداومة ليلية بعد.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($nightSessions, 'links'))
        <div class="p-3 border-t border-gray-100 dark:border-gray-700">
          {{ $nightSessions->links() }}
        </div>
      @endif
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 3: EXIT PASSES                                                      --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'exit_passes')
  <div class="space-y-4">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div class="flex-1 max-w-sm">
        <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="{{ __('بحث في رخص الخروج (التلميذ، الولي، الوجهة)...') }}" class="{{ $selectClasses }}" />
      </div>

      <button
        wire:click="openExitPassModal"
        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 shadow-sm">
        <x-heroicon-o-plus class="w-4 h-4" />
        {{ __('إصدار رخصة خروج تلميذ داخلي') }}
      </button>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
          <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            <tr>
              <th class="p-3">{{ __('رقم الرخصة') }}</th>
              <th class="p-3">{{ __('التلميذ الداخلي') }}</th>
              <th class="p-3">{{ __('القسم') }}</th>
              <th class="p-3">{{ __('نوع الخروج') }}</th>
              <th class="p-3">{{ __('المغادرة') }}</th>
              <th class="p-3">{{ __('العودة المتوقعة') }}</th>
              <th class="p-3">{{ __('الولي أو المرافق') }}</th>
              <th class="p-3">{{ __('الوجهة') }}</th>
              <th class="p-3">{{ __('الحالة') }}</th>
              <th class="p-3 text-center">{{ __('الإجراءات والطباعة') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($exitPasses as $pass)
              <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                <td class="p-3 font-bold font-mono text-gray-900 dark:text-white">{{ $pass->pass_number }}</td>
                <td class="p-3 font-bold">{{ $pass->student?->full_name }}</td>
                <td class="p-3">{{ $pass->student?->group?->classroom?->name ?? '—' }}</td>
                <td class="p-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    {{ $pass->pass_type->label() }}
                  </span>
                </td>
                <td class="p-3 font-mono">{{ $pass->departure_datetime->format('Y-m-d H:i') }}</td>
                <td class="p-3 font-mono font-bold">{{ $pass->expected_return_datetime->format('Y-m-d H:i') }}</td>
                <td class="p-3">{{ $pass->escort_guardian_name }}</td>
                <td class="p-3">{{ $pass->destination }}</td>
                <td class="p-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                    @if($pass->status->value === 'returned') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                    @elseif($pass->status->value === 'overdue') bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300
                    @elseif($pass->status->value === 'departed') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 @endif">
                    {{ $pass->status->label() }}
                  </span>
                </td>
                <td class="p-3 text-center">
                  <div class="flex items-center justify-center gap-1">
                    @if($pass->status->value !== 'returned')
                      <button
                        wire:click="markPassReturned({{ $pass->id }})"
                        title="{{ __('تأكيد العودة والالتحاق') }}"
                        class="p-1 rounded bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <x-heroicon-o-check class="w-4 h-4" />
                      </button>
                    @endif

                    <a href="{{ route('print.boarding.exit-pass', ['locale' => app()->getLocale(), 'pass' => $pass->id]) }}"
                       target="_blank"
                       title="{{ __('طباعة رخصة الخروج') }}"
                       class="p-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                      <x-heroicon-o-printer class="w-4 h-4" />
                    </a>

                    <a href="{{ route('print.boarding.exit-pass.pdf', ['locale' => app()->getLocale(), 'pass' => $pass->id]) }}"
                       target="_blank"
                       title="{{ __('تحميل PDF') }}"
                       class="p-1 rounded bg-primary-50 text-primary-600 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-400">
                      <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="p-6 text-center text-gray-500">{{ __('لا توجد رخص خروج مسجلة مطابقة للبحث.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($exitPasses, 'links'))
        <div class="p-3 border-t border-gray-100 dark:border-gray-700">
          {{ $exitPasses->links() }}
        </div>
      @endif
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 4: SCHOOL TRANSPORT & BUS ROUTES                                    --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'transport')
  <div class="space-y-6">

    <div class="flex justify-between items-center">
      <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('شبكة وخطوط حافلات النقل المدرسي') }}</h3>
      <button
        wire:click="openBusRouteModal"
        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 shadow-sm">
        <x-heroicon-o-plus class="w-4 h-4" />
        {{ __('إضافة خط حافلة جديد') }}
      </button>
    </div>

    {{-- Bus Routes Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      @forelse($busRoutes as $route)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
          <div class="flex justify-between items-start">
            <span class="px-2 py-0.5 rounded text-xs font-bold font-mono bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
              {{ $route->route_code }}
            </span>
            <span class="text-xs text-gray-500 font-mono">{{ $route->bus_license_plate ?: '—' }}</span>
          </div>

          <div class="mt-2 font-bold text-sm text-gray-900 dark:text-white">{{ $route->route_name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ __('الجهة المشغلة:') }} {{ $route->provider_entity }}</div>

          <div class="mt-3 p-2 rounded bg-gray-50 dark:bg-gray-900 text-xs space-y-1">
            <div>{{ __('السائق:') }} <strong>{{ $route->driver_name }}</strong> ({{ $route->driver_phone ?: '—' }})</div>
            <div>{{ __('التوقيت:') }} 🌅 {{ $route->morning_departure_time }} | 🌇 {{ $route->evening_return_time }}</div>
            <div class="truncate">{{ __('المحطات:') }} {{ $route->stops_list }}</div>
          </div>

          <div class="mt-3 flex justify-between items-center pt-2 border-t border-gray-100 dark:border-gray-700 text-xs">
            <span class="font-bold text-indigo-600">{{ $route->subscriptions_count }} / {{ $route->passenger_capacity }} {{ __('راكب') }}</span>
            <button
              wire:click="openBusSubModal({{ $route->id }})"
              class="px-2.5 py-1 font-semibold rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300">
              {{ __('تسجيل تلميذ بالخط') }}
            </button>
          </div>
        </div>
      @empty
        <div class="col-span-full p-8 text-center text-gray-500 bg-white rounded-xl border border-gray-200 dark:border-white/10 dark:bg-gray-800">
          {{ __('لم يتم تسجيل أي خطوط نقل مدرسي بعد. أضف أول خط لربط القرى والأحياء بالمؤسسة.') }}
        </div>
      @endforelse
    </div>

    {{-- Bus Subscriptions Table --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="p-4 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('سجل بطاقات واشتراكات النقل المدرسي الممنوحة') }}</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
          <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            <tr>
              <th class="p-3">{{ __('رقم البطاقة') }}</th>
              <th class="p-3">{{ __('التلميذ المشترك') }}</th>
              <th class="p-3">{{ __('القسم') }}</th>
              <th class="p-3">{{ __('الخط المدرسي') }}</th>
              <th class="p-3">{{ __('محطة الركوب المعتمدة') }}</th>
              <th class="p-3">{{ __('تاريخ الإصدار') }}</th>
              <th class="p-3">{{ __('الحالة') }}</th>
              <th class="p-3 text-center">{{ __('بطاقة الركوب') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($busSubscriptions as $sub)
              <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                <td class="p-3 font-bold font-mono text-gray-900 dark:text-white">{{ $sub->card_number }}</td>
                <td class="p-3 font-bold">{{ $sub->student?->full_name }}</td>
                <td class="p-3">{{ $sub->student?->group?->classroom?->name ?? '—' }}</td>
                <td class="p-3 font-medium">{{ $sub->busRoute?->route_name }} ({{ $sub->busRoute?->route_code }})</td>
                <td class="p-3">{{ $sub->pickup_stop }}</td>
                <td class="p-3 font-mono">{{ $sub->issue_date->format('Y-m-d') }}</td>
                <td class="p-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    {{ __('سارية المفعول') }}
                  </span>
                </td>
                <td class="p-3 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <a href="{{ route('print.boarding.bus-card', ['locale' => app()->getLocale(), 'subscription' => $sub->id]) }}"
                       target="_blank"
                       title="{{ __('معاينة وطباعة البطاقة') }}"
                       class="p-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                      <x-heroicon-o-printer class="w-4 h-4" />
                    </a>
                    <a href="{{ route('print.boarding.bus-card.pdf', ['locale' => app()->getLocale(), 'subscription' => $sub->id]) }}"
                       target="_blank"
                       title="{{ __('تحميل البطاقة PDF') }}"
                       class="p-1 rounded bg-primary-50 text-primary-600 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-400">
                      <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="p-6 text-center text-gray-500">{{ __('لم يتم إصدار بطاقات اشتراك نقل بعد.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($busSubscriptions, 'links'))
        <div class="p-3 border-t border-gray-100 dark:border-gray-700">
          {{ $busSubscriptions->links() }}
        </div>
      @endif
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- MODALS                                                                  --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}

  {{-- Modal 1: Add Dormitory Room --}}
  @if($isRoomModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إضافة مرقد إيواء جديد') }}</h3>
        <button wire:click="closeRoomModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الجناح') }}</label>
            <select wire:model="newRoomWing" class="{{ $selectClasses }}">
              <option value="boys">{{ __('جناح الذكور (Garçons)') }}</option>
              <option value="girls">{{ __('جناح الإناث (Filles)') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الطابق') }}</label>
            <input type="number" min="0" max="5" wire:model="newRoomFloor" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رمز المرقد (تلقائي إن تُرِك فارغاً)') }}</label>
            <input type="text" wire:model="newRoomCode" placeholder="DORT-B-101" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم المرقد') }}</label>
            <input type="text" wire:model="newRoomName" placeholder="مرقد الشهيد مصطفى بن بولعيد" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('طاقة استيعاب الأسرّة') }}</label>
            <input type="number" min="1" max="50" wire:model="newRoomCapacity" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المشرف المسؤول عن الجناح') }}</label>
            <input type="text" wire:model="newRoomSupervisor" placeholder="مساعد التربية للجناح" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات وتجهيزات المرقد') }}</label>
          <textarea wire:model="newRoomNotes" rows="2" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeRoomModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="createRoom" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد إنشاء المرقد') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 2: Allocate Bed to Student --}}
  @if($isAllocationModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تخصيص سرير وتسجيل تلميذ بالمرقد الداخلي') }}</h3>
        <button wire:click="closeAllocationModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المرقد المخصص') }}</label>
          <select wire:model="allocationRoomId" class="{{ $selectClasses }}">
            <option value="">{{ __('— اختر المرقد —') }}</option>
            @foreach($rooms as $r)
              <option value="{{ $r->id }}">
                {{ $r->room_code }} — {{ $r->room_name }} ({{ $r->wing->shortLabel() }} - شاغر: {{ max(0, $r->bed_capacity - $r->active_occupants_count) }})
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('التلميذ النزيل') }}</label>
          <select wire:model="allocationStudentId" class="{{ $selectClasses }}">
            <option value="">{{ __('— اختر التلميذ —') }}</option>
            @foreach($studentsList as $st)
              <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->classroom?->name ?? 'بدون قسم' }}) - {{ $st->matricule_number ?: ($st->registration_number ?: 'بدون رقم') }}</option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم السرير') }}</label>
            <input type="text" wire:model="allocationBedNumber" placeholder="LIT-01" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم الخزانة الفردية') }}</label>
            <input type="text" wire:model="allocationLockerNumber" placeholder="CAS-01" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الدخول والالتحاق') }}</label>
          <input type="date" wire:model="allocationCheckInDate" class="{{ $selectClasses }}" />
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" id="allocLinen" wire:model="allocationLinenReceived" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
          <label for="allocLinen" class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('تم تسليم الفراش، الغطاء والوسادة للتلميذ') }}</label>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات') }}</label>
          <input type="text" wire:model="allocationNotes" class="{{ $selectClasses }}" />
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeAllocationModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="allocateBed" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد التخصيص') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 3: Record Night Session --}}
  @if($isNightSessionModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تسجيل محضر المداومة والمذاكرة الليلية') }}</h3>
        <button wire:click="closeNightSessionModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ المداومة') }}</label>
            <input type="date" wire:model="sessionDate" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('طبيعة الحصة') }}</label>
            <select wire:model="sessionType" class="{{ $selectClasses }}">
              <option value="evening_study">{{ __('مذاكرة مسائية محروسة (Étude du soir)') }}</option>
              <option value="night_supervision">{{ __('مداومة ليلية بالمراقد (Permanence de nuit)') }}</option>
              <option value="weekend_duty">{{ __('مداومة نهاية الأسبوع (Permanence de week-end)') }}</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم المشرف أو المساعد التربوي المناوب') }}</label>
          <input type="text" wire:model="sessionSupervisorName" class="{{ $selectClasses }}" />
        </div>

        <div class="grid grid-cols-4 gap-2">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الحاضرون') }}</label>
            <input type="number" min="0" wire:model="sessionPresentCount" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الغيابات') }}</label>
            <input type="number" min="0" wire:model="sessionAbsentCount" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('العيادة') }}</label>
            <input type="number" min="0" wire:model="sessionSickbayCount" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رخص خروج') }}</label>
            <input type="number" min="0" wire:model="sessionAuthorizedLeaveCount" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تقرير الوقائع والأحداث (انضباط، صحة، أعطال)') }}</label>
          <textarea wire:model="sessionIncidentReport" rows="3" placeholder="{{ __('سير هادئ وعادي، أو تسجيل أي عارض...') }}" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeNightSessionModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="recordNightSession" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد حفظ المحضر') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 4: Issue Exit Pass --}}
  @if($isExitPassModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إصدار رخصة وبطاقة خروج تلميذ داخلي') }}</h3>
        <button wire:click="closeExitPassModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('التلميذ الداخلي') }}</label>
          <select wire:model.live="passStudentId" wire:change="onPassStudentSelected" class="{{ $selectClasses }}">
            <option value="">{{ __('— اختر التلميذ —') }}</option>
            @foreach($studentsList as $st)
              <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->classroom?->name ?? 'بدون قسم' }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('طبيعة الخروج') }}</label>
          <select wire:model="passType" class="{{ $selectClasses }}">
            <option value="weekend_leave">{{ __('خروج عطلة نهاية الأسبوع (Sortie de week-end)') }}</option>
            <option value="holiday_leave">{{ __('خروج العطلة المدرسية الرسمية (Sortie de vacances)') }}</option>
            <option value="exceptional_leave">{{ __('رخصة خروج استثنائية (Sortie exceptionnelle)') }}</option>
            <option value="medical_leave">{{ __('خروج صحي واستشفائي (Sortie médicale)') }}</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ وساعة المغادرة') }}</label>
            <input type="datetime-local" wire:model="passDepartureDatetime" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ وساعة العودة الإلزامية') }}</label>
            <input type="datetime-local" wire:model="passReturnDatetime" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم ولقب الولي / المرافق المفوّض') }}</label>
            <input type="text" wire:model="passEscortName" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الوجهة / مقر الإقامة') }}</label>
            <input type="text" wire:model="passDestination" placeholder="البلدية أو المدينة" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات وتوجيهات خاصة') }}</label>
          <input type="text" wire:model="passNotes" class="{{ $selectClasses }}" />
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeExitPassModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="issueExitPass" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد إصدار الرخصة') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 5: Add Bus Route --}}
  @if($isBusRouteModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إضافة خط حافلة نقل مدرسي') }}</h3>
        <button wire:click="closeBusRouteModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رمز الخط (تلقائي إن تُرِك فارغاً)') }}</label>
            <input type="text" wire:model="newRouteCode" placeholder="BUS-L01" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم المسار / الخط') }}</label>
            <input type="text" wire:model="newRouteName" placeholder="خط قرية الأمل — الثانوية" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم لوحة ترقيم الحافلة') }}</label>
            <input type="text" wire:model="newBusPlate" placeholder="01234-126-16" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الجهة المشغلة / الممولة') }}</label>
            <input type="text" wire:model="newRouteProvider" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم السائق') }}</label>
            <input type="text" wire:model="newDriverName" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('هاتف السائق') }}</label>
            <input type="text" wire:model="newDriverPhone" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('سعة الركاب') }}</label>
            <input type="number" min="5" max="80" wire:model="newRouteCapacity" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('انطلاق الصباح') }}</label>
            <input type="text" wire:model="newMorningDeparture" placeholder="07:15" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('عودة المساء') }}</label>
            <input type="text" wire:model="newEveningReturn" placeholder="16:45" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('محطات ونقاط التوقف (مفصولة بـ —)') }}</label>
          <textarea wire:model="newRouteStops" rows="2" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeBusRouteModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="createBusRoute" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد حفظ الخط') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 6: Subscribe Student to Bus --}}
  @if($isBusSubModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تسجيل تلميذ بخط النقل المدرسي') }}</h3>
        <button wire:click="closeBusSubModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('خط النقل المدرسي') }}</label>
          <select wire:model="subRouteId" class="{{ $selectClasses }}">
            <option value="">{{ __('— اختر خط الحافلة —') }}</option>
            @foreach($busRoutes as $br)
              <option value="{{ $br->id }}">{{ $br->route_code }} — {{ $br->route_name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('التلميذ المشترك') }}</label>
          <select wire:model="subStudentId" class="{{ $selectClasses }}">
            <option value="">{{ __('— اختر التلميذ —') }}</option>
            @foreach($studentsList as $st)
              <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->classroom?->name ?? 'بدون قسم' }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('محطة الركوب المعتمدة') }}</label>
          <input type="text" wire:model="subPickupStop" placeholder="محطة القرية الفلاحية" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات') }}</label>
          <input type="text" wire:model="subNotes" class="{{ $selectClasses }}" />
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeBusSubModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="subscribeStudentToBus" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد الاشتراك وإصدار البطاقة') }}
        </button>
      </div>
    </div>
  </div>
  @endif

</div>
</x-filament-panels::page>
