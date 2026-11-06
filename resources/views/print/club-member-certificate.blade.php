<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('شهادة تقدير ومشاركة') }} — {{ $student->name }} — {{ $club->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Amiri:wght@400;700;900&family=Reem+Kufi:wght@600;700&display=swap" rel="stylesheet">

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
            background: #f1f5f9;
            color: #0f172a;
            font-size: 12px;
            direction: rtl;
        }

        .cert-outer-frame {
            max-width: 281mm;
            height: 194mm;
            margin: 0 auto;
            background: #ffffff;
            border: 12px solid #064e3b;
            padding: 6px;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .cert-inner-frame {
            border: 2.5px solid #d97706;
            height: 100%;
            padding: 18px 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            background: radial-gradient(circle at center, #ffffff 60%, #fbfdfc 100%);
        }

        /* Corner decorations */
        .corner {
            position: absolute;
            width: 24px;
            height: 24px;
            border-color: #059669;
            border-style: solid;
        }
        .corner-tl { top: 4px; right: 4px; border-width: 3px 3px 0 0; }
        .corner-tr { top: 4px; left: 4px; border-width: 3px 0 0 3px; }
        .corner-bl { bottom: 4px; right: 4px; border-width: 0 3px 3px 0; }
        .corner-br { bottom: 4px; left: 4px; border-width: 0 0 3px 3px; }

        .cert-header {
            text-align: center;
        }

        .republic-name {
            font-family: 'Amiri', serif;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .ministry-name {
            font-size: 12.5px;
            font-weight: 700;
            color: #047857;
            margin-bottom: 4px;
        }

        .school-info {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #475569;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .cert-title-container {
            text-align: center;
            margin: 6px 0 12px 0;
        }

        .cert-main-title {
            font-family: 'Amiri', 'Cairo', serif;
            font-size: 28px;
            font-weight: 900;
            color: #064e3b;
            letter-spacing: 1px;
            line-height: 1.2;
            text-shadow: 1px 1px 0 #fef3c7;
        }

        .cert-sub-title {
            font-size: 12px;
            font-weight: 700;
            color: #d97706;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .cert-body {
            text-align: center;
            line-height: 1.8;
            font-size: 14px;
            color: #1e293b;
            padding: 0 20px;
        }

        .student-highlight {
            font-family: 'Amiri', serif;
            font-size: 24px;
            font-weight: 900;
            color: #047857;
            display: inline-block;
            margin: 0 4px;
            border-bottom: 2px solid #a7f3d0;
            padding: 0 12px;
        }

        .club-highlight {
            font-weight: 800;
            color: #d97706;
            font-size: 15px;
        }

        .role-highlight {
            font-weight: 800;
            color: #0284c7;
            font-size: 14px;
        }

        .classroom-highlight {
            font-weight: 700;
            color: #334155;
        }

        .praise-text {
            font-size: 12.5px;
            color: #475569;
            font-style: italic;
            margin-top: 6px;
        }

        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 10px;
            padding-top: 10px;
        }

        .meta-stamp {
            text-align: right;
            font-size: 10px;
            color: #64748b;
        }

        .signatures-row {
            display: flex;
            gap: 60px;
        }

        .sig-container {
            text-align: center;
            min-width: 170px;
        }

        .sig-container .title {
            font-size: 12px;
            font-weight: 800;
            color: #064e3b;
            margin-bottom: 30px;
        }

        .sig-container .name {
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
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
            .cert-outer-frame { border-width: 10px !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">
        <span>🖨️</span> {{ __('طباعة الشهادة (A4 عرضي)') }}
    </button>

    <div class="cert-outer-frame">
        <div class="cert-inner-frame">
            <div class="corner corner-tl"></div>
            <div class="corner corner-tr"></div>
            <div class="corner corner-bl"></div>
            <div class="corner corner-br"></div>

            {{-- Header --}}
            <div class="cert-header">
                <div class="republic-name">الجمهورية الجزائرية الديمقراطية الشعبية</div>
                <div class="ministry-name">وزارة التربية الوطنية</div>
                <div class="school-info">
                    <div>مديرية التربية لولاية: <strong>{{ $school->state_name ?? 'الوادي' }}</strong></div>
                    <div>المؤسسة: <strong>{{ $school->school_name ?? 'متوسطة الشهيد العربي بن مهيدي' }}</strong></div>
                    <div>الموسم الدراسي: <strong>{{ $club->academic_year }}</strong></div>
                </div>
            </div>

            {{-- Title --}}
            <div class="cert-title-container">
                <div class="cert-main-title">شهادة تقـدير ومـشاركـة</div>
                <div class="cert-sub-title">في أنشطة النادي المدرسي والحياة المدرسية</div>
            </div>

            {{-- Body --}}
            <div class="cert-body">
                <div>
                    {{ __('تشهد إدارة المؤسسة وهيئة الإشراف التربوي بأن التلميذ(ة):') }}
                </div>

                <div class="student-highlight">
                    {{ $student->name }}
                </div>

                <div>
                    {{ __('المتمدرس(ة) بالقسم:') }}
                    <span class="classroom-highlight">«{{ $student->group?->classroom?->name ?? __('غير محدد') }}»</span>
                    {{ __('قد شارك(ت) بفاعلية وتميز بصفة:') }}
                    <span class="role-highlight">[{{ $membership->role?->label() }}]</span>
                </div>

                <div>
                    {{ __('ضمن فعاليات وورشات:') }}
                    <span class="club-highlight">«{{ $club->name }}»</span>
                    {{ __('خلال الموسم الدراسي') }}
                    <strong>{{ $club->academic_year }}</strong>
                </div>

                <div class="praise-text">
                    {{ __('تقديراً لانضباطه(ا) ومواظبته(ا)، حسه(ا) الإبداعي وروح المبادرة والعمل الجماعي التي أثرت فضاء الحياة المدرسية بالمؤسسة.') }}
                </div>
            </div>

            {{-- Footer & Signatures --}}
            <div class="cert-footer">
                <div class="meta-stamp">
                    <div>{{ __('الرقم المرجعي') }}: <strong style="font-family: monospace;">{{ $membership->certificateReferenceNumber() }}</strong></div>
                    <div>{{ __('تاريخ التسليم') }}: <strong style="font-family: monospace;">{{ now()->format('d/m/Y') }}</strong></div>
                </div>

                <div class="signatures-row">
                    <div class="sig-container">
                        <div class="title">{{ __('الأستاذ المشرف على النادي') }}</div>
                        <div class="name">{{ $club->leadTeacher?->name }}</div>
                    </div>

                    <div class="sig-container">
                        <div class="title">{{ __('مدير المتوسطة') }}</div>
                        <div class="name">{{ $school->principal_name ?? 'الأستاذ عبد الحميد بن عمر' }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>

