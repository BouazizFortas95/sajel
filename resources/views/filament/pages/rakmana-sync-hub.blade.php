<x-filament-panels::page>
    @php
        $gradeMatrix = $this->gradeMatrix;
        $audit = $this->audit;
        $classrooms = \App\Models\Classroom::orderBy('name')->get();
        $subjects = \App\Models\Subject::orderBy('name')->get();
    @endphp

    <div class="space-y-6">

        {{-- Top Navigation & Tabs --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-white/10 pb-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button
                    wire:click="$set('activeTab', 'import')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'import' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>📥</span>
                    {{ __('استيراد قوائم الرقمنة (Excel / CSV)') }}
                </button>

                <button
                    wire:click="$set('activeTab', 'grades')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'grades' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>📊</span>
                    {{ __('تصدير كشوف رصد النقاط الوزارية') }}
                </button>

                <button
                    wire:click="$set('activeTab', 'audit')"
                    type="button"
                    class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'audit' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/10' }}"
                >
                    <span>🔍</span>
                    {{ __('مدقق المطابقة والفروقات (Audit)') }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $audit['missing_nin_count'] > 0 ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $audit['missing_nin_count'] > 0 ? $audit['missing_nin_count'] : '✓' }}
                    </span>
                </button>
            </div>

            {{-- Quick Sample Link --}}
            <a
                href="{{ route('rakmana.sample-roster', ['locale' => app()->getLocale()]) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-white/10 hover:bg-gray-50 transition shadow-sm"
            >
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>{{ __('تحميل نموذج ملف إكسل الرقمنة (CSV)') }}</span>
            </a>
        </div>

        {{-- TAB 1: IMPORT FROM RAKMANA --}}
        @if($activeTab === 'import')
            <div class="space-y-6">

                {{-- Explanatory Banner --}}
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/40 rounded-xl text-xs text-emerald-900 dark:text-emerald-300 flex items-start gap-3">
                    <span class="text-xl shrink-0">💡</span>
                    <div class="leading-relaxed">
                        <strong class="block font-bold mb-1">{{ __('معالج استيراد بيانات الدخول المدرسي والتحويلات من منصة الرقمنة:') }}</strong>
                        <p>{{ __('يمكنك رفع ملف قوائم التلاميذ كما تم استخراجه مباشرة من منصة الرقمنة لوزارة التربية الوطنية (بصيغة Excel أو CSV). يتعرف النظام تلقائياً على رقم التعريف الوطني NIN، الأسماء وتواريخ الميلاد والأفواج وينشئ الحسابات أو يحدثها دون أي ازدواجية.') }}</p>
                    </div>
                </div>

                {{-- Upload & Parse Box --}}
                @if(!$previewData)
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📁</span>
                            {{ __('اختيار أو سحب ملف قوائم الرقمنة') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('رفع ملف الرقمنة (CSV أو XLS أو TXT):') }}
                                </label>
                                <input
                                    wire:model="rosterFile"
                                    type="file"
                                    accept=".csv, .txt, .xls, .xlsx"
                                    class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 p-2"
                                />
                                <div wire:loading wire:target="rosterFile" class="text-xs text-emerald-600 mt-1 font-semibold">
                                    {{ __('جارٍ تحميل الملف...') }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('أو لصق محتوى القائمة كنص مباشرة:') }}
                                </label>
                                <textarea
                                    wire:model="rawCsvContent"
                                    rows="3"
                                    placeholder="{{ __('الصق هنا الأسطر المنسوخة من ملف الإكسل أو الرقمنة...') }}"
                                    class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 font-mono"
                                ></textarea>
                            </div>
                        </div>

                        {{-- Options Toggles --}}
                        <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-gray-100 dark:border-white/5 text-xs">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="createMissingGroups" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" />
                                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ __('إنشاء وتعيين الأفواج التربوية الناقصة آلياً') }}</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="updateExisting" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" />
                                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ __('تحديث وتصحيح بيانات التلاميذ الموجودين حالياً (الـ NIN، تاريخ الميلاد، الولي)') }}</span>
                            </label>
                        </div>

                        <div class="pt-2">
                            <button
                                wire:click="parseFile"
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-5 py-2.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center gap-2 transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <span>{{ __('معالجة الملف ومعاينة القائمة') }}</span>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- PREVIEW SECTION (When parsed) --}}
                @if($previewData)
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span>🔍</span>
                                    {{ __('معاينة نتائج فحص ملف الرقمنة قبل الحفظ') }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ __('تحقق من الإحصائيات والأفواج وتأكد من مطابقة المعطيات قبل الاعتماد.') }}</p>
                            </div>
                            <button
                                wire:click="clearPreview"
                                type="button"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition"
                            >
                                {{ __('إلغاء واختيار ملف آخر') }}
                            </button>
                        </div>

                        {{-- Metric Badges --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                            <div class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-lg border border-slate-200 dark:border-white/5">
                                <span class="text-xs text-gray-500 block">{{ __('إجمالي السجلات المقروءة') }}</span>
                                <span class="text-xl font-black text-slate-800 dark:text-white font-mono">{{ $previewData['total_count'] }}</span>
                            </div>

                            <div class="bg-emerald-50 dark:bg-emerald-950/20 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800/30">
                                <span class="text-xs text-emerald-700 block">{{ __('تلاميذ جدد سيتم إضافتهم') }}</span>
                                <span class="text-xl font-black text-emerald-800 dark:text-emerald-300 font-mono">+{{ $previewData['new_count'] }}</span>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-950/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800/30">
                                <span class="text-xs text-blue-700 block">{{ __('تلاميذ سيتم تحديث بياناتهم') }}</span>
                                <span class="text-xl font-black text-blue-800 dark:text-blue-300 font-mono">{{ $previewData['update_count'] }}</span>
                            </div>

                            <div class="bg-amber-50 dark:bg-amber-950/20 p-3 rounded-lg border border-amber-200 dark:border-amber-800/30">
                                <span class="text-xs text-amber-700 block">{{ __('سجلات غير مكتملة / مستبعدة') }}</span>
                                <span class="text-xl font-black text-amber-800 dark:text-amber-300 font-mono">{{ $previewData['invalid_count'] }}</span>
                            </div>
                        </div>

                        {{-- Detected Groups --}}
                        @if(!empty($previewData['groups_detected']))
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-lg border border-gray-200 dark:border-white/5 text-xs">
                                <span class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">{{ __('الأفواج التربوية المكتشفة في الملف:') }}</span>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($previewData['groups_detected'] as $groupName => $count)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 font-bold text-gray-800 dark:text-gray-200">
                                            <span>{{ $groupName ?: __('غير محدد') }}</span>
                                            <span class="font-mono text-[10px] bg-primary-100 text-primary-800 px-1 rounded">{{ $count }} {{ __('تلميذ') }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Sample Table (First 15 rows) --}}
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                            <table class="w-full text-right text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-300">
                                    <tr>
                                        <th class="p-2.5 font-bold">#</th>
                                        <th class="p-2.5 font-bold">{{ __('رقم التعريف الوطني (NIN)') }}</th>
                                        <th class="p-2.5 font-bold">{{ __('اللقب والاسم') }}</th>
                                        <th class="p-2.5 font-bold">{{ __('تاريخ الميلاد') }}</th>
                                        <th class="p-2.5 font-bold">{{ __('القسم / الفوج') }}</th>
                                        <th class="p-2.5 font-bold">{{ __('النظام') }}</th>
                                        <th class="p-2.5 font-bold text-center">{{ __('الإجراء المتوقع') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                    @foreach($previewData['sample_rows'] as $idx => $row)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
                                            <td class="p-2.5 font-mono text-gray-500">{{ $idx + 1 }}</td>
                                            <td class="p-2.5 font-mono font-bold text-slate-800 dark:text-slate-200">{{ $row['national_id_number'] ?: '—' }}</td>
                                            <td class="p-2.5 font-bold text-gray-900 dark:text-white">{{ $row['last_name'] }} {{ $row['first_name'] }}</td>
                                            <td class="p-2.5 font-mono text-gray-600 dark:text-gray-400">{{ $row['date_of_birth'] ?: '—' }}</td>
                                            <td class="p-2.5 font-semibold text-gray-700 dark:text-gray-300">{{ $row['group_name'] ?: '—' }}</td>
                                            <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['regime'] ?: 'خارجي' }}</td>
                                            <td class="p-2.5 text-center">
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $row['status'] === 'new' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ $row['status'] === 'new' ? '➕ إضافة كجديد' : '🔄 تحديث ملفه' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-white/5">
                            <span class="text-xs text-gray-500">{{ __('معاينة عينة من 15 سطراً من أصل') }} {{ $previewData['total_count'] }} {{ __('سجل جاهز للاستيراد.') }}</span>
                            <div class="flex items-center gap-2">
                                <button
                                    wire:click="clearPreview"
                                    type="button"
                                    class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
                                >
                                    {{ __('إلغاء') }}
                                </button>
                                <button
                                    wire:click="executeImport"
                                    wire:loading.attr="disabled"
                                    type="button"
                                    class="px-5 py-2.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center gap-2 transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ __('تأكيد واستيراد كافة التلاميذ في قاعدة البيانات') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        @endif

        {{-- TAB 2: GRADE EXPORT FOR RAKMANA --}}
        @if($activeTab === 'grades')
            <div class="space-y-4">
                {{-- Selector Toolbar --}}
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ __('القسم / الفوج') }}</label>
                            <select
                                wire:model.live="selectedClassroomId"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500"
                            >
                                @foreach($classrooms as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ __('المادة التعليمية') }}</label>
                            <select
                                wire:model.live="selectedSubjectId"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500"
                            >
                                @foreach($subjects as $subj)
                                    <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ __('الفصل الدراسي') }}</label>
                            <select
                                wire:model.live="selectedTrimester"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500"
                            >
                                <option value="1">{{ __('الفصل الأول') }}</option>
                                <option value="2">{{ __('الفصل الثاني') }}</option>
                                <option value="3">{{ __('الفصل الثالث') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">{{ __('تحميل الملف المتوافق') }}</label>
                            @if($selectedClassroomId && $selectedSubjectId)
                                <a
                                    href="{{ route('rakmana.grades.export-csv', [
                                        'locale' => app()->getLocale(),
                                        'classroom' => $selectedClassroomId,
                                        'subject' => $selectedSubjectId,
                                        'trimester' => $selectedTrimester,
                                        'academic_year' => $academicYear,
                                    ]) }}"
                                    class="w-full px-4 py-2 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center justify-center gap-2 transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span>{{ __('تنزيل ملف الرقمنة (CSV)') }}</span>
                                </a>
                            @else
                                <button disabled class="w-full px-4 py-2 text-xs font-bold rounded-lg bg-gray-200 text-gray-400 cursor-not-allowed">
                                    {{ __('اختر القسم والمادة') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Live Grade Matrix Table --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-white/10 flex justify-between items-center text-xs">
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            {{ __('جدول رصد النقاط الوزاري المطابق لقالب الرقمنة') }}
                            ({{ count($gradeMatrix['rows'] ?? []) }} {{ __('تلميذ') }})
                        </span>
                        <span class="font-mono text-gray-500 text-[11px]">
                            {{ $academicYear }} — {{ __('الفصل') }} {{ $selectedTrimester }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-gray-100/70 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-white/10">
                                <tr>
                                    <th class="p-2.5 font-bold w-10 text-center">#</th>
                                    <th class="p-2.5 font-bold">{{ __('رقم التعريف الوطني (NIN)') }}</th>
                                    <th class="p-2.5 font-bold">{{ __('اسم ولقب التلميذ') }}</th>
                                    <th class="p-2.5 font-bold">{{ __('تاريخ الميلاد') }}</th>
                                    <th class="p-2.5 font-bold text-center w-24">{{ __('التقويم المستمر') }}</th>
                                    <th class="p-2.5 font-bold text-center w-24">{{ __('الفرض 1') }}</th>
                                    <th class="p-2.5 font-bold text-center w-24">{{ __('الاختبار') }}</th>
                                    <th class="p-2.5 font-bold text-center w-24">{{ __('المعدل الفصلي') }}</th>
                                    <th class="p-2.5 font-bold text-center w-28">{{ __('الملاحظة') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @forelse($gradeMatrix['rows'] ?? [] as $row)
                                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/20">
                                        <td class="p-2.5 text-center font-mono text-gray-500">{{ $row['order'] }}</td>
                                        <td class="p-2.5 font-mono font-bold text-slate-800 dark:text-slate-200">{{ $row['national_id_number'] }}</td>
                                        <td class="p-2.5 font-bold text-gray-900 dark:text-white">{{ $row['full_name'] }}</td>
                                        <td class="p-2.5 font-mono text-gray-600 dark:text-gray-400">{{ $row['date_of_birth'] }}</td>
                                        <td class="p-2.5 text-center font-mono font-semibold">{{ $row['continuous'] ?: '—' }}</td>
                                        <td class="p-2.5 text-center font-mono font-semibold">{{ $row['midterm'] ?: '—' }}</td>
                                        <td class="p-2.5 text-center font-mono font-semibold">{{ $row['exam'] ?: '—' }}</td>
                                        <td class="p-2.5 text-center font-mono font-black">
                                            @if($row['average'] !== '')
                                                <span class="inline-block px-2 py-0.5 rounded text-[11px] {{ (float) $row['average'] >= 10 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                    {{ $row['average'] }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="p-2.5 text-center font-medium text-gray-600 dark:text-gray-300">
                                            {{ $row['appreciation'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="p-8 text-center text-gray-400">
                                            {{ __('لا يوجد تلاميذ مسجلين في هذا القسم أو لم يتم إدراج التقييمات بعد.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 3: DATA RECONCILIATION & AUDIT --}}
        @if($activeTab === 'audit')
            <div class="space-y-6">

                {{-- Executive KPI Metrics --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm">
                        <span class="text-xs text-gray-500 block">{{ __('إجمالي التلاميذ النشطين') }}</span>
                        <span class="text-2xl font-black text-gray-900 dark:text-white font-mono">{{ $audit['total_students'] }}</span>
                    </div>

                    <div class="bg-emerald-50 dark:bg-emerald-950/20 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/30">
                        <span class="text-xs text-emerald-700 block">{{ __('نسبة جاهزية الرقمنة (بحوزتهم NIN)') }}</span>
                        <span class="text-2xl font-black text-emerald-800 dark:text-emerald-300 font-mono">{{ $audit['nin_completeness_rate'] }}%</span>
                        <span class="text-[10px] text-emerald-600 font-mono block mt-0.5">({{ $audit['with_nin_count'] }} {{ __('تلميذ') }})</span>
                    </div>

                    <div class="bg-rose-50 dark:bg-rose-950/20 p-4 rounded-xl border border-rose-200 dark:border-rose-800/30">
                        <span class="text-xs text-rose-700 block">{{ __('تلاميذ بدون رقم تعريف وطني (NIN)') }}</span>
                        <span class="text-2xl font-black text-rose-800 dark:text-rose-300 font-mono">{{ $audit['missing_nin_count'] }}</span>
                        <span class="text-[10px] text-rose-600 block mt-0.5">{{ __('يتطلب استدراك فوري') }}</span>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-950/20 p-4 rounded-xl border border-amber-200 dark:border-amber-800/30">
                        <span class="text-xs text-amber-700 block">{{ __('نقص بيانات الميلاد أو الولي') }}</span>
                        <span class="text-2xl font-black text-amber-800 dark:text-amber-300 font-mono">{{ $audit['missing_birth_count'] + $audit['missing_guardian_count'] }}</span>
                    </div>
                </div>

                {{-- Actionable Deficiency Table --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden">
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-white/10 flex justify-between items-center text-xs">
                        <span class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <span>⚠️</span>
                            {{ __('قائمة التلاميذ المطلوب استدراك أرقام التعريف الوطنية NIN الخاصة بهم') }}
                        </span>
                        <span class="text-slate-500 text-[11px]">{{ __('انقر على الزر لتسجيل رقم الـ NIN مباشرة') }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-gray-100/70 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="p-2.5 font-bold w-12 text-center">#</th>
                                    <th class="p-2.5 font-bold">{{ __('اسم ولقب التلميذ') }}</th>
                                    <th class="p-2.5 font-bold">{{ __('القسم') }}</th>
                                    <th class="p-2.5 font-bold">{{ __('رقم التسجيل') }}</th>
                                    <th class="p-2.5 font-bold">{{ __('تاريخ الميلاد') }}</th>
                                    <th class="p-2.5 font-bold text-center">{{ __('إدخال رقم التعريف الوطني (NIN)') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @forelse($audit['deficient_students'] as $idx => $st)
                                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/20">
                                        <td class="p-2.5 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                                        <td class="p-2.5 font-bold text-gray-900 dark:text-white">{{ $st['full_name'] }}</td>
                                        <td class="p-2.5 font-semibold text-gray-700 dark:text-gray-300">{{ $st['classroom'] }}</td>
                                        <td class="p-2.5 font-mono text-gray-600 dark:text-gray-400">{{ $st['registration_number'] }}</td>
                                        <td class="p-2.5 font-mono text-gray-600 dark:text-gray-400">{{ $st['date_of_birth'] }}</td>
                                        <td class="p-2.5 text-center">
                                            @if($editingStudentId === $st['id'])
                                                <div class="flex items-center justify-center gap-1">
                                                    <input
                                                        wire:model="editNin"
                                                        type="text"
                                                        placeholder="{{ __('NIN من 18 رقماً...') }}"
                                                        class="text-[11px] font-mono rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-2 py-0.5 w-44"
                                                    />
                                                    <button
                                                        wire:click="saveStudentNin"
                                                        type="button"
                                                        class="px-2 py-0.5 rounded bg-emerald-600 text-white font-bold text-[10px]"
                                                    >
                                                        ✓ {{ __('حفظ') }}
                                                    </button>
                                                    <button
                                                        wire:click="$set('editingStudentId', null)"
                                                        type="button"
                                                        class="px-1.5 py-0.5 rounded bg-gray-200 text-gray-700 text-[10px]"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>
                                            @else
                                                <button
                                                    wire:click="openEditNinModal({{ $st['id'] }})"
                                                    type="button"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-semibold text-[10px] transition"
                                                >
                                                    <span>➕ {{ __('إضافة الـ NIN') }}</span>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-emerald-700 dark:text-emerald-400 font-bold">
                                            ✓ {{ __('تهانينا! جميع التلاميذ لديهم أرقام تعريف وطنية NIN مسجلة بنجاح.') }}
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

