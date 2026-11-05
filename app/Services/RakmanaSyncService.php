<?php

namespace App\Services;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
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
}
