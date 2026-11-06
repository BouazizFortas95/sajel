<?php

namespace App\Services;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class RakmanaSyncService
{
    /**
     * Parse raw roster file content (CSV or HTML table from Rakmana).
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseRosterFile(string $content): array
    {
        // Strip UTF-8 BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Check if content is HTML table export
        if (str_contains($content, '<table') || str_contains($content, '<tr')) {
            return $this->parseHtmlTableRoster($content);
        }

        return $this->parseCsvRoster($content);
    }

    /**
     * Parse CSV roster content with auto delimiter detection.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseCsvRoster(string $content): array
    {
        // Normalize line breaks
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = array_filter(explode("\n", $content), fn ($l) => trim($l) !== '');

        if (empty($lines)) {
            return [];
        }

        // Detect delimiter: comma, semicolon, tab, pipe
        $firstLine = reset($lines);
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;

        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        // Parse header line
        $headerRow = str_getcsv(array_shift($lines), $bestDelimiter);
        $fieldMap = $this->mapHeaderColumns($headerRow);

        $parsedRows = [];

        foreach ($lines as $line) {
            $cols = str_getcsv($line, $bestDelimiter);
            if (empty($cols) || count(array_filter($cols, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($fieldMap as $index => $field) {
                $row[$field] = isset($cols[$index]) ? trim($cols[$index]) : null;
            }

            if (! empty($row['first_name']) || ! empty($row['last_name']) || ! empty($row['national_id_number'])) {
                $parsedRows[] = $this->sanitizeStudentRow($row);
            }
        }

        return $parsedRows;
    }

    /**
     * Parse HTML table roster exported from ministerial portal.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseHtmlTableRoster(string $content): array
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $trMatches);
        if (empty($trMatches[1])) {
            return [];
        }

        $allRows = [];
        $headerMapped = false;
        $fieldMap = [];

        foreach ($trMatches[1] as $trHtml) {
            preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is', $trHtml, $tdMatches);
            if (empty($tdMatches[1])) {
                continue;
            }

            $cells = array_map(function ($cell) {
                $clean = strip_tags($cell);

                return trim(html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }, $tdMatches[1]);

            if (! $headerMapped) {
                $fieldMap = $this->mapHeaderColumns($cells);
                if (! empty($fieldMap)) {
                    $headerMapped = true;
                }

                continue;
            }

            $row = [];
            foreach ($fieldMap as $index => $field) {
                $row[$field] = $cells[$index] ?? null;
            }

            if (! empty($row['first_name']) || ! empty($row['last_name']) || ! empty($row['national_id_number'])) {
                $allRows[] = $this->sanitizeStudentRow($row);
            }
        }

        return $allRows;
    }

    /**
     * Map known Arabic and French header labels to canonical student properties.
     *
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    protected function mapHeaderColumns(array $headers): array
    {
        $map = [];

        foreach ($headers as $index => $raw) {
            $header = mb_strtolower(trim($raw), 'UTF-8');
            $header = str_replace(['_', '-', '.', ':', 'أ', 'إ', 'آ'], ['', '', '', '', 'ا', 'ا', 'ا'], $header);

            if (str_contains($header, 'تعريف') || str_contains($header, 'nin') || str_contains($header, 'national')) {
                $map[$index] = 'national_id_number';
            } elseif (str_contains($header, 'تسجيل') || str_contains($header, 'matricule') || str_contains($header, 'قيد')) {
                $map[$index] = 'registration_number';
            } elseif (str_contains($header, 'هاتف') || str_contains($header, 'tel') || str_contains($header, 'phone')) {
                $map[$index] = 'guardian_phone';
            } elseif (str_contains($header, 'ولي') || str_contains($header, 'tuteur')) {
                $map[$index] = 'guardian_name';
            } elseif (str_contains($header, 'لقب') || str_contains($header, 'nom')) {
                $map[$index] = 'last_name';
            } elseif (str_contains($header, 'اسم') || str_contains($header, 'prenom')) {
                $map[$index] = 'first_name';
            } elseif (str_contains($header, 'مكان') || str_contains($header, 'lieu')) {
                $map[$index] = 'place_of_birth';
            } elseif (str_contains($header, 'تاريخ') || str_contains($header, 'naissance') || str_contains($header, 'ازدياد') || str_contains($header, 'ميلاد')) {
                $map[$index] = 'date_of_birth';
            } elseif (str_contains($header, 'جنس') || str_contains($header, 'sexe') || str_contains($header, 'نوع')) {
                $map[$index] = 'gender';
            } elseif (str_contains($header, 'قسم') || str_contains($header, 'فوج') || str_contains($header, 'classe') || str_contains($header, 'division')) {
                $map[$index] = 'group_name';
            } elseif (str_contains($header, 'صفة') || str_contains($header, 'نظام') || str_contains($header, 'regime')) {
                $map[$index] = 'regime';
            }
        }

        return $map;
    }

    /**
     * Normalize individual row values (dates, gender, regime).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function sanitizeStudentRow(array $row): array
    {
        // Date normalization
        if (! empty($row['date_of_birth'])) {
            $rawDate = trim($row['date_of_birth']);
            try {
                if (preg_match('/^\d{2}[\/\-]\d{2}[\/\-]\d{4}$/', $rawDate)) {
                    $row['date_of_birth'] = Carbon::createFromFormat('d/m/Y', str_replace('-', '/', $rawDate))->toDateString();
                } else {
                    $row['date_of_birth'] = Carbon::parse($rawDate)->toDateString();
                }
            } catch (Throwable) {
                // keep raw or null
            }
        }

        // Gender normalization
        if (! empty($row['gender'])) {
            $g = mb_strtolower(trim($row['gender']), 'UTF-8');
            if (in_array($g, ['ذكر', 'm', 'male', '1'], true)) {
                $row['gender'] = 'male';
            } elseif (in_array($g, ['أنثى', 'انثى', 'f', 'female', '2'], true)) {
                $row['gender'] = 'female';
            }
        }

        // Regime normalization
        if (! empty($row['regime'])) {
            $r = mb_strtolower(trim($row['regime']), 'UTF-8');
            if (str_contains($r, 'نصف') || str_contains($r, 'demi') || str_contains($r, 'half')) {
                $row['regime'] = 'half_board';
            } elseif (str_contains($r, 'داخلي') || str_contains($r, 'interne') || str_contains($r, 'board')) {
                $row['regime'] = 'boarder';
            } else {
                $row['regime'] = 'external';
            }
        }

        return $row;
    }

    /**
     * Preview import data, resolving matched records, new students, and groups.
     *
     * @return array<string, mixed>
     */
    public function previewImport(string $content): array
    {
        $rows = $this->parseRosterFile($content);

        $newCount = 0;
        $updateCount = 0;
        $invalidCount = 0;
        $groupsDetected = [];
        $sampleRows = [];

        foreach ($rows as $index => $row) {
            if (empty($row['first_name']) || empty($row['last_name'])) {
                $invalidCount++;

                continue;
            }

            if (! empty($row['group_name'])) {
                $groupsDetected[$row['group_name']] = ($groupsDetected[$row['group_name']] ?? 0) + 1;
            }

            $match = $this->findExistingStudent($row);

            $status = $match ? 'update' : 'new';
            if ($match) {
                $updateCount++;
            } else {
                $newCount++;
            }

            if ($index < 15) {
                $sampleRows[] = array_merge($row, [
                    'status' => $status,
                    'matched_student_id' => $match?->id,
                ]);
            }
        }

        return [
            'total_count' => count($rows),
            'new_count' => $newCount,
            'update_count' => $updateCount,
            'invalid_count' => $invalidCount,
            'groups_detected' => $groupsDetected,
            'sample_rows' => $sampleRows,
            'all_rows' => $rows,
        ];
    }

