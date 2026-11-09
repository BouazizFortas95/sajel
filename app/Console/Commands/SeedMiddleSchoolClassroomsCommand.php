<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Group;
use App\Models\Level;
use App\Models\SchoolSetting;
use Illuminate\Console\Command;

class SeedMiddleSchoolClassroomsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sajel:seed-classrooms
                            {--year= : Academic year (default: current school setting)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the 20 official middle school classrooms for CEM 900 Mesken (7 for 1AM, 5 for 2AM, 4 for 3AM, 4 for 4AM)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $school = SchoolSetting::current();
        $year = $this->option('year') ?: ($school?->academic_year ?? '2026-2027');

        $specs = [
            1 => ['count' => 7, 'label' => 'السنة الأولى متوسط'],
            2 => ['count' => 5, 'label' => 'السنة الثانية متوسط'],
            3 => ['count' => 4, 'label' => 'السنة الثالثة متوسط'],
            4 => ['count' => 4, 'label' => 'السنة الرابعة متوسط'],
        ];

        $this->info("Setting up 20 official classrooms for [{$school?->school_name}] (Year: {$year})...");

        $createdCount = 0;
        $tableData = [];

        foreach ($specs as $gradeNumber => $spec) {
            $level = Level::where('grade_number', $gradeNumber)
                ->whereHas('educationPhase', fn ($q) => $q->where('phase_abbreviation', 'م'))
                ->first();

            if (! $level) {
                $level = Level::where('name', 'like', "%{$spec['label']}%")->first();
            }

            if (! $level) {
                $this->error("Level for {$spec['label']} (grade {$gradeNumber}) not found!");

                continue;
            }

            for ($classNumber = 1; $classNumber <= $spec['count']; $classNumber++) {
                $classroom = Classroom::firstOrNew([
                    'level_id' => $level->id,
                    'class_number' => $classNumber,
                    'academic_year' => $year,
                ]);

                $classroom->name = "{$gradeNumber} م {$classNumber}";
                $classroom->save();

                Group::firstOrCreate([
                    'classroom_id' => $classroom->id,
                    'name' => 'الفوج أ',
                ]);

                Group::firstOrCreate([
                    'classroom_id' => $classroom->id,
                    'name' => 'الفوج ب',
                ]);

                $createdCount++;
                $tableData[] = [
                    $classroom->id,
                    $classroom->name,
                    $level->name,
                    $classroom->class_number,
                    $year,
                    'الفوج أ + الفوج ب',
                ];
            }
        }

        $this->table(
            ['ID', 'Classroom Name', 'Level', 'Class #', 'Academic Year', 'Subgroups'],
            $tableData
        );

        $this->info("Successfully established {$createdCount} official classrooms in the database.");

        return self::SUCCESS;
    }
}
