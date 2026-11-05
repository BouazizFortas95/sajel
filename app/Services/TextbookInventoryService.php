<?php

namespace App\Services;

use App\Enums\TextbookDistributionType;
use App\Enums\TextbookReturnCondition;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentTextbookLoan;
use App\Models\Subject;
use App\Models\Textbook;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Illuminate\Support\Collection;

class TextbookInventoryService
{
    /**
     * Resolve and normalize academic year (handles both '2026/2027' and '2026-2027').
     */
    public function resolveAcademicYear(?string $year = null): string
    {
        if ($year) {
            return $year;
        }

        $current = SchoolSetting::current()?->academic_year ?? '2026/2027';

        return str_replace('-', '/', $current);
    }

    /**
     * Seed or sync the standard Algerian National Curriculum textbooks catalog (ONPS).
     */
    public function seedStandardAlgerianCatalog(): int
    {
        $levels = Level::all();
        $subjects = Subject::all();

        if ($levels->isEmpty() || $subjects->isEmpty()) {
            return 0;
        }

        $standardTitles = [
            'اللغة العربية' => ['title' => 'كتاب اللغة العربية وآدابها', 'price' => 280.00],
            'الرياضيات' => ['title' => 'كتاب الرياضيات للتعليم المتوسط', 'price' => 310.00],
            'العلوم الفيزيائية والتكنولوجيا' => ['title' => 'كتاب العلوم الفيزيائية والتكنولوجية', 'price' => 260.00],
            'الفيزياء' => ['title' => 'كتاب العلوم الفيزيائية والتكنولوجية', 'price' => 260.00],
            'علوم الطبيعة والحياة' => ['title' => 'كتاب علوم الطبيعة والحياة', 'price' => 275.00],
            'العلوم' => ['title' => 'كتاب علوم الطبيعة والحياة', 'price' => 275.00],
            'التاريخ والجغرافيا' => ['title' => 'كتاب التاريخ والجغرافيا', 'price' => 290.00],
            'التاريخ' => ['title' => 'كتاب التاريخ والجغرافيا', 'price' => 290.00],
            'اللغة الفرنسية' => ['title' => 'Livre de Français', 'price' => 270.00],
            'اللغة الإنجليزية' => ['title' => 'My Book of English', 'price' => 250.00],
            'التربية الإسلامية' => ['title' => 'كتاب التربية الإسلامية', 'price' => 210.00],
            'التربية المدنية' => ['title' => 'كتاب التربية المدنية', 'price' => 200.00],
            'الإعلام الآلي' => ['title' => 'كتاب الإعلام الآلي والتكنولوجيا', 'price' => 230.00],
        ];

        $created = 0;

        foreach ($levels as $level) {
            foreach ($subjects as $subject) {
                $match = $standardTitles[$subject->name] ?? [
                    'title' => 'كتاب '.$subject->name,
                    'price' => 250.00,
                ];

                $title = $match['title'].' — '.($level->name ?? 'السنة '.$level->grade_number);

                $textbook = Textbook::firstOrCreate(
                    [
                        'level_id' => $level->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'title' => $title,
                        'isbn' => '978-9961-0-'.rand(1000, 9999).'-'.$level->grade_number,
                        'unit_price' => $match['price'],
                        'total_received' => 120,
                        'storage_location' => 'مخزن الكتب المدرسية — الجناح أ',
                        'is_active' => true,
                    ]
                );

                if ($textbook->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * Distribute textbooks to an individual student.
     *
     * @param  array<int, int>  $textbookIds
     * @return Collection<int, StudentTextbookLoan>
     */
    public function distributeToStudent(
        Student $student,
        array $textbookIds,
        TextbookDistributionType $type = TextbookDistributionType::Loan,
        ?User $distributedBy = null
    ): Collection {
        $year = SchoolSetting::current()?->academic_year ?? '2026/2027';
        $loans = collect();

        foreach ($textbookIds as $tbId) {
            $loan = StudentTextbookLoan::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'textbook_id' => $tbId,
                    'academic_year' => $year,
                ],
                [
                    'distribution_type' => $type,
                    'distributed_at' => now()->toDateString(),
                    'clearance_granted' => false,
                    'distributed_by_id' => $distributedBy?->id ?? auth()->id(),
                ]
            );

            $loans->push($loan);
        }

        return $loans;
    }

    /**
     * Batch distribute curriculum textbooks to an entire classroom section.
     *
     * @param  array<int, int>|null  $textbookIds  If null, distributes all active textbooks of the classroom's level.
     */
    public function batchDistributeToClassroom(
        Classroom $classroom,
        ?array $textbookIds = null,
        TextbookDistributionType $type = TextbookDistributionType::Loan,
        ?User $distributedBy = null
    ): int {
        $levelId = $classroom->level_id;
        $targetIds = $textbookIds ?: Textbook::where('level_id', $levelId)->where('is_active', true)->pluck('id')->all();

        if (empty($targetIds)) {
            return 0;
        }

        // Students in this classroom across all groups
        $students = Student::whereHas('group', fn ($q) => $q->where('classroom_id', $classroom->id))->get();
        $totalDistributed = 0;

        foreach ($students as $student) {
            $loans = $this->distributeToStudent($student, $targetIds, $type, $distributedBy);
            $totalDistributed += $loans->count();
        }

        return $totalDistributed;
    }

    /**
     * Process return of a single textbook with condition assessment.
     */
    public function processReturn(
        StudentTextbookLoan $loan,
        TextbookReturnCondition $condition = TextbookReturnCondition::Good,
        ?string $notes = null
    ): void {
        $loan->update([
            'returned_at' => now()->toDateString(),
            'return_condition' => $condition,
            'clearance_granted' => $condition === TextbookReturnCondition::Good,
            'notes' => $notes ?: $loan->notes,
        ]);
    }

    /**
     * Check student textbook return clearance status.
     *
     * @return array<string, mixed>
     */
    public function checkStudentClearance(Student $student, ?string $academicYear = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);
        $alternateYear = str_contains($year, '/') ? str_replace('/', '-', $year) : str_replace('-', '/', $year);

        $loans = StudentTextbookLoan::where('student_id', $student->id)
            ->where(function ($q) use ($year, $alternateYear): void {
                $q->where('academic_year', $year)
                    ->orWhere('academic_year', $alternateYear);
            })
            ->with(['textbook.subject'])
            ->get();

        $totalBorrowed = $loans->count();
        $totalReturned = $loans->whereNotNull('returned_at')->count();
        $pendingReturns = $loans->whereNull('returned_at');
        $damagedCount = $loans->where('return_condition', TextbookReturnCondition::Damaged)->count();
        $lostCount = $loans->where('return_condition', TextbookReturnCondition::Lost)->count();

        // Cleared if all borrowed books are returned and marked clearance_granted
        $isCleared = $totalBorrowed > 0 && $pendingReturns->isEmpty() && ($damagedCount === 0 && $lostCount === 0);

        return [
            'student' => $student,
            'year' => $year,
            'total_borrowed' => $totalBorrowed,
            'total_returned' => $totalReturned,
            'pending_count' => $pendingReturns->count(),
            'pending_returns' => $pendingReturns,
            'damaged_count' => $damagedCount,
            'lost_count' => $lostCount,
            'is_cleared' => $isCleared,
            'loans' => $loans,
        ];
    }

    /**
     * Grant complete school clearance to a student for textbooks.
     */
    public function grantStudentClearance(Student $student, ?string $academicYear = null): void
    {
        $year = $this->resolveAcademicYear($academicYear);
        $alternateYear = str_contains($year, '/') ? str_replace('/', '-', $year) : str_replace('-', '/', $year);

        StudentTextbookLoan::where('student_id', $student->id)
            ->where(function ($q) use ($year, $alternateYear): void {
                $q->where('academic_year', $year)
                    ->orWhere('academic_year', $alternateYear);
            })
            ->update(['clearance_granted' => true]);
    }

    /**
     * Compute comprehensive inventory and solidarity KPIs.
     *
     * @return array<string, mixed>
     */
    public function getInventoryKpis(?string $academicYear = null): array
    {
        $year = $academicYear ?? SchoolSetting::current()?->academic_year ?? '2026/2027';

        $textbooks = Textbook::where('is_active', true)->get();
        $totalTitles = $textbooks->count();
        $totalReceived = (int) $textbooks->sum('total_received');

        $loans = StudentTextbookLoan::where('academic_year', $year)->get();
        $totalDistributed = $loans->count();
        $totalInStock = max(0, $totalReceived - $totalDistributed);

        $solidarityCount = $loans->where('distribution_type', TextbookDistributionType::Solidarity)->count();
        $solidarityRate = $totalDistributed > 0 ? round(($solidarityCount / $totalDistributed) * 100, 1) : 0.0;

        $totalReturned = $loans->whereNotNull('returned_at')->count();
        $returnRate = $totalDistributed > 0 ? round(($totalReturned / $totalDistributed) * 100, 1) : 0.0;

        $damagedOrLost = $loans->whereIn('return_condition', [
            TextbookReturnCondition::Damaged,
            TextbookReturnCondition::Lost,
        ])->count();

        return [
            'total_titles' => $totalTitles,
            'total_received' => $totalReceived,
            'total_distributed' => $totalDistributed,
            'total_in_stock' => $totalInStock,
            'solidarity_count' => $solidarityCount,
            'solidarity_rate' => $solidarityRate,
            'total_returned' => $totalReturned,
            'return_rate' => $returnRate,
            'damaged_or_lost' => $damagedOrLost,
        ];
    }

    /**
     * Assemble single student clearance certificate data.
     *
     * @return array<string, mixed>
     */
    public function assembleStudentClearanceCertificate(Student $student, ?string $academicYear = null): array
    {
        $status = $this->checkStudentClearance($student, $academicYear);
        $school = SchoolSetting::current();

        return [
            'student' => $student,
            'status' => $status,
            'school' => $school,
            'academicYear' => $status['year'],
            'classroom' => $student->group?->classroom,
        ];
    }

    /**
     * Assemble classroom distribution & return sheet data.
     *
     * @return array<string, mixed>
     */
    public function assembleClassroomDistributionSheet(Classroom $classroom, ?string $academicYear = null): array
    {
        $year = $academicYear ?? SchoolSetting::current()?->academic_year ?? '2026/2027';

        $textbooks = Textbook::where('level_id', $classroom->level_id)
            ->where('is_active', true)
            ->with('subject')
            ->get();

        $students = Student::whereHas('group', fn ($q) => $q->where('classroom_id', $classroom->id))
            ->with(['loans' => fn ($q) => $q->where('academic_year', $year)])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return [
            'classroom' => $classroom,
            'academicYear' => $year,
            'textbooks' => $textbooks,
            'students' => $students,
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Assemble annual ONPS / CRDDP inventory ledger data.
     *
     * @return array<string, mixed>
     */
    public function assembleAnnualOnpsInventory(?string $academicYear = null): array
    {
        $year = $academicYear ?? SchoolSetting::current()?->academic_year ?? '2026/2027';

        $textbooks = Textbook::where('is_active', true)
            ->with(['level', 'subject', 'loans' => fn ($q) => $q->where('academic_year', $year)])
            ->get();

        $rows = collect();
        $totalReceived = 0;
        $totalDistributed = 0;
        $totalSolidarity = 0;
        $totalSold = 0;
        $totalReturned = 0;
        $totalInStock = 0;
        $totalFinancialValue = 0.0;

        foreach ($textbooks as $tb) {
            $received = $tb->total_received;
            $loans = $tb->loans;
            $dist = $loans->count();
            $solidarity = $loans->where('distribution_type', TextbookDistributionType::Solidarity)->count();
            $sold = $loans->where('distribution_type', TextbookDistributionType::Purchased)->count();
            $returned = $loans->whereNotNull('returned_at')->count();
            $inStock = max(0, $received - $dist + $returned);
            $financialValue = $dist * (float) $tb->unit_price;

            $rows->push([
                'textbook' => $tb,
                'level' => $tb->level?->name ?? '—',
                'subject' => $tb->subject?->name ?? '—',
                'received' => $received,
                'distributed' => $dist,
                'solidarity' => $solidarity,
                'sold' => $sold,
                'returned' => $returned,
                'in_stock' => $inStock,
                'unit_price' => $tb->unit_price,
                'financial_value' => $financialValue,
            ]);

            $totalReceived += $received;
            $totalDistributed += $dist;
            $totalSolidarity += $solidarity;
            $totalSold += $sold;
            $totalReturned += $returned;
            $totalInStock += $inStock;
            $totalFinancialValue += $financialValue;
        }

        return [
            'academicYear' => $year,
            'rows' => $rows,
            'totalReceived' => $totalReceived,
            'totalDistributed' => $totalDistributed,
            'totalSolidarity' => $totalSolidarity,
            'totalSold' => $totalSold,
            'totalReturned' => $totalReturned,
            'totalInStock' => $totalInStock,
            'totalFinancialValue' => $totalFinancialValue,
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Generate printable PDF for a student's textbook clearance certificate.
     */
    public function generateClearancePdf(Student $student, ?string $academicYear = null): string
    {
        $data = $this->assembleStudentClearanceCertificate($student, $academicYear);
        $data['inlineCss'] = true;

        $html = view('print.textbook-clearance', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable PDF for classroom textbook distribution and return sheet.
     */
    public function generateClassroomDistributionPdf(Classroom $classroom, ?string $academicYear = null): string
    {
        $data = $this->assembleClassroomDistributionSheet($classroom, $academicYear);
        $data['inlineCss'] = true;

        $html = view('print.classroom-textbook-sheet', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable PDF for the annual ONPS inventory ledger.
     */
    public function generateAnnualOnpsInventoryPdf(?string $academicYear = null): string
    {
        $data = $this->assembleAnnualOnpsInventory($academicYear);
        $data['inlineCss'] = true;

        $html = view('print.annual-textbook-inventory', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }
}
