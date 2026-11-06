<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');
        $mathSubject = Subject::where('name', 'الرياضيات')->first();
        $arabicSubject = Subject::where('name', 'اللغة العربية')->first();

        $profiles = [
            [
                'email' => 'dev@sajel.test',
                'name' => 'مهندس المنصة (المطور)',
                'role' => Role::Developer,
                'weekly_hours_limit' => 0,
            ],
            [
                'email' => 'principal@sajel.test',
                'name' => 'الأستاذ عبد الحميد بن عمار (مدير المؤسسة)',
                'role' => Role::Principal,
                'weekly_hours_limit' => 0,
            ],
            [
                'email' => 'censor@sajel.test',
                'name' => 'الأستاذ البشير الإبراهيمي (ناظر الدروس)',
                'role' => Role::Censor,
                'weekly_hours_limit' => 0,
            ],
            [
                'email' => 'supervisor@sajel.test',
                'name' => 'مولود قاسم نايت بلقاسم (مستشار التربية)',
                'role' => Role::GeneralSupervisor,
                'weekly_hours_limit' => 0,
            ],
            [
                'email' => 'assistant@sajel.test',
                'name' => 'عمر راسم (مشرف التربية)',
                'role' => Role::EducationSupervisor,
                'weekly_hours_limit' => 0,
            ],
            [
                'email' => 'counselor@sajel.test',
                'name' => 'فاطمة نسومر (مستشارة التوجيه والإرشاد)',
                'role' => Role::Counselor,
                'weekly_hours_limit' => 0,
            ],
            [
                'email' => 'bursar@sajel.test',
                'name' => 'محمد راسم (المقتصد / مسير مالي)',
                'role' => Role::FinanceOfficer,
                'weekly_hours_limit' => 0,
            ],
            [
                'email' => 'headteacher@sajel.test',
                'name' => 'الأستاذ رابح درياسة (أستاذ رئيسي ومنسق الرياضيات)',
                'role' => Role::HeadTeacher,
                'primary_subject_id' => $mathSubject?->id,
                'weekly_hours_limit' => 18,
            ],
        ];

        foreach ($profiles as $profile) {
            User::updateOrCreate(
                ['email' => $profile['email']],
                array_merge([
                    'password' => $password,
                    'email_verified_at' => now(),
                ], $profile)
            );
        }
    }
}

