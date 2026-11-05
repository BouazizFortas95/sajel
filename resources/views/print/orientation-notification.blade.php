<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('إشعار التوجيه الرسمي إلى التعليم الثانوي - :name', ['name' => $student->full_name]) }}</title>
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
            line-height: 1.45;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 12pt;
            margin: 0 0 3px 0;
            font-weight: bold;
        }
        .header h2 {
            font-size: 11pt;
            margin: 0 0 3px 0;
            font-weight: normal;
        }
        .header .meta {
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
            margin-top: 4px;
        }
        .ref-bar {
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
            margin-bottom: 12px;
        }
        .title-box {
            text-align: center;
            border: 2px solid #0f172a;
            background: #f8fafc;
            padding: 10px 14px;
            margin: 10px 0;
            border-radius: 6px;
        }
        .title-box h3 {
            font-size: 13.5pt;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .title-box p {
            font-size: 9.5pt;
            margin: 0;
            color: #475569;
        }
        .recipient {
            margin: 12px 0 16px 0;
            font-size: 11pt;
            line-height: 1.6;
        }
        .decision-card {
            border: 2px solid #16a34a;
            background: #f0fdf4;
            padding: 14px 18px;
            border-radius: 8px;
            margin: 14px 0;
            text-align: center;
        }
        .decision-title {
            font-size: 11pt;
            color: #15803d;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .decision-stream {
            font-size: 16pt;
            font-weight: 900;
            color: #14532d;
            letter-spacing: 0.5px;
        }
        table.results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin: 12px 0;
        }
        table.results-table th, table.results-table td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            text-align: center;
        }
        table.results-table th {
            background: #f1f5f9;
            font-weight: bold;
        }
        .appeal-notice {
            border: 1px dashed #d97706;
            background: #fffbeb;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 9.5pt;
            margin: 14px 0;
            line-height: 1.5;
            color: #78350f;
        }
        .signatures {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            text-align: center;
            font-size: 10pt;
        }
        .sign-block {
            width: 45%;
        }
        .sign-space {
            height: 60px;
        }
        .tear-off-line {
            border-top: 1px dashed #475569;
            margin: 25px 0 15px 0;
            position: relative;
            text-align: center;
        }
        .tear-off-badge {
            background: #fff;
            padding: 0 10px;
            position: relative;
            top: -10px;
            font-size: 8.5pt;
            color: #64748b;
        }
        .receipt-slip {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 9pt;
            background: #fcfcfd;
            border-radius: 4px;
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

    <div class="ref-bar">
        <span><strong>الرقم المرجعي:</strong> ت.ث/{{ date('Y') }}/{{ str_pad($student->id, 4, '0', STR_PAD_LEFT) }}</span>
        <span><strong>تاريخ التبليغ:</strong> {{ now()->locale('ar')->translatedFormat('d F Y') }}</span>
    </div>

    <div class="title-box">
        <h3>إشعار رسمي بقرار مجلس القبول والتوجيه إلى التعليم الثانوي</h3>
        <p>بناءً على المنشور الوزاري المحدد لضوابط القبول والتوجيه بعد نهاية مرحلة التعليم المتوسط</p>
    </div>

    <div class="recipient">
        <strong>إلى السيد(ة) ولي أمر التلميذ(ة):</strong> {{ $student->full_name }}<br>
        <strong>المولود(ة) بتاريخ:</strong> {{ $student->date_of_birth?->format('Y/m/d') }} بـ {{ $student->place_of_birth ?? '—' }} |
        <strong>رقم القيد:</strong> <span dir="ltr">{{ $student->matricule_number ?? $student->registration_number ?? '—' }}</span> |
        <strong>القسم:</strong> {{ $student->group?->classroom?->name ?? 'السنة الرابعة متوسط' }}
    </div>

    <p style="font-size:10.5pt; text-align:justify; margin: 6px 0;">
        يشرف إدارة المؤسسة إعلامكم بأنه، عقب اجتماع <strong>مجلس القبول والتوجيه</strong> المنعقد قانوناً برئاسة مدير المؤسسة وحضور مستشار(ة) التوجيه والإرشاد المدرسي وأعضاء الفريق التربوي، وبعد دراسة النتائج المدرسية وحساب مؤشرات الملمح البيداغوجي ورغبات التلميذ(ة)، تقرر ما يلي:
    </p>

    {{-- Decision Banner --}}
    <div class="decision-card">
        <div class="decision-title">قرار المجلس النهائي المعتمد للتلميذ(ة):</div>
        <div class="decision-stream">
            {{ $orientation->effectiveDecision()?->label() ?? 'جذع مشترك علوم وتكنولوجيا' }}
        </div>
        <div style="font-size:9.5pt; color:#166534; margin-top:4px;">
            وضعية القبول: <strong>{{ $orientation->is_admitted ? 'مقبول(ة) في مرحلة التعليم الثانوي' : 'غير مقبول(ة)' }}</strong>
        </div>
    </div>

    {{-- Academic Evaluation Scores Table --}}
    <table class="results-table">
        <thead>
            <tr>
                <th>معدل الملمح العلمي</th>
                <th>معدل الملمح الأدبي</th>
                <th>المعدل السنوي العام</th>
                <th>نقطة امتحان BEM</th>
                <th>معدل القبول المعتمد</th>
                <th>الرغبة الأولى للتلميذ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight:bold; color:#1e40af;">{{ $orientation->scientific_profile_score ?? '—' }} / 20</td>
                <td style="font-weight:bold; color:#92400e;">{{ $orientation->literary_profile_score ?? '—' }} / 20</td>
                <td style="font-weight:bold;">{{ $orientation->annual_general_average ?? '—' }} / 20</td>
                <td style="font-weight:bold; color:#047857;">{{ $orientation->bem_score ?? '—' }} / 20</td>
                <td style="font-weight:bold; font-size:11.5pt; background:#f8fafc;">{{ $orientation->admission_average ?? '—' }} / 20</td>
                <td>{{ $orientation->first_choice?->shortLabel() ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Statutory Appeal Notice --}}
    <div class="appeal-notice">
        <strong>⚠️ تنبيه قانوني حول إجراءات وحق الطعن:</strong><br>
        طبقا للتشريع المدرسي المعمول به، يحق لولي التلميذ(ة) في حال عدم الرضا عن قرار التوجيه الصادر، تقديم <strong>طلب طعن كتابي مبرر</strong> إلى أمانة المؤسسة لإحالته على <strong>اللجنة الولائية للطعن</strong> بمديرية التربية، وذلك في أجل أقصاه <strong>ثمانية (08) أيام</strong> ابتداء من تاريخ استلام هذا الإشعار. وتعتبر قرارات اللجنة الولائية نافذة ونهائية.
    </div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sign-block">
            <strong>مستشار(ة) التوجيه والإرشاد المدرسي</strong>
            <div class="sign-space"></div>
            <span>التأشيرة</span>
        </div>
        <div class="sign-block">
            <strong>مدير(ة) المؤسسة - رئيس المجلس</strong>
            <div class="sign-space"></div>
            <span>الختم والتوقيع الرسمي</span>
        </div>
    </div>

    {{-- Perforated Receipt Return Slip --}}
    <div class="tear-off-line">
        <span class="tear-off-badge">✂ يُقطع ويُعاد إلى إدارة المؤسسة بعد التوقيع (وصل استلام إشعار التوجيه) ✂</span>
    </div>

    <div class="receipt-slip">
        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span><strong>المؤسسة:</strong> {{ $school?->school_name ?? 'متوسطة سِجلّ' }}</span>
            <span><strong>السنة الدراسية:</strong> {{ $academic_year }}</span>
            <span><strong>التلميذ(ة):</strong> {{ $student->full_name }} ({{ $student->group?->classroom?->name ?? '4AM' }})</span>
        </div>
        <div style="margin-top:4px;">
            أنا الموقع أسفله ولي التلميذ(ة) <strong>{{ $student->full_name }}</strong>، أصرح أنني استلمت إشعار التوجيه الرسمي المتضمن توجيهه إلى:
            <strong>{{ $orientation->effectiveDecision()?->shortLabel() ?? '—' }}</strong>، واطلعت على آجال وشروط الطعن.
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:10px;">
            <span>حرر بـ: ..................... في: ..../..../{{ date('Y') }}</span>
            <span><strong>توقيع وبصمة الولي:</strong> .....................................</span>
        </div>
    </div>

</body>
</html>
