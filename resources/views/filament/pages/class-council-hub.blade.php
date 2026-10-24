@php
  $data = $this->getReportDataProperty();
  $classroom = $data['classroom'] ?? null;
  $kpis = $data['kpis'] ?? [];
  $deliberations = $data['studentDeliberations'] ?? collect();
  $honorDist = $data['honorDistribution'] ?? [];
@endphp

<div class="filament-page">
  <x-filament::page.header :heading="__('مجلس القسم')" />

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
      <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('القسم') }}</div>
      <div class="text-lg font-bold text-slate-900 dark:text-white">{{ $classroom?->name ?? '—' }}</div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
      <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('المعدل العام') }}/20</div>
      <div class="text-2xl font-bold text-amber-600">{{ $kpis['class_general_average'] ?? '—' }}</div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
      <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('نسبة النجاح') }}</div>
      <div class="text-2xl font-bold text-emerald-600">{{ $kpis['pass_rate'] ?? 0 }}%</div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
      <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('التلاميذ') }}</div>
      <div class="text-2xl font-bold text-blue-600">{{ $kpis['total_students'] ?? 0 }}</div>
    </div>
  </div>

  <div class="flex gap-3 mb-6">
    <a href="{{ $this->getClassroomPrintUrl() }}" target="_blank" class="filament-button filament-button-primary inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium shadow">
      {{ __('طباعة محضر مجلس القسم') }}
    </a>
    <a href="{{ $this->getClassroomPdfUrl() }}" target="_blank" class="filament-button filament-button-secondary inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium shadow">
      {{ __('تحميل محضر مجلس القسم (PDF)') }}
    </a>
  </div>

  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
    <table class="w-full text-sm text-right">
      <thead class="bg-slate-100 dark:bg-slate-800 text-xs uppercase font-semibold text-slate-700 dark:text-slate-300">
        <tr>
          <th class="px-4 py-3">{{ __('التلميذ(ة)') }}</th>
          <th class="px-3 py-3">{{ __('المعدل العام') }}</th>
          <th class="px-3 py-3">{{ __('الحضور/غياب') }}</th>
          <th class="px-3 py-3">{{ __('ملاحظات سلوكية') }}</th>
          <th class="px-3 py-3">{{ __('التوصية') }}</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
        @forelse ($deliberations as $del)
        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
          <td class="px-4 py-2 font-medium text-slate-900 dark:text-white">{{ $del['full_name'] }}</td>
          <td class="px-3 py-2 text-amber-700 dark:text-amber-300 font-bold">{{ $del['generalAverage'] !== null ? number_format($del['generalAverage'],2) : '—' }}</td>
          <td class="px-3 py-2 text-xs">{{ $del['attendanceCounts']['absent'] ?? 0 }} {{ __('غائب') }} / {{ $del['attendanceCounts']['late'] ?? 0 }} {{ __('متأخر') }}</td>
          <td class="px-3 py-2 text-xs">{{ $del['behaviorCounts']['negative'] > 0 ? '⚠ '.$del['behaviorCounts']['negative'].' '.('تنبيه') : '—' }}</td>
          <td class="px-3 py-2 text-xs">{{ $del['recommendation'] }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">{{ __('لا توجد بيانات') }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>