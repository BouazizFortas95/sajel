<?php

namespace Database\Seeders;

use App\Enums\DailyLogStatus;
use App\Enums\DayOfWeek;
use App\Enums\Role;
use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Group;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class SajelSeeder extends Seeder
{
    /**
     * Seed a complete demo structure: one teacher, a middle school phase,
     * levels, classrooms, groups, subjects, a weekly schedule and one
     * planned daily log ready to be confirmed.
     */
    public function run(): void
    {
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@sajel.test'],
            ['name' => 'أستاذ تجريبي', 'password' => 'password'],
        );

        User::firstOrCreate(
            ['email' => 'admin@sajel.test'],
            [
                'name' => 'مدير المنصة',
                'password' => 'password',
                'role' => Role::Admin->value,
            ],
        );

        $middleSchool = EducationPhase::updateOrCreate(
            ['code' => 'middle'],
            [
                'name' => 'المرحلة المتوسطة',
                'phase_abbreviation' => 'م',
                'settings' => [
                    'period_minutes' => 50,
                    'default_status' => DailyLogStatus::Planned->value,
                ],
            ],
        );

        $firstYear = Level::updateOrCreate(
            ['education_phase_id' => $middleSchool->id, 'name' => 'السنة الأولى متوسطة'],
            ['grade_number' => 1],
        );
        $secondYear = Level::updateOrCreate(
            ['education_phase_id' => $middleSchool->id, 'name' => 'السنة الثانية متوسطة'],
            ['grade_number' => 2],
        );

        // Normalize classrooms created before the naming convention existed,
        // so re-running the seeder updates them instead of creating duplicates.
        $legacyClassrooms = [
            ['level' => $firstYear, 'name' => '1م - أ', 'number' => 1],
            ['level' => $secondYear, 'name' => '2م - ب', 'number' => 2],
        ];

        foreach ($legacyClassrooms as $legacy) {
            Classroom::query()
                ->whereNull('class_number')
                ->where('level_id', $legacy['level']->id)
                ->where('name', $legacy['name'])
                ->first()
                ?->update(['class_number' => $legacy['number']]);
        }

        $classroom = Classroom::updateOrCreate(
            [
                'level_id' => $firstYear->id,
                'class_number' => 1,
                'academic_year' => '2025-2026',
            ],
        );
        Classroom::updateOrCreate(
            [
                'level_id' => $secondYear->id,
                'class_number' => 2,
                'academic_year' => '2025-2026',
            ],
        );

        $groupA = Group::firstOrCreate(['classroom_id' => $classroom->id, 'name' => 'المجموعة أ']);
        Group::firstOrCreate(['classroom_id' => $classroom->id, 'name' => 'المجموعة ب']);

        $math = Subject::firstOrCreate(['name' => 'الرياضيات'], ['color' => '#4f46e5']);
        Subject::firstOrCreate(['name' => 'اللغة العربية'], ['color' => '#059669']);
        Subject::firstOrCreate(['name' => 'اللغة الفرنسية'], ['color' => '#d97706']);
        $physics = Subject::firstOrCreate(['name' => 'الفيزياء'], ['color' => '#0891b2']);

        $teacher->update([
            'role' => Role::Teacher->value,
            'primary_subject_id' => $math->id,
            'secondary_subject_id' => $physics->id,
            'weekly_hours_limit' => 22,
        ]);

        $schedule = Schedule::firstOrCreate(
            [
                'user_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'group_id' => $groupA->id,
                'subject_id' => $math->id,
                'day_of_week' => DayOfWeek::Sunday->value,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ],
        );

        Schedule::firstOrCreate(
            [
                'user_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'group_id' => null,
                'subject_id' => $physics->id,
                'day_of_week' => DayOfWeek::Monday->value,
                'start_time' => '10:00',
                'end_time' => '11:00',
            ],
        );

        $schedule->dailyLogs()->firstOrCreate(
            ['date' => now()->toDateString()],
            [
                'title' => 'درس: جمع الكسور المختلفة المقامات',
                'content_data' => [
                    [
                        'type' => 'warmup',
                        'data' => [
                            'description' => 'تمرين سريع حول المضاعفات المشتركة لتهيئة التلاميذ.',
                            'duration' => 5,
                        ],
                    ],
                    [
                        'type' => 'middle_school_lesson',
                        'data' => [
                            'lesson_title' => 'جمع وطرح الكسور',
                            'competence' => 'يحسب التلميذ مجموعات وفروق الكسور في سياق حياتي',
                            'pedagogical_situation' => 'تحضير وجبة فطور جماعية',
                            'phases' => [
                                [
                                    'type' => 'warmup',
                                    'duration' => 5,
                                    'description' => 'وضعية الانطلاق: كيف نجمع كسرين مختلفي المقامات؟',
                                    'strategy' => 'الحوار',
                                ],
                                [
                                    'type' => 'discovery',
                                    'duration' => 20,
                                    'description' => 'استكشاف طرق جمع الكسور المختلفة المقامات بالوسائل المحسوسة.',
                                    'strategy' => 'العمل في أفواج',
                                ],
                                [
                                    'type' => 'institutionalization',
                                    'duration' => 15,
                                    'description' => 'ترسيخ قاعدة جمع الكسور المختلفة المقامات.',
                                    'strategy' => 'العرض التوضيحي',
                                ],
                                [
                                    'type' => 'evaluation',
                                    'duration' => 10,
                                    'description' => 'تمارين إدماجية من دفتر النشاط.',
                                    'strategy' => 'فردي',
                                ],
                            ],
                        ],
                    ],
                ],
                'homework' => 'حل التمارين 3 و 5 ص 42 من الكتاب المدرسي.',
                'status' => DailyLogStatus::Planned->value,
            ],
        );
    }
}
