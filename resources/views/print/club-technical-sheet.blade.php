<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('البطاقة الفنية والتنظيمية للنادي المدرسي') }} — {{ $club->name }}</title>

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
            line-height: 1.45;
            direction: rtl;
        }

        .sheet-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            padding: 10mm;
            border-radius: 4px;
        }

        .official-header {
            text-align: center;
            border-bottom: 2px solid #047857;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .official-republic {
            font-family: 'Amiri', serif;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .official-ministry {
            font-family: 'Cairo', sans-serif;
            font-size: 11.5px;
            font-weight: 700;
            color: #047857;
        }

        .header-meta {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #475569;
            margin-top: 4px;
            padding: 0 4px;
        }

        .document-title {
            text-align: center;
            background: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            border-radius: 6px;
            padding: 8px 12px;
            margin: 12px 0;
        }

        .document-title h1 {
            font-size: 14px;
            font-weight: 900;
            color: #065f46;
            margin-bottom: 2px;
        }

        .document-title p {
            font-size: 10px;
            color: #047857;
            font-weight: 600;
        }

        .section-title {
            font-size: 11.5px;
            font-weight: 800;
            color: #065f46;
            border-right: 3px solid #059669;
            padding-right: 6px;
            margin: 12px 0 6px 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 8px;
        }

        .grid-table td, .grid-table th {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            text-align: right;
        }

        .grid-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            width: 22%;
        }

        .grid-table td {
            background: #ffffff;
            color: #0f172a;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 10px;
        }

        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: right;
        }

        .data-table th {
            background: #f8fafc;
            color: #1e293b;
            font-weight: 800;
            text-align: center;
        }

        .badge-role {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
        }

        .role-president { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .role-vp { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .role-sec { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 16px;
            padding-top: 8px;
        }

        .sig-block {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            min-height: 85px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fcfcfd;
        }

        .sig-block .title {
            font-weight: 800;
            font-size: 10.5px;
            color: #065f46;
        }

        .sig-block .name {
            font-size: 10px;
            color: #334155;
            font-weight: 600;
        }

        .sig-block .sub {
            font-size: 9px;
            color: #94a3b8;
            font-style: italic;
        }

        .footer-note {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
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
            .sheet-container { border: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">
        <span>🖨️</span> {{ __('طباعة البطاقة (A4)') }}
    </button>

    <div class="sheet-container">
        {{-- National Header --}}
        <div class="official-header">
            <div class="official-republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
            <div class="official-ministry">وزارة التربية الوطنية</div>
            <div class="header-meta">
                <div>مديرية التربية لولاية: <strong>{{ $school->state_name ?? 'الوادي' }}</strong></div>
                <div>المؤسسة: <strong>{{ $school->school_name ?? 'متوسطة الشهيد العربي بن مهيدي' }}</strong></div>
                <div>الموسم الدراسي: <strong>{{ $club->academic_year }}</strong></div>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="document-title">
            <h1>{{ __('البطاقة الفنية والتنظيمية للنادي المدرسي') }}</h1>
            <p>{{ __('مشروع المؤسسة وتفعيل الحياة المدرسية والأنشطة اللاصفية') }}</p>
        </div>

        {{-- Section 1: Identification & Supervisors --}}
        <div class="section-title">
            <span>📌</span> {{ __('أولاً: بطاقة تعريف النادي وهيئة الإشراف') }}
        </div>
        <table class="grid-table">
            <tr>
                <th>{{ __('اسم النادي المدرسي') }}:</th>
                <td><strong>{{ $club->name }}</strong></td>
                <th>{{ __('رمز النادي / التصنيف') }}:</th>
                <td><span style="font-family: monospace; font-weight: bold;">{{ $club->code ?? 'N/A' }}</span> ({{ $club->type?->label() }})</td>
            </tr>
            <tr>
                <th>{{ __('الأستاذ المنسق والمشرف') }}:</th>
                <td><strong>{{ $club->leadTeacher?->name }}</strong> ({{ $club->leadTeacher?->primarySubject?->name ?? __('أستاذ') }})</td>
                <th>{{ __('الأستاذ المساعد / المرافق') }}:</th>
                <td>{{ $club->assistantTeacher?->name ? $club->assistantTeacher->name . ' (' . ($club->assistantTeacher->primarySubject?->name ?? __('أستاذ')) . ')' : __('لا يوجد') }}</td>
            </tr>
            <tr>
                <th>{{ __('يوم وتوقيت النشاط') }}:</th>
                <td>{{ $club->formattedMeetingSchedule() }}</td>
                <th>{{ __('الطاقة الاستيعابية') }}:</th>
                <td><strong>{{ $club->activeMembersCount() }}</strong> / {{ $club->max_members }} {{ __('تلميذاً منخرطاً') }}</td>
            </tr>
            @if ($club->description)
                <tr>
                    <th>{{ __('مجال النشاط والتعريف') }}:</th>
                    <td colspan="3">{{ $club->description }}</td>
                </tr>
            @endif
            @if ($club->objectives)
                <tr>
                    <th>{{ __('الأهداف التربوية والقيمية') }}:</th>
                    <td colspan="3">{{ $club->objectives }}</td>
                </tr>
            @endif
        </table>

        {{-- Section 2: Executive Bureau --}}
        <div class="section-title">
            <span>👥</span> {{ __('ثانياً: تشكيلة المكتب التنفيذي للنادي (من التلاميذ)') }}
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">{{ __('الصفة والمسؤولية في النادي') }}</th>
                    <th>{{ __('اللقب والاسم الكامل للتلميذ') }}</th>
                    <th style="width: 20%;">{{ __('القسم والفوج التربوي') }}</th>
                    <th style="width: 15%;">{{ __('تاريخ الانخراط') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bureau as $member)
                    <tr>
                        <td style="text-align: center;">
                            @if ($member->role === \App\Enums\ClubMemberRole::President)
                                <span class="badge-role role-president">👑 {{ $member->role->label() }}</span>
                            @elseif ($member->role === \App\Enums\ClubMemberRole::VicePresident)
                                <span class="badge-role role-vp">⭐ {{ $member->role->label() }}</span>
                            @else
                                <span class="badge-role role-sec">✍️ {{ $member->role->label() }}</span>
                            @endif
                        </td>
                        <td><strong>{{ $member->student?->name }}</strong></td>
                        <td style="text-align: center;">{{ $member->student?->group?->classroom?->name ?? __('غير محدد') }}</td>
                        <td style="text-align: center; font-family: monospace;">{{ $member->joined_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #64748b; padding: 8px;">
                            {{ __('لم يتم انتخاب أو تعيين أعضاء المكتب التنفيذي بعد.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Section 3: Activities & Events Plan --}}
        <div class="section-title">
            <span>📅</span> {{ __('ثالثاً: ملخص الأنشطة والورشات المبرمجة والمنجزة') }}
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>{{ __('عنوان الفعالية / النشاط') }}</th>
                    <th style="width: 20%;">{{ __('النوع') }}</th>
                    <th style="width: 15%;">{{ __('التاريخ المبرمج') }}</th>
                    <th style="width: 12%;">{{ __('المشاركون') }}</th>
                    <th style="width: 12%;">{{ __('الوضعية') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($club->activities->sortByDesc('activity_date') as $idx => $act)
                    <tr>
                        <td style="text-align: center; font-weight: bold;">{{ $loop->iteration }}</td>
                        <td><strong>{{ $act->title }}</strong></td>
                        <td style="text-align: center;">{{ $act->activity_type?->label() }}</td>
                        <td style="text-align: center; font-family: monospace;">{{ $act->activity_date?->format('d/m/Y') }}</td>
                        <td style="text-align: center; font-family: monospace;">{{ $act->attendees_count > 0 ? $act->attendees_count . ' ' . __('تلميذ') : '—' }}</td>
                        <td style="text-align: center;">
                            <span style="font-size: 9px; font-weight: bold; color: {{ $act->status->value === 'completed' ? '#047857' : '#b45309' }};">
                                {{ $act->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b; padding: 8px;">
                            {{ __('لا توجد أنشطة مسجلة في السجل حتى الآن.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Signatures Grid --}}
        <div class="signatures-grid">
            <div class="sig-block">
                <div>
                    <div class="title">{{ __('الأستاذ المنسق والمشرف') }}</div>
                    <div class="name">{{ $club->leadTeacher?->name }}</div>
                </div>
                <div class="sub">({{ __('التوقيع والتأشيرة') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('مستشار التربية / ناظر المؤسسة') }}</div>
                    <div class="name">{{ __('مصلحة شؤون التلاميذ') }}</div>
                </div>
                <div class="sub">({{ __('التأشيرة للمتابعة والتنسيق') }})</div>
            </div>

            <div class="sig-block">
                <div>
                    <div class="title">{{ __('مدير المتوسطة') }}</div>
                    <div class="name">{{ $school->principal_name ?? 'الأستاذ عبد الحميد بن عمر' }}</div>
                </div>
                <div class="sub">({{ __('الختم والتوقيع الإداري الرسمي') }})</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer-note">
            <div>
                <strong>{{ __('نسخة موجهة إلى') }}:</strong>
                {{ __('أمانة المدير — مستشارية التربية — ملف الأنشطة اللاصفية') }}
            </div>
            <div>{{ __('سجّل — منظومة الحياة المدرسية والنوادي التربوية') }}</div>
        </div>
    </div>

</body>
</html>