    /**
     * Find existing student by NIN, registration number, or full identity.
     */
    public function findExistingStudent(array $row): ?Student
    {
        if (! empty($row['national_id_number'])) {
            $byNin = Student::where('national_id_number', trim($row['national_id_number']))->first();
            if ($byNin) {
                return $byNin;
            }
        }

        if (! empty($row['registration_number'])) {
            $byReg = Student::where('registration_number', trim($row['registration_number']))
                ->orWhere('matricule_number', trim($row['registration_number']))
                ->first();
            if ($byReg) {
                return $byReg;
            }
        }

        if (! empty($row['first_name']) && ! empty($row['last_name']) && ! empty($row['date_of_birth'])) {
            return Student::where('first_name', trim($row['first_name']))
                ->where('last_name', trim($row['last_name']))
                ->where('date_of_birth', $row['date_of_birth'])
                ->first();
        }

        return null;
    }

    /**
     * Execute full import and synchronization inside database transaction.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function executeImport(array $rows, array $options = []): array
    {
        $createGroups = $options['create_missing_groups'] ?? true;
        $updateExisting = $options['update_existing'] ?? true;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                if (empty($row['first_name']) || empty($row['last_name'])) {
                    $skipped++;

                    continue;
                }

                $groupId = null;
                if (! empty($row['group_name'])) {
                    $groupId = $this->resolveOrCreateGroup($row['group_name'], $createGroups);
                }

                $match = $this->findExistingStudent($row);

                if ($match) {
                    if ($updateExisting) {
                        $updateData = [];

                        if (! empty($row['national_id_number'])) {
                            $updateData['national_id_number'] = $row['national_id_number'];
                        }
                        if (! empty($row['place_of_birth'])) {
                            $updateData['place_of_birth'] = $row['place_of_birth'];
                        }
                        if (! empty($row['gender'])) {
                            $updateData['gender'] = $row['gender'];
                        }
                        if (! empty($row['regime'])) {
                            $updateData['regime'] = $row['regime'];
                        }
                        if (! empty($row['guardian_name'])) {
                            $updateData['guardian_name'] = $row['guardian_name'];
                        }
                        if (! empty($row['guardian_phone'])) {
                            $updateData['guardian_phone'] = $row['guardian_phone'];
                        }
                        if ($groupId && ! $match->group_id) {
                            $updateData['group_id'] = $groupId;
                        }

                        if (! empty($updateData)) {
                            $match->update($updateData);
                            $updated++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        $skipped++;
                    }
                } else {
                    // Create new student
                    Student::create([
                        'first_name' => trim($row['first_name']),
                        'last_name' => trim($row['last_name']),
                        'registration_number' => ! empty($row['registration_number']) ? trim($row['registration_number']) : null,
                        'matricule_number' => ! empty($row['registration_number']) ? trim($row['registration_number']) : null,
                        'national_id_number' => ! empty($row['national_id_number']) ? trim($row['national_id_number']) : null,
                        'date_of_birth' => ! empty($row['date_of_birth']) ? $row['date_of_birth'] : null,
                        'place_of_birth' => ! empty($row['place_of_birth']) ? $row['place_of_birth'] : null,
                        'gender' => $row['gender'] ?? null,
                        'regime' => $row['regime'] ?? 'external',
                        'guardian_name' => $row['guardian_name'] ?? null,
                        'guardian_phone' => $row['guardian_phone'] ?? null,
                        'group_id' => $groupId ?? Group::first()?->id ?? 1,
                        'is_active' => true,
                        'enrollment_date' => now()->toDateString(),
                    ]);

                    $created++;
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $errors[] = $e->getMessage();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'success' => empty($errors),
        ];
    }

    /**
     * Resolve existing Group by name or create a default group within matching Classroom.
     */
    protected function resolveOrCreateGroup(string $groupName, bool $allowCreate = true): ?int
    {
        $clean = trim($groupName);

        // Try exact name on Group
        $group = Group::where('name', $clean)->first();
        if ($group) {
            return $group->id;
        }

        // Try matching by classroom name (e.g. "1م1", "1AM1", "1 م 1")
        $classroom = Classroom::where('name', $clean)->first();
        if (! $classroom) {
            $normalizedName = str_replace([' ', 'م', 'AM', 'am'], '', $clean);
            $classroom = Classroom::all()->first(function ($c) use ($normalizedName) {
                return str_replace([' ', 'م', 'AM', 'am'], '', $c->name) === $normalizedName;
            });
        }

        if ($classroom) {
            $existingGroup = $classroom->groups()->first();
            if ($existingGroup) {
                return $existingGroup->id;
            }

            if ($allowCreate) {
                $newGroup = Group::create([
                    'classroom_id' => $classroom->id,
                    'name' => 'الفوج 1',
                ]);

                return $newGroup->id;
            }
        }

        // Fallback: search any classroom with first level
        if ($allowCreate) {
            $fallbackClassroom = Classroom::firstOrCreate(
                ['name' => $clean],
                ['level_id' => Level::first()?->id ?? 1]
            );

            $newGroup = Group::create([
                'classroom_id' => $fallbackClassroom->id,
                'name' => 'الفوج 1',
            ]);

            return $newGroup->id;
        }

        return null;
    }

