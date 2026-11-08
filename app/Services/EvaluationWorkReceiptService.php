<?php

namespace App\Services;

use App\Models\EvaluationWorkReceipt;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use ZipArchive;

class EvaluationWorkReceiptService
{
    /**
     * Get teachers along with their evaluation work receipts for the given academic year and optional subject.
     */
    public function getTeachersWithReceipts(?int $subjectId = null, ?string $academicYear = null): Collection
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $query = User::query()
            ->where('role', 'teacher')
            ->with([
                'primarySubject',
                'secondarySubject',
                'evaluationWorkReceipts' => fn ($q) => $q->where('academic_year', $year),
            ])
            ->orderBy('primary_subject_id')
            ->orderBy('name');

        if ($subjectId) {
            $query->where(function ($q) use ($subjectId) {
                $q->where('primary_subject_id', $subjectId)
                    ->orWhere('secondary_subject_id', $subjectId);
            });
        }

        $teachers = $query->get();

        // Ensure every teacher has a materialized receipt instance
        foreach ($teachers as $teacher) {
            $receipt = $teacher->evaluationWorkReceipts->first();
            if (! $receipt) {
                $receipt = new EvaluationWorkReceipt([
                    'user_id' => $teacher->id,
                    'academic_year' => $year,
                ]);
                $teacher->setRelation('evaluationWorkReceipts', collect([$receipt]));
            }
        }

