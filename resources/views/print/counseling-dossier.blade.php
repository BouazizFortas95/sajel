<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('بطاقة المتابعة النفسية والتربوية - :name', ['name' => $student->full_name]) }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: 'DejaVu Sans', 'Amiri', 'Traditional Arabic', Tahoma, Arial, sans-serif;
            color: #0f172a;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 10pt;
            line-height: 1.35;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 11pt;
            margin: 0 0 2px 0;
            font-weight: bold;
        }
        .header h2 {
            font-size: 10pt;
            margin: 0 0 2px 0;
            font-weight: normal;
        }
        .header .meta {
            display: flex;
            justify-content: space-between;
            font-size: 8.5pt;
            margin-top: 3px;
            font-weight: bold;
        }
        .title-box {
            text-align: center;
            border: 2px solid #0f172a;
            background: #f1f5f9;
            padding: 6px 10px;
            margin: 8px 0;
            border-radius: 4px;
            position: relative;
        }
        .title-box h3 {
            font-size: 12pt;
            font-weight: bold;
            margin: 0 0 2px 0;
            color: #0f172a;
        }
        .title-box p {
            font-size: 8.5pt;
            margin: 0;
            color: #475569;
        }
        .confidential-stamp {
            position: absolute;
            left: 10px;
            top: 6px;
            border: 1px dashed #dc2626;
            color: #dc2626;
            padding: 2px 6px;
            font-size: 8pt;
            font-weight: bold;
            border-radius: 3px;
        }
        .section-title {
            background: #e2e8f0;
            padding: 3px 8px;
            font-size: 9.5pt;
            font-weight: bold;
            border-right: 4px solid #0284c7;
            margin: 6px 0 4px 0;
            border-radius: 2px;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 4px;
        }
        .grid-table td, .grid-table th {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
        }
        .grid-table th {
            background-color: #f8fafc;
            font-weight: bold;
            text-align: right;
            width: 25%;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-emerald { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-amber { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-rose { background: #ffe4e6; color: #9f1239; border: 1px solid #fecdd3; }
        .badge-blue { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .sessions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-top: 4px;
        }
        .sessions-table th, .sessions-table td {
            border: 1px solid #cbd5e1;
            padding: 4px;
            text-align: center;
        }
        .sessions-table th {
            background: #f1f5f9;
            font-weight: bold;
        }
        .signatures {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 45%;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            min-height: 55px;
        }
        .sign-box .role {
            font-weight: bold;
            margin-bottom: 30px;
        }
        .footer-note {
            text-align: center;
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 8px;
            border-top: 1px dotted #cbd5e1;
            padding-top: 3px;
        }
        @media print {
            .no-print { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ __('الجمهورية الجزائرية الديمقراطية الشعبية') }}</h1>
        <h2>{{ __('وزارة التربية الوطنية') }}</h2>
        <div class="meta">
            <span>{{ __('مديرية التربية لولاية:') }} {{ $school->directorate_name ?? '..................' }}</span>
            <span>{{ __('المؤسسة:') }} {{ $school->school_name ?? '..................' }}</span>
            <span>{{ __('السنة الدراسية:') }} {{ $academic_year }}</span>
        </div>
    </div>

    <div class="title-box">
        <span class="confidential-stamp">{{ __('سري وخاص بالتوجيه') }}</span>
        <h3>{{ __('بطاقة المتابعة والتوجيه النفسي والتربوي للتلميذ') }}</h3>
        <p>{{ __('الدوسيي البيداغوجي والنفسي لمستشار التوجيه والإرشاد المدرسي والمهني') }}</p>
    </div>

    {{-- SECTION 1: Civil Identity --}}
    <div class="section-title">1. {{ __('الهوية المدرسية والحالة المدنية للتلميذ') }}</div>
    <table class="grid-table">
        <tr>
            <th>{{ __('الاسم واللقب:') }}</th>
            <td style="font-weight: bold; font-size: 10pt;">{{ $student->full_name }}</td>
            <th>{{ __('رقم القيد والتسجيل:') }}</th>
            <td><code style="font-size: 9pt;">{{ $student->registration_number ?? '-' }}</code></td>
        </tr>
        <tr>
            <th>{{ __('تاريخ ومكان الميلاد:') }}</th>
            <td>{{ $student->date_of_birth ? $student->date_of_birth->format('Y/m/d') : '-' }} ({{ $student->place_of_birth ?? '-' }})</td>
            <th>{{ __('الجنس والنظام المدرسي:') }}</th>
            <td>
                {{ $student->isMale() ? __('ذكر') : __('أنثى') }}
                — <span class="badge badge-blue">{{ $student->regimeLabel() }}</span>
            </td>
        </tr>
        <tr>
            <th>{{ __('القسم التربوي والفوج:') }}</th>
            <td><strong>{{ $classroom->name ?? '-' }}</strong> {{ $group ? '('.$group->name.')' : '' }}</td>
            <th>{{ __('المعدل العام السنوي:') }}</th>
            <td><strong style="color: #0284c7; font-size: 10pt;">{{ $general_average !== null ? number_format($general_average, 2) . ' / 20' : '-' }}</strong></td>
        </tr>
    </table>

    {{-- SECTION 2: Family & Social Context --}}
    <div class="section-title">2. {{ __('الوسط العائلي والوضع الاجتماعي') }}</div>
    <table class="grid-table">
        <tr>
            <th>{{ __('الوضعية العائلية:') }}</th>
            <td>
                <span class="badge badge-{{ $profile->family_status->color() }}">
                    {{ $profile->family_status->label() }}
                </span>
            </td>
            <th>{{ __('المنحة المدرسية الخاصة:') }}</th>
            <td>
                @if($profile->social_grant)
                    <span class="badge badge-emerald">✓ {{ __('مستفيد من المنحة') }}</span>
                @else
                    {{ __('غير مستفيد') }}
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('عدد الإخوة ورتبة التلميذ:') }}</th>
            <td>{{ __('عدد الإخوة:') }} <strong>{{ $profile->siblings_count }}</strong> | {{ __('رتبته بين إخوته:') }} <strong>{{ $profile->birth_order }}</strong></td>
            <th>{{ __('مهنة الوالدين:') }}</th>
            <td>{{ __('الأب:') }} {{ $profile->father_job ?: '-' }} | {{ __('الأم:') }} {{ $profile->mother_job ?: '-' }}</td>
        </tr>
    </table>

    {{-- SECTION 3: Health & Physiological Profile --}}
    <div class="section-title">3. {{ __('الحالة الصحية والفيزيولوجية') }}</div>
    <table class="grid-table">
        <tr>
            <th>{{ __('فصيلة الدم:') }}</th>
            <td><strong style="color: #dc2626;">{{ $profile->blood_type ?: '-' }}</strong></td>
            <th>{{ __('إعفاء التربية البدنية:') }}</th>
            <td>
                @if($profile->pe_exemption)
                    <span class="badge badge-rose">⚠️ {{ __('معفى رسمياً بشهادة طبية') }}</span>
                @else
                    {{ __('مؤهل للنشاط الرياضي') }}
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('الأمراض المزمنة المصرح بها:') }}</th>
            <td colspan="3">
                @if(!empty($profile->chronic_conditions))
                    <span style="color: #b91c1c; font-weight: bold;">{{ implode('، ', (array) $profile->chronic_conditions) }}</span>
                @else
                    <span style="color: #15803d;">{{ __('سليم ولا يعاني من أمراض مزمنة مصرح بها') }}</span>
                @endif
                @if($profile->health_notes)
                    <div style="font-size: 8pt; color: #475569; margin-top: 2px;">{{ __('ملاحظة طبية:') }} {{ $profile->health_notes }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- SECTION 4: Academic History & Psychological Tendencies --}}
    <div class="section-title">4. {{ __('المسار الدراسي، صعوبات التعلم، والملمح النفسي') }}</div>
    <table class="grid-table">
        <tr>
            <th>{{ __('المدرسة الابتدائية الأصلية:') }}</th>
            <td>{{ $profile->primary_school_name ?: '-' }} ({{ $profile->primary_graduation_year ?: '-' }})</td>
            <th>{{ __('تكرار وإعادة السنة:') }}</th>
            <td>
                @if($profile->repeated_years_count > 0)
                    <span class="badge badge-rose">{{ $profile->repeated_years_count }} {{ __('سنة إعادة') }}</span>
                    @if(!empty($profile->repeated_levels))
                        <span style="font-size: 8pt; color: #64748b;">({{ implode(', ', (array) $profile->repeated_levels) }})</span>
                    @endif
                @else
                    <span class="badge badge-emerald">{{ __('مسار منتظم بدون إعادة') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('صعوبات التعلم المشخصة:') }}</th>
            <td>{{ $profile->learningDifficultiesList() }}</td>
            <th>{{ __('السمات والسلوك الصفي:') }}</th>
            <td>{{ $profile->behaviorTendenciesList() }}</td>
        </tr>
        <tr>
            <th>{{ __('الميول والهوايات الشخصية:') }}</th>
            <td>{{ $profile->interestsList() }}</td>
            <th>{{ __('الطموح المهني للتلميذ:') }}</th>
            <td><strong style="color: #0284c7;">{{ $profile->career_aspirations ?: '-' }}</strong></td>
        </tr>
        @if($profile->counselor_general_notes)
            <tr>
                <th>{{ __('ملاحظات مستشار التوجيه:') }}</th>
                <td colspan="3" style="font-style: italic; background: #fafafa;">{{ $profile->counselor_general_notes }}</td>
            </tr>
        @endif
    </table>

    {{-- SECTION 5: Counseling Sessions Ledger --}}
    <div class="section-title">5. {{ __('سجل المقابلات والمتابعات الإرشادية الفردية المنفذة') }} ({{ $sessions->count() }} {{ __('مقابلات') }})</div>
    @if($sessions->isNotEmpty())
        <table class="sessions-table">
            <thead>
                <tr>
                    <th style="width: 15%;">{{ __('التاريخ') }}</th>
                    <th style="width: 25%;">{{ __('سبب المقابلة') }}</th>
                    <th style="width: 18%;">{{ __('المستهدف') }}</th>
                    <th style="width: 27%;">{{ __('الخطة الإرشادية والتوصيات') }}</th>
                    <th style="width: 15%;">{{ __('الحالة') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $sess)
                    <tr>
                        <td>{{ $sess->session_date ? $sess->session_date->format('Y/m/d') : '-' }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ $sess->reason_category->label() }}</td>
                        <td>{{ $sess->intervieweeLabel() }}</td>
                        <td style="text-align: right;">{{ $sess->action_plan ?: ($sess->observations ? \Illuminate\Support\Str::limit($sess->observations, 60) : '-') }}</td>
                        <td>
                            <span class="badge badge-{{ $sess->status->color() }}">
                                {{ $sess->status->label() }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="font-size: 8.5pt; color: #64748b; text-align: center; padding: 6px; border: 1px dashed #cbd5e1; border-radius: 4px; margin-bottom: 6px;">
            {{ __('لم تسجل أية مقابلة إرشادية فردية لهذا التلميذ حتى الآن.') }}
        </div>
    @endif

    {{-- SECTION 6: Signatures Block --}}
    <div class="signatures">
        <div class="sign-box">
            <div class="role">{{ __('مستشار(ة) التوجيه والإرشاد المدرسي') }}</div>
            <div style="font-size: 8pt; color: #64748b;">{{ __('التاريخ، الخاتم والتوقيع:') }}</div>
        </div>
        <div class="sign-box">
            <div class="role">{{ __('تأشيرة وخاتم مدير(ة) المتوسطة') }}</div>
            <div style="font-size: 8pt; color: #64748b;">{{ __('التاريخ، الخاتم والتوقيع:') }}</div>
        </div>
    </div>

    <div class="footer-note">
        {{ __('وثيقة بيداغوجية ونفسية رسمية محمية بموجب السر المهني وأحكام وزارة التربية الوطنية — منصة سجِّل للرقمنة التربوية') }}
    </div>

</body>
</html>