    /**
     * Generate Rakmana Trimester Grade Matrix for a Classroom and Subject.
     *
     * @return array<string, mixed>
     */
    public function generateGradeExportMatrix(
        Classroom $classroom,
        Subject $subject,
        int $trimester = 1,
        ?string $academicYear = null
    ): array {
        $school = SchoolSetting::current();
        $year = $academicYear ?? ($school?->academic_year ?? '2026/2027');

        // Fetch students in this classroom
        $students = Student::whereHas('group', function ($q) use ($classroom) {
            $q->where('classroom_id', $classroom->id);
        })
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Find relevant assessments for this classroom and subject
        $assessments = Assessment::whereHas('schedule', function ($q) use ($classroom, $subject) {
            $q->where('classroom_id', $classroom->id)
                ->where('subject_id', $subject->id);
        })->with('grades')->get();

        $rows = [];

        foreach ($students as $index => $student) {
            $studentGrades = Grade::where('student_id', $student->id)
                ->whereIn('assessment_id', $assessments->pluck('id'))
                ->with('assessment')
                ->get();

            // Continuous assessment score
            $continuousGrade = $studentGrades->first(fn ($g) => in_array($g->assessment?->type, [
                AssessmentType::ContinuousAssessment,
                AssessmentType::Quiz,
            ]))?->score;

            // Midterm score
            $midtermGrade = $studentGrades->first(fn ($g) => $g->assessment?->type === AssessmentType::Midterm)?->score;

            // Exam score
            $examGrade = $studentGrades->first(fn ($g) => $g->assessment?->type === AssessmentType::Exam)?->score;

            // Calculate Algerian middle school trimester average:
            // Standard: (Continuous + Midterm + (Exam * 2)) / 4
            $average = null;
            if ($continuousGrade !== null && $midtermGrade !== null && $examGrade !== null) {
                $average = round(($continuousGrade + $midtermGrade + ($examGrade * 2)) / 4, 2);
            } elseif ($continuousGrade !== null && $examGrade !== null) {
                $average = round(($continuousGrade + ($examGrade * 2)) / 3, 2);
            } elseif ($midtermGrade !== null && $examGrade !== null) {
                $average = round(($midtermGrade + ($examGrade * 2)) / 3, 2);
            } elseif ($examGrade !== null) {
                $average = round($examGrade, 2);
            }

            // Pedagogical appreciation
            $appreciation = '—';
            if ($average !== null) {
                if ($average >= 18) {
                    $appreciation = 'ممتاز';
                } elseif ($average >= 16) {
                    $appreciation = 'جيد جداً';
                } elseif ($average >= 14) {
                    $appreciation = 'جيد';
                } elseif ($average >= 12) {
                    $appreciation = 'قريب من الجيد';
                } elseif ($average >= 10) {
                    $appreciation = 'متوسط';
                } elseif ($average >= 8) {
                    $appreciation = 'دون المتوسط';
                } else {
                    $appreciation = 'ضعيف';
                }
            }

            $rows[] = [
                'order' => $index + 1,
                'student_id' => $student->id,
                'national_id_number' => $student->national_id_number ?? '—',
                'registration_number' => $student->registration_number ?? '—',
                'last_name' => $student->last_name,
                'first_name' => $student->first_name,
                'full_name' => $student->full_name,
                'date_of_birth' => $student->date_of_birth ? Carbon::parse($student->date_of_birth)->format('d/m/Y') : '—',
                'continuous' => $continuousGrade !== null ? number_format((float) $continuousGrade, 2) : '',
                'midterm' => $midtermGrade !== null ? number_format((float) $midtermGrade, 2) : '',
                'exam' => $examGrade !== null ? number_format((float) $examGrade, 2) : '',
                'average' => $average !== null ? number_format((float) $average, 2) : '',
                'appreciation' => $appreciation,
            ];
        }

        return [
            'school' => $school,
            'academic_year' => $year,
            'trimester' => $trimester,
            'classroom' => $classroom,
            'subject' => $subject,
            'students_count' => count($rows),
            'rows' => $rows,
        ];
    }

