@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
    $year = $academicYear ?? ($schoolSetting?->academic_year ?? '2026/2027');
    $recordsCollection = collect($records ?? []);
    $statsData = $stats ?? [
        'total' => $recordsCollection->count(),
        'deliberated' => $recordsCollection->where('status', \App\Enums\DisciplinaryStatus::Deliberated)->count(),
        'scheduled' => $recordsCollection->where('status', \App\Enums\DisciplinaryStatus::Scheduled)->count(),
        'exclusions' => $recordsCollection->whereIn('sanction', [\App\Enums\DisciplinarySanction::TemporaryExclusion, \App\Enums\DisciplinarySanction::DefinitiveExclusion])->count(),
        'warnings' => $recordsCollection->whereIn('sanction', [\App\Enums\DisciplinarySanction::Warning, \App\Enums\DisciplinarySanction::Reprimand])->count(),
    ];
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('سجل مجالس التأديب السنوي') }} — {{ $year }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    @unless ($inlineCss ?? false)
        @vite('resources/css/app.css')
    @endunless

    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box;
        }

        @page {
            size: A4 landscape;
            margin: 6mm 8mm 6mm 8mm !important;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 10px 0;
        }

        .font-traditional {
            font-family: 'Amiri', serif;
        }

        .sheet-container {
            width: 100%;
            max-width: 285mm;
            margin: 0 auto;
            background: #ffffff;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        @media screen {
            .sheet-container {
                border: 1px solid #cbd5e1;
                padding: 6mm 10mm;
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            }
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .sheet-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }

        /* National Algerian Header Grid (Matching System Standard) */
        .doc-header-grid {
            display: grid;
            grid-template-columns: 1fr 1.4fr 1fr;
            border-bottom: 1.5px solid #1e3a8a;
            padding-bottom: 4px;
            margin-bottom: 6px;
            align-items: center;
        }

        /* Title Banner */
        .doc-title-badge {
            display: inline-block;
            border: 2px solid #1e3a8a;
            background: #eff6ff;
            border-radius: 8px;
            padding: 3px 28px;
            text-align: center;
        }

        .title-main {
            font-size: 13pt;
            font-weight: 900;
            color: #1e3a8a;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .title-sub {
            font-size: 7pt;
            font-weight: 700;
            color: #475569;
            margin: 1px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* KPI Summary Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin: 6px 0;
            text-align: center;
        }

        .kpi-card {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 3px 6px;
            background: #ffffff;
        }

        .kpi-label {
            font-size: 7pt;
            font-weight: 700;
            display: block;
            margin-bottom: 1px;
        }

        .kpi-value {
            font-size: 11pt;
            font-weight: 900;
            font-family: monospace;
            line-height: 1;
        }

        /* Main Ledger Table */
        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            border: 1.5px solid #1e3a8a;
            margin-top: 4px;
        }

        table.ledger-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: 700;
            border: 1px solid #3b82f6;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
            font-size: 7.5pt;
        }

        table.ledger-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            vertical-align: middle;
            color: #0f172a;
        }

        table.ledger-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Signatures Grid */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 8px;
            text-align: center;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .signature-item {
            text-align: center;
        }

        .signature-role {
            font-size: 8pt;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0;
        }

        .signature-sub {
            font-size: 7pt;
            color: #64748b;
            font-weight: 600;
            margin: 1px 0 0 0;
        }

        .signature-space {
            height: 44px;
        }

        /* Footer */
        .page-footer-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 6px;
        }
    </style>
