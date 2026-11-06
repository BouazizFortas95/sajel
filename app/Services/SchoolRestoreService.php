<?php

namespace App\Services;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SchoolRestoreService
{
    /**
     * Complete list of academic domain tables in foreign-key dependency order.
     *
     * @var list<string>
     */
    protected array $tables = [
        'school_settings',
        'education_phases',
        'levels',
        'classrooms',
        'groups',
        'subjects',
        'users',
        'curriculum_domains',
        'curriculum_sequences',
        'curriculum_resources',
        'students',
        'schedules',
        'school_holidays',
        'active_learning_strategies',
        'labs',
        'lab_stations',
        'lab_seating_plans',
        'lab_seating_plan_stations',
        'pedagogical_memos',
        'pedagogical_meetings',
        'daily_logs',
        'attendances',
        'assessments',
        'grades',
        'behavior_observations',
        'pedagogical_remediations',
        'inspection_reports',
        'teacher_absences',
        'textbooks',
        'student_textbook_loans',
        'student_transfers',
        'disciplinary_records',
        'school_census_records',
        'student_orientations',
        'student_counseling_profiles',
        'counseling_sessions',
        'quarterly_institutional_reports',
        'school_clubs',
        'club_memberships',
        'club_activities',
        'student_medical_records',
        'pedagogical_councils',
        'pedagogical_council_attendances',
        'pedagogical_seminars',
        'pedagogical_seminar_attendances',
    ];

    /**
     * @return list<string>
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Inspect a backup payload and return a validation report.
     *
     * @param  array<string, mixed>|string  $payload
     * @return array<string, mixed>
     */
    public function inspect(array|string $payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (! is_array($decoded)) {
                return [
                    'valid' => false,
                    'errors' => ['ملف النسخة الاحتياطية غير صالح أو تالف (JSON غير صحيح).'],
                    'warnings' => [],
                    'table_counts' => [],
                    'total_rows' => 0,
                    'school_name' => null,
                    'academic_year' => null,
                    'generated_at' => null,
                    'has_tables' => false,
                ];
            }
            $payload = $decoded;
        }

        $errors = [];
        $warnings = [];

        $hasMeta = isset($payload['metadata']) && is_array($payload['metadata']);
        $hasConfig = isset($payload['institutional_config']) && is_array($payload['institutional_config']);
        $hasTables = isset($payload['tables']) && is_array($payload['tables']);

        if (! $hasMeta && ! $hasConfig && ! $hasTables) {
            $errors[] = 'الملف لا يحتوي على بيانات نسخة احتياطية صالحة لمؤسسة سجِّل.';
        }

        $schoolName = $payload['institutional_config']['school_name']
            ?? $payload['metadata']['school_setting']['school_name']
            ?? null;

        $academicYear = $payload['institutional_config']['academic_year']
            ?? $payload['metadata']['school_setting']['academic_year']
            ?? null;

        $generatedAt = $payload['metadata']['generated_at'] ?? null;

        $currentYear = SchoolSetting::current()?->academic_year;
        if ($academicYear && $currentYear && $academicYear !== $currentYear) {
            $warnings[] = "السنة الدراسية في النسخة الاحتياطية ({$academicYear}) تختلف عن السنة الحالية بالمؤسسة ({$currentYear}).";
        }

        $tableCounts = [];
        $totalRows = 0;

        if ($hasTables) {
            foreach ($payload['tables'] as $table => $rows) {
                if (! Schema::hasTable($table)) {
                    $warnings[] = "الجدول [{$table}] غير موجود في قاعدة بيانات النظام الحالية وتم تخطيه.";

                    continue;
                }

                $count = is_array($rows) ? count($rows) : 0;
                $tableCounts[$table] = $count;
                $totalRows += $count;
            }
        } elseif ($hasMeta && isset($payload['metadata']['table_row_counts']) && is_array($payload['metadata']['table_row_counts'])) {
            $tableCounts = $payload['metadata']['table_row_counts'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'school_name' => $schoolName,
            'academic_year' => $academicYear,
            'generated_at' => $generatedAt,
            'has_tables' => $hasTables,
            'table_counts' => $tableCounts,
            'total_rows' => $totalRows,
        ];
    }

    /**
     * Restore from a backup payload with optional dry-run and clean-wipe modes.
     *
     * @param  array<string, mixed>|string  $payload
     * @return array<string, mixed>
     */
    public function restore(array|string $payload, bool $dryRun = false, bool $wipeExisting = false): array
    {
        $startTime = microtime(true);

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (! is_array($decoded)) {
                return [
                    'success' => false,
                    'errors' => ['تعذر قراءة ملف النسخة الاحتياطية. صيغة JSON غير صالحة.'],
                ];
            }
            $payload = $decoded;
        }

