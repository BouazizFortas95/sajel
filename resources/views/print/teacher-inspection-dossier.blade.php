@php
    $schoolSetting = $school ?? \App\Models\SchoolSetting::current();
@endphp

@include('print.partials.document-head', [
    'printTitle' => 'ملف المتابعة والتفتيش التربوي للأستاذ(ة)',
    'printAcademicYear' => $academicYear,
    'printReference' => 'سجل المتابعة والتفتيش التربوي',
    'printMeta' => 'نسخة رسمية لزيارات التفتيش',
    'printOrientation' => 'portrait',
    'printAutoPrint' => $autoPrint ?? false,
])

<div class="space-y-4" dir="rtl" style="font-family: 'Amiri', 'Cairo', serif; margin-top: 8px;">
    <!-- Teacher Identity Banner (Full Single Line) -->
    <div style="background-color: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 6px 16px; margin-bottom: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; white-space: nowrap; font-family: 'Cairo', 'Amiri', serif;">
            <!-- 1. Teacher Name -->
            <div style="display: inline-flex; align-items: baseline; gap: 6px;">
                <span style="color: #64748b; font-weight: 700; font-size: 9.5pt;">اسم ولقب الأستاذ:</span>
                <span style="color: #0f172a; font-size: 13pt; font-weight: 700; font-family: 'Amiri', serif;">{{ $teacher->name }}</span>
            </div>

            <!-- 2. Subject -->
            <div style="display: inline-flex; align-items: baseline; gap: 6px;">
                <span style="color: #64748b; font-weight: 700; font-size: 9.5pt;">المادة المقررة:</span>
                <span style="color: #1e3a8a; font-size: 11pt; font-weight: 700;">{{ $teacher->primarySubject?->name ?? 'غير محدد' }}</span>
            </div>

            <!-- 3. Weekly Hours -->
            <div style="display: inline-flex; align-items: baseline; gap: 6px;">
                <span style="color: #64748b; font-weight: 700; font-size: 9.5pt;">الحجم الساعي:</span>
                <span style="color: #334155; font-size: 10pt; font-weight: 700;">{{ $teacher->weekly_hours_limit ?? 22 }} حصة/أسبوع</span>
            </div>

            <!-- 4. Compliance Rate -->
            <div style="display: inline-flex; align-items: center; gap: 8px;">
                <span style="color: #64748b; font-weight: 700; font-size: 9.5pt;">نسبة الامتثال العام:</span>
                <span style="display: inline-block; background-color: #1e3a8a; color: #ffffff; padding: 2px 10px; border-radius: 9999px; font-weight: 700; font-size: 9pt; font-family: 'Cairo', sans-serif;">
                    {{ $overallPercentage }}% ({{ $totalSubmitted }}/{{ $totalItems }})
                </span>
            </div>
        </div>
    </div>

    <!-- Summary KPI Strip (4 Cards) -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; text-align: center; font-family: 'Cairo', sans-serif;">
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 6px 8px;">
            <div style="font-size: 8pt; color: #166534; font-weight: bold;">المخططات والتدرجات</div>
            <div style="font-size: 13pt; color: #15803d; font-weight: 900; margin-top: 2px;">
                {{ $submittedMonthsCount + ($annualPlan['submitted'] ? 1 : 0) }}/10
                <span style="font-size: 9pt; font-weight: normal;">({{ round((($submittedMonthsCount + ($annualPlan['submitted'] ? 1 : 0)) / 10) * 100) }}%)</span>
            </div>
        </div>

        <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 6px 8px;">
            <div style="font-size: 8pt; color: #92400e; font-weight: bold;">الأعمال التقويمية</div>
            <div style="font-size: 13pt; color: #b45309; font-weight: 900; margin-top: 2px;">
                {{ $submittedEvalsCount }}/10
                <span style="font-size: 9pt; font-weight: normal;">({{ $evalsPercentage }}%)</span>
            </div>
        </div>

        <div style="background-color: #faf5ff; border: 1px solid #e9d5ff; border-radius: 6px; padding: 6px 8px;">
            <div style="font-size: 8pt; color: #581c87; font-weight: bold;">وتيرة إنجاز المنهاج</div>
            <div style="font-size: 13pt; color: #6d28d9; font-weight: 900; margin-top: 2px;">
                {{ $curriculumPercentage }}%
                <span style="font-size: 8.5pt; font-weight: normal;">({{ $completedCurriculumResources }}/{{ $totalCurriculumResources }})</span>
            </div>
        </div>

        <div style="background-color: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: 6px 8px;">
            <div style="font-size: 8pt; color: #115e59; font-weight: bold;">نسبة التأشير الإداري</div>
            <div style="font-size: 13pt; color: #0f766e; font-weight: 900; margin-top: 2px;">
                {{ $scorecard['visa_rate'] }}%
                <span style="font-size: 8.5pt; font-weight: normal;">({{ $scorecard['visa_count'] }}/{{ $scorecard['completed_logs_count'] }})</span>
            </div>
        </div>
    </div>

    <!-- Section 1: المخططات والتدرجات البيداغوجية -->
    <div style="margin-bottom: 14px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
            <h2 style="margin: 0; font-size: 11pt; font-weight: bold; color: #1e3a8a; border-right: 3px solid #1e3a8a; padding-right: 6px;">
                أولاً: سجل استلام وتأشير المخططات والتدرجات البيداغوجية
            </h2>
            <span style="font-size: 9pt; font-weight: bold; color: #047857;">
                المعتمدة: {{ $submittedMonthsCount + ($annualPlan['submitted'] ? 1 : 0) }} من 10
            </span>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; text-align: center; border: 1.5px solid #334155;">
            <thead>
                <tr style="background-color: #1e3a8a; color: #ffffff;">
                    <th style="border: 1px solid #cbd5e1; padding: 5px 2px; font-weight: bold;">المخطط السنوي</th>
                    @foreach($monthlyPlans as $m)
                        <th style="border: 1px solid #cbd5e1; padding: 5px 2px; font-weight: bold;">{{ $m['label'] }}</th>
                    @endforeach
                    <th style="border: 1px solid #cbd5e1; padding: 5px 2px; font-weight: bold; background-color: #0f172a;">تأشيرة الناظر</th>
                    <th style="border: 1px solid #cbd5e1; padding: 5px 2px; font-weight: bold; background-color: #0f172a;">تأشيرة المدير</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #ffffff;">
                    <td style="border: 1px solid #cbd5e1; padding: 6px 2px;">
                        @if($annualPlan['submitted'])
                            <div style="color: #047857; font-weight: bold; font-size: 10pt;">✓ مؤشر</div>
                            <div style="font-size: 7.5pt; color: #64748b;" dir="ltr">{{ $annualPlan['date']?->format('d/m') ?? '' }}</div>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    @foreach($monthlyPlans as $m)
                        <td style="border: 1px solid #cbd5e1; padding: 6px 2px;">
                            @if($m['submitted'])
                                <div style="color: #047857; font-weight: bold; font-size: 10pt;">✓ مؤشر</div>
                                <div style="font-size: 7.5pt; color: #64748b;" dir="ltr">{{ $m['date']?->format('d/m') ?? '' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                    @endforeach
                    <td style="border: 1px solid #cbd5e1; padding: 6px 2px; background-color: #f8fafc;">
                        @if($annualPlan['censor_visa'])
                            <span style="color: #047857; font-weight: bold; font-size: 8pt;">مؤشر</span>
                            <div style="font-size: 7pt; color: #64748b;">{{ $annualPlan['censor_visa_at']?->format('d/m') ?? '' }}</div>
                        @else
                            <span style="color: #94a3b8; font-size: 8pt;">قيد التأشير</span>
                        @endif
                    </td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px 2px; background-color: #f8fafc;">
                        @if($annualPlan['director_visa'])
                            <span style="color: #047857; font-weight: bold; font-size: 8pt;">معتمد</span>
                            <div style="font-size: 7pt; color: #64748b;">{{ $annualPlan['director_visa_at']?->format('d/m') ?? '' }}</div>
                        @else
                            <span style="color: #94a3b8; font-size: 8pt;">قيد الاعتماد</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Section 2: الأعمال والوثائق التقويمية -->
    <div style="margin-bottom: 14px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
            <h2 style="margin: 0; font-size: 11pt; font-weight: bold; color: #1e3a8a; border-right: 3px solid #1e3a8a; padding-right: 6px;">
                ثانياً: سجل تسليم ومتابعة الأعمال والوثائق التقويمية
            </h2>
            <span style="font-size: 9pt; font-weight: bold; color: #047857;">
                المسلّمة: {{ $submittedEvalsCount }} من 10 ({{ $evalsPercentage }}%)
            </span>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; text-align: center; border: 1.5px solid #334155;">
            <thead>
                <tr>
                    <th rowspan="2" style="border: 1px solid #cbd5e1; padding: 4px; font-weight: bold; background-color: #0f172a; color: #ffffff; width: 10%;">كشف المستوى</th>
                    <th colspan="3" style="border: 1px solid #cbd5e1; padding: 4px; font-weight: bold; background-color: #0284c7; color: #ffffff; width: 30%;">الواجبات المنزلية</th>
                    <th colspan="3" style="border: 1px solid #cbd5e1; padding: 4px; font-weight: bold; background-color: #2563eb; color: #ffffff; width: 30%;">الفروض المحروسة</th>
                    <th colspan="3" style="border: 1px solid #cbd5e1; padding: 4px; font-weight: bold; background-color: #9333ea; color: #ffffff; width: 30%;">اختبارات الفصول</th>
                </tr>
                <tr style="background-color: #f1f5f9; color: #0f172a; font-size: 8pt;">
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">واجب 1</th>
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">واجب 2</th>
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">واجب 3</th>
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">فرض 1</th>
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">فرض 2</th>
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">فرض 3</th>
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">اختبار 1</th>
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">اختبار 2</th>
                    <th style="border: 1px solid #cbd5e1; padding: 3px;">اختبار 3</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #ffffff;">
                    @foreach(['diagnostic_test', 'homework_1', 'homework_2', 'homework_3', 'quiz_1', 'quiz_2', 'quiz_3', 'exam_1', 'exam_2', 'exam_3'] as $k)
                        <td style="border: 1px solid #cbd5e1; padding: 6px 2px;">
                            @if($evalItems[$k]['submitted'])
                                <div style="color: #047857; font-weight: bold; font-size: 10pt;">✓ مؤشر</div>
                                <div style="font-size: 7.5pt; color: #64748b;" dir="ltr">{{ $evalItems[$k]['date']?->format('d/m') ?? '' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Section 3: تقدم المنهاج من واقع دفتر النصوص -->
    <div style="margin-bottom: 14px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
            <h2 style="margin: 0; font-size: 11pt; font-weight: bold; color: #1e3a8a; border-right: 3px solid #1e3a8a; padding-right: 6px;">
                ثالثاً: وضعية تقدم المنهاج وتدرج الدروس الميدانية (من واقع دفتر النصوص)
            </h2>
            <span style="font-size: 9pt; font-weight: bold; color: #6d28d9;">
                نسبة إنجاز المنهاج العام: {{ $curriculumPercentage }}%
            </span>
        </div>

        @if(isset($curriculumOverview) && $curriculumOverview->isNotEmpty())
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; text-align: center; border: 1.5px solid #cbd5e1;">
                <thead>
                    <tr style="background-color: #6d28d9; color: #ffffff;">
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 22%;">القسم المسند</th>
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 20%;">المستوى الدراسي</th>
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 20%;">الموارد المعرفية المنجزة</th>
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 18%;">المقاطع المكتملة</th>
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 20%;">نسبة التقدم</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($curriculumOverview as $row)
                        <tr style="background-color: #ffffff;">
                            <td style="border: 1px solid #cbd5e1; padding: 5px; font-weight: bold; color: #0f172a;">{{ $row['classroom']->name }}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 5px; color: #64748b;">{{ $row['level']?->name ?? '—' }}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 5px; font-weight: bold; color: #1e3a8a;">{{ $row['completed_resources'] }} / {{ $row['total_resources'] }}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 5px; color: #334155;">{{ $row['completed_sequences'] }} / {{ $row['total_sequences'] }}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 5px; font-weight: bold; color: #6d28d9;">
                                {{ $row['percentage'] }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 8px; text-align: center; color: #94a3b8; font-size: 8.5pt;">
                لا توجد حصص مسندة أو موارد معرفية مسجلة في المنهاج حالياً.
            </div>
        @endif
    </div>

    <!-- Section 4: سجل الزيارات التفتيشية السابقة -->
    <div style="margin-bottom: 14px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
            <h2 style="margin: 0; font-size: 11pt; font-weight: bold; color: #1e3a8a; border-right: 3px solid #1e3a8a; padding-right: 6px;">
                رابعاً: سجل زيارات التفتيش البيداغوجي السابقة وتوجيهات الإدارة التربوية
            </h2>
            <span style="font-size: 9pt; font-weight: bold; color: #d97706;">
                الزيارات المسجلة: {{ $scorecard['inspections_count'] }}
                @if($scorecard['average_score'] !== null)
                    (معدل النقطة: {{ number_format($scorecard['average_score'], 2) }}/20)
                @endif
            </span>
        </div>

        @if(isset($inspectionHistory) && $inspectionHistory->isNotEmpty())
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; text-align: center; border: 1.5px solid #cbd5e1;">
                <thead>
                    <tr style="background-color: #d97706; color: #ffffff;">
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 18%;">تاريخ الزيارة والمفتش</th>
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 22%;">موضوع الحصة والقسم</th>
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 14%;">النقطة التربوية</th>
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 16%;">التقدير العام</th>
                        <th style="border: 1px solid #cbd5e1; padding: 5px; width: 30%;">أهم التوجيهات والتوصيات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inspectionHistory->take(3) as $insp)
                        <tr style="background-color: #ffffff;">
                            <td style="border: 1px solid #cbd5e1; padding: 5px; font-weight: bold; color: #0f172a;">
                                <div>{{ $insp->visit_date?->format('Y-m-d') ?? '—' }}</div>
                                <div style="font-size: 7.5pt; color: #64748b;">{{ $insp->inspector_name }}</div>
                            </td>
                            <td style="border: 1px solid #cbd5e1; padding: 5px; color: #1e3a8a;">
                                {{ $insp->lesson_topic }} {{ $insp->classroom ? '('.$insp->classroom->name.')' : '' }}
                            </td>
                            <td style="border: 1px solid #cbd5e1; padding: 5px; font-weight: bold; color: #1e3a8a;">
                                {{ $insp->pedagogical_score ? number_format($insp->pedagogical_score, 2).' / 20' : '—' }}
                            </td>
                            <td style="border: 1px solid #cbd5e1; padding: 5px; font-weight: bold; color: #047857;">
                                {{ $insp->appreciation_label ?? $insp->pedagogical_appreciation ?? '—' }}
                            </td>
                            <td style="border: 1px solid #cbd5e1; padding: 5px; text-align: start; color: #334155; font-size: 8pt;">
                                {{ $insp->strengths ?? $insp->areas_for_improvement ?? 'توجيهات مسجلة بتقرير الزيارة' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 8px; text-align: center; color: #94a3b8; font-size: 8.5pt;">
                لم تسجل زيارات تفتيش سابقة خلال الموسم الحالي.
            </div>
        @endif
    </div>

    <!-- Section 5: بطاقة المعاينة وشبكة التقييم لزيارة التفتيش الميدانية الحالية -->
    <div style="margin-bottom: 12px; border: 1.5px solid #0284c7; border-radius: 6px; padding: 8px 12px; background-color: #f0f9ff;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; border-bottom: 1px solid #bae6fd; padding-bottom: 4px;">
            <h3 style="margin: 0; font-size: 11pt; font-weight: bold; color: #0369a1;">
                خامساً: بطاقة المعاينة وشبكة التقييم لزيارة التفتيش الميدانية الحالية
            </h3>
            <span style="font-size: 8.5pt; color: #0284c7; font-weight: bold;">(تعبأ من طرف مفتش التربية الوطنية أثناء الزيارة)</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; font-size: 8.5pt; font-family: 'Cairo', sans-serif; margin-bottom: 8px;">
            <div><strong>موضوع الحصة:</strong> .....................................</div>
            <div><strong>القسم المعاين:</strong> ......................</div>
            <div><strong>النقطة الممنوحة:</strong> ......... / 20</div>
            <div><strong>التقدير:</strong> [ ] ممتاز  [ ] ج.جداً  [ ] جيد  [ ] مقبول</div>
        </div>

        <div style="font-size: 8.5pt; font-family: 'Cairo', sans-serif;">
            <div style="font-weight: bold; color: #0369a1; margin-bottom: 2px;">ملاحظات وتوجيهات مفتش التربية الوطنية:</div>
            <div style="color: #94a3b8; line-height: 1.8; font-family: monospace;">
                ................................................................................................................................................................................................................................................<br>
                ................................................................................................................................................................................................................................................
            </div>
        </div>
    </div>

    <!-- Signatures Quad -->
    <div style="margin-top: 14px; padding-top: 6px; border-top: 1px dashed #cbd5e1;">
        <table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 9.5pt;">
            <tbody>
                <tr>
                    <td style="width: 25%; vertical-align: top;">
                        <strong style="color: #0f172a; font-size: 10pt;">توقيع واطلاع الأستاذ(ة)</strong>
                        <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">(اطلع وصودق عليه)</div>
                    </td>
                    <td style="width: 25%; vertical-align: top;">
                        <strong style="color: #0f172a; font-size: 10pt;">ناظر الدروس</strong>
                        <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">(التأشير والختم)</div>
                    </td>
                    <td style="width: 25%; vertical-align: top;">
                        <strong style="color: #0f172a; font-size: 10pt;">مدير المؤسسة</strong>
                        <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">(الختم والمصادقة الرسمية)</div>
                    </td>
                    <td style="width: 25%; vertical-align: top;">
                        <strong style="color: #0f172a; font-size: 10pt;">مفتش التربية الوطنية</strong>
                        <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">(التأشير والختم)</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

