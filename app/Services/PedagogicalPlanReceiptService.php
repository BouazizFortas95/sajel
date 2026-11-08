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
        $months = $data['months'];
        $pagePerSubject = $data['pagePerSubject'];
        $printedAt = $data['printed_at'];

        $phpWord = new PhpWord;

        // Configure Section Styles for A4 Landscape
        $sectionStyle = [
            'orientation' => 'landscape',
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
            'pageSizeW' => 16838,
            'pageSizeH' => 11906,
        ];

        $section = $phpWord->addSection($sectionStyle);

        // ----------------------------------------------------
        // Page 1: Official Front Cover (الصفحة الأولى: صفحة رئيسية)
        // ----------------------------------------------------
        $coverTable = $section->addTable(['borderSize' => 0, 'width' => 100 * 50, 'unit' => 'pct', 'alignment' => JcTable::CENTER]);
        $coverHeaderRow = $coverTable->addRow();

        $rightCell = $coverHeaderRow->addCell(4500);
        $rightCell->addText('مديرية التربية لولاية: '.($school->municipality_name ?? 'المغير'), ['name' => 'Amiri', 'size' => 11, 'bold' => true]);
        $rightCell->addText('المؤسسة: '.($school->school_name ?? 'متوسطة حي 900 مسكن'), ['name' => 'Amiri', 'size' => 11, 'bold' => true]);
        $rightCell->addText('الرمز الإداري: '.($school->school_code ?? '00-0000-0001'), ['name' => 'Cairo', 'size' => 9, 'color' => '64748B']);

        $centerCell = $coverHeaderRow->addCell(7000);
        $centerCell->addText('الجمهورية الجزائرية الديمقراطية الشعبية', ['name' => 'Amiri', 'size' => 16, 'bold' => true], ['alignment' => 'center']);
        $centerCell->addText('وزارة التربية الوطنية', ['name' => 'Amiri', 'size' => 13, 'bold' => true, 'color' => '1E3A8A'], ['alignment' => 'center']);

        $leftCell = $coverHeaderRow->addCell(4500);
        $leftCell->addText('سجل المتابعة البيداغوجية', ['name' => 'Cairo', 'size' => 10, 'bold' => true, 'color' => '475569'], ['alignment' => 'left']);
        $leftCell->addText('تاريخ الإصدار: '.$printedAt, ['name' => 'Cairo', 'size' => 9, 'color' => '64748B'], ['alignment' => 'left']);

        $section->addTextBreak(3);

        // Document Title Center Box
        $titleTable = $section->addTable([
            'borderSize' => 12,
            'borderColor' => '1E3A8A',
            'bgColor' => 'F8FAFC',
            'alignment' => JcTable::CENTER,
            'cellMargin' => 200,
        ]);
        $titleRow = $titleTable->addRow();
        $titleCell = $titleRow->addCell(11000);
        $titleCell->addText('وثيقة استلام المخططات السنوية والشهرية', ['name' => 'Amiri', 'size' => 24, 'bold' => true, 'color' => '0F172A'], ['alignment' => 'center']);
        $titleCell->addText('Procès-Verbal de Réception des Plans et Progressions Pédagogiques', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => '1E3A8A'], ['alignment' => 'center']);

        if ($selectedSubject) {
            $titleCell->addText('مادة: '.$selectedSubject->name, ['name' => 'Cairo', 'size' => 14, 'bold' => true, 'color' => '1E3A8A'], ['alignment' => 'center']);
        } else {
            $titleCell->addText('سجل المتابعة والتأشير الإداري لتدرجات ومخططات السادة أساتذة التعليم المتوسط', ['name' => 'Cairo', 'size' => 11, 'color' => '475569'], ['alignment' => 'center']);
        }

        $section->addTextBreak(3);

        // Academic Year & Principal Footer Box
        $footerTable = $section->addTable(['borderSize' => 0, 'width' => 100 * 50, 'unit' => 'pct', 'alignment' => JcTable::CENTER]);
        $footerRow = $footerTable->addRow();
        $footerRow->addCell(3500);
        $yearCell = $footerRow->addCell(9000, ['borderSize' => 10, 'borderColor' => '1E3A8A', 'bgColor' => 'FFFFFF']);
        $yearCell->addText('السنة الدراسية: '.($school->academic_year ?? '2026-2027'), ['name' => 'Cairo', 'size' => 15, 'bold' => true, 'color' => '0F172A'], ['alignment' => 'center']);
        $yearCell->addText('مدير المؤسسة: '.($school->principal_name ?? 'أحمد بوزقاق'), ['name' => 'Amiri', 'size' => 12, 'bold' => true, 'color' => '334155'], ['alignment' => 'center']);
        $footerRow->addCell(3500);

        // ----------------------------------------------------
        // Page 2+: Subject Tables (الصفحة الثانية وما بعدها)
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

            // Subject Header
            $hdrTable = $section->addTable(['borderSize' => 0, 'width' => 100 * 50, 'unit' => 'pct']);
            $hdrRow = $hdrTable->addRow();
            $hdrRow->addCell(5000)->addText(($school->school_name ?? 'متوسطة حي 900 مسكن').' | '.($school->municipality_name ?? 'المغير'), ['name' => 'Amiri', 'size' => 10, 'bold' => true]);
            $hdrRow->addCell(6000)->addText('وثيقة استلام المخططات والتدرجات البيداغوجية', ['name' => 'Amiri', 'size' => 12, 'bold' => true, 'color' => '1E3A8A'], ['alignment' => 'center']);
            $hdrRow->addCell(4000)->addText('السنة الدراسية: '.($school->academic_year ?? '2026-2027'), ['name' => 'Cairo', 'size' => 9, 'bold' => true], ['alignment' => 'left']);

            // Subject Banner
            $bannerTable = $section->addTable(['borderSize' => 0, 'width' => 100 * 50, 'unit' => 'pct']);
            $bannerRow = $bannerTable->addRow();
            $bannerCell = $bannerRow->addCell(15000, ['bgColor' => '1E3A8A']);
            $bannerCell->addText('المادة: '.$subjectName.' ('.$teachers->count().' أساتذة)', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => 'FFFFFF']);

            // 13-Column Ledger Table
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '334155',
                'cellMargin' => 50,
                'alignment' => JcTable::CENTER,
            ]);

            // Row 1 of Header
            $row1 = $table->addRow();
            $row1->addCell(600, ['vMerge' => 'restart', 'bgColor' => 'F1F5F9'])->addText('الرقم', ['name' => 'Cairo', 'size' => 9, 'bold' => true], ['alignment' => 'center']);
            $row1->addCell(2600, ['vMerge' => 'restart', 'bgColor' => 'F1F5F9'])->addText('اسم ولقب الأستاذ', ['name' => 'Amiri', 'size' => 10, 'bold' => true], ['alignment' => 'right']);
            $row1->addCell(1300, ['vMerge' => 'restart', 'bgColor' => 'F1F5F9'])->addText('المخطط السنوي', ['name' => 'Cairo', 'size' => 9, 'bold' => true], ['alignment' => 'center']);
            $row1->addCell(7200, ['gridSpan' => 9, 'bgColor' => 'F1F5F9'])->addText('المخططات والتدرجات الشهرية', ['name' => 'Cairo', 'size' => 9, 'bold' => true], ['alignment' => 'center']);
            $row1->addCell(2300, ['vMerge' => 'restart', 'bgColor' => 'F1F5F9'])->addText('ملاحظات', ['name' => 'Cairo', 'size' => 9, 'bold' => true], ['alignment' => 'center']);

            // Row 2 of Header (Months)
            $row2 = $table->addRow();
            $row2->addCell(600, ['vMerge' => 'continue']);
            $row2->addCell(2600, ['vMerge' => 'continue']);
            $row2->addCell(1300, ['vMerge' => 'continue']);
            foreach ($months as $monthKey => $monthLabel) {
                $row2->addCell(800, ['bgColor' => 'E2E8F0'])->addText($monthLabel, ['name' => 'Cairo', 'size' => 8, 'bold' => true], ['alignment' => 'center']);
            }
            $row2->addCell(2300, ['vMerge' => 'continue']);

            // Data Rows
            $tIndex = 1;
            foreach ($teachers as $teacher) {
                $receipt = $teacher->pedagogicalPlanReceipts->first();
                $tRow = $table->addRow();

                // 1. Number
                $tRow->addCell(600)->addText($tIndex++, ['name' => 'Cairo', 'size' => 9], ['alignment' => 'center']);

                // 2. Name
                $nameCell = $tRow->addCell(2600);
                $nameCell->addText($teacher->name, ['name' => 'Amiri', 'size' => 10, 'bold' => true]);
                if ($teacher->secondarySubject) {
                    $nameCell->addText('+ '.$teacher->secondarySubject->name, ['name' => 'Cairo', 'size' => 8, 'color' => '0284C7']);
                }

                // 3. Annual Plan
                $annualCell = $tRow->addCell(1300);
                if ($receipt && $receipt->annual_plan_submitted) {
                    $annualCell->addText('✔', ['name' => 'DejaVu Sans', 'size' => 11, 'bold' => true, 'color' => '15803D'], ['alignment' => 'center']);
                    $dateStr = $receipt->annual_plan_date ? Carbon::parse($receipt->annual_plan_date)->format('d/m') : '';
                    if ($dateStr) {
                        $annualCell->addText($dateStr, ['name' => 'Cairo', 'size' => 7, 'color' => '0369A1'], ['alignment' => 'center']);
                    }
                } else {
                    $annualCell->addText('□', ['name' => 'DejaVu Sans', 'size' => 10, 'color' => '94A3B8'], ['alignment' => 'center']);
                }

                // 4-12. Months
                foreach ($months as $monthKey => $monthLabel) {
                    $mCell = $tRow->addCell(800);
                    $isSub = $receipt ? $receipt->isMonthSubmitted($monthKey) : false;
                    $mDate = $receipt ? $receipt->getMonthDate($monthKey) : null;

                    if ($isSub) {
                        $mCell->addText('✔', ['name' => 'DejaVu Sans', 'size' => 10, 'bold' => true, 'color' => '15803D'], ['alignment' => 'center']);
                        if ($mDate) {
                            $mCell->addText(Carbon::parse($mDate)->format('d/m'), ['name' => 'Cairo', 'size' => 7, 'color' => '0369A1'], ['alignment' => 'center']);
                        }
                    } else {
                        $mCell->addText('□', ['name' => 'DejaVu Sans', 'size' => 9, 'color' => 'CBD5E1'], ['alignment' => 'center']);
                    }
                }

                // 13. Notes
                $tRow->addCell(2300)->addText($receipt?->notes ?? '', ['name' => 'Amiri', 'size' => 8]);
            }

            // Signatures Section
            $section->addTextBreak(1);
            $sigTable = $section->addTable(['borderSize' => 6, 'borderColor' => '1E3A8A', 'width' => 100 * 50, 'unit' => 'pct']);
            $sigRow = $sigTable->addRow();

            $censorCell = $sigRow->addCell(5000);
            $censorCell->addText('تأشيرة ناظر الدروس', ['name' => 'Cairo', 'size' => 10, 'bold' => true, 'color' => '1E3A8A'], ['alignment' => 'center']);
            $censorCell->addText('السيد(ة): ........................................', ['name' => 'Amiri', 'size' => 9], ['alignment' => 'center']);
            $censorCell->addText('التاريخ: ...... / ...... / 2026 م', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B'], ['alignment' => 'center']);

            $notesCell = $sigRow->addCell(5000);
            $notesCell->addText('ملاحظات وقرارات الإدارة', ['name' => 'Cairo', 'size' => 10, 'bold' => true, 'color' => '1E3A8A'], ['alignment' => 'center']);
            $notesCell->addText('مادة: '.$subjectName, ['name' => 'Amiri', 'size' => 9, 'bold' => true], ['alignment' => 'center']);
            $notesCell->addText('حُرِّر بـ '.($school->municipality_name ?? 'المغير').' في: '.$printedAt, ['name' => 'Cairo', 'size' => 8, 'color' => '64748B'], ['alignment' => 'center']);

            $directorCell = $sigRow->addCell(5000);
            $directorCell->addText('تأشيرة وختم مدير المؤسسة', ['name' => 'Cairo', 'size' => 10, 'bold' => true, 'color' => '1E3A8A'], ['alignment' => 'center']);
            $directorCell->addText($school->principal_name ?? 'أحمد بوزقاق', ['name' => 'Amiri', 'size' => 10, 'bold' => true], ['alignment' => 'center']);
            $directorCell->addText('الختم والتوقيع الرسمي', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B'], ['alignment' => 'center']);
        }

        // Save to temporary file
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'pedagogical_plans_').'.docx';
        $objWriter->save($tempFile);

        return $tempFile;
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
