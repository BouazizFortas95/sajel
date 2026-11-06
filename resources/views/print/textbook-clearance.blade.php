@php
    $isRtl = app()->getLocale() === 'ar';
    $formatDate = fn ($value): string => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $school = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $yearStr = $academicYear ?? ($school?->academic_year ?? '2026/2027');
    $quitusCode = 'QUITUS-'.($student->registration_number ?: $student->id).'-'.date('Y');
@endphp
<!DOCTYPE html>
<html dir="{{ $isRtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('شهادة براءة الذمة لاسترجاع الكتب المدرسية') }} — {{ $student->full_name }}</title>

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
            border-bottom: 2px solid #059669;
            display: inline-block;
            padding-bottom: 2px;
        }
        .document-title p {
            font-size: 9px;
            color: #475569;
            margin-top: 3px;
        }

        /* Student ID Card */
        .student-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px 16px;
            font-size: 9.5px;
        }
        .student-card .label { color: #64748b; }
        .student-card .value { font-weight: 800; color: #0f172a; margin-inline-start: 4px; }

        /* Table */
        .table-title {
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 10px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #94a3b8;
            padding: 3.5px 6px;
            line-height: 1.25;
        }
        .data-table thead th {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
            font-size: 9px;
        }
        .data-table td.center { text-align: center; }
        .data-table td.mono { font-family: monospace; font-size: 9.5px; }

        /* Verdict Box */
        .verdict-box {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1.5px solid;
            margin: 10px 0;
            font-size: 9.5px;
            line-height: 1.4;
        }
        .verdict-box.cleared {
            background: #ecfdf5;
            border-color: #6ee7b7;
            color: #064e3b;
        }
        .verdict-box.uncleared {
            background: #fffbeb;
            border-color: #fde68a;
            color: #78350f;
        }
        .verdict-title {
            font-weight: 900;
            font-size: 10.5px;
            margin-bottom: 2px;
        }

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
            <span style="font-size: 16px;">📜</span>
            <div>
                <strong>{{ __('شهادة براءة الذمة الرسمية (Quitus)') }}</strong>
                <div style="font-size: 9.5px; color: #94a3b8;">{{ $student->full_name }} — {{ $classroom?->name }} ({{ $quitusCode }})</div>
            </div>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-amber">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ __('طباعة الشهادة') }}
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

            {{-- 2. 3-Column Header Meta --}}
            <div class="header-meta-grid">
                <div class="col-school">
                    <div>{{ $school?->directorate_name ?? __('مديرية التربية لولاية الوادي') }}</div>
                    <div class="school-name">{{ $school?->school_name ?? __('متوسطة الشهيد العربي بن مهيدي') }}</div>
                </div>
                <div class="col-code">{{ $quitusCode }}</div>
                <div class="col-date">
                    <div>{{ __('السنة الدراسية') }}: <strong style="font-family: monospace;">{{ $yearStr }}</strong></div>
                    <div>{{ __('حرر بتاريخ') }}: <span style="font-family: monospace;">{{ now()->format('d/m/Y') }}</span></div>
                </div>
            </div>

            {{-- 3. Document Title --}}
            <div class="document-title">
                <h2>{{ __('شهادة براءة الذمة لاسترجاع الكتب المدرسية') }}</h2>
                <p>{{ __('تُسلّم هذه الشهادة لتأكيد إرجاع كافة الكتب والوسائل البيداغوجية قبل تسليم الوثائق المدرسية') }}</p>
            </div>

            {{-- 4. Student Identification Card --}}
            <div class="student-card">
                <div>
                    <span class="label">{{ __('اسم ولقب التلميذ(ة)') }}:</span>
                    <span class="value" style="font-size: 11px;">{{ $student->full_name }}</span>
                </div>
                <div>
                    <span class="label">{{ __('رقم التسجيل') }}:</span>
                    <span class="value" style="font-family: monospace;">{{ $student->registration_number ?: '—' }}</span>
                </div>
                <div>
                    <span class="label">{{ __('القسم والفوج') }}:</span>
                    <span class="value">{{ $classroom?->name }} @if ($student->group) ({{ $student->group->name }}) @endif</span>
                </div>
                <div>
                    <span class="label">{{ __('تاريخ الازدياد') }}:</span>
                    <span class="value" style="font-family: monospace;">{{ $student->birth_date?->format('d/m/Y') ?: '—' }}</span>
                </div>
            </div>

            {{-- 5. Borrowed Textbooks Table --}}
            <div>
                <div class="table-title">
                    <span>📚 {{ __('سجل الكتب المدرسية المعارة والمسترجعة:') }}</span>
                    <span style="font-family: monospace; font-size: 9.5px; color: #475569;">
                        {{ $status['total_returned'] }} / {{ $status['total_borrowed'] }} {{ __('مسترجع') }}
                    </span>
                </div>

                @if ($status['loans']->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th style="text-align: start; padding-inline-start: 6px;">{{ __('عنوان الكتاب المقرر') }}</th>
                                <th style="width: 90px;">{{ __('المادة') }}</th>
                                <th style="width: 95px;">{{ __('طبيعة الاستفادة') }}</th>
                                <th style="width: 75px;">{{ __('تاريخ الاستلام') }}</th>
                                <th style="width: 75px;">{{ __('تاريخ الإرجاع') }}</th>
                                <th style="width: 80px;">{{ __('الحالة عند الإرجاع') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($status['loans'] as $idx => $loan)
                                <tr>
                                    <td class="center font-bold" style="color: #475569;">{{ $idx + 1 }}</td>
                                    <td style="font-weight: 700; color: #0f172a; padding-inline-start: 6px;">{{ $loan->textbook?->title }}</td>
                                    <td style="font-size: 8.5px; color: #475569;">{{ $loan->textbook?->subject?->name ?? '—' }}</td>
                                    <td class="center" style="font-size: 8.5px; font-weight: 700;">
                                        {{ $loan->distribution_type->label() }}
                                    </td>
                                    <td class="center mono">{{ $formatDate($loan->distributed_at) }}</td>
                                    <td class="center mono">{{ $formatDate($loan->returned_at) }}</td>
                                    <td class="center">
                                        @if ($loan->return_condition)
                                            <span style="font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 3px; {{ $loan->return_condition->value === 'good' ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;' }}">
                                                {{ $loan->return_condition->label() }}
                                            </span>
                                        @else
                                            <span style="color: #94a3b8; font-size: 8.5px;">{{ __('في الانتظار') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding: 12px; text-align: center; color: #64748b; font-size: 9px; border: 1px dashed #cbd5e1; border-radius: 4px; margin-bottom: 8px;">
                        {{ __('لم يتم تسجيل أية إعارات للكتب المدرسية لهذا التلميذ.') }}
                    </div>
                @endif
            </div>

            {{-- 6. Official Verdict --}}
            <div class="verdict-box {{ $status['is_cleared'] ? 'cleared' : 'uncleared' }}">
                <div class="verdict-title">
                    {{ $status['is_cleared'] ? '✓ ' . __('إقرار براءة الذمة التامة والمصادقة على الإرجاع') : '⚠ ' . __('إشعار بعدم استكمال براءة الذمة') }}
                </div>
                <p>
                    @if ($status['is_cleared'])
                        {{ __('يشهد مسير مخزن الكتب المدرسية وإدارة المؤسسة بأن التلميذ(ة) المذكور(ة) قد أرجع(ت) كافة الكتب المدرسية المسلمة له(ا) خلال السنة الدراسية :year بحالة سليمة، وتُسلّم له(ا) هذه الشهادة لتمكينه(ا) من استلام وثائقه المدرسية.', ['year' => $yearStr]) }}
                    @else
                        {{ __('يُخطر ولي التلميذ(ة) بأن براءة الذمة لم تكتمل بعد، نظراً لوجود كتب معارة لم يتم إرجاعها أو كتب بحاجة إلى تسوية مالية قبل تسليم الوثائق المدرسية.') }}
                    @endif
                </p>
            </div>

            {{-- 7. Official Tripartite Signatures --}}
            <div class="signatures-grid">
                <div class="sig-col">
                    <div class="sig-title">{{ __('مسؤول مخزن الكتب المدرسية') }}</div>
                    <div class="sig-sub">{{ __('المعاينة والمصادقة العينية') }}</div>
                    <div class="sig-box">
                        <span class="sig-stamp-hint">({{ __('التوقيع والمصادقة') }})</span>
                    </div>
                </div>

                <div class="sig-col">
                    <div class="sig-title">{{ __('مستشار التربية') }}</div>
                    <div class="sig-sub">{{ __('التأشيرة الإدارية والتسجيل') }}</div>
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
                {{ __('منظومة سجل الرقمية لإدارة المتوسطة — شهادة براءة الذمة') }} · {{ $quitusCode }}
            </div>

        </div>
    </div>

</body>
</html>