        return $teachers;
    }

    /**
     * Group teachers by their primary educational subject.
     */
    public function getTeachersGroupedBySubject(?int $subjectId = null, ?string $academicYear = null): Collection
    {
        $teachers = $this->getTeachersWithReceipts($subjectId, $academicYear);

        return $teachers->groupBy(function ($teacher) {
            return $teacher->primarySubject?->name ?? 'غير محدد';
        });
    }

    /**
     * Toggle submission status for a specific deliverable item.
     */
    public function toggleItem(
        int $userId,
        string $itemKey,
        bool $submitted,
        ?string $date = null,
        ?string $academicYear = null
    ): EvaluationWorkReceipt {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $receipt = EvaluationWorkReceipt::firstOrNew([
            'user_id' => $userId,
            'academic_year' => $year,
        ]);

        $subCol = "{$itemKey}_submitted";
        $dateCol = "{$itemKey}_date";

        $receipt->{$subCol} = $submitted;
        $receipt->{$dateCol} = $submitted ? ($date ?: now()->format('Y-m-d')) : null;

        $receipt->save();

        return $receipt;
    }

    /**
     * Update notes for a teacher's evaluation work receipt.
     */
    public function updateNotes(int $userId, ?string $notes, ?string $academicYear = null): EvaluationWorkReceipt
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $receipt = EvaluationWorkReceipt::firstOrNew([
            'user_id' => $userId,
            'academic_year' => $year,
        ]);

        $receipt->notes = $notes;
        $receipt->save();

        return $receipt;
    }

    /**
     * Bulk mark deliverable submission for multiple teachers.
     */
    public function bulkMarkItem(
        array $userIds,
        string $itemKey,
        bool $submitted,
        ?string $date = null,
        ?string $academicYear = null
    ): int {
        $year = $academicYear ?: $this->getCurrentAcademicYear();
        $targetDate = $submitted ? ($date ?: now()->format('Y-m-d')) : null;
        $count = 0;

        $subCol = "{$itemKey}_submitted";
        $dateCol = "{$itemKey}_date";

        foreach ($userIds as $userId) {
            $receipt = EvaluationWorkReceipt::firstOrNew([
                'user_id' => $userId,
                'academic_year' => $year,
            ]);

            $receipt->{$subCol} = $submitted;
            $receipt->{$dateCol} = $targetDate;
            $receipt->save();
            $count++;
        }

        return $count;
    }

    /**
     * Compute summary statistics for evaluation work receipts.
     */
    public function getSummaryStats(?string $academicYear = null): array
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();
        $totalTeachers = User::where('role', 'teacher')->count();

        $diagnosticCount = EvaluationWorkReceipt::where('academic_year', $year)
            ->where('diagnostic_test_submitted', true)
            ->count();

        $hw1Count = EvaluationWorkReceipt::where('academic_year', $year)
            ->where('homework_1_submitted', true)
            ->count();

        $quiz1Count = EvaluationWorkReceipt::where('academic_year', $year)
            ->where('quiz_1_submitted', true)
            ->count();

        $exam1Count = EvaluationWorkReceipt::where('academic_year', $year)
            ->where('exam_1_submitted', true)
            ->count();

        return [
            'total_teachers' => $totalTeachers,
            'diagnostic_count' => $diagnosticCount,
            'diagnostic_percentage' => $totalTeachers > 0 ? round(($diagnosticCount / $totalTeachers) * 100, 1) : 0,
            'homework_1_count' => $hw1Count,
            'homework_1_percentage' => $totalTeachers > 0 ? round(($hw1Count / $totalTeachers) * 100, 1) : 0,
            'quiz_1_count' => $quiz1Count,
            'quiz_1_percentage' => $totalTeachers > 0 ? round(($quiz1Count / $totalTeachers) * 100, 1) : 0,
            'exam_1_count' => $exam1Count,
            'exam_1_percentage' => $totalTeachers > 0 ? round(($exam1Count / $totalTeachers) * 100, 1) : 0,
        ];
    }

    /**
     * Assemble all necessary data for the printable Blade view and Word export.
     */
    public function assemblePrintData(?int $subjectId = null, ?string $academicYear = null, string $layout = 'separate'): array
    {
        $school = SchoolSetting::first();
        $year = $academicYear ?? $this->getCurrentAcademicYear();

        $query = User::where('role', 'teacher')
            ->with([
                'primarySubject',
                'secondarySubject',
                'evaluationWorkReceipts' => function ($q) use ($year) {
                    $q->where('academic_year', $year);
                },
            ])
            ->orderBy('name');

        if ($subjectId) {
            $query->where(function ($q) use ($subjectId) {
                $q->where('primary_subject_id', $subjectId)
                    ->orWhere('secondary_subject_id', $subjectId);
            });
        }

        $teachers = $query->get();

        $groupedTeachers = $teachers->groupBy(function ($teacher) {
            return $teacher->primarySubject?->name ?? 'غير محدد';
        });

        $allSubjects = Subject::where('is_active', true)->orderBy('name')->get();
        $selectedSubject = $subjectId ? Subject::find($subjectId) : null;

        // Statistics
        $totalTeachers = $teachers->count();
        $diagnosticDone = $teachers->filter(fn ($t) => $t->evaluationWorkReceipts->first()?->diagnostic_test_submitted)->count();
        $hw1Done = $teachers->filter(fn ($t) => $t->evaluationWorkReceipts->first()?->homework_1_submitted)->count();
        $quiz1Done = $teachers->filter(fn ($t) => $t->evaluationWorkReceipts->first()?->quiz_1_submitted)->count();
        $exam1Done = $teachers->filter(fn ($t) => $t->evaluationWorkReceipts->first()?->exam_1_submitted)->count();

        $pagePerSubject = ($layout !== 'continuous');

        return [
            'school' => $school,
            'teachers' => $teachers,
            'groupedTeachers' => $groupedTeachers,
            'allSubjects' => $allSubjects,
            'selectedSubject' => $selectedSubject,
            'academic_year' => $year,
            'printed_at' => now()->translatedFormat('d F Y'),
            'pagePerSubject' => $pagePerSubject,
            'layout' => $layout,
            'stats' => [
                'total_teachers' => $totalTeachers,
                'diagnostic_done' => $diagnosticDone,
                'homework_1_done' => $hw1Done,
                'quiz_1_done' => $quiz1Done,
                'exam_1_done' => $exam1Done,
            ],
        ];
    }

    /**
     * Generate PDF binary stream using Browsershot.
     */
    public function generatePdf(?int $subjectId = null, ?string $academicYear = null, string $layout = 'separate'): string
    {
        $data = $this->assemblePrintData($subjectId, $academicYear, $layout);
        $html = view('print.evaluation-work-receipts', $data)->render();

        return BrowsershotFactory::html($html)
            ->landscape()
            ->format('A4')
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate an official Microsoft Word (.docx) ledger file matching ministerial standards.
     */
    public function generateWordDocx(?int $subjectId = null, ?string $academicYear = null, bool|string $layout = 'separate'): string
    {
        $pagePerSubject = is_bool($layout) ? $layout : ($layout !== 'continuous');
        $data = $this->assemblePrintData($subjectId, $academicYear, $pagePerSubject ? 'separate' : 'continuous');

        $school = $data['school'];
        $groupedTeachers = $data['groupedTeachers'];
        $selectedSubject = $data['selectedSubject'];
        $year = $data['academic_year'];
        $printedAt = $data['printed_at'];

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
            'orientation' => 'landscape',
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
            'pageSizeW' => 16838,
            'pageSizeH' => 11906,
        ]);

        // ----------------------------------------------------
        // Page 1: Official Front Cover (الصفحة الأولى: صفحة رئيسية)
        // ----------------------------------------------------
        $coverTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 15400,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);
        $rCov = $coverTable->addRow();

        // Left cell of cover (Metadata)
        $lCell = $rCov->addCell(5000, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $lCell->addText('سجل المتابعة البيداغوجية', ['name' => 'Cairo', 'size' => 10, 'bold' => true, 'color' => '475569', 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);
        $lCell->addText('تاريخ الإصدار: '.$printedAt, ['name' => 'Cairo', 'size' => 9, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);

        // Center cell (Republic & Ministry)
        $cCell = $rCov->addCell(5400, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $cCell->addText('الجمهورية الجزائرية الديمقراطية الشعبية', ['name' => 'Amiri', 'size' => 16, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $cCell->addText('وزارة التربية الوطنية', ['name' => 'Amiri', 'size' => 14, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Right cell of cover (Directorate & School)
        $rCell = $rCov->addCell(5000, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $rCell->addText('مديرية التربية لولاية: '.($school?->municipality_name ?? 'المغير'), ['name' => 'Amiri', 'size' => 12, 'bold' => true, 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $rCell->addText('المؤسسة: '.($school?->school_name ?? 'متوسطة حي 900 مسكن'), ['name' => 'Amiri', 'size' => 12, 'bold' => true, 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

        // Spacer 1: push Title Box to vertical center of landscape page
        $section->addText('', [], ['spaceBefore' => 2200, 'spaceAfter' => 0, 'lineHeight' => 1.0]);

        // Document Title Center Box (Middle)
        $titleTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 13500,
            'borderSize' => 16,
            'borderColor' => '1E3A8A',
            'cellMarginTop' => 240,
            'cellMarginBottom' => 240,
            'cellMarginLeft' => 280,
            'cellMarginRight' => 280,
        ]);
        $tr = $titleTable->addRow();
        $tc = $tr->addCell(13500, ['bgColor' => 'F8FAFC']);
        $tc->addText('وثيقة تسليم الأعمال التقويمية', ['name' => 'Amiri', 'size' => 28, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true, 'spaceAfter' => 40]);

        if ($selectedSubject) {
            $tc->addText('مادة: '.$selectedSubject->name, ['name' => 'Cairo', 'size' => 15, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        } else {
            $tc->addText('سجل المتابعة والتأشير الإداري لتسليم الأعمال والوثائق التقويمية', ['name' => 'Cairo', 'size' => 11, 'color' => '475569', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        }

        // Spacer 2: push Year Box to bottom of landscape page
        $section->addText('', [], ['spaceBefore' => 3800, 'spaceAfter' => 0, 'lineHeight' => 1.0]);

        // Academic Year Center Box (Bottom)
        $yTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 6000,
            'borderSize' => 12,
            'borderColor' => '1E3A8A',
            'cellMarginTop' => 140,
            'cellMarginBottom' => 140,
            'cellMarginLeft' => 180,
            'cellMarginRight' => 180,
        ]);
        $yr = $yTable->addRow();
        $yc = $yr->addCell(6000, ['bgColor' => 'FFFFFF']);
        $yc->addText('السنة الدراسية: '.$year, ['name' => 'Cairo', 'size' => 16, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // ----------------------------------------------------
        // Page 2+: Unified 13-Column RTL Tables (الصفحة الثانية وما بعدها: مفصلة حسب المواد)
        // ----------------------------------------------------
        foreach ($groupedTeachers as $subjectName => $teachers) {
            // Every subject starts on its own dedicated page (كل مادة في صفحة مستقلة)
            $section->addPageBreak();

            // Unified 13-column subject table
            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'unit' => 'dxa',
                'width' => 15400,
                'layout' => 'fixed',
                'borderSize' => 6,
                'borderColor' => '334155',
                'cellMarginTop' => 50,
                'cellMarginBottom' => 50,
                'cellMarginLeft' => 60,
                'cellMarginRight' => 60,
            ]);

            // Row 1: Header (Left to Right visually: Left = Year, Center = Title, Right = School)
            $r1 = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
            $r1Left = $r1->addCell(5280, ['gridSpan' => 4, 'bgColor' => 'F8FAFC']);
            $r1Left->addText('السنة الدراسية: '.$year, ['name' => 'Cairo', 'size' => 9.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);

            $r1Center = $r1->addCell(5520, ['gridSpan' => 6, 'bgColor' => 'F8FAFC']);
            $r1Center->addText('وثيقة تسليم الأعمال التقويمية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

            $r1Right = $r1->addCell(4600, ['gridSpan' => 3, 'bgColor' => 'F8FAFC']);
            $r1Right->addText(($school?->school_name ?? 'متوسطة حي 900 مسكن').' | '.($school?->municipality_name ?? 'المغير'), ['name' => 'Amiri', 'size' => 10, 'bold' => true, 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

            // Row 2: Subject Banner (full 13 cols = 15400 twips)
            $r2 = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
            $bCell = $r2->addCell(15400, ['gridSpan' => 13, 'bgColor' => '1E3A8A']);
            $bCell->addText('المادة: '.$subjectName.' ('.$teachers->count().' أساتذة)', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

            // Row 3: Column Headers (Row 1 of Ledger)
            // Left to Right physically in Word RTL table:
            // [الملاحظة: 2520] [الاختبار: 2760 (3 cols)] [الفروض المحروسة: 2760 (3 cols)] [الواجب المنزلي: 2760 (3 cols)] [اختبار كشف المستوى: 1200] [اسم الأستاذ: 2800] [الرقم: 600]
            $r3 = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
            $r3->addCell(2520, ['vMerge' => 'restart', 'bgColor' => 'E2E8F0'])->addText('الملاحظة', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(2760, ['gridSpan' => 3, 'bgColor' => 'E2E8F0'])->addText('الاختبار', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(2760, ['gridSpan' => 3, 'bgColor' => 'E2E8F0'])->addText('الفروض المحروسة', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(2760, ['gridSpan' => 3, 'bgColor' => 'E2E8F0'])->addText('الواجب المنزلي', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(1200, ['vMerge' => 'restart', 'bgColor' => 'E2E8F0'])->addText('اختبار كشف المستوى', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(2800, ['vMerge' => 'restart', 'bgColor' => 'E2E8F0'])->addText('اسم ولقب الأستاذ', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(600, ['vMerge' => 'restart', 'bgColor' => 'E2E8F0'])->addText('الرقم', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

            // Row 4: Column Headers (Row 2 of Ledger - Sub-columns 1, 2, 3)
            $r4 = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
            $r4->addCell(2520, ['vMerge' => 'continue']);
            // Under الاختبار (3, 2, 1 in physical RTL order)
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('3', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('2', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('1', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            // Under الفروض المحروسة (3, 2, 1)
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('3', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('2', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('1', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            // Under الواجب المنزلي (3, 2, 1)
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('3', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('2', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r4->addCell(920, ['bgColor' => 'F1F5F9'])->addText('1', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            // Left continuous cells
            $r4->addCell(1200, ['vMerge' => 'continue']);
            $r4->addCell(2800, ['vMerge' => 'continue']);
            $r4->addCell(600, ['vMerge' => 'continue']);

            // Teacher Data Rows
            $tIdx = 1;
            foreach ($teachers as $teacher) {
                $receipt = $teacher->evaluationWorkReceipts->first();
                $dRow = $table->addRow(null, ['cantSplit' => true]);

                // Leftmost: الملاحظة
                $dRow->addCell(2520, ['noWrap' => false])->addText($receipt?->notes ?? '', ['name' => 'Amiri', 'size' => 8.5, 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

                // Helper closure to render a deliverable cell with interactive checkbox & date on ONE line
                $addDeliverableCell = function ($row, string $fieldKey) use ($receipt) {
                    $cell = $row->addCell(920, ['noWrap' => true]);
                    $isSub = $receipt ? $receipt->isItemSubmitted($fieldKey) : false;
                    $mDate = $receipt ? $receipt->getItemDate($fieldKey) : null;

                    $run = $cell->addTextRun([
                        'alignment' => Jc::CENTER,
                        'bidi' => true,
                        'spaceBefore' => 0,
                        'spaceAfter' => 0,
                        'lineHeight' => 1.0,
                    ]);

                    if ($isSub) {
                        $run->addText('##CB_ON##', ['name' => 'MS Gothic', 'size' => 10, 'bold' => true, 'color' => '15803D', 'rtl' => true]);
                        $run->addText(' ');
                        if ($mDate) {
                            $run->addText('##DATE_VAL:'.Carbon::parse($mDate)->format('d/m').'##', ['name' => 'Cairo', 'size' => 7, 'bold' => true, 'color' => '0369A1', 'rtl' => true]);
                        } else {
                            $run->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 7, 'color' => '94A3B8', 'rtl' => true]);
                        }
                    } else {
                        $run->addText('##CB_OFF##', ['name' => 'MS Gothic', 'size' => 11, 'bold' => true, 'color' => '475569', 'rtl' => true]);
                        $run->addText(' ');
                        $run->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 7, 'color' => '94A3B8', 'rtl' => true]);
                    }
                };

                // 1. الاختبار (3, 2, 1 in physical order)
                $addDeliverableCell($dRow, 'exam_3');
                $addDeliverableCell($dRow, 'exam_2');
                $addDeliverableCell($dRow, 'exam_1');

                // 2. الفروض المحروسة (3, 2, 1 in physical order)
                $addDeliverableCell($dRow, 'quiz_3');
                $addDeliverableCell($dRow, 'quiz_2');
                $addDeliverableCell($dRow, 'quiz_1');

                // 3. الواجب المنزلي (3, 2, 1 in physical order)
                $addDeliverableCell($dRow, 'homework_3');
                $addDeliverableCell($dRow, 'homework_2');
                $addDeliverableCell($dRow, 'homework_1');

                // 4. اختبار كشف المستوى (Diagnostic test on ONE line)
                $diagCell = $dRow->addCell(1200, ['noWrap' => true]);
                $isDiag = $receipt ? $receipt->diagnostic_test_submitted : false;
                $diagDate = $receipt ? $receipt->diagnostic_test_date : null;
                $diagRun = $diagCell->addTextRun([
                    'alignment' => Jc::CENTER,
                    'bidi' => true,
                    'spaceBefore' => 0,
                    'spaceAfter' => 0,
                    'lineHeight' => 1.0,
                ]);
                if ($isDiag) {
                    $diagRun->addText('##CB_ON##', ['name' => 'MS Gothic', 'size' => 10, 'bold' => true, 'color' => '15803D', 'rtl' => true]);
                    $diagRun->addText(' ');
                    if ($diagDate) {
                        $diagRun->addText('##DATE_VAL:'.Carbon::parse($diagDate)->format('d/m').'##', ['name' => 'Cairo', 'size' => 7, 'bold' => true, 'color' => '0369A1', 'rtl' => true]);
                    } else {
                        $diagRun->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 7, 'color' => '94A3B8', 'rtl' => true]);
                    }
                } else {
                    $diagRun->addText('##CB_OFF##', ['name' => 'MS Gothic', 'size' => 11, 'bold' => true, 'color' => '475569', 'rtl' => true]);
                    $diagRun->addText(' ');
                    $diagRun->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 7, 'color' => '94A3B8', 'rtl' => true]);
                }

                // 5. اسم ولقب الأستاذ (بحجم خط واضح وبارز 13pt)
                $nameCell = $dRow->addCell(2800, ['noWrap' => false]);
                $nameCell->addText($teacher->name, ['name' => 'Amiri', 'size' => 13, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
                if ($teacher->secondarySubject) {
                    $nameCell->addText('+ '.$teacher->secondarySubject->name, ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => '0284C7', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
                }

                // 6. Rightmost: الرقم
                $dRow->addCell(600, ['noWrap' => false])->addText($tIdx++, ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            }

            // Clean Ministerial Footer (ذيل الصفحة بتأشيرة مدير المؤسسة على نفس استقامة التعامد)
            $section->addTextBreak(2);
            $sigTable = $section->addTable([
                'alignment' => JcTable::CENTER,
                'unit' => 'dxa',
                'width' => 15400,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $sRow = $sigTable->addRow(null, ['cantSplit' => true]);

            // Left cell: Principal Signature Spot
            $principalCell = $sRow->addCell(4500, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $principalCell->addText(($school?->municipality_name ?? 'المغير').' في:', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => '1E293B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true, 'spaceAfter' => 60]);
            $principalCell->addText('مدير المؤسسة', ['name' => 'Amiri', 'size' => 14, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

            // Right cell: Empty spacer spanning the rest of the table
            $spacerCell = $sRow->addCell(10900, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $spacerCell->addText('', [], []);
        }

        // Save to temporary file
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'evaluation_receipts_').'.docx';
        $objWriter->save($tempFile);

        $this->postProcessDocxForInteractiveControls($tempFile);

        return $tempFile;
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

        $idCounter = 2000000;

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
                            .'<w:sz w:val="20"/>'
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
                            .'<w:sz w:val="14"/>'
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

        // Modern Word Compatibility Mode (w:val="15") and disable compatibility checker prompts
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

            // Remove any legacy checkCompatibility tag to prevent compatibility warning prompt on save
            $settingsXml = preg_replace('/<w:checkCompatibility[^>]*\/>/', '', $settingsXml);
            $zip->addFromString('word/settings.xml', $settingsXml);
        }

        // Set docProps/app.xml to identify document as native modern Word (eliminates Word Compatibility warning)
        $appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Template>Normal.dotm</Template>'
            .'<Application>Microsoft Office Word</Application>'
            .'<AppVersion>16.0000</AppVersion>'
            .'</Properties>';
        $zip->addFromString('docProps/app.xml', $appXml);

        $zip->close();
    }

    /**
     * Get current active academic year from school settings or fallback.
     */
    public function getCurrentAcademicYear(): string
    {
        return SchoolSetting::current()?->academic_year ?? '2026-2027';
    }
}
