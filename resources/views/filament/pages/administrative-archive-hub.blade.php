<x-filament-panels::page>
    @php
        $installations = $this->installations;
        $handovers     = $this->handovers;
        $mails         = $this->mails;
        $kpi           = $this->kpiStats;
        $staffOptions  = $this->staffOptions;
    @endphp

    <div class="space-y-6">

        {{-- ═══════════════════════════════════════════════════════
             TOP HEADER
        ═══════════════════════════════════════════════════════════ --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 rounded-lg">
                    <x-heroicon-o-archive-box-arrow-down class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        {{ __('الأرشيف الإداري: التنصيب، تسليم المهام، وسجل البريد الرسمي') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('وفق نصوص التشريع المدرسي الجزائري — محاضر رسمية قابلة للطباعة') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-700 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600">
                    <span class="text-xs text-gray-500 dark:text-gray-400">📅 {{ __('السنة:') }}</span>
                    <input type="number" wire:model.live="yearFilter" min="2020" max="2035"
                           class="text-xs bg-transparent border-0 p-0 text-gray-900 dark:text-white font-semibold focus:ring-0 w-16">
                </div>

                <x-filament::button wire:click="openInstallationModal" size="sm" color="primary" icon="heroicon-m-user-plus">
                    {{ __('محضر تنصيب جديد') }}
                </x-filament::button>

                <x-filament::button wire:click="openHandoverModal" size="sm" color="warning" icon="heroicon-m-arrows-right-left">
                    {{ __('محضر تسليم مهام') }}
                </x-filament::button>

                <x-filament::button wire:click="openMailModal" size="sm" color="info" icon="heroicon-m-envelope-open">
                    {{ __('تسجيل بريد') }}
                </x-filament::button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             KPI CARDS
        ═══════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-2xl font-bold text-teal-600">{{ $kpi['installations']['total'] }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ __('إجمالي محاضر التنصيب') }}</div>
                <div class="text-xs text-teal-500 mt-0.5">{{ __('هذه السنة:') }} {{ $kpi['installations']['this_year'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-2xl font-bold text-amber-600">{{ $kpi['handovers']['total'] }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ __('محاضر تسليم المهام') }}</div>
                <div class="text-xs text-amber-500 mt-0.5">{{ __('غير مكتملة:') }} {{ $kpi['handovers']['unsigned'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $kpi['mails']['incoming_total'] }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ __('البريد الوارد') }}</div>
                <div class="text-xs text-blue-500 mt-0.5">{{ __('قيد المعالجة:') }} {{ $kpi['mails']['pending'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-2xl font-bold {{ $kpi['mails']['overdue'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $kpi['mails']['outgoing_total'] }}
                </div>
                <div class="text-xs text-gray-500 mt-1">{{ __('البريد الصادر') }}</div>
                <div class="text-xs {{ $kpi['mails']['overdue'] > 0 ? 'text-red-500' : 'text-gray-400' }} mt-0.5">
                    @if($kpi['mails']['overdue'] > 0)
                        ⚠ {{ __('متأخرة:') }} {{ $kpi['mails']['overdue'] }}
                    @else
                        {{ __('لا تأخيرات') }}
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             TAB BAR
        ═══════════════════════════════════════════════════════════ --}}
        <div class="flex items-center gap-1 bg-white dark:bg-gray-800 p-1 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
            @foreach([
                ['installations', 'heroicon-o-user-plus',           __('محاضر التنصيب')],
                ['handovers',     'heroicon-o-arrows-right-left',    __('تسليم المهام')],
                ['mails',         'heroicon-o-envelope',             __('البريد الصادر والوارد')],
            ] as [$tab, $icon, $label])
            <button wire:click="$set('activeTab', '{{ $tab }}')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap
                           {{ $activeTab === $tab
                              ? 'bg-teal-600 text-white shadow-sm'
                              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <x-dynamic-component :component="$icon" class="w-4 h-4" />
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- ═══════════════════════════════════════════════════════
             TAB: INSTALLATIONS
        ═══════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'installations')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-user-plus class="w-5 h-5 text-teal-500" />
                    {{ __('سجل محاضر التنصيب') }}
                </h3>
                <div class="flex items-center gap-2">
                    <input type="text" wire:model.live.debounce.300ms="searchQuery"
                           placeholder="{{ __('بحث...') }}"
                           class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white w-48 focus:ring-2 focus:ring-teal-500">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('الموظف') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('الوظيفة / الصفة') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('تاريخ التنصيب') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('قرار التعيين') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('المؤسسة السابقة') }}</th>
                            <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($installations as $inst)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $inst->user?->name ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $inst->user?->registration_number ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $inst->role_label ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $inst->installation_date->locale('ar')->isoFormat('LL') }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $inst->recruitment_order_ref ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $inst->previous_institution ?? __('أول تعيين') }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ $this->getInstallationPrintUrl($inst->id) }}" target="_blank"
                                   class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-teal-50 hover:bg-teal-100 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 rounded-lg">
                                    <x-heroicon-m-printer class="w-3.5 h-3.5" />{{ __('طباعة') }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                {{ __('لا توجد محاضر تنصيب مسجلة') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════
             TAB: HANDOVERS
        ═══════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'handovers')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-arrows-right-left class="w-5 h-5 text-amber-500" />
                    {{ __('سجل محاضر تسليم واستلام المهام') }}
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('الوظيفة') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('المُسلِّم') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('المُتسلِّم') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('التاريخ') }}</th>
                            <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('التواقيع') }}</th>
                            <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($handovers as $hv)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                    {{ $hv->role instanceof \App\Enums\HandoverRole ? $hv->role->label() : $hv->role }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $hv->outgoing_label }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $hv->incoming_label }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $hv->handover_date->locale('ar')->isoFormat('LL') }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span title="{{ __('المُسلِّم') }}" class="text-{{ $hv->outgoing_signed ? 'green' : 'gray' }}-500 text-lg">
                                        {{ $hv->outgoing_signed ? '✔' : '○' }}
                                    </span>
                                    <span title="{{ __('المُتسلِّم') }}" class="text-{{ $hv->incoming_signed ? 'green' : 'gray' }}-500 text-lg">
                                        {{ $hv->incoming_signed ? '✔' : '○' }}
                                    </span>
                                    <span title="{{ __('المدير') }}" class="text-{{ $hv->director_signed ? 'green' : 'gray' }}-500 text-lg">
                                        {{ $hv->director_signed ? '✔' : '○' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @if(!$hv->outgoing_signed)
                                    <button wire:click="markSigned({{ $hv->id }}, 'outgoing')"
                                            class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded">
                                        {{ __('توقيع المُسلِّم') }}
                                    </button>
                                    @endif
                                    @if(!$hv->incoming_signed)
                                    <button wire:click="markSigned({{ $hv->id }}, 'incoming')"
                                            class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded">
                                        {{ __('توقيع المُتسلِّم') }}
                                    </button>
                                    @endif
                                    <a href="{{ $this->getHandoverPrintUrl($hv->id) }}" target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-lg">
                                        <x-heroicon-m-printer class="w-3.5 h-3.5" />{{ __('طباعة') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                {{ __('لا توجد محاضر تسليم مسجلة') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════
             TAB: MAIL REGISTRY
        ═══════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'mails')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-envelope class="w-5 h-5 text-blue-500" />
                    {{ __('سجل البريد الصادر والوارد') }}
                </h3>
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Type Toggle --}}
                    <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                        <button wire:click="$set('mailTypeFilter', 'incoming')"
                                class="px-3 py-1.5 text-xs font-semibold transition-colors
                                       {{ $mailTypeFilter === 'incoming' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                            {{ __('وارد') }}
                        </button>
                        <button wire:click="$set('mailTypeFilter', 'outgoing')"
                                class="px-3 py-1.5 text-xs font-semibold transition-colors
                                       {{ $mailTypeFilter === 'outgoing' ? 'bg-orange-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                            {{ __('صادر') }}
                        </button>
                    </div>

                    <input type="text" wire:model.live.debounce.300ms="searchQuery"
                           placeholder="{{ __('بحث...') }}"
                           class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white w-40 focus:ring-2 focus:ring-blue-500">

                    <a href="{{ $this->getMailLedgerPrintUrl($mailTypeFilter, $yearFilter) }}" target="_blank"
                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg">
                        <x-heroicon-m-printer class="w-4 h-4" />{{ __('طباعة السجل') }}
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('رقم البريد') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('التاريخ') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ $mailTypeFilter === 'incoming' ? __('المُرسِل') : __('المُرسَل إليه') }}</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('الموضوع') }}</th>
                            <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('السرية') }}</th>
                            <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('أجل الرد') }}</th>
                            <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('الحالة') }}</th>
                            <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('إجراء') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($mails as $mail)
                        @php
                            $isOverdue = $mail->isOverdue();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ $isOverdue ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="px-4 py-3 font-mono font-bold text-gray-900 dark:text-gray-100 text-xs">
                                {{ $mail->mail_number }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $mail->mail_date->format('Y/m/d') }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $mailTypeFilter === 'incoming' ? ($mail->sender_name ?? '—') : ($mail->recipient_name ?? '—') }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs">
                                <div class="truncate" title="{{ $mail->subject }}">{{ $mail->subject }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php $conf = $mail->confidentiality; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                             {{ $conf === \App\Enums\MailConfidentiality::Confidential ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : '' }}
                                             {{ $conf === \App\Enums\MailConfidentiality::Urgent ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                             {{ $conf === \App\Enums\MailConfidentiality::Normal ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
                                    {{ $conf->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                @if($mail->response_deadline)
                                    <span class="{{ $isOverdue ? 'text-red-600 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $mail->response_deadline->format('Y/m/d') }}
                                        @if($isOverdue) ⚠ @endif
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php $status = $mail->status; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                             {{ $status === \App\Enums\MailStatus::Pending ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                                             {{ $status === \App\Enums\MailStatus::Processed ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                             {{ $status === \App\Enums\MailStatus::Archived ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : '' }}">
                                    {{ $status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @if($status === \App\Enums\MailStatus::Pending)
                                    <button wire:click="updateMailStatus({{ $mail->id }}, 'processed')"
                                            class="px-2 py-1 text-xs bg-green-50 hover:bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded">
                                        {{ __('معالجة') }}
                                    </button>
                                    @endif
                                    @if($status !== \App\Enums\MailStatus::Archived)
                                    <button wire:click="updateMailStatus({{ $mail->id }}, 'archived')"
                                            class="px-2 py-1 text-xs bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                        {{ __('أرشفة') }}
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                {{ __('لا توجد سجلات بريد لهذه الفترة') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>{{-- /space-y-6 --}}

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: NEW INSTALLATION
    ════════════════════════════════════════════════════════════ --}}
    @if($isInstallationModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" wire:click.self="closeInstallationModal">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-user-plus class="w-5 h-5 text-teal-500" />
                    {{ __('تسجيل محضر تنصيب جديد') }}
                </h3>
                <button wire:click="closeInstallationModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <x-heroicon-m-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الموظف') }} *</label>
                        <select wire:model="installUserId" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-teal-500">
                            <option value="">{{ __('اختر موظفاً...') }}</option>
                            @foreach($staffOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('installUserId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ التنصيب') }} *</label>
                        <input type="date" wire:model="installDate" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الوظيفة / الصفة') }} *</label>
                        <input type="text" wire:model="installRoleLabel" placeholder="{{ __('مثال: أستاذ تعليم متوسط، مدير...') }}" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-teal-500">
                        @error('installRoleLabel')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الرتبة القانونية') }}</label>
                        <input type="text" wire:model="installGrade" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم قرار التعيين') }}</label>
                        <input type="text" wire:model="installOrderRef" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('رقم المقرر الوزاري') }}</label>
                        <input type="text" wire:model="installDecisionRef" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('المؤسسة السابقة') }}</label>
                        <input type="text" wire:model="installPrevInstitution" placeholder="{{ __('اتركه فارغاً إن كان أول تعيين') }}" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات') }}</label>
                        <textarea wire:model="installNotes" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="closeInstallationModal" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg">
                    {{ __('إلغاء') }}
                </button>
                <x-filament::button wire:click="saveInstallation" color="primary" icon="heroicon-m-check">
                    {{ __('حفظ المحضر') }}
                </x-filament::button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: NEW HANDOVER
    ════════════════════════════════════════════════════════════ --}}
    @if($isHandoverModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" wire:click.self="closeHandoverModal">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-arrows-right-left class="w-5 h-5 text-amber-500" />
                    {{ __('تسجيل محضر تسليم واستلام المهام') }}
                </h3>
                <button wire:click="closeHandoverModal" class="text-gray-400 hover:text-gray-600">
                    <x-heroicon-m-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الوظيفة المُسلَّمة') }} *</label>
                        <select wire:model="handoverRole" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500">
                            @foreach(\App\Enums\HandoverRole::cases() as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ التسليم') }} *</label>
                        <input type="date" wire:model="handoverDate" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الموظف المُسلِّم') }}</label>
                        <select wire:model="outgoingUserId" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="">{{ __('اختر أو اكتب الاسم أدناه') }}</option>
                            @foreach($staffOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <input type="text" wire:model="outgoingName" placeholder="{{ __('الاسم (إن لم يكن موظفاً في المنظومة)') }}" class="mt-1 w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الموظف المُتسلِّم') }}</label>
                        <select wire:model="incomingUserId" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500">
                            <option value="">{{ __('اختر أو اكتب الاسم أدناه') }}</option>
                            @foreach($staffOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <input type="text" wire:model="incomingName" placeholder="{{ __('الاسم (إن لم يكن موظفاً في المنظومة)') }}" class="mt-1 w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('مرجع محضر الجرد') }}</label>
                        <input type="text" wire:model="inventoryRef" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملخص الجرد') }}</label>
                        <textarea wire:model="inventorySummary" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الملفات المعلقة والقضايا الجارية') }}</label>
                        <textarea wire:model="pendingDossiers" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات ومستجدات') }}</label>
                        <textarea wire:model="handoverRemarks" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500"></textarea>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="closeHandoverModal" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                    {{ __('إلغاء') }}
                </button>
                <x-filament::button wire:click="saveHandover" color="warning" icon="heroicon-m-check">
                    {{ __('حفظ المحضر') }}
                </x-filament::button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: NEW MAIL
    ════════════════════════════════════════════════════════════ --}}
    @if($isMailModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" wire:click.self="closeMailModal">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-envelope-open class="w-5 h-5 text-blue-500" />
                    {{ __('تسجيل بريد في سجل البريد الرسمي') }}
                </h3>
                <button wire:click="closeMailModal" class="text-gray-400 hover:text-gray-600">
                    <x-heroicon-m-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('نوع البريد') }} *</label>
                        <select wire:model.live="mailType" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="incoming">{{ __('وارد (Courrier Arrivée)') }}</option>
                            <option value="outgoing">{{ __('صادر (Courrier Départ)') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تاريخ الوصول / الإرسال') }} *</label>
                        <input type="date" wire:model="mailDate" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $mailType === 'incoming' ? __('اسم المُرسِل') : __('اسم المُرسَل إليه') }}</label>
                        @if($mailType === 'incoming')
                        <input type="text" wire:model="mailSender" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                        @else
                        <input type="text" wire:model="mailRecipient" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الرقم المرجعي للمُرسِل') }}</label>
                        <input type="text" wire:model="mailRefNumber" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('الموضوع') }} *</label>
                        <input type="text" wire:model="mailSubject" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                        @error('mailSubject')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('درجة السرية') }}</label>
                        <select wire:model="mailConfidentiality" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            @foreach(\App\Enums\MailConfidentiality::cases() as $c)
                            <option value="{{ $c->value }}">{{ $c->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('أجل الرد') }}</label>
                        <input type="date" wire:model="mailDeadline" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('تعيين إلى') }}</label>
                        <select wire:model="mailAssignedTo" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ __('غير مُعيَّن') }}</option>
                            @foreach($staffOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('ملاحظات') }}</label>
                        <textarea wire:model="mailNotes" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="closeMailModal" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                    {{ __('إلغاء') }}
                </button>
                <x-filament::button wire:click="saveMail" color="info" icon="heroicon-m-check">
                    {{ __('تسجيل في السجل الرسمي') }}
                </x-filament::button>
            </div>
        </div>
    </div>
    @endif

</x-filament-panels::page>

