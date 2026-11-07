<x-filament-panels::page>
@php
  $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
  $data = $this->getReportDataProperty();
  $classroom = $data['classroom'] ?? null;
  $kpis = $data['kpis'] ?? [];
  $deliberations = $data['studentDeliberations'] ?? collect();
  $honorDist = $data['honorDistribution'] ?? [];
  $classrooms = $this->getClassroomOptionsProperty();
  $generatedAt = $data['generatedAt'] ?? now();
  $currentSession = $this->getCurrentSessionProperty();
  $subjects = \App\Models\Subject::orderBy('name')->get();
  $levels = \App\Models\Level::orderBy('grade_number')->get();
  $teachers = \App\Models\User::where('role', 'teacher')->orderBy('name')->get();

  $selectClasses = 'block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white dark:focus:border-primary-400';
@endphp

<div class="space-y-6">

  {{-- Navigation Tabs Header --}}
  <div class="border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex gap-6" aria-label="Tabs">
      <button
        wire:click="$set('activeTab', 'class_council')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'class_council' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-users class="w-5 h-5" />
        {{ __('مجالس الأقسام ومحاضر المداولات') }}
      </button>

      <button
        wire:click="$set('activeTab', 'subject_coordination')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'subject_coordination' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-academic-cap class="w-5 h-5" />
        {{ __('التنسيق البيداغوجي لمدرسي المادة') }}
      </button>

      <button
        wire:click="$set('activeTab', 'pacing_audit')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'pacing_audit' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-chart-bar class="w-5 h-5" />
        {{ __('تدقيق وتدرج المناهج السنوي') }}
      </button>
    </nav>
  </div>

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 1: CLASS COUNCIL & STATUTORY DELIBERATIONS                          --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'class_council')
  <div class="space-y-6">

    {{-- Filters & Session Control Bar --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('اختر القسم') }}</label>
          <select wire:model.live="classroomId" class="{{ $selectClasses }}">
            @foreach($classrooms as $c)
              <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->level?->name }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('الفصل الدراسي') }}</label>
          <select wire:model.live="trimester" class="{{ $selectClasses }}">
            <option value="1">{{ __('الفصل الأول') }}</option>
            <option value="2">{{ __('الفصل الثاني') }}</option>
            <option value="3">{{ __('الفصل الثالث') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('السنة الدراسية') }}</label>
          <input type="text" wire:model.live="academicYear" class="{{ $selectClasses }}" />
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            wire:click="syncSessionDecisions"
            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg shadow-sm transition-colors">
            <x-heroicon-o-arrow-path class="w-4 h-4" />
            {{ __('حساب المداولات وتوليد القرارات') }}
          </button>

          @if($currentSession)
            <button
              type="button"
              wire:click="certifyCurrentSession"
              class="inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors"
              title="{{ __('المصادقة الرسمية على المحضر') }}">
              <x-heroicon-o-check-badge class="w-4 h-4" />
              {{ __('المصادقة') }}
            </button>
          @endif
        </div>
      </div>
    </div>

    {{-- Official Decree 158 PV Card --}}
    @if($currentSession)
    <div class="rounded-xl border border-primary-200 bg-primary-50/50 p-4 dark:border-primary-900/40 dark:bg-primary-950/20">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ __('محضر مداولات مجلس القسم الرسمي (القرار 158)') }}</span>
            <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $currentSession->status->value === 'certified' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
              {{ $currentSession->status->label() }}
            </span>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
            {{ __('القسم:') }} <strong>{{ $classroom?->name }}</strong> · {{ __('تاريخ الانعقاد:') }} {{ $currentSession->session_date->format('Y/m/d') }} · {{ __('معدل القسم:') }} <strong>{{ number_format($currentSession->class_average ?? 0, 2) }}/20</strong> · {{ __('نسبة النجاح:') }} <strong>{{ number_format($currentSession->success_rate ?? 0, 1) }}%</strong>
          </p>
        </div>

        <div class="flex gap-2">
          @if($this->sessionPvUrl)
            <a href="{{ $this->sessionPvUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-primary-700 bg-white border border-primary-300 rounded-lg shadow-sm hover:bg-primary-50 dark:bg-gray-800 dark:text-primary-400 dark:border-primary-800">
              <x-heroicon-o-printer class="w-4 h-4" />
              {{ __('طباعة المحضر (A4)') }}
            </a>
          @endif
          @if($this->sessionPvPdfUrl)
            <a href="{{ $this->sessionPvPdfUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-primary-600 rounded-lg shadow-sm hover:bg-primary-700">
              <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
              {{ __('تحميل PDF') }}
            </a>
          @endif
        </div>
      </div>
    </div>
    @endif

    {{-- KPI Cards Row (Preserves existing test assertions) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('القسم والمستوى') }}</div>
        <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $classroom?->name ?? '—' }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ trim(($classroom?->level?->name ?? '—').' · '.($classroom?->academic_year ?? '—')) }}</div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('عدد التلاميذ') }}</div>
        <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ (string) ($kpis['total_students'] ?? 0) }}</div>
        <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">{{ __('بانتظار رصد العلامات') }}: {{ $kpis['pending_students'] ?? 0 }}</div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('المعدل العام للقسم /20') }}</div>
        <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">
          {{ ($kpis['class_general_average'] ?? null) !== null ? number_format((float) $kpis['class_general_average'], 2) : '—' }}
        </div>
        <div class="text-xs text-gray-500 mt-1">{{ __('على أساس :count تلميذ مرصود', ['count' => $kpis['graded_students'] ?? 0]) }}</div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('نسبة النجاح %') }}</div>
        <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
          {{ number_format((float) ($kpis['pass_rate'] ?? 0), 1) }}%
        </div>
        <div class="text-xs text-gray-500 mt-1">{{ __('من التلاميذ المرصودة علاماتهم') }}</div>
      </div>
    </div>

    {{-- Registered Decisions Table (Decree 158) --}}
    @if($currentSession && $currentSession->decisions->isNotEmpty())
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="px-4 py-3 border-b border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-gray-700/50 flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('قرارات وتكريمات مجلس القسم الفردية') }} ({{ $currentSession->decisions->count() }} تلميذ)</h3>
        <span class="text-xs text-gray-500">{{ __('مرتبة تنازلياً حسب المعدل الفصلي') }}</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-xs text-right">
          <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-white/10">
            <tr>
              <th class="px-3 py-2 text-center w-12">{{ __('الرتبة') }}</th>
              <th class="px-4 py-2">{{ __('اسم ولقب التلميذ(ة)') }}</th>
              <th class="px-3 py-2 text-center">{{ __('المعدل الفصلي') }}</th>
              <th class="px-3 py-2 text-center">{{ __('التشريفات واللوحة الشرفية') }}</th>
              <th class="px-3 py-2 text-center">{{ __('العقوبات والتنبيهات') }}</th>
              @if($trimester === 3)
              <th class="px-3 py-2 text-center">{{ __('قرار نهاية السنة') }}</th>
              @endif
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach($currentSession->decisions as $dec)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
              <td class="px-3 py-2 text-center font-bold">{{ $dec->rank ?? '-' }}</td>
              <td class="px-4 py-2 font-bold text-gray-900 dark:text-white">
                {{ $dec->student?->last_name }} {{ $dec->student?->first_name }}
              </td>
              <td class="px-3 py-2 text-center font-bold text-sm">
                {{ $dec->trimester_average !== null ? number_format($dec->trimester_average, 2) : '—' }}
              </td>
              <td class="px-3 py-2 text-center">
                @if($dec->honors->value !== 'none')
                  <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    {{ $dec->honors->label() }}
                  </span>
                @else
                  <span class="text-gray-400">—</span>
                @endif
              </td>
              <td class="px-3 py-2 text-center">
                @if($dec->sanctions->value !== 'none')
                  <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                    {{ $dec->sanctions->label() }}
                  </span>
                @else
                  <span class="text-gray-400">—</span>
                @endif
              </td>
              @if($trimester === 3)
              <td class="px-3 py-2 text-center font-bold">
                {{ $dec->end_of_year_decision->label() }}
              </td>
              @endif
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    {{-- Existing Deliberation Matrix Display & Print Button (Preserves existing view expectations) --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 flex justify-between items-center">
      <div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('مداولات القسم') }} — {{ __('كشف النقاط الإجمالي') }}</h4>
        <p class="text-xs text-gray-500">{{ __('عرض تفصيلي لعلامات جميع المواد وحصيلة مداولات مجلس القسم الحالية.') }}</p>
      </div>
      <div class="flex gap-2">
        <a href="{{ $this->getClassroomPrintUrl() }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
          <x-heroicon-o-printer class="w-4 h-4" />
          {{ __('طباعة الكشف المفصل') }}
        </a>
      </div>
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 2: SUBJECT COORDINATION PROTOCOLS (القرار 157)                      --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'subject_coordination')
  <div class="space-y-6">

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex flex-wrap gap-4 items-center">
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('المادة التعليمية') }}</label>
            <select wire:model.live="coordinationSubjectId" class="{{ $selectClasses }}">
              @foreach($subjects as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('الفصل الدراسي') }}</label>
            <select wire:model.live="coordinationTrimester" class="{{ $selectClasses }}">
              <option value="1">{{ __('الفصل الأول') }}</option>
              <option value="2">{{ __('الفصل الثاني') }}</option>
              <option value="3">{{ __('الفصل الثالث') }}</option>
            </select>
          </div>
        </div>

        <button
          type="button"
          wire:click="openCoordinationModal"
          class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg shadow-sm transition-colors">
          <x-heroicon-o-plus-circle class="w-4 h-4" />
          {{ __('تسجيل جلسة تنسيق بيداغوجي جديدة') }}
        </button>
      </div>
    </div>

    {{-- Coordination Protocols List --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="px-4 py-3 border-b border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-gray-700/50">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('محاضر التنسيق البيداغوجي المسجلة') }}</h3>
      </div>

      <div class="divide-y divide-gray-200 dark:divide-white/10">
        @forelse($this->coordinationProtocols as $protocol)
        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-sm text-gray-900 dark:text-white">{{ $protocol->topic }}</span>
                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300">
                  {{ $protocol->subject?->name }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                {{ __('تاريخ الجلسة:') }} {{ $protocol->meeting_date->format('Y/m/d') }} · {{ __('المنسق:') }} {{ $protocol->coordinator?->name ?? 'غير محدد' }}
              </p>
            </div>

            <div class="flex gap-2">
              <a
                href="{{ route('print.subject-coordination.pv', ['locale' => app()->getLocale(), 'protocol' => $protocol]) }}"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <x-heroicon-o-printer class="w-3.5 h-3.5" />
                {{ __('طباعة المحضر') }}
              </a>
              <a
                href="{{ route('print.subject-coordination.pv.pdf', ['locale' => app()->getLocale(), 'protocol' => $protocol]) }}"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-white bg-primary-600 rounded hover:bg-primary-700">
                <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                PDF
              </a>
            </div>
          </div>

          @if($protocol->syllabus_pacing_notes || $protocol->recommendations)
          <div class="mt-3 text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/40 p-2.5 rounded border border-gray-200 dark:border-gray-700">
            @if($protocol->syllabus_pacing_notes)
              <div><strong>{{ __('وضعية التدرج:') }}</strong> {{ $protocol->syllabus_pacing_notes }}</div>
            @endif
            @if($protocol->recommendations)
              <div class="mt-1"><strong>{{ __('التوصيات:') }}</strong> {{ $protocol->recommendations }}</div>
            @endif
          </div>
          @endif
        </div>
        @empty
        <div class="p-8 text-center text-xs text-gray-500">
          {{ __('لا توجد محاضر تنسيق مسجلة لهذه المادة في هذا الفصل.') }}
        </div>
        @endforelse
      </div>
    </div>

    {{-- Modal for New Coordination Session --}}
    @if($isCoordinationModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4">
      <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-gray-200 pb-3 dark:border-gray-700">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('تسجيل جلسة تنسيق بيداغوجي جديدة') }}</h3>
          <button wire:click="closeCoordinationModal" class="text-gray-400 hover:text-gray-500">
            <x-heroicon-o-x-mark class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المادة') }}</label>
              <select wire:model="newSubjectId" class="{{ $selectClasses }}">
                @foreach($subjects as $s)
                  <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الجلسة') }}</label>
              <input type="date" wire:model="newMeetingDate" class="{{ $selectClasses }}" />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('موضوع التنسيق') }}</label>
            <input type="text" wire:model="newTopic" placeholder="{{ __('مثال: توحيد تدرج التعلمات وضبط الفروض المحروسة') }}" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الأستاذ منسق المادة (المسؤول)') }}</label>
            <select wire:model="newCoordinatorId" class="{{ $selectClasses }}">
              <option value="">{{ __('اختر الأستاذ المنسق') }}</option>
              @foreach($teachers as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تقييم تدرج المنهاج والبرامج') }}</label>
            <textarea wire:model="newPacingNotes" rows="2" class="{{ $selectClasses }}" placeholder="{{ __('مدى تقدم الأساتذة في التدرج السنوي...') }}"></textarea>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('توحيد الفروض والتقويمات') }}</label>
            <textarea wire:model="newUnifiedExamsNotes" rows="2" class="{{ $selectClasses }}" placeholder="{{ __('الاتفاق على مواعيد ومعايير الفروض...') }}"></textarea>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الصعوبات المشخصة وخطة المعالجة') }}</label>
            <textarea wire:model="newDifficulties" rows="2" class="{{ $selectClasses }}" placeholder="{{ __('النقائص المشتركة وحصص الاستدراك...') }}"></textarea>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('التوصيات المرفوعة للإدارة') }}</label>
            <textarea wire:model="newRecommendations" rows="2" class="{{ $selectClasses }}" placeholder="{{ __('التوصيات المقترحة...') }}"></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
          <button type="button" wire:click="closeCoordinationModal" class="px-4 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-200">
            {{ __('إلغاء') }}
          </button>
          <button type="button" wire:click="saveCoordinationProtocol" class="px-4 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
            {{ __('حفظ المحضر') }}
          </button>
        </div>
      </div>
    </div>
    @endif

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 3: CURRICULUM PACING & PROGRESS AUDIT                               --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'pacing_audit')
  <div class="space-y-6">

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex flex-wrap gap-4 items-center">
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('المستوى التعليمي') }}</label>
            <select wire:model.live="auditLevelId" class="{{ $selectClasses }}">
              <option value="">{{ __('جميع المستويات') }}</option>
              @foreach($levels as $lvl)
                <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('الفصل الدراسي') }}</label>
            <select wire:model.live="auditTrimester" class="{{ $selectClasses }}">
              <option value="1">{{ __('الفصل الأول') }}</option>
              <option value="2">{{ __('الفصل الثاني') }}</option>
              <option value="3">{{ __('الفصل الثالث') }}</option>
            </select>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            wire:click="runPacingAudit"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg shadow-sm transition-colors">
            <x-heroicon-o-bolt class="w-4 h-4" />
            {{ __('تشغيل التدقيق الآلي للتدرج') }}
          </button>

          <a
            href="{{ route('print.curriculum-pacing-audit', ['locale' => app()->getLocale(), 'year' => str_replace('/', '-', $academicYear), 'trimester' => $auditTrimester, 'level_id' => $auditLevelId]) }}"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
            <x-heroicon-o-printer class="w-4 h-4" />
            {{ __('طباعة كشف المعاينة (A4)') }}
          </a>
        </div>
      </div>
    </div>

    {{-- Audits Table --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="px-4 py-3 border-b border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-gray-700/50 flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('نتائج تدقيق ومتابعة نسب إنجاز المناهج') }} ({{ $this->pacingAudits->count() }} معاينة)</h3>
        <span class="text-xs text-gray-500">{{ __('مقارنة المنجز بدفاتر النصوص مع التدرج السنوي') }}</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-xs text-right">
          <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-white/10">
            <tr>
              <th class="px-4 py-2">{{ __('القسم') }}</th>
              <th class="px-4 py-2">{{ __('المادة') }}</th>
              <th class="px-3 py-2 text-center">{{ __('المقرر وزارياً') }}</th>
              <th class="px-3 py-2 text-center">{{ __('المنجز فعلياً') }}</th>
              <th class="px-3 py-2 text-center">{{ __('نسبة الإنجاز') }}</th>
              <th class="px-3 py-2 text-center">{{ __('وضعية التدرج') }}</th>
              <th class="px-4 py-2">{{ __('مخطط الاستدراك') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse($this->pacingAudits as $audit)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
              <td class="px-4 py-2 font-bold text-gray-900 dark:text-white">{{ $audit->classroom?->name }}</td>
              <td class="px-4 py-2 font-semibold">{{ $audit->subject?->name }}</td>
              <td class="px-3 py-2 text-center">{{ $audit->expected_resources_count }}</td>
              <td class="px-3 py-2 text-center font-bold">{{ $audit->completed_resources_count }}</td>
              <td class="px-3 py-2 text-center font-bold">
                {{ number_format($audit->execution_percentage, 1) }}%
              </td>
              <td class="px-3 py-2 text-center">
                @php
                  $badgeClass = match($audit->pacing_status->value) {
                    'ahead' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
                    'on_track' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                    'minor_delay' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                    'critical_delay' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                    default => 'bg-gray-100 text-gray-800',
                  };
                @endphp
                <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $badgeClass }}">
                  {{ $audit->pacing_status->label() }}
                </span>
              </td>
              <td class="px-4 py-2 text-gray-500">
                {{ $audit->compensatory_plan ?: ($audit->pacing_status->value === 'critical_delay' ? __('تخصيص حصص استدراك') : __('مطابق')) }}
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="p-8 text-center text-gray-500">
                {{ __('لا توجد معاينات مسجلة لهذا الفصل، اضغط على زر تشغيل التدقيق الآلي.') }}
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
</x-filament-panels::page>
