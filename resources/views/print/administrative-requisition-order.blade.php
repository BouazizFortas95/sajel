@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $school ?? \App\Models\SchoolSetting::current();
    $year = $school?->academic_year ?? '2026/2027';
    $reqNumber = $absence->requisitionFormatted();
    $absentTeacher = $absentTeacher ?? $absence->teacher;
    $substituteTeacher = $substituteTeacher ?? $log->substituteTeacher;
    $schedule = $schedule ?? $log->schedule;
    $classroom = $classroom ?? $schedule?->resolvedClassroom();
    $subject = $subject ?? $schedule?->subject;
    $logDate = $log->log_date ? \Carbon\Carbon::parse($log->log_date) : now();
@endphp

<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('قرار تسخير إداري رسمي') }} — {{ $substituteTeacher?->name }} — {{ $reqNumber }}</title>

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
            margin: 10mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.5;
            padding: 20px;
        }

        .no-print {
            max-width: 190mm;
            margin: 0 auto 16px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .btn {
            display: inline-block;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .btn-dark { background: #1e293b; color: #ffffff; }
        .btn-indigo { background: #4338ca; color: #ffffff; }

        .sheet {
            max-width: 190mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm;
            border: 2px solid #4338ca;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            page-break-inside: avoid !important;
        }

        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 12px;
        }
        .traditional-header .republic {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .traditional-header .ministry {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }

        .identity-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #c7d2fe;
            padding-bottom: 8px;
            margin-bottom: 16px;
            font-size: 10.5px;
        }
        .identity-row .col-meta {
            text-align: end;
            font-family: monospace;
        }

        .document-title {
            text-align: center;
            margin: 16px 0 18px 0;
        }
        .document-title h1 {
            display: inline-block;
            font-size: 17px;
            font-weight: 900;
            color: #3730a3;
            border-bottom: 2px solid #3730a3;
            padding-bottom: 4px;
        }
        .document-title .subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 600;
        }

        .legal-references {
            background: #f8fafc;
            border: 1px solid #e0e7ff;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .order-box {
            font-size: 12px;
            line-height: 1.8;
            color: #1e293b;
            margin-bottom: 16px;
            text-align: justify;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 11px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
        }
        .data-table th {
            width: 32%;
            background: #eef2ff;
            color: #3730a3;
            font-weight: 700;
            text-align: start;
        }
        .data-table td {
            color: #0f172a;
            font-weight: 600;
        }

        .obligations-notice {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 10px;
            color: #166534;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 24px;
            text-align: center;
        }
        .sig-block {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sig-block .title {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
        }
        .sig-block .name {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            margin-top: 3px;
        }
        .sig-block .sub {
            font-size: 8.5px;
            color: #94a3b8;
        }

        .footer-note {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body { padding: 0 !important; background: #ffffff !important; }
            .no-print { display: none !important; }
            .sheet { border: 2px solid #3730a3 !important; box-shadow: none !important; padding: 8mm !important; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 8px;">
            <strong style="color: #3730a3;">{{ __('معاينة قرار التسخير الإداري الرسمي') }}</strong>
            <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: #e0e7ff; color: #3730a3;">
                {{ $reqNumber }}
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-dark">
                🖨️ {{ __('طباعة القرار (A4)') }}
            </button>
            <a href="{{ route('print.teacher-absence.requisition-order.pdf', ['locale' => app()->getLocale(), 'absence' => $absence->id, 'log' => $log->id]) }}" class="btn btn-indigo">
                📥 {{ __('تحميل PDF رسمي') }}
            </a>
        </div>
    </div>

    <div class="sheet">
        {{-- Republic Header --}}
        <div class="traditional-header">
            <div class="republic">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }}</div>
        </div>

        {{-- Establishment Identification --}}
        <div class="identity-row">
            <div>
                <div><strong>{{ __('مديرية التربية لولاية') }}:</strong> {{ $school?->directorate_name ?? '....................................' }}</div>
                <div><strong>{{ __('مفتشية التعليم المتوسط لمقاطعة') }}:</strong> {{ $school?->inspection_district ?? '....................................' }}</div>
                <div><strong>{{ __('المؤسسة') }}:</strong> {{ $school?->school_name ?? 'متوسطة ساجل النموذجية' }}</div>
            </div>
            <div class="col-meta" dir="ltr">
                <div><strong>Réf:</strong> {{ $reqNumber }}</div>
                <div><strong>Date:</strong> {{ $formatDate(now()) }}</div>
                <div><strong>Année:</strong> {{ $year }}</div>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="document-title">
            <h1>{{ __('قـرار تـسـخـيـر إداري لـتـغـطـيـة حـصـة تـعـلـيـمـيـة') }}</h1>
            <div class="subtitle">Ordre de Réquisition Administrative de Service Pédagogique</div>
        </div>

        {{-- Legal Foundations --}}
        <div class="legal-references">
            <strong>{{ __('بناءً على النصوص التشريعية المعمول بها') }}:</strong><br>
            • {{ __('القانون التوجيهي للتربية الوطنية رقم 08-04 المؤرخ في 23 جانفي 2008، لاسيما المادة 22 منه (ضمان التمدرس واستمرارية الخدمة العمومية).') }}<br>
            • {{ __('المرسوم التنفيذي رقم 08-315 المتضمن القانون الأساسي الخاص بأسلاك التربية الوطنية، لاسيما المهام البيداغوجية والإدارية.') }}<br>
            • {{ __('مقتضيات ضرورة المصلحة لضمان سلامة وسير تمدرس التلاميذ ومنع الحصص الشاغرة.') }}
        </div>

        {{-- Operative Requisition Text --}}
        <div class="order-box">
            {{ __('يُقرر مدير المؤسسة بموجب هذا الأمر ما يلي:') }}<br>
            <strong>{{ __('المادة الأولى') }}:</strong>
            {{ __('يُسخر السيد(ة)') }} <strong>{{ $substituteTeacher?->name }}</strong>، {{ __('أستاذ(ة) التعليم المتوسط لمادة') }} <strong>{{ $substituteTeacher?->primarySubject?->name ?? ($subject?->name ?? '—') }}</strong>، {{ __('لضمان الحصة التعليمية الشاغرة المحددة في الجدول أدناه، والتعويض عن غياب الأستاذ(ة)') }} <strong>{{ $absentTeacher?->name }}</strong>.
        </div>

        {{-- Requisition Session Specification Table --}}
        <table class="data-table">
            <tbody>
                <tr>
                    <th>{{ __('الأستاذ المعني بالتسخير') }}</th>
                    <td><strong>{{ $substituteTeacher?->name }}</strong></td>
                </tr>
                <tr>
                    <th>{{ __('الأستاذ الغائب المستخلف') }}</th>
                    <td>{{ $absentTeacher?->name }} ({{ $absence->reason?->label() }})</td>
                </tr>
                <tr>
                    <th>{{ __('الفوج التربوي / القسم') }}</th>
                    <td><strong>{{ $classroom?->name ?? '—' }}</strong></td>
                </tr>
                <tr>
                    <th>{{ __('المادة التعليمية') }}</th>
                    <td>{{ $subject?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>{{ __('تاريخ وتوقيت الحصة') }}</th>
                    <td>
                        <strong style="color: #3730a3;">
                            {{ $formatDate($logDate) }}
                            @if ($schedule?->start_time)
                                ({{ __('من') }} {{ substr($schedule->start_time, 0, 5) }} {{ __('إلى') }} {{ substr($schedule->end_time, 0, 5) }})
                            @endif
                        </strong>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('المبرر الإداري للتسخير') }}</th>
                    <td>{{ $absence->requisition_justification ?: __('ضرورة المصلحة، حماية حق التلاميذ في التمدرس المستمر، واستدراك الحصص الشاغرة') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Administrative Instructions --}}
        <div class="obligations-notice">
            <strong>{{ __('الآثار والواجبات الإدارية') }}:</strong>
            {{ __('يُعد هذا القرار ملزماً إدارياً، وتُدون هذه الحصة رسمياً في دفتر النصوص بصفة "حصة استخلاف بتسخير إداري"، وتُحسب ساعاتها لصالح الأستاذ المسخر في سجل الخدمات الاستثنائية والتعويضات البيداغوجية المعمول بها.') }}
        </div>

        {{-- Tri-party Signatures --}}
        <div class="signatures-grid">
            <div class="sig-block">
                <div>
                    <div class="title">{{ __('الأستاذ المسخر (بالتبليغ)') }}</div>
                    <div class="name">{{ $substituteTeacher?->name }}</div>
                </div>
                <div class="sub">({{ __('التوقيع وتاريخ التبليغ') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('مستشار التربية / ناظر المؤسسة') }}</div>
                    <div class="name">{{ __('التأشيرة ومتابعة دفتر النصوص') }}</div>
                </div>
                <div class="sub">({{ __('التأشيرة للمتابعة البيداغوجية') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('مدير المتوسطة') }}</div>
                    <div class="name">{{ $school?->principal_name ?? 'الأستاذ عبد الحميد بن عمر' }}</div>
                </div>
                <div class="sub">({{ __('التوقيع والختم الإداري الرسمي') }})</div>
            </div>
        </div>

        {{-- Footer Carbon Copies --}}
        <div class="footer-note">
            <div>
                <strong>{{ __('نسخة للإعلام') }}:</strong>
                {{ __('السيد مفتش التعليم المتوسط للمقاطعة') }} — {{ __('الأستاذ المسخر') }} — {{ __('الملف الإداري') }}
            </div>
            <div>{{ __('سجّل — المنظومة البيداغوجية والإدارية') }}</div>
        </div>
    </div>

</body>
</html>
