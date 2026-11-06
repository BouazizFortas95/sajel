@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $year = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $mandateCode = 'MANDAT-'.$log->id.'-'.($log->date?->format('Ymd') ?? now()->format('Ymd'));
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('استدعاء وتكليف بمهمة استخلاف بيداغوجي') }} — {{ $substituteTeacher->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 190mm;
            margin: 6px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 11px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .no-print .title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .no-print .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            font-size: 10.5px;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
            transition: all 0.15s ease;
        }
        .btn-amber { background: #f59e0b; color: #0f172a; }
        .btn-amber:hover { background: #d97706; }
        .btn-slate { background: #334155; color: #f1f5f9; }
        .btn-slate:hover { background: #475569; }

        .sheet-container {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
            padding: 4mm 6mm;
            background: #ffffff;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .outer-border {
            border: 2px solid #0f172a;
            padding: 14px 18px;
            border-radius: 6px;
        }

        /* Traditional Algerian Republic Header */
        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 4px;
        }
        .traditional-header .country {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }
        .traditional-header .ministry {
            font-size: 11px;
            font-weight: 600;
            color: #334155;
        }

        /* 3-Column Header Meta */
        .header-meta-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 10px;
            font-size: 9.5px;
        }
        .header-meta-grid .col-school {
            text-align: start;
            flex: 1;
            line-height: 1.3;
        }
        .header-meta-grid .col-school .school-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 10.5px;
        }
        .header-meta-grid .col-code {
            text-align: center;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px 8px;
            font-family: monospace;
            font-weight: 800;
            font-size: 9.5px;
        }
        .header-meta-grid .col-date {
            text-align: end;
            flex: 1;
            font-size: 9px;
            line-height: 1.3;
        }

        /* Document Title */
        .document-title {
            text-align: center;
            margin: 10px 0 12px;
        }
        .document-title h2 {
            font-size: 14.5px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            border-bottom: 2px solid #f59e0b;
            display: inline-block;
            padding-bottom: 2px;
        }
        .document-title p {
            font-size: 9px;
            color: #475569;
            margin-top: 3px;
        }

        /* Card sections */
        .info-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .info-card.amber {
            background: #fffbeb;
            border-color: #fde68a;
        }
        .card-header-bar {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .info-card.amber .card-header-bar {
            color: #92400e;
            border-bottom-color: #fde68a;
        }
        .two-cols {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px 14px;
            font-size: 9.5px;
        }
        .two-cols .label { color: #64748b; }
        .two-cols .value { font-weight: 800; color: #0f172a; margin-inline-start: 4px; }

        /* Legal Directive Box */
        .legal-box {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 9px;
            line-height: 1.4;
            color: #334155;
            margin-bottom: 12px;
        }
        .legal-box strong { color: #0f172a; }

        /* 3 Official Signatures Block */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            border-top: 1.5px solid #0f172a;
            padding-top: 6px;
            margin-top: 8px;
            text-align: center;
        }
        .sig-col { padding: 2px 4px; }
        .sig-title { font-size: 9.5px; font-weight: 800; color: #0f172a; }
        .sig-sub { font-size: 8px; color: #64748b; margin-top: 0.5px; }
        .sig-box {
            height: 48px;
            border-bottom: 1px dashed #94a3b8;
            margin-top: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sig-stamp-hint {
            font-size: 8px;
            color: #94a3b8;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1px 8px;
        }

        .footer-note {
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 6px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                max-width: 100% !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Floating Action Bar --}}
    <div class="no-print">
        <div class="title-group">
            <span style="font-size: 16px;">📋</span>
            <div>
                <strong>{{ __('تكليف رسمي بمهمة استخلاف بيداغوجي') }}</strong>
                <div style="font-size: 9.5px; color: #94a3b8;">{{ $substituteTeacher->name }} — {{ $classroom?->name }} ({{ $mandateCode }})</div>
            </div>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-amber">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة التكليف') }}
            </button>
            <button onclick="window.history.back()" class="btn btn-slate">
                {{ __('رجوع') }}
            </button>
        </div>
    </div>

    <div class="sheet-container">
        <div class="outer-border">

            {{-- 1. Official Republic Header --}}
            <div class="traditional-header">
                <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
                <div class="ministry">{{ __('وزارة التربية الوطنية') }}</div>
            </div>

            {{-- 2. 3-Column Metadata Strip --}}
            <div class="header-meta-grid">
                <div class="col-school">
                    <div>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</div>
                    <div class="school-name">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</div>
                </div>
                <div class="col-code">{{ $mandateCode }}</div>
                <div class="col-date">
                    <div>{{ __('السنة الدراسية') }}: <strong style="font-family: monospace;">{{ $year }}</strong></div>
                    <div>{{ __('حرر بتاريخ') }}: <span style="font-family: monospace;">{{ now()->format('d/m/Y') }}</span></div>
                </div>
            </div>

            {{-- 3. Document Title --}}
            <div class="document-title">
                <h2>{{ __('استدعاء وتكليف بمهمة استخلاف بيداغوجي') }}</h2>
                <p>{{ __('ضماناً للاستمرارية البيداغوجية وحفاظاً على زمن التمدرس لتلاميذ المؤسسة') }}</p>
            </div>

            {{-- 4. Mandate Details Card --}}
            <div class="info-card">
                <div class="card-header-bar">📌 {{ __('بيانات الأستاذ المستخلف والمهمة المسندة') }}</div>
                <div class="two-cols">
                    <div>
                        <span class="label">{{ __('الأستاذ المكلف بالاستخلاف') }}:</span>
                        <span class="value" style="font-size: 11px;">{{ $substituteTeacher->name }}</span>
                    </div>
                    <div>
                        <span class="label">{{ __('تخصص الأستاذ المستخلف') }}:</span>
                        <span class="value">{{ $substituteTeacher->primarySubject?->name ?? __('أستاذ التعليم المتوسط') }}</span>
                    </div>
                </div>
            </div>

            {{-- 5. Absence Context Card --}}
            <div class="info-card amber">
                <div class="card-header-bar">⏱️ {{ __('بيانات الحصة والشغور البيداغوجي') }}</div>
                <div class="two-cols">
                    <div>
                        <span class="label">{{ __('الأستاذ الأصلي المتغيب') }}:</span>
                        <span class="value">{{ $absentTeacher?->name }}</span>
                    </div>
                    <div>
                        <span class="label">{{ __('سبب الغياب المعتمد') }}:</span>
                        <span class="value">{{ $absence->reason->label() }}</span>
                        @if ($absence->reference_number)
                            <span style="font-family: monospace; font-size: 8.5px; color: #64748b;">({{ $absence->reference_number }})</span>
                        @endif
                    </div>
                    <div>
                        <span class="label">{{ __('تاريخ الحصة وتوقيتها') }}:</span>
                        <span class="value" style="font-family: monospace;">{{ $log->date?->format('d/m/Y') }}</span>
                        <span style="font-family: monospace; font-size: 8.5px; color: #64748b;">({{ \Carbon\Carbon::parse($schedule?->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule?->end_time)->format('H:i') }})</span>
                    </div>
                    <div>
                        <span class="label">{{ __('القسم المستهدف') }}:</span>
                        <span class="value" style="background: #fef3c7; padding: 1px 6px; border-radius: 3px;">{{ $classroom?->name }} @if ($schedule?->group) ({{ $schedule->group->name }}) @endif</span>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <span class="label">{{ __('المادة المقررة') }}:</span>
                        <span class="value">{{ $subject?->name }}</span>
                    </div>
                </div>
            </div>

            {{-- 6. Observations --}}
            @if ($log->observations)
                <div class="info-card">
                    <div class="card-header-bar">📝 {{ __('التعليمات والتوجيهات البيداغوجية للحصة') }}</div>
                    <div style="font-size: 9px; color: #334155; line-height: 1.4; white-space: pre-line;">{{ $log->observations }}</div>
                </div>
            @endif

            {{-- 7. Legal Directive Box --}}
            <div class="legal-box">
                <p><strong>⚖️ {{ __('تذكير بالواجبات البيداغوجية والتنظيمية:') }}</strong> {{ __('بناءً على هذا التكليف، يُرجى من السيد(ة) الأستاذ(ة) المستخلف(ة) الالتحاق بالقسم في الموعد المحدد لتسيير الحصة، مراقبة انضباط وحضور التلاميذ، وتدوين موضوع الحصة والأنشطة المنجزة في دفتر النصوص الرسمي للقسم.') }}</p>
            </div>

            {{-- 8. Official Tripartite Signatures --}}
            <div class="signatures-grid">
                <div class="sig-col">
                    <div class="sig-title">{{ __('الأستاذ المكلف بالاستخلاف') }}</div>
                    <div class="sig-sub">{{ __('تأشيرة الاستلام والموافقة') }}</div>
                    <div class="sig-box">
                        <span class="sig-stamp-hint">({{ __('التوقيع والموافقة') }})</span>
                    </div>
                </div>

                <div class="sig-col">
                    <div class="sig-title">{{ __('مستشار التربية / ناظر الدروس') }}</div>
                    <div class="sig-sub">{{ __('المعاينة والتسجيل بسجل المداومة') }}</div>
                    <div class="sig-box">
                        <span class="sig-stamp-hint">({{ __('التأشيرة والختم') }})</span>
                    </div>
                </div>

                <div class="sig-col">
                    <div class="sig-title">{{ __('مدير المؤسسة') }}</div>
                    <div class="sig-sub">{{ __('المصادقة والختم الرسمي') }}</div>
                    <div class="sig-box">
                        <span class="sig-stamp-hint">({{ __('الختم الرسمي للمؤسسة') }})</span>
                    </div>
                </div>
            </div>

            <div class="footer-note">
                {{ __('منظومة سجل الرقمية لإدارة المتوسطة') }} · {{ $mandateCode }}
            </div>

        </div>
    </div>

</body>
</html>
