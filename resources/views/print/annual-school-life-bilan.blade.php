<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('الحصيلة السنوية الشاملة للحياة المدرسية والنوادي التربوية') }} — {{ $academic_year }}</title>

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
            size: A4 landscape;
            margin: 8mm;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.4;
            direction: rtl;
        }

        .bilan-container {
            max-width: 281mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            padding: 8mm;
            border-radius: 4px;
        }

        .official-header {
            text-align: center;
            border-bottom: 2px solid #047857;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .official-republic {
            font-family: 'Amiri', serif;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .official-ministry {
            font-family: 'Cairo', sans-serif;
            font-size: 11px;
            font-weight: 700;
            color: #047857;
        }

        .header-meta {
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: #475569;
            margin-top: 3px;
            padding: 0 4px;
        }

        .document-title {
            text-align: center;
            background: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            border-radius: 6px;
            padding: 6px 10px;
            margin: 8px 0;
        }

        .document-title h1 {
            font-size: 13.5px;
            font-weight: 900;
            color: #065f46;
        }

        .document-title p {
            font-size: 9.5px;
            color: #047857;
            font-weight: 600;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
            margin-bottom: 10px;
        }

        .kpi-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px;
            text-align: center;
            background: #f8fafc;
        }

        .kpi-val {
            font-size: 16px;
            font-weight: 900;
            color: #065f46;
            font-family: monospace;
            line-height: 1.1;
        }

        .kpi-lbl {
            font-size: 8.5px;
            font-weight: 700;
            color: #475569;
            margin-top: 2px;
        }

        .section-title {
            font-size: 10.5px;
            font-weight: 800;
            color: #065f46;
            border-right: 3px solid #059669;
            padding-right: 6px;
            margin: 8px 0 4px 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 8px;
        }

        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: right;
        }

        .data-table th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 800;
            text-align: center;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 10px;
            padding-top: 6px;
        }

        .sig-block {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 6px;
            text-align: center;
            min-height: 75px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fcfcfd;
        }

        .sig-block .title {
            font-weight: 800;
            font-size: 10px;
            color: #065f46;
        }

        .sig-block .name {
            font-size: 9.5px;
            color: #334155;
            font-weight: 600;
        }

        .sig-block .sub {
            font-size: 8.5px;
            color: #94a3b8;
            font-style: italic;
        }

        .footer-note {
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 8.5px;
            color: #64748b;
        }

        .print-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            background: #047857;
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 999;
        }

        @media print {
            .print-btn { display: none !important; }
            body { background: white !important; }
            .bilan-container { border: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">
        <span>🖨️</span> {{ __('طباعة التقرير السنوي (A4 عرضي)') }}
    </button>

    <div class="bilan-container">
        {{-- National Header --}}
        <div class="official-header">
            <div class="official-republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
            <div class="official-ministry">وزارة التربية الوطنية</div>
            <div class="header-meta">
                <div>مديرية التربية لولاية: <strong>{{ $school->state_name ?? 'الوادي' }}</strong></div>
                <div>المؤسسة: <strong>{{ $school->school_name ?? 'متوسطة الشهيد العربي بن مهيدي' }}</strong></div>
                <div>الموسم الدراسي: <strong>{{ $academic_year }}</strong></div>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="document-title">
            <h1>{{ __('الحصيلة السنوية الرسمية للحياة المدرسية والنوادي التربوية اللاصفية') }}</h1>
            <p>{{ __('وثيقة إدارية بيداغوجية موجهة لمجلس التربية والتسيير ومفتشية التعليم المتوسط للإدارة') }}</p>
        </div>

        {{-- KPIs Summary --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-val">{{ $total_clubs }}</div>
                <div class="kpi-lbl">{{ __('إجمالي النوادي المعتمدة') }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val" style="color: #059669;">{{ $active_clubs }}</div>
                <div class="kpi-lbl">{{ __('النوادي النشطة فعلياً') }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val" style="color: #0284c7;">{{ $total_enrolled_students }}</div>
                <div class="kpi-lbl">{{ __('التلاميذ المنخرطون') }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val" style="color: #d97706;">{{ $bureau_members_count }}</div>
                <div class="kpi-lbl">{{ __('أعضاء المكاتب التنفيذية') }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val" style="color: #7c3aed;">{{ $completed_activities }}</div>
                <div class="kpi-lbl">{{ __('الأنشطة والورشات المنجزة') }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val" style="color: #dc2626;">{{ $total_attendees }}</div>
                <div class="kpi-lbl">{{ __('المستفيدون من الفعاليات') }}</div>
            </div>
        </div>

        {{-- Section 1: Clubs Matrix --}}
        <div class="section-title">
            <span>🏷️</span> {{ __('أولاً: جدول تفصيلي للنوادي المدرسية المعتمدة وهيئات الإشراف') }}
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 10%;">{{ __('الرمز') }}</th>
                    <th>{{ __('اسم النادي المدرسي') }}</th>
                    <th style="width: 14%;">{{ __('التصنيف / المجال') }}</th>
                    <th style="width: 16%;">{{ __('الأستاذ المنسق والمشرف') }}</th>
                    <th style="width: 16%;">{{ __('يوم وفضاء النشاط') }}</th>
                    <th style="width: 10%;">{{ __('المنخرطون') }}</th>
                    <th style="width: 9%;">{{ __('الأنشطة') }}</th>
                    <th style="width: 8%;">{{ __('الحالة') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clubs as $idx => $c)
                    <tr>
                        <td style="text-align: center; font-weight: bold;">{{ $loop->iteration }}</td>
                        <td style="text-align: center; font-family: monospace; font-weight: bold;">{{ $c->code ?? '—' }}</td>
                        <td><strong>{{ $c->name }}</strong></td>
                        <td style="text-align: center;">{{ $c->type?->label() }}</td>
                        <td>{{ $c->leadTeacher?->name }} ({{ $c->leadTeacher?->primarySubject?->name ?? __('أستاذ') }})</td>
                        <td>{{ $c->formattedMeetingSchedule() }}</td>
                        <td style="text-align: center; font-family: monospace;">{{ $c->activeMembersCount() }} / {{ $c->max_members }}</td>
                        <td style="text-align: center; font-family: monospace; font-weight: bold;">{{ $c->completedActivitiesCount() }}</td>
                        <td style="text-align: center;">
                            <span style="font-size: 8.5px; font-weight: bold; color: {{ $c->status->value === 'active' ? '#047857' : '#b45309' }};">
                                {{ $c->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: #64748b; padding: 6px;">
                            {{ __('لا توجد نوادي مسجلة لهذا الموسم الدراسي.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Signatures Grid --}}
        <div class="signatures-grid">
            <div class="sig-block">
                <div>
                    <div class="title">{{ __('منسق الأنشطة الثقافية والرياضية') }}</div>
                    <div class="name">{{ __('أستاذ منسق الحياة المدرسية') }}</div>
                </div>
                <div class="sub">({{ __('التوقيع والتأشيرة') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('مستشار التربية الرئيسي') }}</div>
                    <div class="name">{{ __('مصلحة شؤون التلاميذ والنظام التربوي') }}</div>
                </div>
                <div class="sub">({{ __('التأشيرة والمصادقة') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('مدير المتوسطة') }}</div>
                    <div class="name">{{ $school->principal_name ?? 'الأستاذ عبد الحميد بن عمر' }}</div>
                </div>
                <div class="sub">({{ __('الختم والتوقيع الرسمي للمؤسسة') }})</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer-note">
            <div>
                <strong>{{ __('نسخة موجهة إلى') }}:</strong>
                {{ __('أمانة المدير — مجلس التربية والتسيير — مفتشية التعليم المتوسط للإدارة والمتابعة') }}
            </div>
            <div>{{ __('سجّل — المنظومة الرقمية الشاملة للإدارة والرقمنة المدرسية') }}</div>
        </div>
    </div>

</body>
</html>
