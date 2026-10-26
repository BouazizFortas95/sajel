<?php

namespace App\Services;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolBackupService
{
    protected array $tables = [
        'active_learning_strategies', 'assessments', 'attendance', 'behavior_observations',
        'classrooms', 'curriculum_domains', 'curriculum_resources', 'curriculum_sequences',
        'daily_logs', 'education_phases', 'groups', 'grades', 'labs', 'lab_seatings',
        'levels', 'pedagogical_memos', 'pedagogical_remediations', 'schedule', 'schools',
        'school_settings', 'students', 'subjects', 'teachers', 'users',
    ];

    public function streamDownload(): StreamedResponse
    {
        $filename = 'sajel-backup-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () {
            $data = $this->snapshot();
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function snapshot(): array
    {
        $meta = [
            'generated_at' => now()->toIso8601String(),
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

        return [
            'metadata' => array_merge($meta, ['table_row_counts' => $counts]),
            'institutional_config' => $institutional,
        ];
    }
}
