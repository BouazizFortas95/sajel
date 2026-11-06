@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $school ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $startDateStr = $formatDate($menu->week_start_date);
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('البرنامج الأسبوعي للوجبات الغذائية') }} — {{ __('الأسبوع ') }}{{ $menu->week_number }}</title>

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
            margin: 6mm 8mm 6mm 8mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            -webkit-font-smoothing: antialiased;
        }

        .no-print {
            max-width: 200mm;
            margin: 6px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .btn-amber { background: #f59e0b; color: #0f172a; }
        .btn-amber:hover { background: #d97706; }
        .btn-slate { background: #334155; color: #f1f5f9; }

        .sheet-container {
            width: 100%;
            max-width: 200mm;
            margin: 0 auto;
            padding: 2mm 3mm;
            background: #ffffff;
        }

        /* Traditional Header */
        .traditional-header {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 3px;
            line-height: 1.2;
        }
        .traditional-header .country {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
        }
        .traditional-header .ministry {
            font-size: 9.5px;
            font-weight: 600;
            color: #334155;
        }

        /* Meta Table */
        .header-meta-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 3px;
            margin-bottom: 4px;
            font-size: 8.5px;
        }
        .header-meta-col-school {
            display: table-cell !important;
            width: 33% !important;
            text-align: start;
            vertical-align: middle;
            line-height: 1.3;
        }
        .header-meta-col-school .school-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 9.5px;
        }
        .header-meta-col-title {
            display: table-cell !important;
            width: 44% !important;
            text-align: center;
            vertical-align: middle;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 6px;
        }
        .header-meta-col-title .main-title {
            font-size: 11.5px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.2;
        }
        .header-meta-col-title .sub-title {
            font-size: 9px;
            font-weight: 700;
            color: #92400e;
            margin-top: 1px;
        }
        .header-meta-col-date {
            display: table-cell !important;
            width: 23% !important;
            text-align: end;
            vertical-align: middle;
            font-size: 8px;
            line-height: 1.3;
        }

        /* Day Menu Box */
        .day-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 3.5px;
            overflow: hidden;
        }
        .day-header {
            background: #f1f5f9;
            padding: 2.5px 6px;
            font-weight: 800;
            font-size: 8.5px;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .day-content {
            padding: 3.5px 6px;
            font-size: 8.5px;
            line-height: 1.35;
            color: #334155;
        }

        /* Signatures */
        .signatures-table {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            border-top: 1.2px solid #0f172a !important;
            padding-top: 3px !important;
            margin-top: 4px !important;
            text-align: center !important;
            page-break-inside: avoid !important;
        }
        .sig-col {
            display: table-cell !important;
            width: 33.333% !important;
            vertical-align: top !important;
            padding: 1px 4px !important;
        }
        .sig-title { font-size: 8.5px; font-weight: 800; color: #0f172a; }
        .sig-box { height: 35px; border-bottom: 1px dashed #94a3b8; margin-top: 3px; }

        .footer-note {
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 3px;
        }

        @media print {
            .no-print { display: none !important; }
            .sheet-container { max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

    @if (! ($inlineCss ?? false))
        <div class="no-print">
            <div>
                <strong>{{ __('الجدول الأسبوعي للوجبات الغذائية بالمطعم المدرسي') }}</strong>
                <span style="font-size: 9.5px; color: #94a3b8; margin-inline-start: 8px;">{{ __('الأسبوع ') }}{{ $menu->week_number }} · {{ $yearStr }}</span>
            </div>
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-amber">{{ __('طباعة المعلقة') }}</button>
                <button onclick="window.history.back()" class="btn btn-slate">{{ __('رجوع') }}</button>
            </div>
        </div>
    @endif

    <div class="sheet-container">
        {{-- Republic Header --}}
        <div class="traditional-header">
            <div class="country">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</div>
            <div class="ministry">{{ __('وزارة التربية الوطنية') }} — {{ __('المصالح الاقتصادية والتغذية المدرسية') }}</div>
        </div>

        {{-- Meta Table --}}
        <div class="header-meta-table">
            <div class="header-meta-col-school">
                <div>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</div>
                <div class="school-name">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</div>
                <div>{{ __('المطعم المدرسي ونظام نصف الداخلي') }}</div>
            </div>
            <div class="header-meta-col-title">
                <div class="main-title">{{ __('البرنامج الأسبوعي للوجبات الغذائية') }}</div>
                <div class="sub-title">{{ __('الأسبوع رقم:') }} {{ $menu->week_number }} · {{ __('الموسم:') }} {{ $yearStr }}</div>
            </div>
            <div class="header-meta-col-date">
                <div>{{ __('بداية من:') }} <strong>{{ $startDateStr }}</strong></div>
                <div>{{ __('القيمة الطاقوية:') }} ~<strong>{{ $menu->nutrition_calories_approx }}</strong> {{ __('سعرة') }}</div>
                <div style="color: #047857; font-weight: 700;">{{ __('معتمد من الصحة المدرسية') }} ✓</div>
            </div>
        </div>

        {{-- Days of Week --}}
        <div class="day-box">
            <div class="day-header">
                <span>🗓️ {{ __('يوم الأحد (Dimanche)') }}</span>
                <span style="font-weight: normal; color: #64748b;">{{ __('وجبة متوازنة كاملة') }}</span>
            </div>
            <div class="day-content">
                {{ $menu->sunday_menu ?? __('طبق رئيسي: أرز مفور بالخضر ولحم البقر الطازج · سلطة خضراء متنوعة بالطماطم · فاكهة موسمية (برتقال) · خبز عادي نصف مدعم.') }}
            </div>
        </div>

        <div class="day-box">
            <div class="day-header">
                <span>🗓️ {{ __('يوم الاثنين (Lundi)') }}</span>
                <span style="font-weight: normal; color: #64748b;">{{ __('بروتينات وألياف') }}</span>
            </div>
            <div class="day-content">
                {{ $menu->monday_menu ?? __('طبق رئيسي: عدس يابس مطهو بالخضار والدجاج · بيض مسلوق طازج · جبن أبيض مثلثات · فاكهة طازجة (تفاح) · خبز عادي.') }}
            </div>
        </div>

        <div class="day-box">
            <div class="day-header">
                <span>🗓️ {{ __('يوم الثلاثاء (Mardi)') }}</span>
                <span style="font-weight: normal; color: #64748b;">{{ __('طبق تقليدي متكامل') }}</span>
            </div>
            <div class="day-content">
                {{ $menu->tuesday_menu ?? __('طبق رئيسي: كسكسي جزائري بمرق الخضر المشكلة ولحم الدجاج · سلطة شوندر (براف) · ياغورت أو تحلية الحليب · خبز عادي.') }}
            </div>
        </div>

        <div class="day-box">
            <div class="day-header">
                <span>🗓️ {{ __('يوم الأربعاء (Mercredi)') }}</span>
                <span style="font-weight: normal; color: #64748b;">{{ __('طاقة ونشاط') }}</span>
            </div>
            <div class="day-content">
                {{ $menu->wednesday_menu ?? __('طبق رئيسي: مقرونة غراتان بالصلصة الحمراء واللحم المفروم والجبن · سلطة خضراء بالخيار · فاكهة موسمية · خبز عادي.') }}
            </div>
        </div>

        <div class="day-box">
            <div class="day-header">
                <span>🗓️ {{ __('يوم الخميس (Jeudi)') }}</span>
                <span style="font-weight: normal; color: #64748b;">{{ __('أوميغا وفيتامينات') }}</span>
            </div>
            <div class="day-content">
                {{ $menu->thursday_menu ?? __('طبق رئيسي: بطاطا كوشة بالسمك الطازج (سردين) أو لحم الدجاج المحمر · حساء خضار دافئ · فاكهة موسمية · خبز عادي.') }}
            </div>
        </div>

        {{-- Signatures --}}
        <div class="signatures-table">
            <div class="sig-col">
                <div class="sig-title">{{ __('طبيب وحدة الكشف والمتابعة (UDS)') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('التوازن الغذائي ومطابقة الوجبات') }}</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('المقتصد / مسير المصالح المالية') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('التموين ومطابقة المخزون والميزانية') }}</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">{{ __('مدير المؤسسة') }}</div>
                <div style="font-size: 7.5px; color: #64748b;">{{ __('المصادقة والتأشيرة الإدارية') }}</div>
                <div class="sig-box"></div>
            </div>
        </div>

        <div class="footer-note">
            {{ __('منظومة سجل الرقمية لإدارة المتوسطة — البرنامج الأسبوعي للوجبات الغذائية') }} · {{ $menu->academic_year }}
        </div>
    </div>
</body>
</html>

