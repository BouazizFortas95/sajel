<?php

namespace App\Services;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolBackupService
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
    ];

    /**
     * @return list<string>
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function streamDownload(bool $full = true): StreamedResponse
    {
        $filename = 'sajel-backup-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($full) {
            $data = $this->snapshot($full);
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function snapshot(bool $includeTables = false): array
    {
        $meta = [
            'generated_at' => now()->toIso8601String(),
            'version' => '1.0',
            'school_setting' => SchoolSetting::current()?->toArray() ?? null,
        ];

        $counts = [];
        foreach ($this->tables as $table) {
            try {
                $counts[$table] = DB::table($table)->count();
            } catch (\Exception $e) {
                $counts[$table] = 0;
            }
        }

        $institutional = SchoolSetting::current()?->toArray() ?? [];

        // Remove sensitive fields from institutional snapshot if present
        unset($institutional['password'], $institutional['remember_token'], $institutional['api_token']);

        $payload = [
            'metadata' => array_merge($meta, ['table_row_counts' => $counts]),
            'institutional_config' => $institutional,
        ];

        if ($includeTables) {
            $tablesData = [];
            foreach ($this->tables as $table) {
                try {
                    if ($table === 'users') {
                        $tablesData[$table] = DB::table('users')->get()->map(function ($row) {
                            $arr = (array) $row;
                            unset($arr['password'], $arr['remember_token']);

                            return $arr;
                        })->toArray();
                    } else {
                        $tablesData[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->toArray();
                    }
                } catch (\Exception $e) {
                    $tablesData[$table] = [];
                }
            }

            $payload['tables'] = $tablesData;
        }

        return $payload;
    }

    public function fullSnapshot(): array
    {
        return $this->snapshot(true);
    }
}