</head>
<body class="antialiased text-slate-800">

    {{-- Browser Action Toolbar --}}
    <div class="no-print max-w-[285mm] mx-auto mb-3 flex items-center justify-between bg-white border border-slate-200 px-4 py-2 rounded-lg shadow-sm">
        <div class="flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-blue-800"></span>
            <span class="font-bold text-xs text-slate-800">{{ __('السجل السنوي لمجالس التأديب والقرارات السلوكية') }}</span>
            <span class="text-[11px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-mono font-bold">{{ $year }}</span>
            <span class="text-[11px] text-slate-500">({{ $recordsCollection->count() }} {{ __('حالة مسجلة') }})</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('print.disciplinary.ledger.pdf', ['locale' => app()->getLocale(), 'academic_year' => $year]) }}" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded bg-rose-700 text-white hover:bg-rose-800 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('تحميل PDF رسمي') }}
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded bg-slate-800 text-white hover:bg-slate-900 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة السجل') }}
            </button>
        </div>
    </div>

    <div class="sheet-container">

        {{-- 1. Official National Republic Header (Matching System Standard) --}}
        <div class="doc-header-grid">
            <div style="text-align: right; line-height: 1.3;">
                <p style="font-size: 7.5pt; color: #475569; margin: 0;">{{ $schoolSetting?->directorate_name ?? __('مديرية التربية') }}</p>
                <p style="font-size: 9.5pt; font-weight: 800; color: #1e3a8a; margin: 1px 0 0 0;">{{ $schoolSetting?->school_name ?? __('المتوسطة') }}</p>
                <p style="font-size: 7pt; color: #64748b; margin: 0;">{{ __('مصلحة الاستشارة التربوية — أمانة مجلس التأديب') }}</p>
            </div>

            <div style="text-align: center; line-height: 1.25;">
                <p class="font-traditional" style="font-size: 11.5pt; font-weight: 700; color: #0f172a; margin: 0;">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</p>
                <p style="font-size: 9pt; font-weight: 700; color: #1e3a8a; margin: 1px 0 0 0;">{{ __('وزارة التربية الوطنية') }}</p>
            </div>

            <div style="text-align: left; font-size: 7pt; color: #475569; line-height: 1.35;">
                <p style="margin: 0;">{{ __('السنة الدراسية:') }} <strong style="color: #0f172a;">{{ $year }}</strong></p>
                <p style="margin: 0;">{{ __('تاريخ الاستخراج:') }} <strong style="color: #0f172a;">{{ now()->format('d/m/Y') }}</strong></p>
                <p style="margin: 0;">{{ __('السند القانوني:') }} <span style="color: #1e3a8a; font-weight: 600;">{{ __('القرار الوزاري رقم 65') }}</span></p>
            </div>
        </div>

        {{-- 2. Document Title Banner --}}
        <div style="text-align: center; margin: 2px 0 4px 0;">
            <div class="doc-title-badge">
                <h1 class="title-main">{{ __('سجل جلسات مجلس التأديب والقرارات السلوكية السنوي') }}</h1>
                <p class="title-sub">REGISTRE ANNUEL DES CONSEILS DE DISCIPLINE ET SANCTIONS</p>
            </div>
        </div>

        {{-- 3. Statistical Summary Bar --}}
        <div class="kpi-grid">
            <div class="kpi-card" style="border-color: #cbd5e1; background: #f8fafc;">
                <span class="kpi-label" style="color: #475569;">{{ __('إجمالي القضايا المسجلة') }}</span>
                <span class="kpi-value" style="color: #0f172a;">{{ $statsData['total'] ?? $recordsCollection->count() }}</span>
            </div>
            <div class="kpi-card" style="border-color: #86efac; background: #f0fdf4;">
                <span class="kpi-label" style="color: #166534;">{{ __('جلسات مفصول فيها') }}</span>
                <span class="kpi-value" style="color: #15803d;">{{ $statsData['deliberated'] ?? 0 }}</span>
            </div>
            <div class="kpi-card" style="border-color: #fde68a; background: #fffbeb;">
                <span class="kpi-label" style="color: #92400e;">{{ __('جلسات مبرمجة قيد النظر') }}</span>
                <span class="kpi-value" style="color: #b45309;">{{ $statsData['scheduled'] ?? 0 }}</span>
            </div>
            <div class="kpi-card" style="border-color: #bfdbfe; background: #eff6ff;">
                <span class="kpi-label" style="color: #1e40af;">{{ __('عقوبات الإنذار والتوبيخ') }}</span>
                <span class="kpi-value" style="color: #1d4ed8;">{{ $statsData['warnings'] ?? 0 }}</span>
            </div>
            <div class="kpi-card" style="border-color: #fecaca; background: #fef2f2;">
                <span class="kpi-label" style="color: #991b1b;">{{ __('قرارات الإقصاء (مؤقت/نهائي)') }}</span>
                <span class="kpi-value" style="color: #b91c1c;">{{ $statsData['exclusions'] ?? 0 }}</span>
            </div>
        </div>

        {{-- 4. Main Ledger Table --}}
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 75px;">{{ __('رقم المحضر') }}</th>
                    <th style="width: 70px;">{{ __('تاريخ الجلسة') }}</th>
                    <th style="width: 140px;">{{ __('اسم ولقب التلميذ') }}</th>
                    <th style="width: 75px;">{{ __('القسم والفوج') }}</th>
                    <th style="width: 75px;">{{ __('تاريخ الميلاد') }}</th>
                    <th>{{ __('طبيعة المخالفة المنسوبة') }}</th>
                    <th style="width: 110px;">{{ __('العقوبة المقررة') }}</th>
                    <th style="width: 95px;">{{ __('الإقصاء / التعويض') }}</th>
                    <th style="width: 90px;">{{ __('المحيل / التبليغ') }}</th>
                    <th style="width: 75px;">{{ __('حالة الملف') }}</th>
                    <th style="width: 110px;">{{ __('حيثيات وملاحظات') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recordsCollection as $index => $record)
                    @php
                        $student = $record->student;
                        $classroom = $student?->group?->classroom;
                    @endphp
                    <tr>
                        <td style="text-align: center; font-family: monospace; font-weight: 700;">{{ $index + 1 }}</td>
                        <td style="text-align: center; font-family: monospace; font-weight: 700; color: #1e3a8a;">{{ $record->session_number ?? 'CD-'.$record->id }}</td>
                        <td style="text-align: center; font-family: monospace;">{{ $formatDate($record->hearing_date) }}</td>
                        <td>
                            <strong style="color: #0f172a;">{{ $student?->full_name ?? '—' }}</strong>
                            @if($student?->registration_number)
                                <div style="font-size: 6.5pt; color: #64748b; font-family: monospace;">{{ $student->registration_number }}</div>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: 600;">{{ $classroom?->name ?? '—' }}</td>
                        <td style="text-align: center; font-family: monospace; font-size: 7.5pt;">{{ $formatDate($student?->birth_date) }}</td>
                        <td>
                            <strong style="color: #991b1b; font-size: 7.5pt;">{{ $record->degree?->getLabel() ?? '—' }}</strong>
                            <div style="font-size: 7pt; color: #475569; line-height: 1.25;">
                                {{ \Illuminate\Support\Str::limit($record->incident_description, 55) }}
                            </div>
                        </td>
                        <td style="text-align: center;">
                            @if($record->sanction)
                                <span style="display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7.5pt; font-weight: 700; {{ in_array($record->sanction->value, ['temporary_exclusion', 'definitive_exclusion']) ? 'background: #fee2e2; color: #991b1b;' : 'background: #eff6ff; color: #1e40af;' }}">
                                    {{ $record->sanction->getLabel() }}
                                </span>
                            @else
                                <span style="color: #94a3b8; font-style: italic; font-size: 7pt;">{{ __('قيد المداولة') }}</span>
                            @endif
                        </td>
                        <td style="text-align: center; font-size: 7pt;">
                            @if($record->exclusion_days)
                                <div style="font-weight: 800; color: #b91c1c;">{{ $record->exclusion_days }} {{ __('أيام') }}</div>
                            @endif
                            @if($record->restitution_amount && $record->restitution_amount > 0)
                                <div style="font-family: monospace; color: #92400e; font-weight: 700;">{{ number_format($record->restitution_amount, 0) }} د.ج</div>
                            @endif
                            @if(!$record->exclusion_days && (!$record->restitution_amount || $record->restitution_amount <= 0))
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="text-align: center; font-size: 7pt;">
                            <div style="color: #334155; font-weight: 600;">{{ $record->reportedBy?->name ?? __('الإدارة') }}</div>
                            <div style="font-size: 6.5pt; color: {{ $record->guardian_notified ? '#15803d' : '#94a3b8' }};">
                                {{ $record->guardian_notified ? __('تم التبليغ ✓') : __('لم يُبلغ') }}
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span style="display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7pt; font-weight: 700; {{ $record->status === \App\Enums\DisciplinaryStatus::Deliberated ? 'background: #dcfce7; color: #15803d;' : ($record->status === \App\Enums\DisciplinaryStatus::Scheduled ? 'background: #fef3c7; color: #b45309;' : 'background: #f1f5f9; color: #475569;') }}">
                                {{ $record->status?->getLabel() ?? '—' }}
                            </span>
                        </td>
                        <td style="font-size: 7pt; color: #475569;">
                            {{ \Illuminate\Support\Str::limit($record->decision_notes ?? $record->defense_summary ?? '—', 45) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 18px 0; color: #94a3b8; font-style: italic; font-size: 8.5pt;">
                            {{ __('لا توجد سجلات مجالس تأديب مقيدة للموسم الدراسي') }} {{ $year }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- 5. Legal Authentication & Signatures Area (Clean & Open for physical stamping) --}}
        <div class="signatures-grid">
            <div class="signature-item">
                <p class="signature-role">{{ __('مستشار(ة) التربية (متابعة السجلات)') }}</p>
                <p class="signature-sub">{{ $schoolSetting?->counselor_name ?: __('الاستشارة التربوية') }}</p>
                <div class="signature-space"></div>
            </div>

            <div class="signature-item">
                <p class="signature-role">{{ __('ملاحظات وتأشيرة هيئة التفتيش الإداري') }}</p>
                <p class="signature-sub">{{ __('مفتشية التعليم المتوسط للإدارة') }}</p>
                <div class="signature-space"></div>
            </div>

            <div class="signature-item">
                <p class="signature-role">{{ __('مدير(ة) المؤسسة — رئيس المجلس والختم الرسمي') }}</p>
                <p class="signature-sub">{{ $schoolSetting?->principal_name ?? ($schoolSetting?->director_name ?? __('رئيس المؤسسة')) }}</p>
                <div class="signature-space"></div>
            </div>
        </div>

        {{-- 6. Page Footer --}}
        <div class="page-footer-bar">
            <span>{{ __('منظومة سجل الرقمية لإدارة المتوسطة — سجل مجالس التأديب والقرارات السلوكية المطابق للتنظيم الوزاري') }}</span>
            <span>{{ $schoolSetting?->school_name }}</span>
            <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
        </div>

    </div>

</body>
</html>