        $inspection = $this->inspect($payload);
        if (! $inspection['valid']) {
            return [
                'success' => false,
                'errors' => $inspection['errors'],
                'warnings' => $inspection['warnings'],
                'inspection' => $inspection,
            ];
        }

        if ($dryRun) {
            $simulated = [];
            if ($inspection['has_tables']) {
                foreach ($this->tables as $table) {
                    if (isset($payload['tables'][$table]) && is_array($payload['tables'][$table])) {
                        $simulated[$table] = count($payload['tables'][$table]);
                    }
                }
            } elseif (! empty($payload['institutional_config'])) {
                $simulated['school_settings'] = 1;
            }

            return [
                'success' => true,
                'dry_run' => true,
                'inspection' => $inspection,
                'simulated_counts' => $simulated,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'message' => 'اكتملت المعاينة التجريبية بنجاح دون إجراء أي تعديل على قاعدة البيانات.',
            ];
        }

        $driver = DB::getDriverName();
        $restoredCounts = [];

        DB::beginTransaction();

        try {
            $this->disableForeignKeys($driver);

            if ($wipeExisting) {
                $this->wipeTables();
            }

            // 1. Restore tables data
            if ($inspection['has_tables']) {
                foreach ($this->tables as $table) {
                    if (! isset($payload['tables'][$table]) || ! is_array($payload['tables'][$table])) {
                        continue;
                    }

                    $rows = $payload['tables'][$table];
                    if (empty($rows)) {
                        continue;
                    }

                    $inserted = 0;
                    if ($table === 'users') {
                        foreach ($rows as $userRow) {
                            $userRow = (array) $userRow;
                            $email = $userRow['email'] ?? null;
                            if (! $email) {
                                continue;
                            }

                            $existing = DB::table('users')->where('email', $email)->first();
                            if ($existing) {
                                $updateData = $userRow;
                                unset($updateData['id'], $updateData['password'], $updateData['remember_token']);
                                if (! empty($updateData)) {
                                    DB::table('users')->where('id', $existing->id)->update($updateData);
                                }
                            } else {
                                $userRow['password'] = Hash::make('password');
                                DB::table('users')->insert($userRow);
                            }
                            $inserted++;
                        }
                    } else {
                        $chunks = array_chunk($rows, 100);
                        foreach ($chunks as $chunk) {
                            $cleanChunk = array_map(fn ($r) => (array) $r, $chunk);
                            DB::table($table)->insertOrIgnore($cleanChunk);
                            $inserted += count($cleanChunk);
                        }
                    }

                    $restoredCounts[$table] = $inserted;
                }
            }

            // 2. Restore institutional settings if present
            if (! empty($payload['institutional_config'])) {
                $config = (array) $payload['institutional_config'];
                unset($config['id']);

                $setting = SchoolSetting::query()->orderBy('id')->first() ?? new SchoolSetting;
                $setting->fill($config)->save();
                SchoolSetting::flushCache();
                $restoredCounts['school_settings'] = 1;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'errors' => ['حدث خطأ أثناء استرجاع البيانات: '.$e->getMessage()],
                'inspection' => $inspection,
            ];
        } finally {
            $this->enableForeignKeys($driver);
        }

        return [
            'success' => true,
            'dry_run' => false,
            'inspection' => $inspection,
            'restored_counts' => $restoredCounts,
            'total_restored' => array_sum($restoredCounts),
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'message' => 'تم استرجاع النسخة الاحتياطية بنجاح.',
        ];
    }

    /**
     * Restore from a file path.
     *
     * @return array<string, mixed>
     */
    public function restoreFromFile(string $filePath, bool $dryRun = false, bool $wipeExisting = false): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [
                'success' => false,
                'errors' => ["ملف النسخة الاحتياطية غير موجود أو لا يمكن قراءته: {$filePath}"],
            ];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [
                'success' => false,
                'errors' => ['فشل قراءة محتوى ملف النسخة الاحتياطية.'],
            ];
        }

        return $this->restore($content, $dryRun, $wipeExisting);
    }

    protected function wipeTables(): void
    {
        $currentAuthId = Auth::id();

        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($table === 'users' && $currentAuthId) {
                DB::table('users')->where('id', '!=', $currentAuthId)->delete();
            } else {
                DB::table($table)->delete();
            }
        }
    }

    protected function disableForeignKeys(string $driver): void
    {
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }
    }

    protected function enableForeignKeys(string $driver): void
    {
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }
}
