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
        'pedagogical_plan_receipts',
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
        'canteen_food_items',
        'canteen_daily_sheets',
        'canteen_consumption_entries',
        'canteen_weekly_menus',
        'staff_evaluations',
        'career_advancements',
        'staff_movements',
        'staff_installations',
        'administrative_handovers',
        'official_mails',
        'class_council_sessions',
        'class_council_decisions',
        'subject_coordination_protocols',
        'curriculum_pacing_audits',
        'inventory_items',
        'inventory_allocations',
        'inventory_reforms',
        'inventory_recolements',
        'school_budget_lines',
        'budget_commitments',
        'payment_mandates',
        'official_exam_centers',
        'official_exam_rooms',
        'official_exam_supervisors',
        'official_exam_incidents',
        'school_solidarity_campaigns',
        'student_solidarity_beneficiaries',
        'school_solidarity_committee_members',
        'school_solidarity_aid_distributions',
        'dormitory_rooms',
        'student_dormitory_allocations',
        'boarding_night_sessions',
        'boarding_exit_passes',
        'school_bus_routes',
        'student_bus_subscriptions',
        'safety_emergency_plans',
        'safety_intervention_teams',
        'school_safety_incidents',
        'safety_fire_equipment',
        'safety_evacuation_drills',
        'safety_committee_meetings',
        'school_board_sessions',
        'school_board_members',
        'school_projects',
        'school_internal_regulations',
        'parents_associations',
        'parents_association_meetings',
        'school_building_facilities',
        'maintenance_work_orders',
        'central_heating_logs',
        'water_tank_hygiene_inspections',
        'energy_utility_meters',
        'utility_consumption_records',
        'school_sports_associations',
        'sports_cultural_disciplines',
        'student_sports_licenses',
        'sports_competitions_events',
        'ascs_financial_transactions',
        'sports_equipment_inventories',
        'school_cooperatives',
        'cooperative_projects_initiatives',
        'cooperative_financial_ledger',
        'pedagogical_garden_plots',
        'botanical_plants_inventory',
        'eco_green_club_actions',
        'platform_settings',
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
