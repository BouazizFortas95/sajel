<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('بطاقة الرغبات الرسمية للتوجيه - :name', ['name' => $student->full_name]) }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', 'Amiri', 'Traditional Arabic', Tahoma, Arial, sans-serif;
            color: #111827;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 12pt;
            margin: 0 0 4px 0;
            font-weight: bold;
        }
        .header h2 {
            font-size: 11pt;
            margin: 0 0 4px 0;
            font-weight: normal;
        }
        .header .meta {
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
            margin-top: 4px;
        }
        .title-box {
            text-align: center;
            border: 2px solid #111827;
            background: #f8fafc;
            padding: 8px 12px;
            margin: 12px 0;
            border-radius: 6px;
        }
        .title-box h3 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 3px 0;
            color: #0f172a;
        }
        .title-box p {
            font-size: 9.5pt;
            margin: 0;
            color: #475569;
        }
        .section-title {
            font-size: 10.5pt;
            font-weight: bold;
            background: #e2e8f0;
            padding: 4px 8px;
            border-right: 4px solid #1e293b;
            margin: 12px 0 6px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px 16px;
            font-size: 10pt;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dotted #cbd5e1;
            padding-bottom: 2px;
        }
        .info-label {
            font-weight: bold;
            color: #334155;
        }
        .info-value {
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin: 8px 0;
        }
        table, th, td {
            border: 1px solid #64748b;
        }
        th {
            background-color: #f1f5f9;
            padding: 5px 6px;
            text-align: center;
            font-weight: bold;
        }
        td {
            padding: 5px 6px;
            text-align: center;
        }
        .wishes-box {
            border: 2px solid #2563eb;
            background: #eff6ff;
            border-radius: 6px;
            padding: 10px 14px;
            margin: 10px 0;
        }
        .wishes-item {
            display: flex;
            align-items: center;
            margin: 6px 0;
            font-size: 10.5pt;
        }
        .wishes-badge {
            background: #1e40af;
            color: white;
            padding: 2px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-left: 10px;
            min-width: 80px;
            text-align: center;
        }
        .commitment-box {
            border: 1px solid #cbd5e1;
            background: #fdfefe;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 9.5pt;
            margin-top: 10px;
            line-height: 1.5;
        }
        .signatures {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            text-align: center;
            font-size: 10pt;
        }
        .sign-block {
            width: 30%;
        }
        .sign-space {
            height: 60px;
        }
        @media print {
            body { font-size: 10pt; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>الجمهورية الجزائرية الديمقراطية الشعبية</h1>
        <h2>وزارة التربية الوطنية</h2>
        <div class="meta">
            <span>{{ $school?->directorate_name ?? 'مديرية التربية الوطنية' }}</span>
            <span>{{ $school?->school_name ?? 'متوسطة سِجلّ النموذجية' }}</span>
            <span>السنة الدراسية: {{ $academic_year }}</span>
        </div>
    </div>

    <div class="title-box">
        <h3>بطاقة الرغبات للتوجيه إلى التعليم الثانوي العام والتكنولوجي</h3>
        <p>تخص تلاميذ أقسام السنة الرابعة من التعليم المتوسط (4AM) — استمارة مصلحة التوجيه المدرسي</p>
    </div>

    {{-- Student Identification --}}
    <div class="section-title">أولاً: بطاقة الهوية المدرسية للتلميذ(ة)</div>
    <div class="info-grid">
        <div class="info-row">
            <span class="info-label">اللقب والاسم:</span>
            <span class="info-value font-bold">{{ $student->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم القيد (Matricule):</span>
            <span class="info-value font-mono">{{ $student->matricule_number ?? $student->registration_number ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ ومكان الازدياد:</span>
            <span class="info-value">{{ $student->date_of_birth?->format('Y/m/d') }} @if($student->place_of_birth) بـ {{ $student->place_of_birth }} @endif</span>
        </div>
        <div class="info-row">
            <span class="info-label">القسم والفوج:</span>
            <span class="info-value">{{ $student->group?->classroom?->name ?? 'السنة الرابعة متوسط' }} @if($student->group) ({{ $student->group->name }}) @endif</span>
        </div>
        <div class="info-row">
            <span class="info-label">النظام المدرسي:</span>
            <span class="info-value">{{ $student->regimeLabel() }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الصفة بالمؤسسة:</span>
            <span class="info-value">{{ $student->repeater ? 'معيد(ة)' : 'متمدرس(ة) لأول مرة' }}</span>
        </div>
    </div>

    {{-- Academic Profile Indices --}}
    <div class="section-title">ثانياً: النتائج المدرسية وحساب مؤشرات الملمح البيداغوجي</div>
    <table>
        <thead>
            <tr>
                <th colspan="3" style="background:#dbeafe; color:#1e40af;">الملمح العلمي والتكنولوجي (Profil Scientifique)</th>
                <th colspan="4" style="background:#fef3c7; color:#92400e;">الملمح الأدبي واللغات (Profil Littéraire)</th>
                <th rowspan="2" style="background:#e2e8f0;">المعدل العام السنوي</th>
            </tr>
            <tr>
                <th>الرياضيات</th>
                <th>الفيزياء</th>
                <th>العلوم الطبيعية</th>
                <th>اللغة العربية</th>
                <th>الفرنسية</th>
                <th>الإنجليزية</th>
                <th>التاريخ والجغرافيا</th>
            </tr>
        </thead>
        <tbody>
            @php
                $subA = $profile['subject_averages'] ?? [];
                $math = collect($subA)->first(fn($s) => str_contains(mb_strtolower($s['name']), 'رياضيات'))['average'] ?? '—';
                $phys = collect($subA)->first(fn($s) => str_contains(mb_strtolower($s['name']), 'فيزياء'))['average'] ?? '—';
                $nat = collect($subA)->first(fn($s) => str_contains(mb_strtolower($s['name']), 'طبيعة') || str_contains(mb_strtolower($s['name']), 'علوم الطبيعة'))['average'] ?? '—';
                $arab = collect($subA)->first(fn($s) => str_contains(mb_strtolower($s['name']), 'عربية'))['average'] ?? '—';
                $fr = collect($subA)->first(fn($s) => str_contains(mb_strtolower($s['name']), 'فرنسية'))['average'] ?? '—';
                $eng = collect($subA)->first(fn($s) => str_contains(mb_strtolower($s['name']), 'إنجليزية') || str_contains(mb_strtolower($s['name']), 'انجليزية'))['average'] ?? '—';
                $hist = collect($subA)->first(fn($s) => str_contains(mb_strtolower($s['name']), 'تاريخ'))['average'] ?? '—';
            @endphp
            <tr>
                <td style="font-weight:bold;">{{ $math }}</td>
                <td style="font-weight:bold;">{{ $phys }}</td>
                <td style="font-weight:bold;">{{ $nat }}</td>
                <td style="font-weight:bold;">{{ $arab }}</td>
                <td style="font-weight:bold;">{{ $fr }}</td>
                <td style="font-weight:bold;">{{ $eng }}</td>
                <td style="font-weight:bold;">{{ $hist }}</td>
                <td style="font-weight:bold; font-size:11pt; background:#f8fafc;">{{ $profile['general_average'] ?? '—' }}</td>
            </tr>
            <tr style="background:#f8fafc; font-weight:bold;">
                <td colspan="3" style="color:#1e40af;">
                    معدل الملمح العلمي: <strong>{{ $profile['scientific_score'] ?? '—' }} / 20</strong>
                </td>
                <td colspan="4" style="color:#92400e;">
                    معدل الملمح الأدبي: <strong>{{ $profile['literary_score'] ?? '—' }} / 20</strong>
                </td>
                <td style="color:#0f172a; font-size:9pt;">
                    الملمح الأرجح: {{ $profile['recommended_stream']?->shortLabel() ?? 'متوازن' }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Wishes Section --}}
    <div class="section-title">ثالثاً: رغبات التلميذ(ة) وموافقة الولي الشرعي</div>
    <div class="wishes-box">
        <div class="wishes-item">
            <span class="wishes-badge">الرغبة الأولى:</span>
            <strong style="font-size:11.5pt;">{{ $orientation->first_choice?->label() ?? 'جذع مشترك علوم وتكنولوجيا' }}</strong>
        </div>
        <div class="wishes-item">
            <span class="wishes-badge" style="background:#475569;">الرغبة الثانية:</span>
            <strong style="font-size:11.5pt;">{{ $orientation->second_choice?->label() ?? 'جذع مشترك آداب' }}</strong>
        </div>
    </div>

    <div class="commitment-box">
        <strong>تعهد الولي الشرعي:</strong>
        أنا الموقع أسفله ولي التلميذ(ة)، أصرح بعد اطلاعي على النتائج الدراسية والملمح البيداغوجي لابني/ابنتي، بأن الرغبات المعبر عنها أعلاه تعكس اختياره الحقيقي، وألتزم بقرار مجلس القبول والتوجيه المؤسس على المعايير الوزارية الرسمية، مع احتفاظي بالحق في الطعن القانوني في الآجال المحددة.
        <div style="margin-top:6px; font-weight:bold;">
            موافقة الولي: {{ $orientation->guardian_approved ? '✓ مصادق عليها وموقعة رسمياً' : '⏳ قيد التوقيع والمصادقة' }}
        </div>
    </div>

    {{-- Pre-Orientation Council Opinion --}}
    <div class="section-title">رابعاً: رأي مجلس التوجيه المسبق (نهاية الفصل الثاني)</div>
    <div style="border:1px solid #cbd5e1; padding:8px 12px; font-size:9.5pt; border-radius:4px; background:#fafafa;">
        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span><strong>اقتراح المجلس المسبق:</strong> {{ $orientation->pre_orientation_choice?->label() ?? 'قيد المداولة في الفصل الثاني' }}</span>
            <span><strong>التاريخ:</strong> نهاية الفصل الثاني</span>
        </div>
        <div>
            <strong>ملاحظات مستشار التوجيه والأساتذة:</strong>
            {{ $orientation->pre_orientation_notes ?? 'النتائج متناسقة مع الرغبة المعبر عنها، يُنصح بتكثيف المراجعة في المواد الأساسية.' }}
        </div>
    </div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sign-block">
            <strong>توقيع التلميذ(ة) والولي الشرعي</strong>
            <div class="sign-space"></div>
            <span>الاسم والمصادقة: .....................</span>
        </div>
        <div class="sign-block">
            <strong>مستشار(ة) التوجيه والإرشاد المدرسي</strong>
            <div class="sign-space"></div>
            <span>التأشيرة والختم</span>
        </div>
        <div class="sign-block">
            <strong>رئيس المجلس (مدير المؤسسة)</strong>
            <div class="sign-space"></div>
            <span>الختم والتوقيع الرسمي</span>
        </div>
    </div>

</body>
</html>
