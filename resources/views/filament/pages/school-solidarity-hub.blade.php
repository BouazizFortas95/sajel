<x-filament-panels::page>
@php
  $kpis = $this->kpis;
  $campaign = $this->selectedCampaign;
  $campaigns = $this->campaigns;
  $beneficiaries = $this->beneficiaries;
  $aidDistributions = $this->aidDistributions;
  $committeeMembers = $this->committeeMembers;
  $studentsList = $this->studentsList;

  $selectClasses = 'block w-full rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white dark:focus:border-primary-400';
@endphp

<div class="space-y-6">

  {{-- Top Navigation Tabs --}}
  <div class="border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex flex-wrap gap-4 sm:gap-6" aria-label="Tabs">
      <button
        wire:click="$set('activeTab', 'campaigns')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'campaigns' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-gift class="w-5 h-5" />
        {{ __('حملات منحة التمدرس (5000 دج)') }}
      </button>

      <button
        wire:click="$set('activeTab', 'beneficiaries')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'beneficiaries' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-users class="w-5 h-5" />
        {{ __('ملفات التلاميذ وقوائم الاستحقاق') }}
      </button>

      <button
        wire:click="$set('activeTab', 'committee')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'committee' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
        {{ __('لجنة التضامن ومحضر المداولة') }}
      </button>

      <button
        wire:click="$set('activeTab', 'aid')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'aid' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-book-open class="w-5 h-5" />
        {{ __('مجانية الكتاب والحقيبة المدرسية') }}
      </button>
    </nav>
  </div>

  {{-- Telemetry KPI Banner --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-primary-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('إجمالي الملفات المودعة') }}</div>
      <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $kpis['totalBeneficiaries'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ $kpis['submittedCount'] }} {{ __('قيد التدقيق الأولي') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-emerald-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('الملفات المقبولة رسميًا') }}</div>
      <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $kpis['approvedCount'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('مؤشر عليها بالمداولة والدائرة') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-sky-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('المبالغ المصروفة عبر البريد') }}</div>
      <div class="text-xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ number_format($kpis['totalDisbursed'], 2) }} دج</div>
      <div class="text-xs text-gray-500 mt-1">{{ $kpis['paidCount'] }} {{ __('حوالة تم تحويلها') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-amber-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('المساعدات العينية المسلمة') }}</div>
      <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $kpis['aidDistributionsCount'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ number_format($kpis['totalAidValue'], 2) }} دج {{ __('قيمة تقديرية') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-rose-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('اليتامى والمعوزون') }}</div>
      <div class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $kpis['orphansCount'] + $kpis['needyCount'] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ $kpis['specialNeedsCount'] }} {{ __('ذوي احتياجات خاصة') }}</div>
    </div>
  </div>

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 1: CAMPAIGNS                                                        --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'campaigns')
  <div class="space-y-6">

    {{-- Campaign Selector & Main Actions --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex-1 max-w-md">
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('الحملة التضامنية المحددة') }}</label>
          <select wire:model.live="selectedCampaignId" class="{{ $selectClasses }}">
            @foreach($campaigns as $camp)
              <option value="{{ $camp->id }}">{{ $camp->title }} ({{ $camp->academic_year }})</option>
            @endforeach
          </select>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          @if($campaign)
            {{-- Print Official Nominative List --}}
            <a href="{{ route('print.solidarity.nominative-list', ['locale' => app()->getLocale(), 'campaign' => $campaign->id]) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
              <x-heroicon-o-printer class="w-4 h-4 text-emerald-600" />
              {{ __('الكشف الإسمي (CCP)') }}
            </a>

            {{-- PDF Nominative List --}}
            <a href="{{ route('print.solidarity.nominative-list.pdf', ['locale' => app()->getLocale(), 'campaign' => $campaign->id]) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
              <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-emerald-600" />
              {{ __('PDF الكشف') }}
            </a>

            {{-- Print Committee Deliberation PV --}}
            <a href="{{ route('print.solidarity.committee-pv', ['locale' => app()->getLocale(), 'campaign' => $campaign->id]) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
              <x-heroicon-o-document-text class="w-4 h-4 text-primary-600" />
              {{ __('محضر المداولات (PV)') }}
            </a>

            {{-- PDF Committee PV --}}
            <a href="{{ route('print.solidarity.committee-pv.pdf', ['locale' => app()->getLocale(), 'campaign' => $campaign->id]) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
              <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-primary-600" />
              {{ __('PDF المحضر') }}
            </a>
          @endif

          <button
            wire:click="openCampaignModal"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 shadow-sm">
            <x-heroicon-o-plus class="w-4 h-4" />
            {{ __('إطلاق حملة سنوية جديدة') }}
          </button>
        </div>
      </div>

      @if($campaign)
      <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div>
          <span class="text-gray-500">{{ __('السنة الدراسية:') }}</span>
          <span class="font-bold text-gray-900 dark:text-white mr-1">{{ $campaign->academic_year }}</span>
        </div>
        <div>
          <span class="text-gray-500">{{ __('قيمة المنحة:') }}</span>
          <span class="font-bold text-primary-600 mr-1">{{ number_format($campaign->grant_amount, 2) }} دج</span>
        </div>
        <div>
          <span class="text-gray-500">{{ __('فترة الحملة:') }}</span>
          <span class="font-medium text-gray-700 dark:text-gray-300 mr-1">{{ $campaign->start_date->format('Y-m-d') }} إلى {{ $campaign->end_date->format('Y-m-d') }}</span>
        </div>
        <div>
          <span class="text-gray-500">{{ __('حالة الحملة:') }}</span>
          <span class="px-2 py-0.5 rounded-full font-bold text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
            {{ $campaign->status->label() }}
          </span>
        </div>
      </div>
      @endif
    </div>

    {{-- Campaigns History Table --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('سجل حملات منحة التمدرس والتضامن المدرسي') }}</h3>
      </div>
      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
          <tr>
            <th class="p-3">{{ __('السنة المالية') }}</th>
            <th class="p-3">{{ __('السنة الدراسية') }}</th>
            <th class="p-3">{{ __('عنوان الحملة') }}</th>
            <th class="p-3">{{ __('المنحة الفردية') }}</th>
            <th class="p-3">{{ __('المستفيدون المقبولون') }}</th>
            <th class="p-3">{{ __('إجمالي المصروف') }}</th>
            <th class="p-3">{{ __('الحالة') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @forelse($campaigns as $camp)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
              <td class="p-3 font-bold">{{ $camp->campaign_year }}</td>
              <td class="p-3">{{ $camp->academic_year }}</td>
              <td class="p-3 font-medium">{{ $camp->title }}</td>
              <td class="p-3 font-bold text-primary-600">{{ number_format($camp->grant_amount, 2) }} دج</td>
              <td class="p-3">{{ $camp->approved_beneficiaries_count }} {{ __('تلميذ') }}</td>
              <td class="p-3 font-bold text-emerald-600">{{ number_format($camp->total_disbursed_amount, 2) }} دج</td>
              <td class="p-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                  {{ $camp->status->label() }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-6 text-center text-gray-500">{{ __('لا توجد حملات مسجلة بعد. ابدأ بإطلاق أول حملة.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 2: BENEFICIARIES & DOSSIERS                                         --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'beneficiaries')
  <div class="space-y-4">

    {{-- Filter Bar --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('بحث سريع (التلميذ، الولي، رقم الملف، ب.ت.و)') }}</label>
          <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="{{ __('اكتب للبحث...') }}" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('فئة الاستحقاق') }}</label>
          <select wire:model.live="categoryFilter" class="{{ $selectClasses }}">
            <option value="all">{{ __('جميع الفئات') }}</option>
            <option value="orphan">{{ __('اليتامى') }}</option>
            <option value="needy_no_income">{{ __('معوزون (بدون دخل)') }}</option>
            <option value="low_income">{{ __('دخل ضعيف (≤ SNMG)') }}</option>
            <option value="special_needs">{{ __('ذوو الاحتياجات الخاصة') }}</option>
            <option value="national_tragedy">{{ __('ضحايا المأساة وحالات خاصة') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('حالة الملف') }}</label>
          <select wire:model.live="statusFilter" class="{{ $selectClasses }}">
            <option value="all">{{ __('جميع الحالات') }}</option>
            <option value="submitted">{{ __('ملف مودع') }}</option>
            <option value="verified_by_school">{{ __('مدقق بالمؤسسة') }}</option>
            <option value="deliberated_approved">{{ __('مقبول بمداولة اللجنة') }}</option>
            <option value="approved_by_daira">{{ __('مصادق عليه من الدائرة') }}</option>
            <option value="paid">{{ __('تم الصرف والتحويل') }}</option>
            <option value="rejected">{{ __('ملف مرفوض') }}</option>
          </select>
        </div>
      </div>

      <div class="mt-3 flex justify-end">
        <button
          wire:click="openBeneficiaryModal"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 shadow-sm">
          <x-heroicon-o-plus class="w-4 h-4" />
          {{ __('إيداع وتسجيل ملف تلميذ مستفيد') }}
        </button>
      </div>
    </div>

    {{-- Beneficiaries Table --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
          <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            <tr>
              <th class="p-3">{{ __('رقم الملف') }}</th>
              <th class="p-3">{{ __('التلميذ والقسم') }}</th>
              <th class="p-3">{{ __('رقم التعريف المدرسي') }}</th>
              <th class="p-3">{{ __('فئة الاستحقاق') }}</th>
              <th class="p-3">{{ __('الولي ورقم ب.ت.و') }}</th>
              <th class="p-3">{{ __('الدخل الشهري') }}</th>
              <th class="p-3">{{ __('الحساب البريدي (CCP)') }}</th>
              <th class="p-3">{{ __('الحالة') }}</th>
              <th class="p-3 text-center">{{ __('الإجراءات والمصادقة') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($beneficiaries as $ben)
              <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $ben->file_number }}</td>
                <td class="p-3">
                  <div class="font-bold text-gray-900 dark:text-white">{{ $ben->student?->full_name }}</div>
                  <div class="text-gray-500">{{ $ben->student?->group?->classroom?->name ?? '—' }}</div>
                </td>
                <td class="p-3 font-mono">{{ $ben->student?->matricule_number ?? $ben->student?->registration_number ?? '—' }}</td>
                <td class="p-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    {{ $ben->eligibility_category->label() }}
                  </span>
                </td>
                <td class="p-3">
                  <div class="font-medium">{{ $ben->guardian_full_name }}</div>
                  <div class="text-gray-500 font-mono">{{ $ben->guardian_national_id }}</div>
                </td>
                <td class="p-3 font-bold">{{ number_format($ben->monthly_income, 2) }} دج</td>
                <td class="p-3 font-mono dir-ltr text-right">
                  {{ $ben->ccp_account_number ? $ben->ccp_account_number . ' / ' . ($ben->ccp_key ?? '00') : '—' }}
                </td>
                <td class="p-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                    @if($ben->status->value === 'paid') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                    @elseif($ben->status->value === 'rejected') bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300
                    @elseif($ben->status->value === 'deliberated_approved') bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300
                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                    {{ $ben->status->label() }}
                  </span>
                </td>
                <td class="p-3 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    @if($ben->status->value !== 'deliberated_approved' && $ben->status->value !== 'paid')
                      <button
                        wire:click="approveBeneficiary({{ $ben->id }})"
                        title="{{ __('قبول الملف') }}"
                        class="p-1 rounded bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <x-heroicon-o-check class="w-4 h-4" />
                      </button>
                    @endif

                    @if($ben->status->value !== 'paid')
                      <button
                        wire:click="openPaymentModal({{ $ben->id }})"
                        title="{{ __('تسجيل تحويل وصرف المنحة') }}"
                        class="p-1 rounded bg-sky-50 text-sky-600 hover:bg-sky-100 dark:bg-sky-900/30 dark:text-sky-400">
                        <x-heroicon-o-credit-card class="w-4 h-4" />
                      </button>

                      <button
                        wire:click="rejectBeneficiary({{ $ben->id }})"
                        title="{{ __('رفض الملف') }}"
                        class="p-1 rounded bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400">
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="p-6 text-center text-gray-500">{{ __('لا توجد ملفات مطابقة لشروط البحث والتصفية.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($beneficiaries, 'links'))
        <div class="p-3 border-t border-gray-100 dark:border-gray-700">
          {{ $beneficiaries->links() }}
        </div>
      @endif
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 3: SOLIDARITY COMMITTEE & DELIBERATION                              --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'committee')
  <div class="space-y-4">

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-900 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
      <div class="font-bold mb-1 flex items-center gap-1.5">
        <x-heroicon-o-information-circle class="w-4 h-4 text-blue-600" />
        {{ __('الإطار القانوني والتنظيمي للجنة التضامن المدرسي بالمؤسسة (المرسوم التنفيذي 21-61):') }}
      </div>
      <div>
        {{ __('تتشكل لجنة التضامن المدرسي بمقتضى المناشير الوزارية المشتركة برئاسة مدير المؤسسة، وبعضوية المسير المالي والمقتصد (مقرراً)، ناظر الدراسات ومستشار التربية، مستشار التوجيه المدرسي، ممثل هيئة التدريس، ورئيس جمعية أولياء التلاميذ، للبت في استحقاق المنحة الخاصة 5000 دج ومجانية التجهيزات.') }}
      </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تشكيلة أعضاء لجنة التضامن المدرسي بالمؤسسة') }}</h3>
        <div class="flex items-center gap-2">
          @if($campaign)
            <a href="{{ route('print.solidarity.committee-pv', ['locale' => app()->getLocale(), 'campaign' => $campaign->id]) }}"
               target="_blank"
               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
              <x-heroicon-o-printer class="w-4 h-4" />
              {{ __('طباعة محضر المداولة الرسمي (PV)') }}
            </a>
          @endif
          <button
            wire:click="openCommitteeModal"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <x-heroicon-o-plus class="w-4 h-4" />
            {{ __('إضافة عضو') }}
          </button>
        </div>
      </div>

      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
          <tr>
            <th class="p-3">{{ __('الاسم واللقب') }}</th>
            <th class="p-3">{{ __('الصفة في اللجنة') }}</th>
            <th class="p-3">{{ __('الوظيفة بالمؤسسة') }}</th>
            <th class="p-3">{{ __('حضور المداولة') }}</th>
            <th class="p-3">{{ __('الملاحظات والتأشيرة') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @forelse($committeeMembers as $member)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
              <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $member->member_name }}</td>
              <td class="p-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                  {{ $member->committee_role->label() }}
                </span>
              </td>
              <td class="p-3">{{ $member->institution_role_label }}</td>
              <td class="p-3">
                @if($member->attended_deliberation)
                  <span class="text-emerald-600 font-bold">✓ {{ __('حاضر ومصادق') }}</span>
                @else
                  <span class="text-gray-400">{{ __('غائب بعذر') }}</span>
                @endif
              </td>
              <td class="p-3 text-gray-500">{{ $member->signature_notes ?: '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="p-6 text-center text-gray-500">{{ __('لم يتم تسجيل أعضاء اللجنة لهذه الحملة بعد.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 4: IN-KIND AID & FREE TEXTBOOKS                                     --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'aid')
  <div class="space-y-4">

    <div class="flex justify-between items-center">
      <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('سجل تسليم المساعدات العينية ومجانية الكتاب والحقيبة المدرسية') }}</h3>
      <button
        wire:click="openAidModal"
        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 shadow-sm">
        <x-heroicon-o-plus class="w-4 h-4" />
        {{ __('تسجيل تسليم إعانة ومجانية مستلزمات') }}
      </button>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
          <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            <tr>
              <th class="p-3">{{ __('رقم السند') }}</th>
              <th class="p-3">{{ __('تاريخ التسليم') }}</th>
              <th class="p-3">{{ __('التلميذ المستفيد') }}</th>
              <th class="p-3">{{ __('القسم') }}</th>
              <th class="p-3">{{ __('نوع الإعانة') }}</th>
              <th class="p-3">{{ __('طبيعة المستلزمات والكتب') }}</th>
              <th class="p-3">{{ __('القيمة التقديرية') }}</th>
              <th class="p-3">{{ __('الجهة المانحة') }}</th>
              <th class="p-3 text-center">{{ __('سند ومحضر التسليم') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($aidDistributions as $aid)
              <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $aid->voucher_number }}</td>
                <td class="p-3 font-mono">{{ $aid->distribution_date->format('Y-m-d') }}</td>
                <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $aid->student?->full_name }}</td>
                <td class="p-3">{{ $aid->student?->group?->classroom?->name ?? '—' }}</td>
                <td class="p-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ $aid->aid_type->label() }}
                  </span>
                </td>
                <td class="p-3 max-w-xs truncate">{{ $aid->items_description }}</td>
                <td class="p-3 font-bold">{{ number_format($aid->estimated_value, 2) }} دج</td>
                <td class="p-3">{{ $aid->donor_entity }}</td>
                <td class="p-3 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <a href="{{ route('print.solidarity.aid-voucher', ['locale' => app()->getLocale(), 'distribution' => $aid->id]) }}"
                       target="_blank"
                       title="{{ __('معاينة وطباعة السند') }}"
                       class="p-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                      <x-heroicon-o-printer class="w-4 h-4" />
                    </a>
                    <a href="{{ route('print.solidarity.aid-voucher.pdf', ['locale' => app()->getLocale(), 'distribution' => $aid->id]) }}"
                       target="_blank"
                       title="{{ __('تحميل سند PDF') }}"
                       class="p-1 rounded bg-primary-50 text-primary-600 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-400">
                      <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="p-6 text-center text-gray-500">{{ __('لا توجد مساعدات عينية مسجلة لهذه الحملة بعد.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($aidDistributions, 'links'))
        <div class="p-3 border-t border-gray-100 dark:border-gray-700">
          {{ $aidDistributions->links() }}
        </div>
      @endif
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- MODALS                                                                  --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}

  {{-- Modal 1: Add Campaign --}}
  @if($isCampaignModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إطلاق حملة منحة التمدرس والتضامن المدرسي') }}</h3>
        <button wire:click="closeCampaignModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('السنة المالية') }}</label>
            <input type="number" wire:model="newCampaignYear" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('السنة الدراسية') }}</label>
            <input type="text" wire:model="newAcademicYear" placeholder="2026/2027" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('عنوان الحملة') }}</label>
          <input type="text" wire:model="newCampaignTitle" class="{{ $selectClasses }}" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الانطلاق') }}</label>
            <input type="date" wire:model="newStartDate" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الاختتام') }}</label>
            <input type="date" wire:model="newEndDate" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مبلغ المنحة الفردية (دج)') }}</label>
            <input type="number" step="100" wire:model="newGrantAmount" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الغلاف المالي المخصص (اختياري)') }}</label>
            <input type="number" step="1000" wire:model="newAllocatedBudget" placeholder="0.00" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات وتوجيهات تنظيمية') }}</label>
          <textarea wire:model="newCampaignNotes" rows="2" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeCampaignModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="createCampaign" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد إطلاق الحملة وتشكيل اللجنة') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 2: Add Beneficiary Dossier --}}
  @if($isBeneficiaryModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إيداع وتسجيل ملف تلميذ للاستفادة من المنحة الخاصة') }}</h3>
        <button wire:click="closeBeneficiaryModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اختر التلميذ المتمدرس') }}</label>
          <select wire:model.live="newStudentId" wire:change="onStudentSelected" class="{{ $selectClasses }}">
            <option value="">{{ __('— اختر تلميذاً من القائمة —') }}</option>
            @foreach($studentsList as $st)
              <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->classroom?->name ?? 'بدون قسم' }}) - {{ $st->matricule_number ?: ($st->registration_number ?: 'بدون رقم') }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('فئة الاستحقاق الاجتماعي (المرسوم 21-61)') }}</label>
          <select wire:model="newEligibilityCategory" class="{{ $selectClasses }}">
            <option value="orphan">{{ __('يتيم (فاقد الأب أو الوالدين معاً)') }}</option>
            <option value="needy_no_income">{{ __('معوز (أرباب الأسر بدون أي دخل)') }}</option>
            <option value="low_income">{{ __('دخل شهري ضعيف (أقل أو مساوٍ للأجر الأدنى SNMG)') }}</option>
            <option value="special_needs">{{ __('ذوو الاحتياجات الخاصة والإعاقة') }}</option>
            <option value="national_tragedy">{{ __('ضحايا المأساة الوطنية وحالات خاصة') }}</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('صفة الولي / الكفيل') }}</label>
            <select wire:model="newGuardianType" class="{{ $selectClasses }}">
              <option value="father">{{ __('الأب') }}</option>
              <option value="mother">{{ __('الأم (أرملة / حاضنة)') }}</option>
              <option value="legal_tutor">{{ __('كفيل شرعي بحكم قضائي') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم ولقب الولي / الكفيل') }}</label>
            <input type="text" wire:model="newGuardianFullName" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم بطاقة التعريف الوطنية للولي (CNI)') }}</label>
            <input type="text" wire:model="newGuardianNationalId" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مهنة الولي أو حالته الاجتماعية') }}</label>
            <input type="text" wire:model="newGuardianProfession" placeholder="عامل يومي / بطال / متقاعد" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الدخل الشهري (دج)') }}</label>
            <input type="number" step="100" wire:model="newMonthlyIncome" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم الحساب البريدي (CCP)') }}</label>
            <input type="text" wire:model="newCcpAccountNumber" placeholder="12345678" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المفتاح (Clé)') }}</label>
            <input type="text" wire:model="newCcpKey" placeholder="99" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات إدارية') }}</label>
          <textarea wire:model="newBeneficiaryNotes" rows="2" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeBeneficiaryModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="registerBeneficiary" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تسجيل الملف') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 3: Mark Beneficiary Paid --}}
  @if($isPaymentModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تسجيل صرف المنحة والتحويل البريدي (CCP)') }}</h3>
        <button wire:click="closePaymentModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مرجع الحوالة أو جدول الإرسال للبريد') }}</label>
          <input type="text" wire:model="paymentReference" class="{{ $selectClasses }}" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ التحويل والصرف') }}</label>
          <input type="date" wire:model="paymentDate" class="{{ $selectClasses }}" />
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closePaymentModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="confirmPayment" class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500">
          {{ __('تأكيد الصرف (5000 دج)') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 4: Record In-Kind Aid Distribution --}}
  @if($isAidModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('تسليم إعانة عينية ومجانية الكتاب والحقيبة المدرسية') }}</h3>
        <button wire:click="closeAidModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('التلميذ المستفيد') }}</label>
          <select wire:model="aidStudentId" class="{{ $selectClasses }}">
            <option value="">{{ __('— اختر التلميذ —') }}</option>
            @foreach($studentsList as $st)
              <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->group?->classroom?->name ?? 'بدون قسم' }})</option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع الإعانة العينية') }}</label>
            <select wire:model="aidType" class="{{ $selectClasses }}">
              <option value="free_textbooks">{{ __('مجانية طقم الكتب المدرسية') }}</option>
              <option value="school_bag">{{ __('الحقيبة المدرسية المجهزة') }}</option>
              <option value="stationery_supplies">{{ __('الأدوات والكراريس المدرسية') }}</option>
              <option value="clothing_aid">{{ __('كسوة الشتاء والتضامن') }}</option>
              <option value="other">{{ __('مساعدات عينية أخرى') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ التسليم') }}</label>
            <input type="date" wire:model="aidDistributionDate" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('وصف دقيق للمواد والكتب المسلمة') }}</label>
          <textarea wire:model="aidItemsDescription" rows="2" placeholder="طقم كتب السنة الرابعة متوسط كامل (12 كتاباً) + محفظة مدرسية" class="{{ $selectClasses }}"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('القيمة التقديرية للإعانة (دج)') }}</label>
            <input type="number" step="100" wire:model="aidEstimatedValue" placeholder="3500.00" class="{{ $selectClasses }}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الجهة المانحة / الممولة') }}</label>
            <input type="text" wire:model="aidDonorEntity" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات') }}</label>
          <input type="text" wire:model="aidNotes" class="{{ $selectClasses }}" />
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeAidModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="recordAidDistribution" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد التسليم واستخراج السند') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 5: Add Committee Member --}}
  @if($isCommitteeModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إضافة عضو إلى لجنة التضامن المدرسي') }}</h3>
        <button wire:click="closeCommitteeModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الاسم واللقب') }}</label>
          <input type="text" wire:model="committeeMemberName" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الصفة في اللجنة') }}</label>
          <select wire:model="committeeRole" class="{{ $selectClasses }}">
            <option value="president">{{ __('مدير المؤسسة (رئيس اللجنة)') }}</option>
            <option value="financial_reporter">{{ __('المقتصد / المسير المالي (المقرر)') }}</option>
            <option value="censor_member">{{ __('الناظر / مستشار التربية (عضو)') }}</option>
            <option value="guidance_member">{{ __('مستشار التوجيه المدرسي (عضو)') }}</option>
            <option value="teacher_representative">{{ __('ممثل هيئة التدريس (عضو)') }}</option>
            <option value="parent_association_representative">{{ __('ممثل جمعية أولياء التلاميذ (عضو)') }}</option>
            <option value="board_member">{{ __('ممثل مجلس الإدارة والتسيير') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المسمى الوظيفي بالمؤسسة') }}</label>
          <input type="text" wire:model="committeeRoleLabel" placeholder="أستاذ التعليم المتوسط / ممثل منتخب" class="{{ $selectClasses }}" />
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" id="commAttended" wire:model="committeeAttended" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
          <label for="commAttended" class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('حاضر في جلسة المداولة والمصادقة') }}</label>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeCommitteeModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="addCommitteeMember" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('حفظ وإضافة العضو') }}
        </button>
      </div>
    </div>
  </div>
  @endif

</div>
</x-filament-panels::page>
