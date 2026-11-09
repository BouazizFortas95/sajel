@php
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
@endphp

@include('print.partials.document-head', [
    'printTitle' => 'كشف وضعية استلام وتأشير المخططات والأعمال التقويمية',
    'printAcademicYear' => $academicYear,
    'printReference' => 'سجل المتابعة البيداغوجية',
    'printMeta' => 'نسخة الأستاذ الرسمية',
    'printOrientation' => 'portrait',
    'printAutoPrint' => $autoPrint ?? false,
])

<div class="space-y-5" dir="rtl" style="font-family: 'Amiri', 'Cairo', serif; margin-top: 10px;">
    <!-- Teacher Identity Banner (Full Single Line) -->
    <div style="background-color: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 7px 18px; margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; white-space: nowrap; font-family: 'Cairo', 'Amiri', serif;">
            <!-- 1. Teacher Name (Right) -->
            <div style="display: inline-flex; align-items: baseline; gap: 6px;">
                <span style="color: #64748b; font-weight: 700; font-size: 9.5pt;">اسم ولقب الأستاذ:</span>
                <span style="color: #0f172a; font-size: 13pt; font-weight: 700; font-family: 'Amiri', serif;">{{ $teacher->name }}</span>
            </div>

            <!-- 2. Subject (Center) -->
            <div style="display: inline-flex; align-items: baseline; gap: 6px;">
                <span style="color: #64748b; font-weight: 700; font-size: 9.5pt;">المادة المقررة:</span>
                <span style="color: #1e3a8a; font-size: 11pt; font-weight: 700;">{{ $teacher->primarySubject?->name ?? 'غير محدد' }}</span>
            </div>

            <!-- 3. Compliance Rate (Left) -->
            <div style="display: inline-flex; align-items: center; gap: 8px;">
                <span style="color: #64748b; font-weight: 700; font-size: 9.5pt;">نسبة الامتثال العام:</span>
                <span style="display: inline-block; background-color: #1e3a8a; color: #ffffff; padding: 2px 10px; border-radius: 9999px; font-weight: 700; font-size: 9pt; font-family: 'Cairo', sans-serif;">
                    {{ $overallPercentage }}% ({{ $totalSubmitted }}/{{ $totalItems }})
                </span>
            </div>
        </div>
    </div>

    <!-- Section 1: المخططات والتدرجات البيداغوجية -->
    <div style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <h2 style="margin: 0; font-size: 12.5pt; font-weight: bold; color: #1e3a8a; border-right: 4px solid #1e3a8a; padding-right: 8px;">
                أولاً: سجل استلام وتأشير المخططات والتدرجات البيداغوجية
            </h2>
            <span style="font-size: 10pt; font-weight: bold; color: #047857;">
                المخططات المعتمدة: {{ $submittedMonthsCount + ($annualPlan['submitted'] ? 1 : 0) }} من 10 ({{ round((($submittedMonthsCount + ($annualPlan['submitted'] ? 1 : 0)) / 10) * 100) }}%)
            </span>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 9.5pt; text-align: center; border: 1.5px solid #334155;">
            <thead>
                <tr style="background-color: #1e3a8a; color: #ffffff;">
                    <th style="border: 1px solid #cbd5e1; padding: 6px 3px; font-weight: bold;">المخطط السنوي</th>
                    @foreach($monthlyPlans as $m)
                        <th style="border: 1px solid #cbd5e1; padding: 6px 3px; font-weight: bold;">{{ $m['label'] }}</th>
                    @endforeach
                    <th style="border: 1px solid #cbd5e1; padding: 6px 3px; font-weight: bold; background-color: #0f172a;">تأشيرة الناظر</th>
                    <th style="border: 1px solid #cbd5e1; padding: 6px 3px; font-weight: bold; background-color: #0f172a;">تأشيرة المدير</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #ffffff;">
                    <!-- Annual Plan -->
                    <td style="border: 1px solid #cbd5e1; padding: 8px 4px;">
                        @if($annualPlan['submitted'])
                            <div style="color: #047857; font-weight: bold; font-size: 11pt;">✓ مؤشر</div>
                            <div style="font-size: 8pt; color: #64748b;" dir="ltr">{{ $annualPlan['date']?->format('d/m') ?? '' }}</div>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>

                    <!-- 9 Months -->
                    @foreach($monthlyPlans as $m)
                        <td style="border: 1px solid #cbd5e1; padding: 8px 3px;">
                            @if($m['submitted'])
                                <div style="color: #047857; font-weight: bold; font-size: 11pt;">✓</div>
                                <div style="font-size: 8pt; color: #64748b;" dir="ltr">{{ $m['date']?->format('d/m') ?? '' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                    @endforeach

                    <!-- Censor Visa -->
                    <td style="border: 1px solid #cbd5e1; padding: 8px 4px; background-color: #f8fafc;">
                        @if($annualPlan['censor_visa'])
                            <div style="color: #1e3a8a; font-weight: bold; font-size: 10pt;">✓ تم التأشير</div>
                            <div style="font-size: 7.5pt; color: #64748b;" dir="ltr">{{ $annualPlan['censor_visa_at']?->format('d/m/Y') }}</div>
                        @else
                            <span style="color: #94a3b8; font-size: 8.5pt;">قيد الانتظار</span>
                        @endif
                    </td>

                    <!-- Director Visa -->
                    <td style="border: 1px solid #cbd5e1; padding: 8px 4px; background-color: #f8fafc;">
                        @if($annualPlan['director_visa'])
                            <div style="color: #1e3a8a; font-weight: bold; font-size: 10pt;">✓ اعتمد رسمياً</div>
                            <div style="font-size: 7.5pt; color: #64748b;" dir="ltr">{{ $annualPlan['director_visa_at']?->format('d/m/Y') }}</div>
                        @else
                            <span style="color: #94a3b8; font-size: 8.5pt;">قيد الاعتماد</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        @if(!empty($annualPlan['notes']))
            <div style="margin-top: 6px; padding: 6px 12px; background-color: #eff6ff; border-right: 3px solid #2563eb; font-size: 9.5pt; border-radius: 4px;">
                <strong style="color: #1d4ed8;">ملاحظات الإدارة حول المخططات:</strong>
                <span style="color: #334155;">{{ $annualPlan['notes'] }}</span>
            </div>
        @endif
    </div>

    <!-- Section 2: تسليم الأعمال والوثائق التقويمية -->
    <div style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <h2 style="margin: 0; font-size: 12.5pt; font-weight: bold; color: #1e3a8a; border-right: 4px solid #1e3a8a; padding-right: 8px;">
                ثانياً: سجل تسليم ومتابعة الأعمال والوثائق التقويمية
            </h2>
            <span style="font-size: 10pt; font-weight: bold; color: #b45309;">
                الأعمال المسلّمة: {{ $submittedEvalsCount }} من 10 ({{ $evalsPercentage }}%)
            </span>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 9.5pt; text-align: center; border: 1.5px solid #334155;">
            <thead>
                <tr style="background-color: #334155; color: #ffffff;">
                    <th rowspan="2" style="border: 1px solid #cbd5e1; padding: 6px 4px; width: 13%;">كشف المستوى</th>
                    <th colspan="3" style="border: 1px solid #cbd5e1; padding: 6px 4px; background-color: #0284c7;">الواجبات المنزلية</th>
                    <th colspan="3" style="border: 1px solid #cbd5e1; padding: 6px 4px; background-color: #6366f1;">الفروض المحروسة</th>
                    <th colspan="3" style="border: 1px solid #cbd5e1; padding: 6px 4px; background-color: #9333ea;">اختبارات الفصول</th>
                </tr>
                <tr style="background-color: #475569; color: #ffffff; font-size: 9pt;">
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">واجب 1</th>
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">واجب 2</th>
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">واجب 3</th>
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">فرض 1</th>
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">فرض 2</th>
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">فرض 3</th>
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">اختبار 1</th>
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">اختبار 2</th>
                    <th style="border: 1px solid #cbd5e1; padding: 4px;">اختبار 3</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #ffffff;">
                    <!-- Diagnostic Test -->
                    <td style="border: 1px solid #cbd5e1; padding: 10px 4px;">
                        @if($evalItems['diagnostic_test']['submitted'])
                            <div style="color: #0284c7; font-weight: bold; font-size: 11pt;">✓ مؤشر</div>
                            <div style="font-size: 8pt; color: #64748b;" dir="ltr">{{ $evalItems['diagnostic_test']['date']?->format('d/m') ?? '' }}</div>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>

                    <!-- Homework 1, 2, 3 -->
                    @foreach(['homework_1', 'homework_2', 'homework_3'] as $hwKey)
                        <td style="border: 1px solid #cbd5e1; padding: 10px 4px;">
                            @if($evalItems[$hwKey]['submitted'])
                                <div style="color: #0284c7; font-weight: bold; font-size: 11pt;">✓</div>
                                <div style="font-size: 8pt; color: #64748b;" dir="ltr">{{ $evalItems[$hwKey]['date']?->format('d/m') ?? '' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                    @endforeach

                    <!-- Quiz 1, 2, 3 -->
                    @foreach(['quiz_1', 'quiz_2', 'quiz_3'] as $qzKey)
                        <td style="border: 1px solid #cbd5e1; padding: 10px 4px;">
                            @if($evalItems[$qzKey]['submitted'])
                                <div style="color: #4f46e5; font-weight: bold; font-size: 11pt;">✓</div>
                                <div style="font-size: 8pt; color: #64748b;" dir="ltr">{{ $evalItems[$qzKey]['date']?->format('d/m') ?? '' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                    @endforeach

                    <!-- Exam 1, 2, 3 -->
                    @foreach(['exam_1', 'exam_2', 'exam_3'] as $exKey)
                        <td style="border: 1px solid #cbd5e1; padding: 10px 4px;">
                            @if($evalItems[$exKey]['submitted'])
                                <div style="color: #7e22ce; font-weight: bold; font-size: 11pt;">✓</div>
                                <div style="font-size: 8pt; color: #64748b;" dir="ltr">{{ $evalItems[$exKey]['date']?->format('d/m') ?? '' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        @if(!empty($evalNotes))
            <div style="margin-top: 6px; padding: 6px 12px; background-color: #fffbeb; border-right: 3px solid #f59e0b; font-size: 9.5pt; border-radius: 4px;">
                <strong style="color: #b45309;">ملاحظات الإدارة حول الأعمال التقويمية:</strong>
                <span style="color: #334155;">{{ $evalNotes }}</span>
            </div>
        @endif
    </div>

    <!-- Official Certification Footer -->
    <div style="margin-top: 35px; padding-top: 15px; border-top: 1.5px dashed #94a3b8;">
        <div style="font-size: 11pt; font-weight: bold; margin-bottom: 25px; text-align: left;">
            حرر بـ {{ $schoolSetting?->municipality_name ?? 'المغير' }} في: {{ now()->locale('ar')->translatedFormat('d F Y') }}
        </div>

        <table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 11.5pt;">
            <tr>
                <td style="width: 33%; vertical-align: top;">
                    <div style="font-weight: bold; color: #0f172a;">توقيع الأستاذ(ة) المعني(ة)</div>
                    <div style="margin-top: 50px; font-size: 9.5pt; color: #64748b;">(اطلع وصودق عليه)</div>
                </td>
                <td style="width: 33%; vertical-align: top;">
                    <div style="font-weight: bold; color: #0f172a;">ناظر الدروس</div>
                    <div style="margin-top: 50px; font-size: 9.5pt; color: #64748b;">(التأشير والختم)</div>
                </td>
                <td style="width: 33%; vertical-align: top;">
                    <div style="font-weight: bold; color: #0f172a;">مدير المؤسسة</div>
                    <div style="margin-top: 50px; font-size: 9.5pt; color: #64748b;">(الختم والمصادقة الرسمية)</div>
                </td>
            </tr>
        </table>
    </div>
</div>
</main>
</body>
</html>

