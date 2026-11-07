<x-filament-panels::page>
@php
  $viewData = $this->getViewData();
  $kpis = $viewData['kpis'];
  $lines = $viewData['lines'];
  $commitments = $viewData['commitments'];
  $mandates = $viewData['mandates'];
  $expenditureLines = $viewData['expenditureLines'];
  $eligibleCommitments = $viewData['eligibleCommitments'];

  $selectClasses = 'block w-full rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white dark:focus:border-primary-400';
@endphp

<div class="space-y-6">

  {{-- Top Navigation Tabs --}}
  <div class="border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex gap-6" aria-label="Tabs">
      <button
        wire:click="$set('activeTab', 'lines')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'lines' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-table-cells class="w-5 h-5" />
        {{ __('خريطة الميزانية والاعتمادات') }}
      </button>

      <button
        wire:click="$set('activeTab', 'commitments')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'commitments' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-document-text class="w-5 h-5" />
        {{ __('سندات الطلب وسجل الالتزامات') }}
      </button>

      <button
        wire:click="$set('activeTab', 'mandates')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'mandates' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-credit-card class="w-5 h-5" />
        {{ __('حوالات الدفع وتصفية النفقات') }}
      </button>
    </nav>
  </div>

  {{-- Live KPI Banner --}}
  <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-primary-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('الاعتمادات المخصصة') }}</div>
      <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($kpis['totalAllocated'], 2) }} دج</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('نفقات السنة المالية') }} {{ $fiscalYear }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-amber-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('مجموع الالتزامات') }}</div>
      <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($kpis['totalCommitted'], 2) }} دج</div>
      <div class="text-xs text-gray-500 mt-1">{{ $kpis['commitmentsCount'] }} {{ __('سند طلب ملتزم به') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-emerald-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('المصفى والمدفوع') }}</div>
      <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($kpis['totalLiquidated'], 2) }} دج</div>
      <div class="text-xs text-gray-500 mt-1">{{ $kpis['mandatesCount'] }} {{ __('حوالة دفع منجزة') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-sky-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('الرصيد المتاح المتبقي') }}</div>
      <div class="text-xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ number_format($kpis['availableBalance'], 2) }} دج</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('اعتماد غير ملتزم به') }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-indigo-500">
      <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('نسبة الاستهلاك') }}</div>
      <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $kpis['utilizationRate'] }}%</div>
      <div class="text-xs text-gray-500 mt-1">{{ __('من مجمل النفقات') }}</div>
    </div>
  </div>

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 1: BUDGET LINES & GRID                                              --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'lines')
  <div class="space-y-6">

    {{-- Filter Bar & Controls --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1">
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('السنة المالية') }}</label>
            <input type="number" wire:model.live="fiscalYear" class="{{ $selectClasses }}" min="2020" max="2035" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('نوع البند') }}</label>
            <select wire:model.live="budgetTypeFilter" class="{{ $selectClasses }}">
              <option value="all">{{ __('الكل (نفقات وإيرادات)') }}</option>
              <option value="expenditure">{{ __('النفقات فقط (Dépenses)') }}</option>
              <option value="revenue">{{ __('الإيرادات فقط (Recettes)') }}</option>
            </select>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            wire:click="seedStandardGrid"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors">
            <x-heroicon-o-arrow-path class="w-4 h-4 text-gray-500" />
            {{ __('توليد الأبواب النموذجية (91-311)') }}
          </button>

          <a
            href="{{ route('print.budget.ledger', ['locale' => app()->getLocale(), 'fiscalYear' => $fiscalYear]) }}"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
            <x-heroicon-o-printer class="w-4 h-4 text-gray-500" />
            {{ __('كشف الميزانية') }}
          </a>

          <a
            href="{{ route('print.budget.ledger.pdf', ['locale' => app()->getLocale(), 'fiscalYear' => $fiscalYear]) }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-primary-600" />
            PDF
          </a>

          <button
            type="button"
            wire:click="openLineModal"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition-colors shadow-sm">
            <x-heroicon-o-plus class="w-4 h-4" />
            {{ __('إضافة بند ميزانية') }}
          </button>
        </div>
      </div>
    </div>

    {{-- Budget Lines Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="p-3">#</th>
            <th class="p-3">{{ __('النوع') }}</th>
            <th class="p-3">{{ __('الباب المالي') }}</th>
            <th class="p-3">{{ __('المادة والتعيين') }}</th>
            <th class="p-3 text-center">{{ __('الاعتماد المخصص (دج)') }}</th>
            <th class="p-3 text-center">{{ __('الملتزم به (دج)') }}</th>
            <th class="p-3 text-center">{{ __('المصفى (دج)') }}</th>
            <th class="p-3 text-center">{{ __('الرصيد المتاح (دج)') }}</th>
            <th class="p-3 text-center">{{ __('الاستهلاك') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
          @forelse($lines as $index => $line)
          <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <td class="p-3 font-medium text-gray-500">{{ $index + 1 }}</td>
            <td class="p-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $line->budget_type === \App\Enums\BudgetType::Expenditure ? 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                {{ $line->budget_type->label() }}
              </span>
            </td>
            <td class="p-3 font-semibold text-gray-900 dark:text-white">{{ $line->chapter }}</td>
            <td class="p-3 text-gray-700 dark:text-gray-300">{{ $line->article }}</td>
            <td class="p-3 text-center font-bold text-gray-900 dark:text-white">{{ number_format($line->allocated_amount, 2) }}</td>
            <td class="p-3 text-center font-semibold text-amber-600 dark:text-amber-400">{{ number_format($line->committed_amount, 2) }}</td>
            <td class="p-3 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($line->liquidated_amount, 2) }}</td>
            <td class="p-3 text-center font-bold text-sky-600 dark:text-sky-400">{{ number_format($line->availableBalance(), 2) }}</td>
            <td class="p-3 text-center">
              <div class="flex items-center justify-center gap-1.5">
                <div class="w-16 bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 overflow-hidden">
                  <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ min(100, $line->utilizationRate()) }}%"></div>
                </div>
                <span class="font-medium text-[11px]">{{ $line->utilizationRate() }}%</span>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="p-8 text-center text-gray-500 dark:text-gray-400">
              <x-heroicon-o-banknotes class="w-10 h-10 mx-auto text-gray-400 mb-2" />
              <div>{{ __('لا توجد بنود ميزانية مسجلة لهذه السنة المالية.') }}</div>
              <div class="text-xs text-gray-400 mt-1">{{ __('يمكنك النقر على "توليد الأبواب النموذجية" لإدراج أبواب ونفقات المرسوم 91-311 تلقائياً.') }}</div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 2: PURCHASE ORDERS & COMMITMENTS                                    --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'commitments')
  <div class="space-y-6">

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h3 class="font-bold text-sm text-gray-900 dark:text-white">{{ __('سجل الالتزامات وسندات الطلب الرسمية (Bon de Commande)') }}</h3>
          <p class="text-xs text-gray-500 mt-0.5">{{ __('إدارة الارتباط بالنفقة مع الموردين والمتعاملين وتتبع الاستهلاك المالي') }}</p>
        </div>

        <button
          type="button"
          wire:click="openCommitmentModal"
          class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition-colors shadow-sm">
          <x-heroicon-o-plus class="w-4 h-4" />
          {{ __('إصدار سند طلب جديد') }}
        </button>
      </div>
    </div>

    {{-- Commitments Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="p-3">{{ __('رقم السند') }}</th>
            <th class="p-3">{{ __('التاريخ') }}</th>
            <th class="p-3">{{ __('رقم الالتزام') }}</th>
            <th class="p-3">{{ __('الباب والمادة') }}</th>
            <th class="p-3">{{ __('المورد / المتعامل') }}</th>
            <th class="p-3">{{ __('الموضوع') }}</th>
            <th class="p-3 text-center">{{ __('المبلغ (دج)') }}</th>
            <th class="p-3 text-center">{{ __('الحالة') }}</th>
            <th class="p-3 text-center">{{ __('إجراءات') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
          @forelse($commitments as $c)
          <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <td class="p-3 font-bold text-primary-600 dark:text-primary-400">{{ $c->order_reference }}</td>
            <td class="p-3 text-gray-600 dark:text-gray-400">{{ $c->order_date?->format('Y-m-d') }}</td>
            <td class="p-3 font-mono text-[11px] text-gray-700 dark:text-gray-300">{{ $c->commitment_number }}</td>
            <td class="p-3 text-gray-700 dark:text-gray-300">
              <div class="font-medium">{{ $c->budgetLine?->chapter }}</div>
              <div class="text-[11px] text-gray-500">{{ $c->budgetLine?->article }}</div>
            </td>
            <td class="p-3 font-semibold text-gray-900 dark:text-white">{{ $c->supplier_name }}</td>
            <td class="p-3 text-gray-700 dark:text-gray-300">{{ $c->subject }}</td>
            <td class="p-3 text-center font-bold text-gray-900 dark:text-white">{{ number_format($c->estimated_amount, 2) }}</td>
            <td class="p-3 text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $c->isLiquidated() ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                {{ $c->status?->label() }}
              </span>
            </td>
            <td class="p-3 text-center">
              <div class="flex items-center justify-center gap-1.5">
                <a
                  href="{{ route('print.budget.purchase-order', ['locale' => app()->getLocale(), 'commitment' => $c->id]) }}"
                  target="_blank"
                  title="{{ __('طباعة سند الطلب') }}"
                  class="p-1.5 rounded text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                  <x-heroicon-o-printer class="w-4 h-4" />
                </a>
                <a
                  href="{{ route('print.budget.purchase-order.pdf', ['locale' => app()->getLocale(), 'commitment' => $c->id]) }}"
                  title="PDF"
                  class="p-1.5 rounded text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/40">
                  <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                </a>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="p-8 text-center text-gray-500 dark:text-gray-400">
              <x-heroicon-o-document-text class="w-10 h-10 mx-auto text-gray-400 mb-2" />
              <div>{{ __('لا توجد سندات طلب أو التزامات مسجلة لهذه السنة المالية.') }}</div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 3: PAYMENT MANDATES & LIQUIDATIONS                                  --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'mandates')
  <div class="space-y-6">

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h3 class="font-bold text-sm text-gray-900 dark:text-white">{{ __('حوالات الدفع وتصفية النفقات (Mandat de Paiement)') }}</h3>
          <p class="text-xs text-gray-500 mt-0.5">{{ __('أداء النفقات المصفاة وتوثيق الفواتير وأوامر الصرف طبقا للمرسوم 91-311') }}</p>
        </div>

        <button
          type="button"
          wire:click="openMandateModal"
          class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition-colors shadow-sm">
          <x-heroicon-o-plus class="w-4 h-4" />
          {{ __('إصدار حوالة دفع جديدة') }}
        </button>
      </div>
    </div>

    {{-- Mandates Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
      <table class="w-full text-right text-xs">
        <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="p-3">{{ __('رقم الحوالة') }}</th>
            <th class="p-3">{{ __('تاريخ الحوالة') }}</th>
            <th class="p-3">{{ __('سند الطلب') }}</th>
            <th class="p-3">{{ __('المستفيد') }}</th>
            <th class="p-3">{{ __('رقم الفاتورة') }}</th>
            <th class="p-3 text-center">{{ __('المبلغ المصفى (دج)') }}</th>
            <th class="p-3">{{ __('طريقة الدفع') }}</th>
            <th class="p-3 text-center">{{ __('الحالة') }}</th>
            <th class="p-3 text-center">{{ __('إجراءات') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
          @forelse($mandates as $m)
          <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <td class="p-3 font-bold text-emerald-600 dark:text-emerald-400">{{ $m->mandate_number }}</td>
            <td class="p-3 text-gray-600 dark:text-gray-400">{{ $m->mandate_date?->format('Y-m-d') }}</td>
            <td class="p-3 font-medium text-gray-700 dark:text-gray-300">{{ $m->commitment?->order_reference }}</td>
            <td class="p-3 font-semibold text-gray-900 dark:text-white">{{ $m->commitment?->supplier_name }}</td>
            <td class="p-3 text-gray-700 dark:text-gray-300">
              <span>{{ $m->invoice_number }}</span>
              <span class="text-[11px] text-gray-500">({{ $m->invoice_date?->format('Y-m-d') }})</span>
            </td>
            <td class="p-3 text-center font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($m->liquidated_amount, 2) }}</td>
            <td class="p-3 text-gray-700 dark:text-gray-300">
              <div>{{ $m->payment_method?->label() }}</div>
              @if($m->payment_reference)
                <div class="text-[11px] text-gray-500 font-mono">{{ $m->payment_reference }}</div>
              @endif
            </td>
            <td class="p-3 text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                {{ $m->status?->label() }}
              </span>
            </td>
            <td class="p-3 text-center">
              <div class="flex items-center justify-center gap-1.5">
                <a
                  href="{{ route('print.budget.mandate', ['locale' => app()->getLocale(), 'mandate' => $m->id]) }}"
                  target="_blank"
                  title="{{ __('طباعة حوالة الدفع') }}"
                  class="p-1.5 rounded text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                  <x-heroicon-o-printer class="w-4 h-4" />
                </a>
                <a
                  href="{{ route('print.budget.mandate.pdf', ['locale' => app()->getLocale(), 'mandate' => $m->id]) }}"
                  title="PDF"
                  class="p-1.5 rounded text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40">
                  <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                </a>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="p-8 text-center text-gray-500 dark:text-gray-400">
              <x-heroicon-o-credit-card class="w-10 h-10 mx-auto text-gray-400 mb-2" />
              <div>{{ __('لا توجد حوالات دفع أو تصفية نفقات مسجلة لهذه السنة المالية.') }}</div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- MODALS                                                                   --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}

  {{-- Modal 1: Add Budget Line --}}
  @if($isLineModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إضافة بند ميزانية واعتماد مالي') }}</h3>
        <button wire:click="closeLineModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع البند') }}</label>
          <select wire:model="lineBudgetType" class="{{ $selectClasses }}">
            <option value="expenditure">{{ __('نفقات (Dépenses)') }}</option>
            <option value="revenue">{{ __('إيرادات (Recettes)') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الباب المالي (Chapitre)') }}</label>
          <input type="text" wire:model="lineChapter" placeholder="{{ __('مثال: الباب 2: نفقات التسيير والوسائل البيداغوجية') }}" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المادة والتعيين (Article)') }}</label>
          <input type="text" wire:model="lineArticle" placeholder="{{ __('مثال: المادة 1: مستلزمات التدريس وأدوات المخابر') }}" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الاعتماد المالي المخصص (دج)') }}</label>
          <input type="number" step="0.01" wire:model="lineAllocatedAmount" placeholder="0.00" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات') }}</label>
          <textarea wire:model="lineNotes" rows="2" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeLineModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="saveLine" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('حفظ البند') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 2: Add Purchase Order / Commitment --}}
  @if($isCommitmentModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إصدار سند طلب والتزام مالي') }}</h3>
        <button wire:click="closeCommitmentModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('بند الميزانية والاعتماد المفتوح') }}</label>
          <select wire:model="selectedBudgetLineId" class="{{ $selectClasses }}">
            <option value="">{{ __('اختر الباب والمادة المخصصة...') }}</option>
            @foreach($expenditureLines as $line)
              <option value="{{ $line->id }}">
                {{ $line->chapter }} - {{ $line->article }} ({{ __('المتاح') }}: {{ number_format($line->availableBalance(), 2) }} دج)
              </option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم المورد / المتعامل') }}</label>
            <input type="text" wire:model="supplierName" placeholder="{{ __('اسم المؤسسة أو المورد') }}" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم التعريف الجبائي (NIF)') }}</label>
            <input type="text" wire:model="supplierTaxNumber" placeholder="{{ __('اختياري') }}" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ سند الطلب') }}</label>
            <input type="date" wire:model="orderDate" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المبلغ التقديري (دج)') }}</label>
            <input type="number" step="0.01" wire:model="commitmentEstimatedAmount" placeholder="0.00" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('موضوع الطلبية والتجهيز') }}</label>
          <input type="text" wire:model="commitmentSubject" placeholder="{{ __('مثال: اقتناء مستلزمات مخبر الكيمياء والفيزياء') }}" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('شروط وملاحظات التسليم') }}</label>
          <textarea wire:model="commitmentNotes" rows="2" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeCommitmentModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="saveCommitment" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تسجيل الالتزام') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal 3: Add Payment Mandate --}}
  @if($isMandateModalOpen)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 text-right">
      <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('إصدار حوالة دفع وتصفية نفقة') }}</h3>
        <button wire:click="closeMandateModal" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('سند الطلب والالتزام المرتبط') }}</label>
          <select wire:model="selectedCommitmentId" class="{{ $selectClasses }}">
            <option value="">{{ __('اختر سند الطلب...') }}</option>
            @foreach($eligibleCommitments as $comm)
              <option value="{{ $comm->id }}">
                {{ $comm->order_reference }} - {{ $comm->supplier_name }} ({{ number_format($comm->estimated_amount, 2) }} دج)
              </option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الحوالة') }}</label>
            <input type="date" wire:model="mandateDate" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المبلغ المصفى للدفع (دج)') }}</label>
            <input type="number" step="0.01" wire:model="mandateLiquidatedAmount" placeholder="0.00" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم الفاتورة') }}</label>
            <input type="text" wire:model="invoiceNumber" placeholder="FAC-2026/01" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الفاتورة') }}</label>
            <input type="date" wire:model="invoiceDate" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('طريقة الدفع') }}</label>
            <select wire:model="paymentMethod" class="{{ $selectClasses }}">
              <option value="treasury_transfer">{{ __('تحويل خزينة عمومية / بريد') }}</option>
              <option value="cheque">{{ __('صك مالي / بنكي') }}</option>
              <option value="cash">{{ __('نقدا / صندوق النثريات') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مرجع الصك / السند') }}</label>
            <input type="text" wire:model="paymentReference" placeholder="{{ __('رقم الصك أو الحوالة') }}" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم وصل الاستلام (Bon de Réception)') }}</label>
          <input type="text" wire:model="receptionSlipNumber" placeholder="{{ __('اختياري') }}" class="{{ $selectClasses }}" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات وتأشيرات التصفية') }}</label>
          <textarea wire:model="mandateNotes" rows="2" class="{{ $selectClasses }}"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2 border-t pt-3 dark:border-gray-700">
        <button wire:click="closeMandateModal" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
          {{ __('إلغاء') }}
        </button>
        <button wire:click="saveMandate" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-500">
          {{ __('تأكيد التصفية والدفع') }}
        </button>
      </div>
    </div>
  </div>
  @endif

</div>
</x-filament-panels::page>

