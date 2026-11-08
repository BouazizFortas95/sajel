<?php

namespace App\Services;

use App\Models\PedagogicalPlanReceipt;
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

class PedagogicalPlanReceiptService
{
    /**
     * Get teachers along with their plan receipts for the given academic year and optional subject.
     */
    public function getTeachersWithReceipts(?int $subjectId = null, ?string $academicYear = null): Collection
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $query = User::query()
            ->where('role', 'teacher')
            ->with([
                'primarySubject',
                'secondarySubject',
                'pedagogicalPlanReceipts' => fn ($q) => $q->where('academic_year', $year),
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
            $receipt = $teacher->pedagogicalPlanReceipts->first();
            if (! $receipt) {
                $receipt = new PedagogicalPlanReceipt([
                    'user_id' => $teacher->id,
                    'academic_year' => $year,
                    'annual_plan_submitted' => false,
                ]);
                $teacher->setRelation('pedagogicalPlanReceipts', collect([$receipt]));
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
     * Toggle submission status for annual plan or a specific month.
     */
    public function togglePlan(
        int $userId,
        string $planKey,
        bool $submitted,
        ?string $date = null,
        ?string $academicYear = null
    ): PedagogicalPlanReceipt {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $receipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $userId,
            'academic_year' => $year,
        ]);

        $subCol = ($planKey === 'annual') ? 'annual_plan_submitted' : strtolower($planKey).'_submitted';
        $dateCol = ($planKey === 'annual') ? 'annual_plan_date' : strtolower($planKey).'_date';

        $receipt->{$subCol} = $submitted;
        $receipt->{$dateCol} = $submitted ? ($date ?: now()->format('Y-m-d')) : null;

        $receipt->save();

        return $receipt;
    }

    /**
     * Update notes for a teacher's plan receipt.
     */
    public function updateNotes(int $userId, ?string $notes, ?string $academicYear = null): PedagogicalPlanReceipt
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $receipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $userId,
            'academic_year' => $year,
        ]);

        $receipt->notes = $notes;
        $receipt->save();

        return $receipt;
    }

    /**
     * Bulk mark annual plan submission for multiple teachers.
     */
    public function bulkMarkAnnualPlan(
        array $userIds,
        bool $submitted,
        ?string $date = null,
        ?string $academicYear = null
    ): int {
        $year = $academicYear ?: $this->getCurrentAcademicYear();
        $targetDate = $submitted ? ($date ?: now()->format('Y-m-d')) : null;
        $count = 0;

        foreach ($userIds as $userId) {
            $receipt = PedagogicalPlanReceipt::firstOrNew([
                'user_id' => $userId,
                'academic_year' => $year,
            ]);

            $receipt->annual_plan_submitted = $submitted;
            $receipt->annual_plan_date = $targetDate;
            $receipt->save();
            $count++;
        }

        return $count;
    }

    /**
     * Toggle director or censor visa.
     */
    public function toggleVisa(int $userId, string $role, bool $signed, ?string $academicYear = null): PedagogicalPlanReceipt
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();

        $receipt = PedagogicalPlanReceipt::firstOrNew([
            'user_id' => $userId,
            'academic_year' => $year,
        ]);

        if ($role === 'director') {
            $receipt->director_visa = $signed;
            $receipt->director_visa_at = $signed ? now() : null;
        } elseif ($role === 'censor') {
            $receipt->censor_visa = $signed;
            $receipt->censor_visa_at = $signed ? now() : null;
        }

        $receipt->save();

        return $receipt;
    }

    /**
     * Compute summary statistics for pedagogical plan receipts.
     */
    public function getSummaryStats(?string $academicYear = null): array
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();
        $totalTeachers = User::where('role', 'teacher')->count();

        $annualSubmitted = PedagogicalPlanReceipt::where('academic_year', $year)
            ->where('annual_plan_submitted', true)
            ->count();

        $currentMonthKey = $this->getCurrentAcademicMonthKey();
        $currentMonthSubmitted = 0;

        if ($currentMonthKey) {
            $currentMonthSubmitted = PedagogicalPlanReceipt::where('academic_year', $year)
                ->where($currentMonthKey.'_submitted', true)
                ->count();
        }

        $annualPercentage = $totalTeachers > 0 ? round(($annualSubmitted / $totalTeachers) * 100, 1) : 0;
        $monthPercentage = $totalTeachers > 0 ? round(($currentMonthSubmitted / $totalTeachers) * 100, 1) : 0;

        return [
            'total_teachers' => $totalTeachers,
            'annual_submitted' => $annualSubmitted,
            'annual_percentage' => $annualPercentage,
            'current_month_key' => $currentMonthKey,
            'current_month_label' => $currentMonthKey ? PedagogicalPlanReceipt::MONTHS[$currentMonthKey] : null,
            'current_month_submitted' => $currentMonthSubmitted,
            'current_month_percentage' => $monthPercentage,
        ];
    }

    /**
     * Assemble full printable dataset conforming to the Algerian ministerial model.
     */
    public function assemblePrintData(?int $subjectId = null, ?string $academicYear = null, string $layout = 'separate'): array
    {
        $year = $academicYear ?: $this->getCurrentAcademicYear();
        $school = SchoolSetting::current();
        $groupedTeachers = $this->getTeachersGroupedBySubject($subjectId, $year);

        $selectedSubject = $subjectId ? Subject::find($subjectId) : null;
        $allSubjects = Subject::orderBy('name')->get();

        return [
            'school' => $school,
            'academic_year' => $year,
            'groupedTeachers' => $groupedTeachers,
            'selectedSubject' => $selectedSubject,
            'allSubjects' => $allSubjects,
            'layout' => $layout,
            'pagePerSubject' => ($layout !== 'continuous'),
            'months' => PedagogicalPlanReceipt::MONTHS,
            'stats' => $this->getSummaryStats($year),
            'printed_at' => now()->locale('ar')->isoFormat('LL'),
        ];
    }

    /**
     * Generate PDF binary using Browsershot.
     */
    public function generatePdf(?int $subjectId = null, ?string $academicYear = null, string $layout = 'separate'): string
    {
        $data = $this->assemblePrintData($subjectId, $academicYear, $layout);
        $html = view('print.pedagogical-plan-receipts', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(8, 10, 8, 10)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate official Microsoft Word (.docx) document using PhpWord.
     */
    public function generateWordDocx(?int $subjectId = null, ?string $academicYear = null, string $layout = 'separate'): string
    {
        $data = $this->assemblePrintData($subjectId, $academicYear, $layout);
        $school = $data['school'];
        $groupedTeachers = $data['groupedTeachers'];
        $selectedSubject = $data['selectedSubject'];
        $pagePerSubject = $data['pagePerSubject'];
        $printedAt = $data['printed_at'];
        $year = $data['academic_year'];

        $monthsReversed = array_reverse(PedagogicalPlanReceipt::MONTHS, true); // May down to September for RTL

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Cairo');
        $phpWord->setDefaultFontSize(9.5);
        $phpWord->setDefaultParagraphStyle([
            'bidi' => true,
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            'lineHeight' => 1.0,
        ]);

        // Configure Section Styles for A4 Landscape
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
        $tc->addText('وثيقة استلام المخططات السنوية والشهرية', ['name' => 'Amiri', 'size' => 28, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true, 'spaceAfter' => 40]);

        if ($selectedSubject) {
            $tc->addText('مادة: '.$selectedSubject->name, ['name' => 'Cairo', 'size' => 15, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        } else {
            $tc->addText('سجل المتابعة والتأشير الإداري لتدرجات ومخططات السادة أساتذة التعليم المتوسط', ['name' => 'Cairo', 'size' => 11, 'color' => '475569', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
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
        // Page 2+: Unified 13-Column RTL Tables (الصفحة الثانية وما بعدها)
        // ----------------------------------------------------
        $isFirstSubject = true;

        foreach ($groupedTeachers as $subjectName => $teachers) {
            if ($isFirstSubject) {
                $section->addPageBreak();
                $isFirstSubject = false;
            } elseif ($pagePerSubject) {
                $section->addPageBreak();
            } else {
                $section->addTextBreak(1);
            }

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
            $r1 = $table->addRow();
            // Left: Year (cols 1-4: 2500 + 3x900 = 5200 twips)
            $r1Left = $r1->addCell(5200, ['gridSpan' => 4, 'bgColor' => 'F8FAFC']);
            $r1Left->addText('السنة الدراسية: '.$year, ['name' => 'Cairo', 'size' => 9.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);

            // Center: Title (cols 5-10: 6x900 = 5400 twips)
            $r1Center = $r1->addCell(5400, ['gridSpan' => 6, 'bgColor' => 'F8FAFC']);
            $r1Center->addText('وثيقة استلام المخططات والتدرجات البيداغوجية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

            // Right: School (cols 11-13: 1400 + 2800 + 600 = 4800 twips)
            $r1Right = $r1->addCell(4800, ['gridSpan' => 3, 'bgColor' => 'F8FAFC']);
            $r1Right->addText(($school?->school_name ?? 'متوسطة حي 900 مسكن').' | '.($school?->municipality_name ?? 'المغير'), ['name' => 'Amiri', 'size' => 10, 'bold' => true, 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

            // Row 2: Subject Banner (full 13 cols = 15400 twips)
            $r2 = $table->addRow();
            $bCell = $r2->addCell(15400, ['gridSpan' => 13, 'bgColor' => '1E3A8A']);
            $bCell->addText('المادة: '.$subjectName.' ('.$teachers->count().' أساتذة)', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

            // Row 3: Column Headers (Row 1 of Ledger)
            // Left to Right: [ملاحظات: 2500] [المخططات الشهرية: 8100] [المخطط السنوي: 1400] [اسم الأستاذ: 2800] [الرقم: 600]
            $r3 = $table->addRow();
            $r3->addCell(2500, ['vMerge' => 'restart', 'bgColor' => 'E2E8F0'])->addText('ملاحظات', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(8100, ['gridSpan' => 9, 'bgColor' => 'E2E8F0'])->addText('المخططات والتدرجات الشهرية', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(1400, ['vMerge' => 'restart', 'bgColor' => 'E2E8F0'])->addText('المخطط السنوي', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(2800, ['vMerge' => 'restart', 'bgColor' => 'E2E8F0'])->addText('اسم ولقب الأستاذ', ['name' => 'Amiri', 'size' => 10, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $r3->addCell(600, ['vMerge' => 'restart', 'bgColor' => 'E2E8F0'])->addText('الرقم', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

            // Row 4: Column Headers (Row 2 of Ledger - Months from May down to September)
            $r4 = $table->addRow();
            $r4->addCell(2500, ['vMerge' => 'continue']);
            foreach ($monthsReversed as $mKey => $mLabel) {
                $r4->addCell(900, ['bgColor' => 'F1F5F9'])->addText($mLabel, ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            }
            $r4->addCell(1400, ['vMerge' => 'continue']);
            $r4->addCell(2800, ['vMerge' => 'continue']);
            $r4->addCell(600, ['vMerge' => 'continue']);

            // Teacher Data Rows
            $tIdx = 1;
            foreach ($teachers as $teacher) {
                $receipt = $teacher->pedagogicalPlanReceipts->first();
                $dRow = $table->addRow();

                // Leftmost: ملاحظات
                $dRow->addCell(2500, ['noWrap' => false])->addText($receipt?->notes ?? '', ['name' => 'Amiri', 'size' => 8.5, 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

                // Months (May down to September)
                foreach ($monthsReversed as $mKey => $mLabel) {
                    $mCell = $dRow->addCell(900, ['noWrap' => false]);
                    $isSub = $receipt ? $receipt->isMonthSubmitted($mKey) : false;
                    $mDate = $receipt ? $receipt->getMonthDate($mKey) : null;

                    if ($isSub) {
                        $mCell->addText('##CB_ON##', ['name' => 'MS Gothic', 'size' => 10, 'bold' => true, 'color' => '15803D', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                        if ($mDate) {
                            $mCell->addText('##DATE_VAL:'.Carbon::parse($mDate)->format('d/m').'##', ['name' => 'Cairo', 'size' => 7.5, 'bold' => true, 'color' => '0369A1', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                        } else {
                            $mCell->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 7, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                        }
                    } else {
                        // Placeholder markers replaced by interactive Content Controls in post-processing
                        $mCell->addText('##CB_OFF##', ['name' => 'MS Gothic', 'size' => 11, 'bold' => true, 'color' => '475569', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                        $mCell->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 7, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                    }
                }

                // المخطط السنوي
                $annualCell = $dRow->addCell(1400, ['noWrap' => false]);
                if ($receipt && $receipt->annual_plan_submitted) {
                    $annualCell->addText('##CB_ON##', ['name' => 'MS Gothic', 'size' => 10, 'bold' => true, 'color' => '15803D', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                    if ($receipt->annual_plan_date) {
                        $annualCell->addText('##DATE_VAL:'.Carbon::parse($receipt->annual_plan_date)->format('d/m').'##', ['name' => 'Cairo', 'size' => 7.5, 'bold' => true, 'color' => '0369A1', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                    } else {
                        $annualCell->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 7, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                    }
                } else {
                    $annualCell->addText('##CB_OFF##', ['name' => 'MS Gothic', 'size' => 11, 'bold' => true, 'color' => '475569', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                    $annualCell->addText('##DATE_EMPTY##', ['name' => 'Cairo', 'size' => 7, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                }

                // اسم ولقب الأستاذ
                $nameCell = $dRow->addCell(2800, ['noWrap' => false]);
                $nameCell->addText($teacher->name, ['name' => 'Amiri', 'size' => 10, 'bold' => true, 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
                if ($teacher->secondarySubject) {
                    $nameCell->addText('+ '.$teacher->secondarySubject->name, ['name' => 'Cairo', 'size' => 7.5, 'color' => '0284C7', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
                }

                // Rightmost: الرقم
                $dRow->addCell(600, ['noWrap' => false])->addText($tIdx++, ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            }

            // Clean Ministerial Footer (ذيل الصفحة بدون إطارات زائدة وبدون مؤشرات شاذة)
            $section->addTextBreak(2);
            $sigTable = $section->addTable([
                'alignment' => JcTable::CENTER,
                'unit' => 'dxa',
                'width' => 15400,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $sRow = $sigTable->addRow();

            // Left cell: Principal Signature Spot (موقع تأشيرة مدير المؤسسة على نفس استقامة التعامد)
            $principalCell = $sRow->addCell(4500, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $principalCell->addText(($school?->municipality_name ?? 'المغير').' في:', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => '1E293B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true, 'spaceAfter' => 60]);
            $principalCell->addText('مدير المؤسسة', ['name' => 'Amiri', 'size' => 14, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

            // Right cell: Empty spacer spanning the rest of the table
            $spacerCell = $sRow->addCell(10900, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $spacerCell->addText('', [], []);
        }

        // Save to temporary file
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'pedagogical_plans_').'.docx';
        $objWriter->save($tempFile);

        $this->postProcessDocxForInteractiveControls($tempFile);

        return $tempFile;
    }

    /**
     * Post-process DOCX file to inject native interactive Word Content Controls
     * for checkboxes (w14:checkbox) and editable date pickers (w:date).
     */
    private function postProcessDocxForInteractiveControls(string $filePath): void
    {
        $zip = new \ZipArchive;
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

        $idCounter = 1000000;

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
                            .'<w:sz w:val="24"/>'
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
                            .'<w:sz w:val="15"/>'
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

        // 2. Upgrade settings.xml to modern Word compatibility mode (w:val="15")
        // Word 2007 mode (w:val="12") disables checkbox controls and triggers the Compatibility Checker dialog.
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

            // Upgrade compatibilityMode from 12 to 15 (Word 2013-2026 native mode)
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

            // Remove any legacy checkCompatibility tag to prevent compatibility prompt
            $settingsXml = preg_replace('/<w:checkCompatibility[^>]*\/>/', '', $settingsXml);

            $zip->addFromString('word/settings.xml', $settingsXml);
        }

        $zip->close();
    }

    /**
     * Get current active academic year from school settings or fallback.
     */
    public function getCurrentAcademicYear(): string
    {
        return SchoolSetting::current()?->academic_year ?? '2026-2027';
    }

    /**
     * Resolve the current school calendar month key (September to May).
     */
    public function getCurrentAcademicMonthKey(): ?string
    {
        $monthNum = (int) now()->format('n');

        $map = [
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
        ];

        return $map[$monthNum] ?? 'september';
    }
}