    /**
     * Generate UTF-8 BOM CSV compatible with Rakmana import template.
     */
    public function generateRakmanaGradeCsv(
        Classroom $classroom,
        Subject $subject,
        int $trimester = 1,
        ?string $academicYear = null
    ): string {
        $matrix = $this->generateGradeExportMatrix($classroom, $subject, $trimester, $academicYear);

        $output = fopen('php://temp', 'r+');

        // Headers
        fputcsv($output, [
            'الرقم',
            'رقم التعريف الوطني',
            'رقم التسجيل',
            'اللقب',
            'الاسم',
            'تاريخ الميلاد',
            'التقويم المستمر',
            'الفرض 1',
            'الاختبار',
            'المعدل الفصلي',
            'الملاحظة',
        ]);

        foreach ($matrix['rows'] as $row) {
            fputcsv($output, [
                $row['order'],
                $row['national_id_number'],
                $row['registration_number'],
                $row['last_name'],
                $row['first_name'],
                $row['date_of_birth'],
                $row['continuous'],
                $row['midterm'],
                $row['exam'],
                $row['average'],
                $row['appreciation'],
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // Prepend UTF-8 BOM for Microsoft Excel compatibility
        return "\xEF\xBB\xBF".$csvContent;
    }

    /**
     * Audit institution database for Rakmana completeness and consistency.
     *
     * @return array<string, mixed>
     */
    public function auditReconciliation(?string $academicYear = null): array
    {
        $allStudents = Student::query()->where('is_active', true)->get();
        $total = $allStudents->count();

        $withNin = $allStudents->filter(fn ($s) => ! empty($s->national_id_number))->count();
        $missingNin = $total - $withNin;
        $ninCompletenessRate = $total > 0 ? round(($withNin / $total) * 100, 1) : 100.0;

        $missingBirth = $allStudents->filter(fn ($s) => empty($s->date_of_birth))->count();
        $missingBirthPlace = $allStudents->filter(fn ($s) => empty($s->place_of_birth))->count();
        $missingGuardian = $allStudents->filter(fn ($s) => empty($s->guardian_name) && empty($s->guardian_phone))->count();

        // Students with missing NIN for actionable list
        $ninDeficientList = $allStudents->filter(fn ($s) => empty($s->national_id_number))
            ->take(30)
            ->map(fn ($s) => [
                'id' => $s->id,
                'full_name' => $s->full_name,
                'classroom' => $s->group?->classroom?->name ?? '—',
                'registration_number' => $s->registration_number ?? '—',
                'date_of_birth' => $s->date_of_birth ? Carbon::parse($s->date_of_birth)->format('d/m/Y') : '—',
            ])->values()->all();

        return [
            'total_students' => $total,
            'with_nin_count' => $withNin,
            'missing_nin_count' => $missingNin,
            'nin_completeness_rate' => $ninCompletenessRate,
            'missing_birth_count' => $missingBirth,
            'missing_birth_place_count' => $missingBirthPlace,
            'missing_guardian_count' => $missingGuardian,
            'deficient_students' => $ninDeficientList,
        ];
    }

    /**
     * Resolve date boundaries for Algerian academic trimesters.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function getTrimesterPeriod(int $trimester = 1, ?string $academicYear = null): array
    {
        $yearStr = $academicYear ?? SchoolSetting::currentAcademicYear();
        $parts = preg_split('/[\/\-]/', $yearStr);
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : (int) date('Y');
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : $startYear + 1;

        return match ($trimester) {
            2 => [
                'start' => Carbon::create($endYear, 1, 1)->startOfDay(),
                'end' => Carbon::create($endYear, 3, 31)->endOfDay(),
            ],
            3 => [
                'start' => Carbon::create($endYear, 4, 1)->startOfDay(),
                'end' => Carbon::create($endYear, 6, 30)->endOfDay(),
            ],
            default => [
                'start' => Carbon::create($startYear, 9, 1)->startOfDay(),
                'end' => Carbon::create($startYear, 12, 31)->endOfDay(),
            ],
        };
    }

    /**
     * Generate ministerial student roster CSV according to Algerian Ministry format.
     */
    public function generateRakmanaStudentRosterCsv(
        ?int $classroomId = null,
        ?int $levelId = null,
        ?string $academicYear = null
    ): string {
        $query = Student::query()
            ->where('is_active', true)
            ->with(['group.classroom.level']);

        if ($classroomId) {
            $query->whereHas('group', fn ($q) => $q->where('classroom_id', $classroomId));
        } elseif ($levelId) {
            $query->whereHas('group.classroom', fn ($q) => $q->where('level_id', $levelId));
        }

        $students = $query
            ->orderBy('group_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $output = fopen('php://temp', 'r+');

        // Official Ministry Headers
        fputcsv($output, [
            'الرقم',
            'رقم التعريف الوطني',
            'رقم التسجيل',
            'اللقب بالعربية',
            'الاسم بالعربية',
            'الجنس',
            'تاريخ الميلاد',
            'مكان الميلاد',
            'المستوى التعليمي',
            'القسم التربوي',
            'الفوج',
            'صفة التمدرس',
            'اسم ولقب الولي',
            'صلة القرابة',
            'رقم هاتف الولي',
        ]);

        foreach ($students as $index => $student) {
            $regimeLabel = match ($student->regime) {
                'half_board' => 'نصف داخلي',
                'boarder' => 'داخلي',
                default => 'خارجي',
            };

            $genderLabel = $student->isMale() ? 'ذكر' : 'أنثى';
            $dob = $student->date_of_birth ? Carbon::parse($student->date_of_birth)->format('d/m/Y') : '—';
            $nin = $student->national_id_number ? '="'.$student->national_id_number.'"' : '';
            $regNum = $student->registration_number ?? $student->matricule_number ?? '';

            fputcsv($output, [
                $index + 1,
                $nin,
                $regNum,
                $student->last_name,
                $student->first_name,
                $genderLabel,
                $dob,
                $student->place_of_birth ?? '—',
                $student->group?->classroom?->level?->name ?? '—',
                $student->group?->classroom?->name ?? '—',
                $student->group?->name ?? '—',
                $regimeLabel,
                $student->guardian_name ?? '—',
                $student->guardian_relation ?? 'أب/ولي أمر',
                $student->guardian_phone ?? '—',
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // Prepend UTF-8 BOM
        return "\xEF\xBB\xBF".$csvContent;
    }

    /**
     * Generate blank ministerial grade booking template CSV for teachers.
     */
    public function generateBlankGradeTemplateCsv(
        Classroom $classroom,
        Subject $subject,
        int $trimester = 1,
        ?string $academicYear = null
    ): string {
        $school = SchoolSetting::current();
        $year = $academicYear ?? ($school?->academic_year ?? '2026/2027');

        $students = Student::whereHas('group', fn ($q) => $q->where('classroom_id', $classroom->id))
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $output = fopen('php://temp', 'r+');

        // Headers matching Ministry Rakmana grade import sheet
        fputcsv($output, [
            'الرقم',
            'رقم التعريف الوطني',
            'رقم التسجيل',
            'اللقب',
            'الاسم',
            'تاريخ الميلاد',
            'التقويم المستمر',
            'الفرض 1',
            'الاختبار',
            'المعدل الفصلي',
            'الملاحظة',
        ]);

        foreach ($students as $index => $student) {
            $dob = $student->date_of_birth ? Carbon::parse($student->date_of_birth)->format('d/m/Y') : '—';
            $nin = $student->national_id_number ? '="'.$student->national_id_number.'"' : '';
            $regNum = $student->registration_number ?? $student->matricule_number ?? '';

            fputcsv($output, [
                $index + 1,
                $nin,
                $regNum,
                $student->last_name,
                $student->first_name,
                $dob,
                '', // Continuous
                '', // Midterm
                '', // Exam
                '', // Average
                '', // Observation
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return "\xEF\xBB\xBF".$csvContent;
    }

    /**
     * Parse raw ministerial grade booking content (CSV, TSV, or semicolons).
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseGradeBookingFile(string $content): array
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = array_filter(explode("\n", $content), fn ($l) => trim($l) !== '');

        if (empty($lines)) {
            return [];
        }

        $firstLine = reset($lines);
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;

        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $rawHeader = str_getcsv(array_shift($lines), $bestDelimiter);
        $fieldMap = $this->mapGradeHeaderColumns($rawHeader);

        $parsedRows = [];

        foreach ($lines as $line) {
            $cols = str_getcsv($line, $bestDelimiter);
            if (empty($cols) || count(array_filter($cols, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($fieldMap as $index => $field) {
                $val = isset($cols[$index]) ? trim($cols[$index]) : null;
                // Strip ="..." wrappers used for Excel string protection
                if ($val !== null && str_starts_with($val, '="') && str_ends_with($val, '"')) {
                    $val = substr($val, 2, -1);
                }
                $row[$field] = $val;
            }

            if (! empty($row['national_id_number']) || ! empty($row['registration_number']) || (! empty($row['last_name']) && ! empty($row['first_name']))) {
                $parsedRows[] = $this->sanitizeGradeRow($row);
            }
        }

        return $parsedRows;
    }

    /**
     * Map grade booking headers to canonical keys.
     *
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    protected function mapGradeHeaderColumns(array $headers): array
    {
        $map = [];

        foreach ($headers as $index => $raw) {
            $header = mb_strtolower(trim($raw), 'UTF-8');
            $header = str_replace(['_', '-', '.', ':', 'أ', 'إ', 'آ'], ['', '', '', '', 'ا', 'ا', 'ا'], $header);

            if (str_contains($header, 'تعريف') || str_contains($header, 'nin') || str_contains($header, 'national')) {
                $map[$index] = 'national_id_number';
            } elseif (str_contains($header, 'تسجيل') || str_contains($header, 'matricule') || str_contains($header, 'قيد')) {
                $map[$index] = 'registration_number';
            } elseif (str_contains($header, 'لقب') || str_contains($header, 'nom')) {
                $map[$index] = 'last_name';
            } elseif (str_contains($header, 'اسم') || str_contains($header, 'prenom')) {
                $map[$index] = 'first_name';
            } elseif (str_contains($header, 'تقويم') || str_contains($header, 'مستمر') || str_contains($header, 'continue') || str_contains($header, 'eval')) {
                $map[$index] = 'continuous';
            } elseif (str_contains($header, 'فرض 1') || str_contains($header, 'الفرض') || str_contains($header, 'devoir 1') || str_contains($header, 'devoir')) {
                $map[$index] = 'midterm';
            } elseif (str_contains($header, 'اختبار') || str_contains($header, 'امتحان') || str_contains($header, 'examen') || str_contains($header, 'compo')) {
                $map[$index] = 'exam';
            } elseif (str_contains($header, 'معدل') || str_contains($header, 'moyenne')) {
                $map[$index] = 'average';
            } elseif (str_contains($header, 'ملاحظة') || str_contains($header, 'تقدير') || str_contains($header, 'appreciation') || str_contains($header, 'obs')) {
                $map[$index] = 'remarks';
            }
        }

        return $map;
    }

    /**
     * Normalize individual score values (handling comma decimals, absence markers, range 0-20).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function sanitizeGradeRow(array $row): array
    {
        foreach (['continuous', 'midterm', 'exam'] as $scoreField) {
            if (! isset($row[$scoreField]) || $row[$scoreField] === '') {
                $row[$scoreField] = null;

                continue;
            }

            $raw = trim((string) $row[$scoreField]);
            // Convert comma decimal to dot: "14,50" -> "14.50"
            $normalized = str_replace(',', '.', $raw);

            // Handle absence markers
            if (in_array(mb_strtolower($raw, 'UTF-8'), ['غ', 'غائب', 'غ/م', 'abs', 'a'], true)) {
                $row[$scoreField] = null;
                $row['is_absent_'.$scoreField] = true;

                continue;
            }

            if (is_numeric($normalized)) {
                $val = (float) $normalized;
                $row[$scoreField] = ($val >= 0 && $val <= 20) ? round($val, 2) : null;
            } else {
                $row[$scoreField] = null;
            }
        }

        return $row;
    }

    /**
     * Preview ministerial grade file import before committing to database.
     *
     * @return array<string, mixed>
     */
    public function previewGradeImport(
        string $content,
        Classroom $classroom,
        Subject $subject,
        int $trimester = 1,
        ?string $academicYear = null
    ): array {
        $parsedRows = $this->parseGradeBookingFile($content);

        // Fetch classroom students
        $students = Student::whereHas('group', fn ($q) => $q->where('classroom_id', $classroom->id))
            ->where('is_active', true)
            ->get();

        $matchedRows = [];
        $unmatchedRows = [];
        $matchedStudentIds = [];

        foreach ($parsedRows as $row) {
            $matchedStudent = null;

            // 1. Try matching by National ID Number (NIN)
            if (! empty($row['national_id_number'])) {
                $cleanNin = preg_replace('/\D/', '', $row['national_id_number']);
                $matchedStudent = $students->first(fn ($s) => $s->national_id_number && preg_replace('/\D/', '', $s->national_id_number) === $cleanNin);
            }

            // 2. Try matching by Registration Number
            if (! $matchedStudent && ! empty($row['registration_number'])) {
                $cleanReg = trim($row['registration_number']);
                $matchedStudent = $students->first(fn ($s) => $s->registration_number === $cleanReg || $s->matricule_number === $cleanReg);
            }

            // 3. Try matching by Name
            if (! $matchedStudent && ! empty($row['first_name']) && ! empty($row['last_name'])) {
                $cLast = mb_strtolower(trim($row['last_name']), 'UTF-8');
                $cFirst = mb_strtolower(trim($row['first_name']), 'UTF-8');
                $matchedStudent = $students->first(function ($s) use ($cLast, $cFirst) {
                    return mb_strtolower($s->last_name, 'UTF-8') === $cLast && mb_strtolower($s->first_name, 'UTF-8') === $cFirst;
                });
            }

            if ($matchedStudent) {
                $matchedStudentIds[] = $matchedStudent->id;

                // Calculate projected average
                $c = $row['continuous'];
                $m = $row['midterm'];
                $e = $row['exam'];
                $projAvg = null;
                if ($c !== null && $m !== null && $e !== null) {
                    $projAvg = round(($c + $m + ($e * 2)) / 4, 2);
                } elseif ($c !== null && $e !== null) {
                    $projAvg = round(($c + ($e * 2)) / 3, 2);
                } elseif ($m !== null && $e !== null) {
                    $projAvg = round(($m + ($e * 2)) / 3, 2);
                } elseif ($e !== null) {
                    $projAvg = round($e, 2);
                }

                $matchedRows[] = [
                    'student_id' => $matchedStudent->id,
                    'student_name' => $matchedStudent->full_name,
                    'national_id_number' => $matchedStudent->national_id_number ?? '—',
                    'registration_number' => $matchedStudent->registration_number ?? '—',
                    'continuous' => $c,
                    'midterm' => $m,
                    'exam' => $e,
                    'projected_average' => $projAvg,
                    'remarks' => $row['remarks'] ?? null,
                ];
            } else {
                $unmatchedRows[] = $row;
            }
        }

        $missingStudents = $students->reject(fn ($s) => in_array($s->id, $matchedStudentIds))->values()->all();

        return [
            'total_parsed_rows' => count($parsedRows),
            'matched_count' => count($matchedRows),
            'unmatched_count' => count($unmatchedRows),
            'missing_in_file_count' => count($missingStudents),
            'matched_rows' => $matchedRows,
            'unmatched_rows' => $unmatchedRows,
            'missing_students' => array_map(fn ($s) => [
                'id' => $s->id,
                'full_name' => $s->full_name,
                'national_id_number' => $s->national_id_number ?? '—',
            ], $missingStudents),
        ];
    }

    /**
     * Execute full grade import inside database transaction.
     *
     * @param  array<int, array<string, mixed>>  $matchedRows
     * @return array<string, mixed>
     */
    public function executeGradeImport(
        array $matchedRows,
        Classroom $classroom,
        Subject $subject,
        int $trimester = 1,
        ?User $user = null,
        ?string $academicYear = null
    ): array {
        if (empty($matchedRows)) {
            return ['success' => false, 'error' => 'No matched rows to import.'];
        }

        $period = $this->getTrimesterPeriod($trimester, $academicYear);

        DB::beginTransaction();
        try {
            // 1. Resolve or create schedule for this classroom and subject
            $schedule = Schedule::where('classroom_id', $classroom->id)
                ->where('subject_id', $subject->id)
                ->first();

            if (! $schedule) {
                $teacher = User::where('role', 'teacher')->first() ?? $user ?? User::first();
                if (! $teacher) {
                    $teacher = User::create([
                        'name' => 'أستاذ المادة',
                        'email' => 'teacher.default@sajel.local',
                        'password' => bcrypt('secret'),
                        'role' => 'teacher',
                    ]);
                }

                $group = $classroom->groups()->first() ?? Group::create([
                    'classroom_id' => $classroom->id,
                    'name' => 'الفوج 1',
                ]);

                $schedule = Schedule::create([
                    'user_id' => $teacher->id,
                    'classroom_id' => $classroom->id,
                    'group_id' => $group->id,
                    'subject_id' => $subject->id,
                    'day_of_week' => 'sunday',
                    'start_time' => '08:00:00',
                    'end_time' => '09:00:00',
                ]);
            }

            // 2. Resolve or create the 3 standard assessments for this trimester
            $continuousAssessment = Assessment::where('schedule_id', $schedule->id)
                ->where('type', AssessmentType::ContinuousAssessment)
                ->whereBetween('date', [$period['start']->toDateString(), $period['end']->toDateString()])
                ->first();

            if (! $continuousAssessment) {
                $continuousAssessment = Assessment::create([
                    'schedule_id' => $schedule->id,
                    'title' => 'التقويم المستمر - الفصل '.$trimester,
                    'type' => AssessmentType::ContinuousAssessment,
                    'date' => $period['start']->copy()->addDays(30)->toDateString(),
                    'max_score' => 20,
                ]);
            }

            $midtermAssessment = Assessment::where('schedule_id', $schedule->id)
                ->where('type', AssessmentType::Midterm)
                ->whereBetween('date', [$period['start']->toDateString(), $period['end']->toDateString()])
                ->first();

            if (! $midtermAssessment) {
                $midtermAssessment = Assessment::create([
                    'schedule_id' => $schedule->id,
                    'title' => 'الفرض المحروس - الفصل '.$trimester,
                    'type' => AssessmentType::Midterm,
                    'date' => $period['start']->copy()->addDays(60)->toDateString(),
                    'max_score' => 20,
                ]);
            }

            $examAssessment = Assessment::where('schedule_id', $schedule->id)
                ->where('type', AssessmentType::Exam)
                ->whereBetween('date', [$period['start']->toDateString(), $period['end']->toDateString()])
                ->first();

            if (! $examAssessment) {
                $examAssessment = Assessment::create([
                    'schedule_id' => $schedule->id,
                    'title' => 'الاختبار الفصلي - الفصل '.$trimester,
                    'type' => AssessmentType::Exam,
                    'date' => $period['end']->copy()->subDays(10)->toDateString(),
                    'max_score' => 20,
                ]);
            }

            $importedContinuous = 0;
            $importedMidterm = 0;
            $importedExam = 0;

            // 3. Upsert grades
            foreach ($matchedRows as $row) {
                $studentId = $row['student_id'];

                if (isset($row['continuous']) && $row['continuous'] !== null && $row['continuous'] !== '') {
                    Grade::updateOrCreate(
                        ['assessment_id' => $continuousAssessment->id, 'student_id' => $studentId],
                        ['score' => (float) $row['continuous']]
                    );
                    $importedContinuous++;
                }

                if (isset($row['midterm']) && $row['midterm'] !== null && $row['midterm'] !== '') {
                    Grade::updateOrCreate(
                        ['assessment_id' => $midtermAssessment->id, 'student_id' => $studentId],
                        ['score' => (float) $row['midterm']]
                    );
                    $importedMidterm++;
                }

                if (isset($row['exam']) && $row['exam'] !== null && $row['exam'] !== '') {
                    Grade::updateOrCreate(
                        ['assessment_id' => $examAssessment->id, 'student_id' => $studentId],
                        [
                            'score' => (float) $row['exam'],
                            'remarks' => ! empty($row['remarks']) ? trim($row['remarks']) : null,
                        ]
                    );
                    $importedExam++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'students_processed' => count($matchedRows),
                'continuous_saved' => $importedContinuous,
                'midterm_saved' => $importedMidterm,
                'exam_saved' => $importedExam,
                'total_grades_saved' => $importedContinuous + $importedMidterm + $importedExam,
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
