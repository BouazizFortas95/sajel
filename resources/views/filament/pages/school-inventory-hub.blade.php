<x-filament-panels::page>
@php
  $service = app(\App\Services\SchoolInventoryService::class);
  $kpis = $service->getInventoryKpis();
  $categories = \App\Enums\InventoryCategory::cases();
  $conditions = \App\Enums\InventoryCondition::cases();
  $users = \App\Models\User::orderBy('name')->get();
  $activeItems = \App\Models\InventoryItem::active()->orderBy('designation')->get();

  $selectClasses = 'block w-full rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white dark:focus:border-primary-400';
@endphp

<div class="space-y-6">

  {{-- Top Navigation Tabs --}}
  <div class="border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex gap-6" aria-label="Tabs">
      <button
        wire:click="$set('activeTab', 'ledger')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'ledger' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-book-open class="w-5 h-5" />
        {{ __('سجل الجرد الدائم العام والخاص') }}
      </button>

      <button
        wire:click="$set('activeTab', 'allocations')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'allocations' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-map-pin class="w-5 h-5" />
        {{ __('بطاقات التخصيص والعهدة للمواقع') }}
      </button>

      <button
        wire:click="$set('activeTab', 'reforms')"
        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'reforms' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
        <x-heroicon-o-archive-box-x-mark class="w-5 h-5" />
        {{ __('محاضر الإسقاط والإتلاف ولجان المعاينة') }}
      </button>
    </nav>
  </div>

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 1: INVENTORY LEDGER                                                 --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'ledger')
  <div class="space-y-6">

    {{-- Live KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-primary-500">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('مجموع القطع المقيدة') }}</div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $kpis['totalItems'] }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ __('قطع وتجهيزات نشطة') }}</div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-emerald-500">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('القيمة المالية التقديرية') }}</div>
        <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($kpis['totalValue'], 2) }} دج</div>
        <div class="text-xs text-gray-500 mt-1">{{ __('قيمة الجرد الإجمالية') }}</div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-sky-500">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('عتاد علمي ومخبري') }}</div>
        <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ $kpis['scientificCount'] }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ __('تجهيزات المخابر والورشات') }}</div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-amber-500">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('بحاجة لصيانة / معطل') }}</div>
        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $kpis['damagedCount'] }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ __('تتطلب تدخلاً أو إصلاحاً') }}</div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 border-t-4 border-t-rose-500">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('مسقط من الجرد') }}</div>
        <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $kpis['reformedCount'] }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ __('بمحاضر رسمية معتمدة') }}</div>
      </div>
    </div>

    {{-- Filter Bar & Actions --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 flex-1">
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('البحث') }}</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('اسم العتاد، رقم الجرد، القاعة...') }}" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('الفئة والتصنيف') }}</label>
            <select wire:model.live="categoryFilter" class="{{ $selectClasses }}">
              <option value="all">{{ __('جميع الفئات') }}</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('الحالة الفنية') }}</label>
            <select wire:model.live="conditionFilter" class="{{ $selectClasses }}">
              <option value="all">{{ __('جميع الحالات') }}</option>
              @foreach($conditions as $cond)
                <option value="{{ $cond->value }}">{{ $cond->label() }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('نوع الموقع') }}</label>
            <select wire:model.live="locationTypeFilter" class="{{ $selectClasses }}">
              <option value="all">{{ __('جميع الأماكن') }}</option>
              <option value="classroom">{{ __('قاعات التدريس') }}</option>
              <option value="lab">{{ __('المخابر العلمية') }}</option>
              <option value="office">{{ __('المكاتب الإدارية') }}</option>
              <option value="library">{{ __('المكتبة المدرسية') }}</option>
              <option value="canteen">{{ __('المطعم المدرسي') }}</option>
              <option value="store">{{ __('المخزن المركزي') }}</option>
            </select>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            wire:click="openItemModal"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg shadow-sm transition-colors whitespace-nowrap">
            <x-heroicon-o-plus-circle class="w-4 h-4" />
            {{ __('قيد تجهيز جديد في الجرد') }}
          </button>

          <a
            href="{{ route('print.inventory.general-ledger', ['locale' => app()->getLocale(), 'category' => $categoryFilter !== 'all' ? $categoryFilter : null, 'location_type' => $locationTypeFilter !== 'all' ? $locationTypeFilter : null]) }}"
            target="_blank"
            class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 whitespace-nowrap">
            <x-heroicon-o-printer class="w-4 h-4" />
            {{ __('طباعة السجل (A4)') }}
          </a>
        </div>
      </div>
    </div>

    {{-- Items Table --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="px-4 py-3 border-b border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-gray-700/50 flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('قائمة التجهيزات والأثاث المقيد بالسجل') }} ({{ $this->items->count() }} قطعة)</h3>
        <span class="text-xs text-gray-500">{{ __('مرتب حسب أحدث القيود') }}</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-xs text-right">
          <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-white/10">
            <tr>
              <th class="px-3 py-2 text-center">{{ __('رقم الجرد') }}</th>
              <th class="px-4 py-2">{{ __('تعيين العتاد') }}</th>
              <th class="px-3 py-2">{{ __('الفئة') }}</th>
              <th class="px-3 py-2 text-center">{{ __('تاريخ الاقتناء') }}</th>
              <th class="px-3 py-2 text-center">{{ __('العدد') }}</th>
              <th class="px-4 py-2">{{ __('الموقع والعهدة') }}</th>
              <th class="px-3 py-2 text-center">{{ __('القيمة (دج)') }}</th>
              <th class="px-3 py-2 text-center">{{ __('الحالة') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse($this->items as $item)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
              <td class="px-3 py-2 text-center font-mono font-bold text-primary-600 dark:text-primary-400">
                {{ $item->inventory_number }}
              </td>
              <td class="px-4 py-2">
                <div class="font-bold text-gray-900 dark:text-white">{{ $item->designation }}</div>
                @if($item->specifications)
                  <div class="text-gray-500 text-[11px] truncate max-w-xs">{{ $item->specifications }}</div>
                @endif
              </td>
              <td class="px-3 py-2">
                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                  {{ $item->category->label() }}
                </span>
              </td>
              <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-300">
                {{ $item->acquisition_date?->format('Y/m/d') ?? '-' }}
              </td>
              <td class="px-3 py-2 text-center font-bold">{{ $item->quantity }}</td>
              <td class="px-4 py-2">
                <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $item->location_name }}</div>
                <div class="text-gray-500 text-[11px]">{{ $item->custodian?->name ?? 'غير محدد' }}</div>
              </td>
              <td class="px-3 py-2 text-center font-semibold">
                {{ $item->acquisition_cost ? number_format($item->acquisition_cost, 2) : '-' }}
              </td>
              <td class="px-3 py-2 text-center">
                @php
                  $condColor = match($item->condition->value) {
                    'good' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                    'fair' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
                    'damaged' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                    'reformed' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                    'lost' => 'bg-red-200 text-red-900',
                    default => 'bg-gray-100 text-gray-800',
                  };
                @endphp
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $condColor }}">
                  {{ $item->condition->label() }}
                </span>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" class="p-8 text-center text-gray-500">
                {{ __('لا توجد تجهيزات مطابقة لشروط البحث الحالية.') }}
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Modal: Add New Inventory Item --}}
    @if($isItemModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4">
      <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-gray-200 pb-3 dark:border-gray-700">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('قيد تجهيز جديد في سجل الجرد الدائم') }}</h3>
          <button wire:click="closeItemModal" class="text-gray-400 hover:text-gray-500">
            <x-heroicon-o-x-mark class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الفئة والتصنيف') }}</label>
              <select wire:model="itemCategory" class="{{ $selectClasses }}">
                @foreach($categories as $cat)
                  <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الاقتناء') }}</label>
              <input type="date" wire:model="itemAcquisitionDate" class="{{ $selectClasses }}" />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تعيين واسم العتاد / التجهيز') }}</label>
            <input type="text" wire:model="itemDesignation" placeholder="{{ __('مثال: مجهر ضوئي ثلاثي الأبعاد، طاولة مخبرية...') }}" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المواصفات التقنية ورقم الصانع') }}</label>
            <textarea wire:model="itemSpecifications" rows="2" class="{{ $selectClasses }}" placeholder="{{ __('العلامة التجارية، الطراز، الرقم التسلسلي للصانع...') }}"></textarea>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('العدد / الكمية') }}</label>
              <input type="number" wire:model="itemQuantity" min="1" class="{{ $selectClasses }}" />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('القيمة التقديرية (دج)') }}</label>
              <input type="number" step="0.01" wire:model="itemAcquisitionCost" class="{{ $selectClasses }}" />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مصدر الاقتناء') }}</label>
              <input type="text" wire:model="itemAcquisitionSource" class="{{ $selectClasses }}" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع الموقع') }}</label>
              <select wire:model="itemLocationType" class="{{ $selectClasses }}">
                <option value="lab">{{ __('مخبر علمي') }}</option>
                <option value="classroom">{{ __('قاعة تدريس') }}</option>
                <option value="office">{{ __('مكتب إداري') }}</option>
                <option value="library">{{ __('المكتبة') }}</option>
                <option value="canteen">{{ __('المطعم') }}</option>
                <option value="store">{{ __('المخزن') }}</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم المكان / القاعة') }}</label>
              <input type="text" wire:model="itemLocationName" placeholder="{{ __('مثال: مخبر العلوم الطبيعية 1') }}" class="{{ $selectClasses }}" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المسؤول عن العهدة') }}</label>
              <select wire:model="itemCustodianId" class="{{ $selectClasses }}">
                <option value="">{{ __('اختر الموظف أو الأستاذ المسؤول') }}</option>
                @foreach($users as $u)
                  <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الحالة الراهنة') }}</label>
              <select wire:model="itemCondition" class="{{ $selectClasses }}">
                @foreach($conditions as $cond)
                  <option value="{{ $cond->value }}">{{ $cond->label() }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات إضافية') }}</label>
            <input type="text" wire:model="itemNotes" class="{{ $selectClasses }}" />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
          <button type="button" wire:click="closeItemModal" class="px-4 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-200">
            {{ __('إلغاء') }}
          </button>
          <button type="button" wire:click="saveItem" class="px-4 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
            {{ __('قيد في السجل') }}
          </button>
        </div>
      </div>
    </div>
    @endif

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 2: ROOM & LAB ALLOCATIONS                                           --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'allocations')
  <div class="space-y-6">

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 flex justify-between items-center">
      <div>
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('بطاقات التخصيص والعهدة للقاعات والمخابر (المنشور 143/97)') }}</h3>
        <p class="text-xs text-gray-500">{{ __('توثيق عهدة العتاد والأثاث الموجود في كل حجرة أو مخبر علمي بإشراف الأستاذ أو الموظف المسؤول.') }}</p>
      </div>

      <button
        type="button"
        wire:click="openAllocationModal"
        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg shadow-sm transition-colors whitespace-nowrap">
        <x-heroicon-o-plus-circle class="w-4 h-4" />
        {{ __('إعداد بطاقة تخصيص جديدة') }}
      </button>
    </div>

    {{-- Allocations Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      @forelse($this->allocations as $alloc)
      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 space-y-3">
        <div class="flex justify-between items-start">
          <div>
            <span class="text-xs font-mono font-bold text-primary-600 dark:text-primary-400">{{ $alloc->allocation_ref }}</span>
            <h4 class="text-base font-bold text-gray-900 dark:text-white mt-0.5">{{ $alloc->room_name }}</h4>
            <span class="text-xs text-gray-500">{{ $alloc->room_type }} · {{ $alloc->academic_year }}</span>
          </div>

          <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
            {{ $alloc->items_count }} {{ __('قطع') }}
          </span>
        </div>

        <div class="text-xs text-gray-600 dark:text-gray-300 border-t border-gray-100 dark:border-gray-700 pt-2">
          <div><strong>{{ __('المسؤول:') }}</strong> {{ $alloc->responsibleUser?->name ?? 'غير محدد' }}</div>
          @if($alloc->notes)
            <div class="mt-1 text-gray-500">{{ $alloc->notes }}</div>
          @endif
        </div>

        <div class="flex justify-end gap-2 border-t border-gray-100 dark:border-gray-700 pt-3">
          <a
            href="{{ route('print.inventory.allocation-sheet', ['locale' => app()->getLocale(), 'allocation' => $alloc]) }}"
            target="_blank"
            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
            <x-heroicon-o-printer class="w-3.5 h-3.5" />
            {{ __('طباعة البطاقة') }}
          </a>
          <a
            href="{{ route('print.inventory.allocation-sheet.pdf', ['locale' => app()->getLocale(), 'allocation' => $alloc]) }}"
            target="_blank"
            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-white bg-primary-600 rounded hover:bg-primary-700">
            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
            PDF
          </a>
        </div>
      </div>
      @empty
      <div class="col-span-3 p-8 text-center text-xs text-gray-500 bg-white rounded-xl border border-gray-200 dark:bg-gray-800 dark:border-white/10">
        {{ __('لم يتم إنشاء بطاقات تخصيص حتى الآن، اضغط على زر إعداد بطاقة تخصيص جديدة.') }}
      </div>
      @endforelse
    </div>

    {{-- Modal: New Allocation Card --}}
    @if($isAllocationModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4">
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 space-y-4">
        <div class="flex justify-between items-center border-b border-gray-200 pb-3 dark:border-gray-700">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('إعداد بطاقة تخصيص عتاد جديدة') }}</h3>
          <button wire:click="closeAllocationModal" class="text-gray-400 hover:text-gray-500">
            <x-heroicon-o-x-mark class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('اسم الحجرة / المخبر') }}</label>
            <input type="text" wire:model="allocationRoomName" placeholder="{{ __('مثال: مخبر العلوم الطبيعية 1') }}" class="{{ $selectClasses }}" />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع القاعة') }}</label>
            <select wire:model="allocationRoomType" class="{{ $selectClasses }}">
              <option value="مخبر علمي">{{ __('مخبر علمي') }}</option>
              <option value="قاعة تدريس">{{ __('قاعة تدريس') }}</option>
              <option value="مكتب إداري">{{ __('مكتب إداري') }}</option>
              <option value="ورشة عمل">{{ __('ورشة عمل') }}</option>
              <option value="المكتبة">{{ __('المكتبة') }}</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الأستاذ / الموظف القائم بالعهدة') }}</label>
            <select wire:model="allocationResponsibleId" class="{{ $selectClasses }}">
              <option value="">{{ __('اختر المسؤول عن العهدة') }}</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات إضافية') }}</label>
            <textarea wire:model="allocationNotes" rows="2" class="{{ $selectClasses }}" placeholder="{{ __('ملاحظات التخصيص والحالة...') }}"></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
          <button type="button" wire:click="closeAllocationModal" class="px-4 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-200">
            {{ __('إلغاء') }}
          </button>
          <button type="button" wire:click="saveAllocation" class="px-4 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
            {{ __('حفظ البطاقة') }}
          </button>
        </div>
      </div>
    </div>
    @endif

  </div>
  @endif

  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 3: ASSET REFORMS & DECOMMISSIONING                                  --}}
  {{-- ════════════════════════════════════════════════════════════════════════ --}}
  @if($activeTab === 'reforms')
  <div class="space-y-6">

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-800 flex justify-between items-center">
      <div>
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('محاضر الإسقاط والإتلاف ولجان المعاينة الرسمية') }}</h3>
        <p class="text-xs text-gray-500">{{ __('حصر ومعاينة التجهيزات والأثاث غير الصالح للاستعمال تمهيداً للتصرف فيها قانونياً وتسليمها لمصالح أملاك الدولة.') }}</p>
      </div>

      <button
        type="button"
        wire:click="openReformModal"
        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors whitespace-nowrap">
        <x-heroicon-o-plus-circle class="w-4 h-4" />
        {{ __('إعداد محضر إسقاط جديد') }}
      </button>
    </div>

    {{-- Reforms List --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm dark:border-white/10 dark:bg-gray-800">
      <div class="px-4 py-3 border-b border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-gray-700/50">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('سجل محاضر الإسقاط المعتمدة') }}</h3>
      </div>

      <div class="divide-y divide-gray-200 dark:divide-white/10">
        @forelse($this->reforms as $reform)
        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono font-bold text-sm text-rose-600 dark:text-rose-400">{{ $reform->reform_number }}</span>
                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                  {{ $reform->status->label() }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                {{ __('تاريخ المحضر:') }} {{ $reform->reform_date->format('Y/m/d') }} · {{ __('رئيس اللجنة:') }} {{ $reform->committeePresident?->name ?? 'المدير / المقتصد' }} · {{ __('مجموع القطع:') }} <strong>{{ $reform->total_items_count }}</strong> · {{ __('القيمة الإجمالية:') }} <strong>{{ number_format($reform->total_estimated_value, 2) }} دج</strong>
              </p>
              @if($reform->commission_observations)
                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1.5">{{ $reform->commission_observations }}</p>
              @endif
            </div>

            <div class="flex gap-2">
              <a
                href="{{ route('print.inventory.reform-pv', ['locale' => app()->getLocale(), 'reform' => $reform]) }}"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <x-heroicon-o-printer class="w-3.5 h-3.5" />
                {{ __('طباعة المحضر') }}
              </a>
              <a
                href="{{ route('print.inventory.reform-pv.pdf', ['locale' => app()->getLocale(), 'reform' => $reform]) }}"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-white bg-rose-600 rounded hover:bg-rose-700">
                <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                PDF
              </a>
            </div>
          </div>
        </div>
        @empty
        <div class="p-8 text-center text-xs text-gray-500">
          {{ __('لا توجد محاضر إسقاط مسجلة.') }}
        </div>
        @endforelse
      </div>
    </div>

    {{-- Modal: New Reform Minutes --}}
    @if($isReformModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4">
      <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-gray-200 pb-3 dark:border-gray-700">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('إعداد محضر إسقاط ومعاينة تجهيزات جديدة') }}</h3>
          <button wire:click="closeReformModal" class="text-gray-400 hover:text-gray-500">
            <x-heroicon-o-x-mark class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ انعقاد اللجنة') }}</label>
              <input type="date" wire:model="reformDate" class="{{ $selectClasses }}" />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رئيس اللجنة (المدير / المقتصد)') }}</label>
              <select wire:model="reformPresidentId" class="{{ $selectClasses }}">
                <option value="">{{ __('اختر رئيس اللجنة') }}</option>
                @foreach($users as $u)
                  <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('حدد التجهيزات المقترحة للإسقاط') }} ({{ count($reformItemIds) }} محددة)</label>
            <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-2 max-h-48 overflow-y-auto space-y-1">
              @forelse($activeItems as $act)
              <label class="flex items-center gap-2 p-1 hover:bg-gray-50 dark:hover:bg-gray-700/40 rounded cursor-pointer">
                <input type="checkbox" wire:model="reformItemIds" value="{{ $act->id }}" class="rounded text-primary-600 focus:ring-primary-500" />
                <span class="font-mono text-primary-600 dark:text-primary-400 font-bold">{{ $act->inventory_number }}</span>
                <span class="text-gray-800 dark:text-gray-200">{{ $act->designation }}</span>
                <span class="text-gray-400 text-[11px]">({{ $act->location_name }})</span>
              </label>
              @empty
              <div class="text-gray-400 text-center py-2">{{ __('لا توجد تجهيزات نشطة قابلة للإسقاط.') }}</div>
              @endforelse
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات ومداولات لجنة الإسقاط') }}</label>
            <textarea wire:model="reformObservations" rows="3" class="{{ $selectClasses }}" placeholder="{{ __('أسباب الإسقاط وتوصية اللجنة بعرض العتاد على مصالح أملاك الدولة...') }}"></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
          <button type="button" wire:click="closeReformModal" class="px-4 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-200">
            {{ __('إلغاء') }}
          </button>
          <button type="button" wire:click="saveReform" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg">
            {{ __('المصادقة وحفظ المحضر') }}
          </button>
        </div>
      </div>
    </div>
    @endif

  </div>
  @endif

</div>
</x-filament-panels::page>
