<?php

namespace App\Services;

use App\Models\EvaluationWorkReceipt;
use App\Models\PedagogicalPlanReceipt;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use ZipArchive;

class TeacherReceiptsService
{
    /**
     * Build all view data for a teacher's pedagogical and evaluation receipt profile.
     *
     * @return array<string, mixed>
     */
    public function viewData(int|User $teacher, ?string $academicYear = null): array
    {
        $teacherModel = $teacher instanceof User
            ? $teacher
            : User::with(['primarySubject', 'secondarySubject'])->findOrFail($teacher);

        $school = SchoolSetting::current();
        $year = $academicYear ?: ($school?->academic_year ?? '2026-2027');

        $planReceipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $teacherModel->id,
            'academic_year' => $year,
        ]);

        $evalReceipt = EvaluationWorkReceipt::firstOrNew([
            'user_id' => $teacherModel->id,
            'academic_year' => $year,
        ]);

        // Monthly plans
        $monthlyPlans = [];
        $submittedMonthsCount = 0;
        foreach (PedagogicalPlanReceipt::MONTHS as $key => $label) {
            $isSubmitted = (bool) $planReceipt->{$key.'_submitted'};
            $subDate = $planReceipt->{$key.'_date'};
            if ($isSubmitted) {
                $submittedMonthsCount++;
            }
            $monthlyPlans[$key] = [
                'key' => $key,
                'label' => $label,
                'submitted' => $isSubmitted,
                'date' => $subDate,
            ];
        }

        $monthsPercentage = round(($submittedMonthsCount / 9) * 100, 1);

        // Evaluation works
        $evalDefinitions = [
            'diagnostic_test' => ['label' => 'اختبار كشف المستوى', 'category' => 'تقويم تشخيصي', 'order' => 1],
            'homework_1' => ['label' => 'الواجب المنزلي (1)', 'category' => 'الفصل الأول', 'order' => 2],
            'quiz_1' => ['label' => 'الفرض المحروس (1)', 'category' => 'الفصل الأول', 'order' => 3],
            'exam_1' => ['label' => 'اختبار الفصل الأول', 'category' => 'الفصل الأول', 'order' => 4],
            'homework_2' => ['label' => 'الواجب المنزلي (2)', 'category' => 'الفصل الثاني', 'order' => 5],
            'quiz_2' => ['label' => 'الفرض المحروس (2)', 'category' => 'الفصل الثاني', 'order' => 6],
            'exam_2' => ['label' => 'اختبار الفصل الثاني', 'category' => 'الفصل الثاني', 'order' => 7],
            'homework_3' => ['label' => 'الواجب المنزلي (3)', 'category' => 'الفصل الثالث', 'order' => 8],
            'quiz_3' => ['label' => 'الفرض المحروس (3)', 'category' => 'الفصل الثالث', 'order' => 9],
            'exam_3' => ['label' => 'اختبار الفصل الثالث', 'category' => 'الفصل الثالث', 'order' => 10],
        ];

        $evalItems = [];
        $submittedEvalsCount = 0;
        foreach ($evalDefinitions as $key => $meta) {
            $isSubmitted = (bool) $evalReceipt->{$key.'_submitted'};
            $subDate = $evalReceipt->{$key.'_date'};
            if ($isSubmitted) {
                $submittedEvalsCount++;
            }
            $evalItems[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'category' => $meta['category'],
                'submitted' => $isSubmitted,
                'date' => $subDate,
            ];
        }

        $evalsPercentage = round(($submittedEvalsCount / 10) * 100, 1);

        // Total compliance score (20 items in total)
        $totalItems = 20;
        $totalSubmitted = ($planReceipt->annual_plan_submitted ? 1 : 0) + $submittedMonthsCount + $submittedEvalsCount;
        $overallPercentage = round(($totalSubmitted / $totalItems) * 100, 1);

        // Curriculum progress stats from CurriculumProgressService
        $curriculumOverview = app(CurriculumProgressService::class)->overview($teacherModel);
        $totalCurriculumResources = (int) $curriculumOverview->sum('total_resources');
        $completedCurriculumResources = (int) $curriculumOverview->sum('completed_resources');
        $curriculumPercentage = $totalCurriculumResources > 0
            ? (int) round(($completedCurriculumResources / $totalCurriculumResources) * 100)
            : 0;
        $totalCurriculumSequences = (int) $curriculumOverview->sum('total_sequences');
        $completedCurriculumSequences = (int) $curriculumOverview->sum('completed_sequences');

        return [
            'teacher' => $teacherModel,
            'school' => $school,
            'academicYear' => $year,
            'planReceipt' => $planReceipt,
            'evalReceipt' => $evalReceipt,
            'annualPlan' => [
                'submitted' => (bool) $planReceipt->annual_plan_submitted,
                'date' => $planReceipt->annual_plan_date,
                'director_visa' => (bool) $planReceipt->director_visa,
                'director_visa_at' => $planReceipt->director_visa_at,
                'censor_visa' => (bool) $planReceipt->censor_visa,
                'censor_visa_at' => $planReceipt->censor_visa_at,
                'notes' => $planReceipt->notes,
            ],
            'monthlyPlans' => $monthlyPlans,
            'submittedMonthsCount' => $submittedMonthsCount,
            'monthsPercentage' => $monthsPercentage,
            'evalItems' => $evalItems,
            'submittedEvalsCount' => $submittedEvalsCount,
            'evalsPercentage' => $evalsPercentage,
            'evalNotes' => $evalReceipt->notes,
            'totalItems' => $totalItems,
            'totalSubmitted' => $totalSubmitted,
            'overallPercentage' => $overallPercentage,
            'curriculumOverview' => $curriculumOverview,
            'totalCurriculumResources' => $totalCurriculumResources,
            'completedCurriculumResources' => $completedCurriculumResources,
            'curriculumPercentage' => $curriculumPercentage,
            'totalCurriculumSequences' => $totalCurriculumSequences,
            'completedCurriculumSequences' => $completedCurriculumSequences,
            'generatedAt' => now(),
        ];
    }

    /**
     * Generate an official individual receipt and compliance status PDF voucher.
     */
    public function generatePdf(int|User $teacher, ?string $academicYear = null): string
    {
        $data = $this->viewData($teacher, $academicYear);
        $html = view('print.teacher-receipts-individual', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->portrait()
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate an official individual receipt and compliance status Word (.docx) document.
     */
    public function generateWord(int|User $teacher, ?string $academicYear = null): string
    {
        $data = $this->viewData($teacher, $academicYear);
        $teacherModel = $data['teacher'];
        $school = $data['school'];
        $year = $data['academicYear'];

        $phpWord = new PhpWord;
        $phpWord->getCompatibility()->setOoxmlVersion(15);
        $phpWord->setDefaultFontName('Cairo');
        $phpWord->setDefaultFontSize(9.5);
        $phpWord->setDefaultParagraphStyle([
            'bidi' => true,
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            'lineHeight' => 1.0,
        ]);

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 500,
            'marginBottom' => 500,
            'marginLeft' => 600,
            'marginRight' => 600,
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
        ]);

        // ----------------------------------------------------
        // 1. Official Ministerial Header (3 Columns)
        // ----------------------------------------------------
        $headerTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMarginTop' => 30,
            'cellMarginBottom' => 30,
            'cellMarginLeft' => 50,
            'cellMarginRight' => 50,
        ]);
        $hr = $headerTable->addRow();

        // Left cell: Academic Year & Metadata
        $hl = $hr->addCell(3200, ['borderSize' => 0]);
        $hl->addText('السنة الدراسية: '.$year, ['name' => 'Cairo', 'size' => 9.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);
        $hl->addText('تاريخ الإصدار: '.now()->locale('ar')->translatedFormat('d F Y'), ['name' => 'Cairo', 'size' => 8.5, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);
        $hl->addText('سجل المتابعة البيداغوجية: نسخة الأستاذ الرسمية', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);

        // Center cell: Republic & Ministry & Directorate
        $hc = $hr->addCell(4200, ['borderSize' => 0]);
        $hc->addText('الجمهورية الجزائرية الديمقراطية الشعبية', ['name' => 'Amiri', 'size' => 12.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $hc->addText('وزارة التربية الوطنية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $hc->addText('مديرية التربية لولاية: '.($school?->municipality_name ?? 'المغير'), ['name' => 'Amiri', 'size' => 10, 'bold' => true, 'color' => '334155', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Right cell: School & Municipality
        $hrCell = $hr->addCell(3000, ['borderSize' => 0]);
        $hrCell->addText($school?->school_name ?? 'متوسطة حي 900 مسكن', ['name' => 'Amiri', 'size' => 11.5, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $schoolMeta = $school?->municipality_name ?? 'المغير';
        if (! empty($school?->school_code)) {
            $schoolMeta .= ' · الرقم: '.$school->school_code;
        }
        $hrCell->addText($schoolMeta, ['name' => 'Cairo', 'size' => 8.5, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 80, 'spaceAfter' => 0]);

        // ----------------------------------------------------
        // 2. Document Title Box
        // ----------------------------------------------------
        $titleTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 12,
            'borderColor' => '1E3A8A',
            'cellMarginTop' => 80,
            'cellMarginBottom' => 80,
            'cellMarginLeft' => 120,
            'cellMarginRight' => 120,
        ]);
        $tr = $titleTable->addRow();
        $tc = $tr->addCell(10400, ['bgColor' => 'F8FAFC']);
        $tc->addText('كشف وضعية استلام وتأشير المخططات والأعمال التقويمية', ['name' => 'Amiri', 'size' => 14, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 100, 'spaceAfter' => 0]);

        // ----------------------------------------------------
        // 3. Teacher Identity Banner
        // ----------------------------------------------------
        $idTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => 'CBD5E1',
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 100,
            'cellMarginRight' => 100,
        ]);
        $idRow = $idTable->addRow();

        // Left: Compliance Percentage
        $idLeft = $idRow->addCell(3200, ['bgColor' => 'F1F5F9']);
        $idLeftRun = $idLeft->addTextRun(['alignment' => Jc::END, 'bidi' => true]);
        $idLeftRun->addText('نسبة الامتثال العام: ', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '64748B', 'rtl' => true]);
        $idLeftRun->addText($data['overallPercentage'].'% ('.$data['totalSubmitted'].'/'.$data['totalItems'].')', ['name' => 'Cairo', 'size' => 9.5, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true]);

        // Center: Subject
        $idCenter = $idRow->addCell(3400, ['bgColor' => 'F1F5F9']);
        $idCenterRun = $idCenter->addTextRun(['alignment' => Jc::CENTER, 'bidi' => true]);
        $idCenterRun->addText('المادة المقررة: ', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '64748B', 'rtl' => true]);
        $idCenterRun->addText($teacherModel->primarySubject?->name ?? 'غير محدد', ['name' => 'Cairo', 'size' => 10, 'bold' => true, 'color' => '0F172A', 'rtl' => true]);

        // Right: Teacher Name
        $idRight = $idRow->addCell(3800, ['bgColor' => 'F1F5F9']);
        $idRightRun = $idRight->addTextRun(['alignment' => Jc::START, 'bidi' => true]);
        $idRightRun->addText('اسم ولقب الأستاذ: ', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '64748B', 'rtl' => true]);
        $idRightRun->addText($teacherModel->name, ['name' => 'Amiri', 'size' => 11.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true]);

        $section->addText('', [], ['spaceBefore' => 140, 'spaceAfter' => 0]);

        // ----------------------------------------------------
        // 4. Section 1: المخططات والتدرجات البيداغوجية
        // ----------------------------------------------------
        $s1HeaderTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 0,
        ]);
        $s1Row = $s1HeaderTable->addRow();
        $s1Left = $s1Row->addCell(4000, ['borderSize' => 0]);
        $s1SubmittedCount = $data['submittedMonthsCount'] + ($data['annualPlan']['submitted'] ? 1 : 0);
        $s1Left->addText('المخططات المعتمدة: '.$s1SubmittedCount.' من 10 ('.round(($s1SubmittedCount / 10) * 100).'%)', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '047857', 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);

        $s1Right = $s1Row->addCell(6400, ['borderSize' => 0]);
        $s1Right->addText('أولاً: سجل استلام وتأشير المخططات والتدرجات البيداغوجية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 60, 'spaceAfter' => 0]);

        // Section 1 Table: 12 Columns
        // Left to Right physically: [تأشيرة المدير: 1260] [تأشيرة الناظر: 1260] [ماي..سبتمبر: 9 x 770 = 6930] [المخطط السنوي: 950]
        $planTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => '334155',
            'cellMarginTop' => 40,
            'cellMarginBottom' => 40,
            'cellMarginLeft' => 40,
            'cellMarginRight' => 40,
        ]);

        $pHead = $planTable->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        $pHead->addCell(1260, ['bgColor' => '0F172A'])->addText('تأشيرة المدير', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $pHead->addCell(1260, ['bgColor' => '0F172A'])->addText('تأشيرة الناظر', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $monthsReversed = array_reverse(PedagogicalPlanReceipt::MONTHS, true);
        foreach ($monthsReversed as $mKey => $mLabel) {
            $pHead->addCell(770, ['bgColor' => '1E3A8A'])->addText($mLabel, ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        }
        $pHead->addCell(950, ['bgColor' => '1E3A8A'])->addText('المخطط السنوي', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Data Row
        $pData = $planTable->addRow(null, ['cantSplit' => true]);

        // Director Visa
        $dCell = $pData->addCell(1260, ['bgColor' => 'F8FAFC']);
        if ($data['annualPlan']['director_visa']) {
            $dRun = $dCell->addTextRun(['alignment' => Jc::CENTER, 'bidi' => true]);
            $dRun->addText('✓ اعتمد', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => '15803D', 'rtl' => true]);
            if ($data['annualPlan']['director_visa_at']) {
                $dRun->addText(' '.Carbon::parse($data['annualPlan']['director_visa_at'])->format('d/m'), ['name' => 'Cairo', 'size' => 7, 'color' => '64748B', 'rtl' => true]);
            }
        } else {
            $dCell->addText('قيد الاعتماد', ['name' => 'Cairo', 'size' => 7.5, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        }

        // Censor Visa
        $cCell = $pData->addCell(1260, ['bgColor' => 'F8FAFC']);
        if ($data['annualPlan']['censor_visa']) {
            $cRun = $cCell->addTextRun(['alignment' => Jc::CENTER, 'bidi' => true]);
            $cRun->addText('✓ تم التأشير', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true]);
            if ($data['annualPlan']['censor_visa_at']) {
                $cRun->addText(' '.Carbon::parse($data['annualPlan']['censor_visa_at'])->format('d/m'), ['name' => 'Cairo', 'size' => 7, 'color' => '64748B', 'rtl' => true]);
            }
        } else {
            $cCell->addText('قيد الانتظار', ['name' => 'Cairo', 'size' => 7.5, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        }

        // 9 Months (May down to Sep)
        foreach ($monthsReversed as $mKey => $mLabel) {
            $mCell = $pData->addCell(770);
            $isSub = $data['monthlyPlans'][$mKey]['submitted'] ?? false;
            $mDate = $data['monthlyPlans'][$mKey]['date'] ?? null;
            $mRun = $mCell->addTextRun(['alignment' => Jc::CENTER, 'bidi' => true]);

            if ($isSub) {
                $mRun->addText('##CB_ON##', ['name' => 'MS Gothic', 'size' => 9, 'bold' => true, 'color' => '15803D', 'rtl' => true]);
                $mRun->addText(' ');
                if ($mDate) {
                    $mRun->addText('##DATE_VAL:'.Carbon::parse($mDate)->format('d/m').'##', ['name' => 'Cairo', 'size' => 6.5, 'bold' => true, 'color' => '0369A1', 'rtl' => true]);
                } else {
                    $mRun->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 6.5, 'color' => '94A3B8', 'rtl' => true]);
                }
            } else {
                $mRun->addText('##CB_OFF##', ['name' => 'MS Gothic', 'size' => 9, 'bold' => true, 'color' => '475569', 'rtl' => true]);
                $mRun->addText(' ');
                $mRun->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 6.5, 'color' => '94A3B8', 'rtl' => true]);
            }
        }

        // Annual Plan
        $apCell = $pData->addCell(950);
        $isApSub = $data['annualPlan']['submitted'] ?? false;
        $apDate = $data['annualPlan']['date'] ?? null;
        $apRun = $apCell->addTextRun(['alignment' => Jc::CENTER, 'bidi' => true]);
        if ($isApSub) {
            $apRun->addText('##CB_ON##', ['name' => 'MS Gothic', 'size' => 9, 'bold' => true, 'color' => '15803D', 'rtl' => true]);
            $apRun->addText(' ');
            if ($apDate) {
                $apRun->addText('##DATE_VAL:'.Carbon::parse($apDate)->format('d/m').'##', ['name' => 'Cairo', 'size' => 6.5, 'bold' => true, 'color' => '0369A1', 'rtl' => true]);
            } else {
                $apRun->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 6.5, 'color' => '94A3B8', 'rtl' => true]);
            }
        } else {
            $apRun->addText('##CB_OFF##', ['name' => 'MS Gothic', 'size' => 9, 'bold' => true, 'color' => '475569', 'rtl' => true]);
            $apRun->addText(' ');
            $apRun->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 6.5, 'color' => '94A3B8', 'rtl' => true]);
        }

        if (! empty($data['annualPlan']['notes'])) {
            $section->addText('ملاحظات الإدارة حول المخططات: '.$data['annualPlan']['notes'], ['name' => 'Cairo', 'size' => 8, 'color' => '1E40AF', 'italic' => true, 'rtl' => true], ['spaceBefore' => 40, 'spaceAfter' => 0]);
        }

        $section->addText('', [], ['spaceBefore' => 140, 'spaceAfter' => 0]);

        // ----------------------------------------------------
        // 5. Section 2: تسليم الأعمال والوثائق التقويمية
        // ----------------------------------------------------
        $s2HeaderTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 0,
        ]);
        $s2Row = $s2HeaderTable->addRow();
        $s2Left = $s2Row->addCell(4000, ['borderSize' => 0]);
        $s2Left->addText('الأعمال المسلّمة: '.$data['submittedEvalsCount'].' من 10 ('.$data['evalsPercentage'].'%)', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => 'B45309', 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);

        $s2Right = $s2Row->addCell(6400, ['borderSize' => 0]);
        $s2Right->addText('ثانياً: سجل تسليم ومتابعة الأعمال والوثائق التقويمية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 60, 'spaceAfter' => 0]);

        // Section 2 Table: 10 Columns
        // Left to Right physically: [اختبارات الفصول: 3 x 1020 = 3060] [الفروض المحروسة: 3 x 1020 = 3060] [الواجبات المنزلية: 3 x 1020 = 3060] [كشف المستوى: 1220]
        $evalTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => '334155',
            'cellMarginTop' => 40,
            'cellMarginBottom' => 40,
            'cellMarginLeft' => 40,
            'cellMarginRight' => 40,
        ]);

        // Group Header Row
        $eHead1 = $evalTable->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        $eHead1->addCell(3060, ['gridSpan' => 3, 'bgColor' => '7E22CE'])->addText('اختبارات الفصول', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead1->addCell(3060, ['gridSpan' => 3, 'bgColor' => '4338CA'])->addText('الفروض المحروسة', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead1->addCell(3060, ['gridSpan' => 3, 'bgColor' => '0284C7'])->addText('الواجبات المنزلية', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead1->addCell(1220, ['vMerge' => 'restart', 'bgColor' => '334155'])->addText('كشف المستوى', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Sub Header Row
        $eHead2 = $evalTable->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        $eHead2->addCell(1020, ['bgColor' => 'F3E8FF'])->addText('اختبار 3', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead2->addCell(1020, ['bgColor' => 'F3E8FF'])->addText('اختبار 2', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead2->addCell(1020, ['bgColor' => 'F3E8FF'])->addText('اختبار 1', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $eHead2->addCell(1020, ['bgColor' => 'EEF2FF'])->addText('فرض 3', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead2->addCell(1020, ['bgColor' => 'EEF2FF'])->addText('فرض 2', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead2->addCell(1020, ['bgColor' => 'EEF2FF'])->addText('فرض 1', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $eHead2->addCell(1020, ['bgColor' => 'E0F2FE'])->addText('واجب 3', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead2->addCell(1020, ['bgColor' => 'E0F2FE'])->addText('واجب 2', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead2->addCell(1020, ['bgColor' => 'E0F2FE'])->addText('واجب 1', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $eHead2->addCell(1220, ['vMerge' => 'continue']);

        // Data Row
        $eData = $evalTable->addRow(null, ['cantSplit' => true]);

        $addEvalCell = function ($row, string $itemKey, int $width = 1020) use ($data) {
            $cell = $row->addCell($width);
            $isSub = $data['evalItems'][$itemKey]['submitted'] ?? false;
            $eDate = $data['evalItems'][$itemKey]['date'] ?? null;
            $run = $cell->addTextRun(['alignment' => Jc::CENTER, 'bidi' => true]);

            if ($isSub) {
                $run->addText('##CB_ON##', ['name' => 'MS Gothic', 'size' => 9, 'bold' => true, 'color' => '15803D', 'rtl' => true]);
                $run->addText(' ');
                if ($eDate) {
                    $run->addText('##DATE_VAL:'.Carbon::parse($eDate)->format('d/m').'##', ['name' => 'Cairo', 'size' => 6.5, 'bold' => true, 'color' => '0369A1', 'rtl' => true]);
                } else {
                    $run->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 6.5, 'color' => '94A3B8', 'rtl' => true]);
                }
            } else {
                $run->addText('##CB_OFF##', ['name' => 'MS Gothic', 'size' => 9, 'bold' => true, 'color' => '475569', 'rtl' => true]);
                $run->addText(' ');
                $run->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 6.5, 'color' => '94A3B8', 'rtl' => true]);
            }
        };

        // Exams (3, 2, 1)
        $addEvalCell($eData, 'exam_3');
        $addEvalCell($eData, 'exam_2');
        $addEvalCell($eData, 'exam_1');

        // Quizzes (3, 2, 1)
        $addEvalCell($eData, 'quiz_3');
        $addEvalCell($eData, 'quiz_2');
        $addEvalCell($eData, 'quiz_1');

        // Homeworks (3, 2, 1)
        $addEvalCell($eData, 'homework_3');
        $addEvalCell($eData, 'homework_2');
        $addEvalCell($eData, 'homework_1');

        // Diagnostic test
        $addEvalCell($eData, 'diagnostic_test', 1220);

        if (! empty($data['evalNotes'])) {
            $section->addText('ملاحظات الإدارة حول الأعمال التقويمية: '.$data['evalNotes'], ['name' => 'Cairo', 'size' => 8, 'color' => '92400E', 'italic' => true, 'rtl' => true], ['spaceBefore' => 40, 'spaceAfter' => 0]);
        }

        $section->addText('', [], ['spaceBefore' => 200, 'spaceAfter' => 0]);

        // ----------------------------------------------------
        // 6. Official Certification & Signatures Box
        // ----------------------------------------------------
        $section->addText('حرر بـ '.($school?->municipality_name ?? 'المغير').' في: '.now()->locale('ar')->translatedFormat('d F Y'), ['name' => 'Cairo', 'size' => 9.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 120, 'spaceAfter' => 0]);

        $sigTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);
        $sr = $sigTable->addRow();

        // Left: Principal
        $scLeft = $sr->addCell(3466, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $scLeft->addText('مدير المؤسسة', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $scLeft->addText('(الختم والمصادقة الرسمية)', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true, 'spaceBefore' => 600]);

        // Center: Censor
        $scCenter = $sr->addCell(3466, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $scCenter->addText('ناظر الدروس', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $scCenter->addText('(التأشير والختم)', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true, 'spaceBefore' => 600]);

        // Right: Teacher
        $scRight = $sr->addCell(3468, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $scRight->addText('توقيع الأستاذ(ة) المعني(ة)', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $scRight->addText('(اطلع وصودق عليه)', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true, 'spaceBefore' => 600]);

        // Save Word to temp file and post-process
        $tempFile = tempnam(sys_get_temp_dir(), 'teacher_word_').'.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        $this->postProcessDocxForInteractiveControls($tempFile);

        $content = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /**
     * Post-process DOCX file to inject native interactive Word Content Controls
     * for checkboxes (w14:checkbox) and editable date pickers (w:date),
     * and upgrade settings.xml to modern Word compatibility mode (w:val="15").
     */
    private function postProcessDocxForInteractiveControls(string $filePath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if (! $xml) {
            $zip->close();

            return;
        }

        if (! str_contains($xml, 'xmlns:w14=')) {
            $xml = str_replace(
                '<w:document ',
                '<w:document xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" ',
                $xml
            );
        }

        if (str_contains($xml, 'mc:Ignorable=')) {
            $xml = preg_replace('/mc:Ignorable="([^"]*)"/', 'mc:Ignorable="$1 w14"', $xml);
        } else {
            $xml = str_replace(
                '<w:document ',
                '<w:document xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="w14" ',
                $xml
            );
        }

        $idCounter = 3000000;

        $makeCheckboxSdt = function (bool $checked, int $id): string {
            $checkedVal = $checked ? '1' : '0';
            $symbol = $checked ? '☑' : '☐';
            $colorXml = $checked ? '<w:color w:val="15803D"/>' : '<w:color w:val="475569"/>';

            return '<w:sdt>'
                .'<w:sdtPr>'
                    .'<w:id w:val="'.$id.'"/>'
                    .'<w14:checkbox>'
                        .'<w14:checked w14:val="'.$checkedVal.'"/>'
                        .'<w14:checkedState w14:val="2611" w14:font="MS Gothic"/>'
                        .'<w14:uncheckedState w14:val="2610" w14:font="MS Gothic"/>'
                    .'</w14:checkbox>'
                .'</w:sdtPr>'
                .'<w:sdtContent>'
                    .'<w:r>'
                        .'<w:rPr>'
                            .'<w:rFonts w:ascii="MS Gothic" w:eastAsia="MS Gothic" w:hAnsi="MS Gothic" w:hint="eastAsia"/>'
                            .'<w:sz w:val="18"/>'
                            .$colorXml
                        .'</w:rPr>'
                        .'<w:t>'.$symbol.'</w:t>'
                    .'</w:r>'
                .'</w:sdtContent>'
            .'</w:sdt>';
        };

        $makeDateSdt = function (?string $dateText, int $id): string {
            $hasDate = ! empty($dateText);
            $display = $hasDate ? $dateText : '.. / ..';
            $colorXml = $hasDate ? '<w:color w:val="0369A1"/><w:b/>' : '<w:color w:val="94A3B8"/>';

            return '<w:sdt>'
                .'<w:sdtPr>'
                    .'<w:id w:val="'.$id.'"/>'
                    .'<w:placeholder><w:docPart w:val="DefaultPlaceholder"/></w:placeholder>'
                    .'<w:date>'
                        .'<w:dateFormat w:val="dd/MM"/>'
                        .'<w:lid w:val="ar-DZ"/>'
                        .'<w:storeMappedDataAs w:val="text"/>'
                        .'<w:calendar w:val="gregorian"/>'
                    .'</w:date>'
                .'</w:sdtPr>'
                .'<w:sdtContent>'
                    .'<w:r>'
                        .'<w:rPr>'
                            .'<w:rFonts w:ascii="Cairo" w:hAnsi="Cairo" w:cs="Cairo"/>'
                            .'<w:sz w:val="13"/>'
                            .$colorXml
                        .'</w:rPr>'
                        .'<w:t xml:space="preserve">'.$display.'</w:t>'
                    .'</w:r>'
                .'</w:sdtContent>'
            .'</w:sdt>';
        };

        $xml = preg_replace_callback('/<w:r\b[^>]*>(?:(?!<\/w:r>).)*?##CB_OFF##.*?<\/w:r>/s', function () use ($makeCheckboxSdt, &$idCounter) {
            return $makeCheckboxSdt(false, ++$idCounter);
        }, $xml);

        $xml = preg_replace_callback('/<w:r\b[^>]*>(?:(?!<\/w:r>).)*?##CB_ON##.*?<\/w:r>/s', function () use ($makeCheckboxSdt, &$idCounter) {
            return $makeCheckboxSdt(true, ++$idCounter);
        }, $xml);

        $xml = preg_replace_callback('/<w:r\b[^>]*>(?:(?!<\/w:r>).)*?##DATE_EMPTY##.*?<\/w:r>/s', function () use ($makeDateSdt, &$idCounter) {
            return $makeDateSdt(null, ++$idCounter);
        }, $xml);

        $xml = preg_replace_callback('/<w:r\b[^>]*>(?:(?!<\/w:r>).)*?##DATE_VAL:([^#]+)##.*?<\/w:r>/s', function ($matches) use ($makeDateSdt, &$idCounter) {
            return $makeDateSdt($matches[1], ++$idCounter);
        }, $xml);

        $zip->addFromString('word/document.xml', $xml);

        // Modern Word Compatibility Mode (w:val="15")
        $settingsXml = $zip->getFromName('word/settings.xml');
        if ($settingsXml) {
            if (! str_contains($settingsXml, 'xmlns:w14=')) {
                $settingsXml = str_replace(
                    '<w:settings ',
                    '<w:settings xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" ',
                    $settingsXml
                );
            }

            if (str_contains($settingsXml, 'mc:Ignorable=')) {
                $settingsXml = preg_replace('/mc:Ignorable="([^"]*)"/', 'mc:Ignorable="$1 w14 w15"', $settingsXml);
            } else {
                $settingsXml = str_replace(
                    '<w:settings ',
                    '<w:settings xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="w14 w15" ',
                    $settingsXml
                );
            }

            if (str_contains($settingsXml, 'compatibilityMode')) {
                $settingsXml = preg_replace(
                    '/(<w:compatSetting[^>]*compatibilityMode[^>]*w:val=")[^"]*(")/',
                    '${1}15${2}',
                    $settingsXml
                );
            } else {
                if (str_contains($settingsXml, '</w:compat>')) {
                    $settingsXml = str_replace(
                        '</w:compat>',
                        '<w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>',
                        $settingsXml
                    );
                } elseif (str_contains($settingsXml, '</w:settings>')) {
                    $settingsXml = str_replace(
                        '</w:settings>',
                        '<w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat></w:settings>',
                        $settingsXml
                    );
                }
            }

            $settingsXml = preg_replace('/<w:checkCompatibility[^>]*\/>/', '', $settingsXml);
            $zip->addFromString('word/settings.xml', $settingsXml);
        }

        $zip->close();
    }
}
