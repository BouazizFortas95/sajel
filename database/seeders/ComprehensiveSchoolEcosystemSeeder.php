<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\EducationPhase;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprehensiveSchoolEcosystemSeeder extends Seeder
{
    /**
     * Seed all operational middle school institutional ecosystem tables with
     * realistic, authentic Algerian educational data.
     */
    public function run(): void
    {
        $today = Carbon::today();
        $academicYear = '2025/2026';

        // Retrieve existing foundational entities
        $school = SchoolSetting::first();
        $principal = User::where('email', 'principal@sajel.test')->first() ?? User::first();
        $bursar = User::where('email', 'bursar@sajel.test')->first() ?? User::skip(1)->first() ?? $principal;
        $counselor = User::where('email', 'counselor@sajel.test')->first() ?? User::skip(2)->first() ?? $principal;
        $supervisor = User::where('email', 'supervisor@sajel.test')->first() ?? User::skip(3)->first() ?? $principal;
        $teachers = User::where('role', 'Teacher')->get();
        if ($teachers->isEmpty()) {
            $teachers = User::all();
        }

        $middlePhase = EducationPhase::where('code', 'middle')->first();
        $levels = Level::where('education_phase_id', $middlePhase?->id)->orderBy('order_index')->get();
        if ($levels->isEmpty()) {
            $levels = Level::all();
        }
        $classrooms = Classroom::all();
        $subjects = Subject::all();
        $students = Student::all();

        // 1. Platform Settings (Developer / Super Admin Customization)
        if (DB::getSchemaBuilder()->hasTable('platform_settings')) {
            DB::table('platform_settings')->updateOrInsert(
                ['id' => 1],
                [
                    'platform_name' => 'منظومة سَجِّل — الإدارة الرقمية للمتوسطات الجزائرية',
                    'platform_slogan' => 'المنصة الوطنية المتكاملة لتسيير الشؤون البيداغوجية، الإدارية، والمالية لقطاع التربية الوطنية',
                    'meta_description' => 'منظومة رقمية جزائرية متطورة لإدارة وتسيير المؤسسات التربوية وفق المعايير الوزارية الرسمية',
                    'developer_name' => 'فريق التطوير والرقمنة التربوية بالجزائر',
                    'developer_organization' => 'سَجِّل للحلول البرمجية والتربوية الجزائرية',
                    'developer_website' => 'https://sajel.dz',
                    'developer_support_email' => 'dev@sajel.dz',
                    'developer_support_phone' => '+213 29 12 34 56',
                    'version_tag' => 'v3.5.0-LTS',
                    'primary_color' => '#1b365d',
                    'footer_copyright_text' => 'جميع الحقوق محفوظة © لمنظومة سَجِّل الرقمية — وزارة التربية الوطنية — الجمهورية الجزائرية الديمقراطية الشعبية',
                    'is_maintenance_mode' => false,
                    'enable_public_registration' => false,
                    'enable_ai_assistant' => true,
                    'ai_default_model' => 'gemini-2.0-flash',
                    'max_file_upload_mb' => 25,
                    'session_lifetime_minutes' => 120,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Textbooks & Student Textbook Loans
        DB::table('student_textbook_loans')->delete();
        DB::table('textbooks')->delete();

        $textbooksCatalog = [
            ['title' => 'كتاب اللغة العربية وآدابها — س1م', 'level_order' => 1, 'isbn' => '978-9961-0-1201-1', 'price' => 280.00, 'qty' => 160],
            ['title' => 'كتاب الرياضيات والأنشطة العددية والهندسية — س1م', 'level_order' => 1, 'isbn' => '978-9961-0-1202-8', 'price' => 310.00, 'qty' => 160],
            ['title' => 'كتاب العلوم الفيزيائية والتكنولوجيا — س1م', 'level_order' => 1, 'isbn' => '978-9961-0-1203-5', 'price' => 250.00, 'qty' => 160],
            ['title' => 'كتاب علوم الطبيعة والحياة — س2م', 'level_order' => 2, 'isbn' => '978-9961-0-1204-2', 'price' => 270.00, 'qty' => 150],
            ['title' => 'كتاب اللغة الإنجليزية — My English Book 2AM', 'level_order' => 2, 'isbn' => '978-9961-0-1205-9', 'price' => 240.00, 'qty' => 150],
            ['title' => 'كتاب التاريخ والجغرافيا والتربية المدنية — س3م', 'level_order' => 3, 'isbn' => '978-9961-0-1206-6', 'price' => 320.00, 'qty' => 150],
            ['title' => 'كتاب اللغة الفرنسية — Mon livre de français 3AM', 'level_order' => 3, 'isbn' => '978-9961-0-1207-3', 'price' => 290.00, 'qty' => 150],
            ['title' => 'كتاب الرياضيات والتحضير لشهادة التعليم المتوسط — س4م', 'level_order' => 4, 'isbn' => '978-9961-0-1208-0', 'price' => 350.00, 'qty' => 140],
            ['title' => 'كتاب اللغة العربية والتحضير للبيام BEM — س4م', 'level_order' => 4, 'isbn' => '978-9961-0-1209-7', 'price' => 320.00, 'qty' => 140],
        ];

        $insertedTextbooks = [];
        foreach ($textbooksCatalog as $item) {
            $level = $levels[$item['level_order'] - 1] ?? $levels->first();
            $subject = $subjects->first();

            $tid = DB::table('textbooks')->insertGetId([
                'level_id' => $level->id,
                'subject_id' => $subject->id,
                'title' => $item['title'],
                'isbn' => $item['isbn'],
                'unit_price' => $item['price'],
                'total_received' => $item['qty'],
                'storage_location' => 'مخزن الكتب المدرسية — الجناح ب',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $insertedTextbooks[] = $tid;
        }

        // Student textbook loans (Free grant & paid loans)
        foreach ($students->take(40) as $idx => $st) {
            $tbId = $insertedTextbooks[$idx % count($insertedTextbooks)];
            DB::table('student_textbook_loans')->insert([
                'student_id' => $st->id,
                'textbook_id' => $tbId,
                'academic_year' => $academicYear,
                'distribution_type' => ($idx % 3 === 0) ? 'free_grant' : 'loan',
                'distributed_at' => $today->copy()->subMonths(2)->format('Y-m-d'),
                'distributed_by_id' => $principal->id,
                'clearance_granted' => false,
                'returned_at' => null,
                'return_condition' => null,
                'notes' => ($idx % 3 === 0) ? 'مستفيد من المجانية (منحة التضامن المدرسي 5000 دج)' : 'استعارة مدرسية نظامية مدفوعة',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Teacher Absences & Inspection Reports
        DB::table('inspection_reports')->delete();
        DB::table('teacher_absences')->delete();

        $absReasons = [
            ['reason' => 'عطلة مرضية قصيرة المدى مدفوعة الأجر مؤشرة من الضمان الاجتماعي', 'code' => 'certified_medical', 'days' => 3],
            ['reason' => 'عطلة أمومة قانونية مدفوعة الأجر', 'code' => 'maternity_leave', 'days' => 15],
            ['reason' => 'غياب مبرر لظرف عائلي قاهر (زواج / وفاة أصول)', 'code' => 'exceptional_family', 'days' => 3],
            ['reason' => 'المشاركة في ندوة تربوية تكوينية لمفتشي المادة', 'code' => 'pedagogical_mission', 'days' => 1],
        ];

        foreach ($teachers->take(8) as $idx => $t) {
            $reasonData = $absReasons[$idx % count($absReasons)];
            $startDate = $today->copy()->subDays(rand(5, 45));
            $endDate = $startDate->copy()->addDays($reasonData['days']);

            DB::table('teacher_absences')->insert([
                'teacher_id' => $t->id,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'reason' => $reasonData['reason'],
                'status' => 'approved',
                'recorded_by_id' => $principal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inspection_reports')->insert([
                'teacher_id' => $t->id,
                'inspector_name' => 'السيد عبد القادر بن عيسى (مفتش التعليم المتوسط للمادة)',
                'subject_id' => $t->subject_id ?? $subjects->first()?->id ?? 1,
                'classroom_id' => $classrooms->first()?->id ?? 1,
                'visit_date' => $today->copy()->subDays(rand(10, 60)),
                'lesson_topic' => 'بناء التعلمات وإرساء الموارد في الوضعية المشكلة التعلمية',
                'pedagogical_score' => 17.50,
                'pedagogical_appreciation' => 'ممتاز',
                'strengths' => 'أداء تربوي متميز، تحكم تام في تقنيات التنشيط وإشراك التلاميذ، وإعداد دقيق لمذكرات الدروس وفق المقاربة بالكفاءات.',
                'areas_for_improvement' => 'مواصلة تفعيل استراتيجيات التعلم النشط وتوثيق أنشطة المعالجة البيداغوجية بالسجل المخصص.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Student Transfers & Disciplinary Records
        DB::table('disciplinary_records')->delete();
        DB::table('student_transfers')->delete();

        foreach ($students->take(10) as $idx => $st) {
            DB::table('student_transfers')->insert([
                'student_id' => $st->id,
                'transfer_type' => ($idx % 2 === 0) ? 'outgoing' : 'incoming',
                'academic_year' => $academicYear,
                'transfer_date' => $today->copy()->subDays(rand(5, 50)),
                'other_school_name' => ($idx % 2 === 0) ? 'متوسطة الإخوة الشهداء مسعودي — المغير' : 'متوسطة الشيخ البشير الإبراهيمي — قمار',
                'reference_number' => 'TRANS-2026-'.str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'reason' => 'change_of_residence',
                'status' => 'approved',
                'approved_by_id' => $principal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('disciplinary_records')->insert([
                'student_id' => $st->id,
                'academic_year' => $academicYear,
                'incident_date' => $today->copy()->subDays(rand(10, 40)),
                'session_date' => $today->copy()->subDays(rand(2, 9))->setTime(14, 0),
                'infraction_degree' => 'second_degree',
                'incident_description' => 'تكرار التأخر غير المبرر والإخلال بالهدوء أثناء فترة الاستراحة ومخالفة النظام الداخلي للمؤسسة.',
                'decision' => ($idx % 2 === 0) ? 'warning' : 'temporary_exclusion',
                'exclusion_days' => ($idx % 2 === 0) ? 0 : 2,
                'guardian_attended' => true,
                'guardian_notified_at' => $today->copy()->subDays(rand(2, 8)),
                'status' => 'deliberated',
                'president_id' => $principal->id,
                'reported_by_id' => $supervisor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Psychological & Educational Counseling
        DB::table('counseling_sessions')->delete();
        DB::table('student_counseling_profiles')->delete();

        foreach ($students->take(15) as $idx => $st) {
            $profId = DB::table('student_counseling_profiles')->insertGetId([
                'student_id' => $st->id,
                'family_status' => ($idx % 3 === 0) ? 'orphan' : 'together',
                'social_grant' => ($idx % 2 === 0),
                'learning_difficulties' => ($idx % 4 === 0) ? 'صعوبات خفيفة في استيعاب المفاهيم الهندسية والرياضية' : null,
                'interests_and_talents' => 'شغف بالرسم والمطالعة والأنشطة العلمية والمنافسات الرياضية',
                'counselor_general_notes' => 'تلميذ هادئ ومؤدب، يبدي رغبة حقيقية في تحسين نتائجه ومرافقة مستشار التوجيه.',
                'is_confidential' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('counseling_sessions')->insert([
                'student_id' => $st->id,
                'counselor_id' => $counselor->id,
                'academic_year' => $academicYear,
                'session_date' => $today->copy()->subDays(rand(3, 30)),
                'reason_category' => ($idx % 2 === 0) ? 'academic_remediation' : 'behavioral_orientation',
                'interviewee_type' => 'student',
                'observations' => 'جلسة إصغاء فردية لمناقشة أساليب تنظيم وقت المذاكرة والتغلب على قلق الامتحانات.',
                'action_plan' => 'وضع جدول للمراجعة المنزلية، تشجيع المشاركة الصيفية، وإشراك الولي في المتابعة الأسبوعية.',
                'follow_up_date' => $today->copy()->addDays(15),
                'status' => 'completed',
                'is_confidential' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 6. School Clubs & Extracurricular Activities
        DB::table('club_activities')->delete();
        DB::table('club_memberships')->delete();
        DB::table('school_clubs')->delete();

        $clubsCatalog = [
            ['name' => 'نادي العلوم والبيئة والروبوتات التعليمية', 'desc' => 'تطوير الفكر العلمي والتجارب البيئية والتطبيقية وبرمجة الروبوتات للمتوسط'],
            ['name' => 'نادي الفصاحة، الخط العربي والمطالعة الممتعة', 'desc' => 'صقل المهارات اللغوية، تذوق الأدب الجزائري، وتحفيز مسابقة تحدي القراءة العربي'],
            ['name' => 'نادي تكنولوجيا الإعلام والاتصال والذكاء الاصطناعي', 'desc' => 'مبادئ البرمجة للمبتدئين، السلامة الرقمية، والاستخدام الآمن للإنترنت'],
            ['name' => 'نادي التاريخ العريق والذاكرة الوطنية', 'desc' => 'إحياء المناسبات والأعياد الوطنية، إعداد مجلات جدارية، وزيارة المتاحف التاريخية'],
        ];

        foreach ($clubsCatalog as $idx => $cc) {
            $leadTeacher = $teachers[$idx % $teachers->count()];
            $clubId = DB::table('school_clubs')->insertGetId([
                'name' => $cc['name'],
                'description' => $cc['desc'],
                'lead_teacher_id' => $leadTeacher->id,
                'academic_year' => $academicYear,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($students->slice($idx * 6, 6) as $st) {
                DB::table('club_memberships')->insert([
                    'school_club_id' => $clubId,
                    'student_id' => $st->id,
                    'role' => 'عضو نشط',
                    'joined_at' => $today->copy()->subMonths(3),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('club_activities')->insert([
                'school_club_id' => $clubId,
                'title' => 'معرض الإبداع السنوي المفتوح وورشة العمل التطبيقية الأولى',
                'summary' => 'تنظيم معرض لعرض أعمال التلاميذ وإنجازاتهم المتميزة أمام زملائهم وأوليائهم.',
                'activity_date' => $today->copy()->subDays(12),
                'location' => 'المكتبة المدرسية وقاعة النشاطات متعددة الوسائط',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 7. School Health (UDS)
        DB::table('student_medical_records')->delete();
        foreach ($students->take(20) as $idx => $st) {
            DB::table('student_medical_records')->insert([
                'student_id' => $st->id,
                'academic_year' => $academicYear,
                'screening_date' => $today->copy()->subMonths(2),
                'doctor_name' => 'د. بلقاسم المنور (طبيب الصحة المدرسية)',
                'uds_name' => 'وحدة الكشف والمتابعة الصحية بالوادي',
                'visual_acuity_right' => 10,
                'visual_acuity_left' => 9,
                'hearing_condition' => 'normal',
                'weight_kg' => rand(38, 55),
                'height_cm' => rand(145, 170),
                'blood_type' => ['A+', 'B+', 'O+', 'AB+'][$idx % 4],
                'has_chronic_illness' => ($idx % 5 === 0),
                'chronic_condition_type' => ($idx % 5 === 0) ? 'asthma' : null,
                'chronic_illness_details' => ($idx % 5 === 0) ? 'حساسية موسمية وربو خفيف' : null,
                'eps_dispensation_status' => 'none',
                'general_clinical_notes' => 'فحص روتيني شامل من طبيب وحدة الكشف والمتابعة (UDS)، الحالة الصحية العامة ممتازة.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 8. Pedagogical Councils & Seminars
        DB::table('pedagogical_seminar_attendances')->delete();
        DB::table('pedagogical_seminars')->delete();
        DB::table('pedagogical_council_attendances')->delete();
        DB::table('pedagogical_councils')->delete();

        $councils = [
            ['title' => 'مجلس التنسيق التربوي والتعليمي للدخول المدرسي 2025/2026', 'date' => $today->copy()->subMonths(4)],
            ['title' => 'مجلس التعليم والتقويم البيداغوجي لنتائج الفصل الأول', 'date' => $today->copy()->subDays(45)],
        ];

        foreach ($councils as $c) {
            $councilId = DB::table('pedagogical_councils')->insertGetId([
                'academic_year' => $academicYear,
                'title' => $c['title'],
                'held_at' => $c['date'],
                'coordinator_id' => $principal->id,
                'agenda' => 'تحليل التوزيع السنوي للتعلمات، التنسيق الأفقي والعمودي للمناهج، وخطة الدعم الاستدراكي.',
                'recommendations' => 'إلزامية استعمال دفتر النصوص الرقمي والتقيد بالتوزيع الزمني الرسمي المعتمد وتفعيل الحصص المعملية.',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($teachers->take(6) as $t) {
                DB::table('pedagogical_council_attendances')->insert([
                    'pedagogical_council_id' => $councilId,
                    'user_id' => $t->id,
                    'is_present' => true,
                    'role_in_council' => 'أستاذ المادة وعضو المجلس',
                    'notes' => 'مشاركة فعالة ومداخلات بيداغوجية بناءة في إثراء النقاش.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $seminarId = DB::table('pedagogical_seminars')->insertGetId([
            'academic_year' => $academicYear,
            'subject_id' => 1,
            'title' => 'الندوة التربوية الولائية لتطوير ممارسات تقويم الكفاءات وفق المقاربة النصية',
            'inspector_name' => 'السيد عبد القادر بن عيسى (مفتش التعليم المتوسط للمقاطعة الأولى)',
            'host_school' => 'متوسطة الشهيد العربي بن مهيدي',
            'scheduled_at' => $today->copy()->subDays(25),
            'objectives' => 'توحيد معايير ومؤشرات تصحيح الوضعيات الإدماجية والتحضير الجيد لامتحان شهادة التعليم المتوسط.',
            'recommendations_summary' => 'عرض نماذج تطبيقية لشبكات التقويم، ورشات عمل مصغرة، واستخلاص توصيات عملية لتحسين التحصيل.',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($teachers->take(8) as $t) {
            DB::table('pedagogical_seminar_attendances')->insert([
                'pedagogical_seminar_id' => $seminarId,
                'user_id' => $t->id,
                'attended' => true,
                'certificate_ref' => 'CERT-SEM-2026-'.rand(100, 999),
                'notes' => 'حضور متميز واستلام شهادة المشاركة المعتمدة من مفتشية التعليم المتوسط.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 9. Class Councils, Deliberations & Curriculum Pacing
        DB::table('class_council_decisions')->delete();
        DB::table('class_council_sessions')->delete();
        DB::table('subject_coordination_protocols')->delete();
        DB::table('curriculum_pacing_audits')->delete();

        foreach ($classrooms->take(6) as $cls) {
            $sessionId = DB::table('class_council_sessions')->insertGetId([
                'classroom_id' => $cls->id,
                'trimester' => 1,
                'academic_year' => $academicYear,
                'session_date' => $today->copy()->subDays(35),
                'president_id' => $principal->id,
                'main_teacher_id' => $teachers->first()?->id,
                'counselor_id' => $counselor->id,
                'supervisor_id' => $supervisor->id,
                'general_appreciation' => 'فصل إيجابي ومجهودات ملحوظة للتلاميذ، مع ضرورة الحفاظ على الهدوء والمواظبة.',
                'class_average' => 12.85,
                'highest_average' => 17.65,
                'lowest_average' => 8.40,
                'success_rate' => 78.50,
                'total_students' => 30,
                'status' => 'approved',
                'recorded_by' => $principal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Retrieve students in this classroom via groups
            $classStudentIds = DB::table('students')
                ->join('groups', 'students.group_id', '=', 'groups.id')
                ->where('groups.classroom_id', $cls->id)
                ->pluck('students.id');

            if ($classStudentIds->isEmpty()) {
                $classStudentIds = $students->slice(0, 5)->pluck('id');
            }

            foreach ($classStudentIds->take(5) as $idx => $stId) {
                DB::table('class_council_decisions')->insert([
                    'class_council_session_id' => $sessionId,
                    'student_id' => $stId,
                    'trimester_average' => 14.50 + ($idx * 0.5),
                    'rank' => $idx + 1,
                    'honors' => ($idx === 0) ? 'تهنئة وتشجيع' : 'لوحة شرف',
                    'remarks' => 'تلميذ مجد ومنضبط ومشارك بفعالية في الحصص التعليمية.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('curriculum_pacing_audits')->insert([
                'academic_year' => $academicYear,
                'trimester' => 1,
                'audit_date' => $today->copy()->subDays(20),
                'classroom_id' => $cls->id,
                'subject_id' => 1,
                'teacher_id' => $teachers->first()?->id,
                'expected_resources_count' => 24,
                'completed_resources_count' => 23,
                'execution_percentage' => 95.83,
                'pacing_status' => 'on_track',
                'audited_by' => $principal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('subject_coordination_protocols')->insert([
            'academic_year' => $academicYear,
            'subject_id' => 1,
            'trimester' => 1,
            'meeting_date' => $today->copy()->subDays(40),
            'coordinator_id' => $teachers->first()?->id ?? $principal->id,
            'topic' => 'تنسيق الفروض المحروسة والامتحانات الفصلية وتوحيد سلالم التنقيط',
            'syllabus_pacing_notes' => 'تطابق تام في تدرج الدروس بين كل الأساتذة دون تسجيل أي تأخر بيداغوجي.',
            'unified_exams_notes' => 'الاتفاق على صياغة مواضيع اختبارات موحدة وفق الدليل التوجيهي الوزاري.',
            'recommendations' => 'إيلاء العناية الكافية بالوضعيات الإدماجية ومعالجة الأخطاء الشائعة.',
            'recorded_by' => $principal->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 10. School Canteen & Nutrition
        DB::table('canteen_consumption_entries')->delete();
        DB::table('canteen_daily_sheets')->delete();
        DB::table('canteen_weekly_menus')->delete();
        DB::table('canteen_food_items')->delete();

        $canteenItems = [
            ['name' => 'سميد ممتاز للخبز والكسكسي', 'cat' => 'حبوب ومشتقاتها', 'unit' => 'kg', 'price' => 85.00, 'qty' => 300, 'safe' => 40],
            ['name' => 'أرز أبيض مفور ممتاز', 'cat' => 'حبوب ومشتقاتها', 'unit' => 'kg', 'price' => 140.00, 'qty' => 150, 'safe' => 30],
            ['name' => 'عدس يابس ممتاز', 'cat' => 'بقوليات جافة', 'unit' => 'kg', 'price' => 160.00, 'qty' => 100, 'safe' => 20],
            ['name' => 'دجاج محلي طازج', 'cat' => 'لحوم ودواجن', 'unit' => 'kg', 'price' => 390.00, 'qty' => 180, 'safe' => 25],
            ['name' => 'زيت المائدة نباتي صافي', 'cat' => 'زيوت ودهون', 'unit' => 'L', 'price' => 125.00, 'qty' => 120, 'safe' => 25],
            ['name' => 'طماطم مصبرة معلبة', 'cat' => 'معلبات ومصبرات', 'unit' => 'kg', 'price' => 210.00, 'qty' => 60, 'safe' => 15],
            ['name' => 'جبن مثلثات غذائي معقم', 'cat' => 'مشتقات الحليب والأجبان', 'unit' => 'قطعة', 'price' => 15.00, 'qty' => 1500, 'safe' => 200],
            ['name' => 'ياغورت ممزوج بالفواكه', 'cat' => 'مشتقات الحليب والأجبان', 'unit' => 'علبة', 'price' => 25.00, 'qty' => 1000, 'safe' => 150],
        ];

        $canteenItemIds = [];
        foreach ($canteenItems as $ci) {
            $id = DB::table('canteen_food_items')->insertGetId([
                'name' => $ci['name'],
                'category' => $ci['cat'],
                'unit' => $ci['unit'],
                'unit_price' => $ci['price'],
                'current_stock' => $ci['qty'],
                'safety_threshold' => $ci['safe'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $canteenItemIds[] = $id;
        }

        DB::table('canteen_weekly_menus')->insert([
            'academic_year' => $academicYear,
            'week_number' => 10,
            'week_start_date' => $today->copy()->startOfWeek(),
            'sunday_menu' => 'طاجين زيتون مع لحم الدجاج + أرز بالخضر + خبز + فاكهة البرتقال',
            'monday_menu' => 'شربة فريك جزائرية + كفتة لحم بصلصة الطماطم + جبن + خبز',
            'tuesday_menu' => 'كسكسي جزائري بمرق الخضر الطازجة ولحم الدجاج + علبة ياغورت',
            'wednesday_menu' => 'عدس مطبوخ بزيت الزيتون + سلطة طماطم وخيار + بيض مسلوق + فاكهة',
            'thursday_menu' => 'غراتان بطاطا ودجاج بالجبن + حساء خضر طازجة + ياغورت',
            'nutrition_calories_approx' => 750,
            'is_approved_by_doctor' => true,
            'approved_by_principal' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($d = 1; $d <= 4; $d++) {
            $sheetId = DB::table('canteen_daily_sheets')->insertGetId([
                'academic_year' => $academicYear,
                'sheet_date' => $today->copy()->subDays($d),
                'reference_number' => 'CAN-'.$academicYear.'-0'.$d,
                'half_board_students_present' => 180,
                'boarder_students_present' => 0,
                'staff_beneficiaries_count' => 6,
                'service_workers_count' => 4,
                'total_beneficiaries_count' => 190,
                'menu_description' => 'وجبة غذائية صحية متكاملة وساخنة مطابقة لبرنامج التغذية المدرسية الوزاري.',
                'total_cost' => 22800.00,
                'cost_per_meal' => 120.00,
                'storekeeper_name' => 'عمار بن زايد (مسؤول المخزن الغذائي)',
                'kitchen_hygiene_status' => 'excellent',
                'status' => 'certified',
                'certified_at' => $today->copy()->subDays($d)->setTime(14, 0),
                'certified_by_id' => $bursar->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('canteen_consumption_entries')->insert([
                'canteen_daily_sheet_id' => $sheetId,
                'canteen_food_item_id' => $canteenItemIds[$d % count($canteenItemIds)],
                'quantity_consumed' => 35.0,
                'unit_price' => 140.00,
                'total_item_cost' => 4900.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 11. Staff Career, Evaluations & Movements
        DB::table('staff_movements')->delete();
        DB::table('career_advancements')->delete();
        DB::table('staff_evaluations')->delete();

        foreach ($teachers->take(6) as $idx => $t) {
            DB::table('staff_evaluations')->insert([
                'user_id' => $t->id,
                'academic_year' => $academicYear,
                'evaluation_year' => 2026,
                'administrative_score' => 18.5,
                'punctuality_score' => 4.5,
                'performance_score' => 5.0,
                'conduct_score' => 4.5,
                'initiative_score' => 4.5,
                'pedagogical_score' => 17.0,
                'appreciation' => 'أستاذ كفء ومنضبط، يؤدي مهامه بإتقان وإخلاص، نتائجه ممتازة.',
                'evaluator_id' => $principal->id,
                'evaluated_at' => $today->copy()->subDays(20),
                'staff_signature_status' => 'signed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stepBaseDate = $today->copy()->subYears(3);
            DB::table('career_advancements')->insert([
                'user_id' => $t->id,
                'current_step' => 4 + $idx,
                'next_step' => 5 + $idx,
                'current_step_date' => $stepBaseDate,
                'eligible_at_minimum' => $stepBaseDate->copy()->addMonths(30),
                'eligible_at_medium' => $stepBaseDate->copy()->addYears(3),
                'eligible_at_maximum' => $stepBaseDate->copy()->addMonths(42),
                'advancement_rhythm' => ($idx % 2 === 0) ? 'minimum' : 'medium',
                'effective_date' => $today->copy()->subMonths(2),
                'financial_impact_date' => $today->copy()->subMonths(2),
                'status' => 'approved_by_cap',
                'commission_reference' => 'CAP-2026-'.rand(1000, 9999),
                'notes' => 'مؤشر ومصادق عليه من قبل اللجنة الإدارية المتساوية الأعضاء المختصة بأسلاك التعليم.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('staff_movements')->insert([
                'user_id' => $t->id,
                'movement_type' => 'transfer',
                'status' => 'approved',
                'effective_date' => $today->copy()->addMonths(6),
                'decision_number' => 'DEC-MUT-2026-'.str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                'decision_date' => $today->copy()->subMonths(1),
                'source_institution' => $school->school_name ?? 'متوسطة الشهيد العربي بن مهيدي',
                'target_institution' => 'متوسطة أول نوفمبر 1954 — الوادي',
                'reason' => 'تقريب مقر العمل من الإقامة العائلية بناءً على الرغبة والمجموع النقطي بالحركة الولائية',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 12. Administrative Archive
        DB::table('official_mails')->delete();
        DB::table('administrative_handovers')->delete();
        DB::table('staff_installations')->delete();

        foreach ($teachers->take(6) as $idx => $t) {
            DB::table('staff_installations')->insert([
                'user_id' => $t->id,
                'installation_date' => $today->copy()->subMonths(5),
                'recruitment_order_ref' => '424'.str_pad($idx + 10, 3, '0', STR_PAD_LEFT),
                'role_label' => 'أستاذ',
                'statutory_grade' => '13',
                'previous_institution' => 'أول تعيين',
                'ministry_decision_ref' => '6422'.str_pad($idx + 10, 3, '0', STR_PAD_LEFT),
                'notes' => 'تنصيب قانوني بموجب محضر تنصيب رسمي مؤشر وفق التنظيم المعمول به.',
                'recorded_by' => $principal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('administrative_handovers')->insert([
            'role' => 'المقتصد / مسير مالي',
            'handover_date' => $today->copy()->subMonths(3),
            'outgoing_user_id' => $principal->id,
            'incoming_user_id' => $bursar->id,
            'outgoing_name' => $principal->name,
            'incoming_name' => $bursar->name,
            'inventory_summary' => 'تم تسليم كافة دفاتر المحاسبة وسجلات الجرد والصندوق البريدي دون أي تحفظات.',
            'inventory_ref' => 'INV-HAND-2026-01',
            'pending_dossiers' => 'ملفات منحة التضامن المدرسي 5000 دج قيد التدقيق النهائي.',
            'remarks' => 'تمت العملية في ظروف نظامية ملائمة وفق القوانين السارية.',
            'outgoing_signed' => true,
            'incoming_signed' => true,
            'director_signed' => true,
            'recorded_by' => $principal->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($m = 1; $m <= 12; $m++) {
            $isIncoming = ($m % 2 === 0);
            DB::table('official_mails')->insert([
                'mail_number' => ($isIncoming ? 'IN-' : 'OUT-').$academicYear.'-'.str_pad($m, 4, '0', STR_PAD_LEFT),
                'mail_type' => $isIncoming ? 'incoming' : 'outgoing',
                'mail_date' => $today->copy()->subDays(rand(2, 60)),
                'sender_name' => $isIncoming ? 'مديرية التربية لولاية الوادي — مصلحة التمدرس والامتحانات' : ($school->school_name ?? 'متوسطة العربي بن مهيدي'),
                'recipient_name' => $isIncoming ? ($school->school_name ?? 'متوسطة العربي بن مهيدي') : 'مديرية التربية لولاية الوادي — مصلحة البرمجة والمتابعة',
                'subject' => $isIncoming ? 'إرسال تعليمات تنظيم امتحانات شهادة التعليم المتوسط دورة جوان 2026' : 'إرسال الحصيلة الشهرية لبطاقة تموين المطعم المدرسي ونفقات التسيير',
                'reference_number' => 'REF-MEN-'.rand(1000, 9999),
                'confidentiality' => ($m === 5) ? 'confidential' : 'normal',
                'status' => 'processed',
                'recorded_by' => $principal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 13. Inventory, Allocations & Recolement
        DB::table('inventory_reforms')->delete();
        DB::table('inventory_recolements')->delete();
        DB::table('inventory_allocations')->delete();
        DB::table('inventory_items')->delete();

        $inventoryCatalog = [
            ['name' => 'مكتب إداري خشبي مع أدراج ومفتاح أمان', 'cat' => 'أثاث مكتبي', 'qty' => 12, 'loc' => 'الإدارة العامة والمكاتب', 'code' => 'INV-BUR-01'],
            ['name' => 'حاسوب مكتبي متكامل HP Core i5 شاشة 22 بوصة', 'cat' => 'تجهيزات رقمية وإعلام آلي', 'qty' => 18, 'loc' => 'قاعة الإعلام الآلي والمخبر', 'code' => 'INV-IT-02'],
            ['name' => 'طابعة ليزرية متعددة الوظائف Canon مع ماسح ضوئي', 'cat' => 'تجهيزات مكتبية', 'qty' => 4, 'loc' => 'أمانة المديرية والمقتصدية', 'code' => 'INV-PRT-03'],
            ['name' => 'مجهر بصري ضوئي مزدوج العدسة للتجارب المخبرية', 'cat' => 'عتاد علمي ومخبري', 'qty' => 15, 'loc' => 'مخبر علوم الطبيعة والحياة', 'code' => 'INV-LAB-04'],
            ['name' => 'جهاز عرض البيانات DataShow مع حامل سقفي وشاشة عرض', 'cat' => 'وسائل تعليمية بيداغوجية', 'qty' => 6, 'loc' => 'قاعة العروض والمدرج', 'code' => 'INV-DS-05'],
            ['name' => 'طاولة تجارب مخبرية مقاومة للأحماض مع حوض ماء وصنبور', 'cat' => 'أثاث مخبري', 'qty' => 10, 'loc' => 'مخبر العلوم الفيزيائية', 'code' => 'INV-LAB-06'],
            ['name' => 'سبورة بيضاء مغناطيسية للأقسام قياس 300 × 120 سم', 'cat' => 'تجهيز قاعات التدريس', 'qty' => 24, 'loc' => 'قاعات التدريس من 01 إلى 20', 'code' => 'INV-CLS-07'],
            ['name' => 'خزانة حديدية لحفظ الأرشيف والملفات بقفل أمان', 'cat' => 'أثاث حفظ وأرشيف', 'qty' => 8, 'loc' => 'قاعة الأرشيف والأمانة العامة', 'code' => 'INV-ARC-08'],
        ];

        foreach ($inventoryCatalog as $item) {
            $itemId = DB::table('inventory_items')->insertGetId([
                'inventory_number' => $item['code'],
                'category' => $item['cat'],
                'designation' => $item['name'],
                'specifications' => 'مطابق للمواصفات التقنية الرسمية لوزارة التربية الوطنية.',
                'acquisition_date' => $today->copy()->subMonths(rand(6, 24)),
                'acquisition_source' => 'تجهيز ولائي رسمي من مديرية التربية',
                'acquisition_cost' => rand(15000, 95000),
                'quantity' => $item['qty'],
                'location_type' => 'internal',
                'location_name' => $item['loc'],
                'custodian_id' => $bursar->id,
                'condition' => 'good',
                'recorded_by' => $bursar->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory_allocations')->insert([
                'allocation_ref' => 'ALC-'.$item['code'],
                'room_name' => $item['loc'],
                'room_type' => 'administrative',
                'responsible_user_id' => $teachers->first()?->id ?? $principal->id,
                'academic_year' => $academicYear,
                'items_count' => rand(1, 4),
                'condition_verified' => true,
                'custody_nature' => 'official',
                'recorded_by' => $bursar->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('inventory_recolements')->insert([
            'recolement_number' => 'REC-INV-'.$academicYear.'-01',
            'room_name' => 'المخزن والمكاتب الإدارية والمخابر',
            'room_type' => 'mixed',
            'recolement_date' => $today->copy()->subDays(15),
            'academic_year' => $academicYear,
            'auditor_id' => $principal->id,
            'witness_id' => $bursar->id,
            'theoretical_count' => 97,
            'verified_count' => 97,
            'conform_count' => 97,
            'missing_count' => 0,
            'surplus_count' => 0,
            'damaged_count' => 0,
            'status' => 'certified',
            'findings' => 'تطابق تام ومثالي بين سجل الجرد الدائم والجرد الميداني الفعلي دون أي فروقات.',
            'recorded_by' => $bursar->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_reforms')->insert([
            'reform_number' => 'REF-INV-2026-001',
            'reform_date' => $today->copy()->subDays(10),
            'academic_year' => $academicYear,
            'committee_president_id' => $principal->id,
            'total_items_count' => 14,
            'total_estimated_value' => 42000.00,
            'commission_observations' => 'أثاث مدرسي قديم غير صالح للاستعمال وغير قابل للإصلاح (طاولات وكراسي مهترئة) معروض للإسقاط والبيع بالمزاد عبر إدارة أملاك الدولة.',
            'domains_notification_date' => $today->copy()->subDays(5),
            'status' => 'approved',
            'recorded_by' => $bursar->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 14. School Budget & Financial Accounting
        DB::table('payment_mandates')->delete();
        DB::table('budget_commitments')->delete();
        DB::table('school_budget_lines')->delete();

        $budgetChapters = [
            ['chap' => '21', 'art' => '01', 'title' => 'التغذية المدرسية وإطعام التلاميذ نصف الداخليين', 'allocated' => 4500000.00],
            ['chap' => '22', 'art' => '02', 'title' => 'الأدوات والوسائل البيداغوجية والتجارب المخبرية', 'allocated' => 850000.00],
            ['chap' => '23', 'art' => '03', 'title' => 'صيانة المباني والمنشآت وشبكات التدفئة والكهرباء', 'allocated' => 1200000.00],
            ['chap' => '24', 'art' => '04', 'title' => 'فواتير الطاقة والمياه والغاز والهاتف والإنترنت', 'allocated' => 750000.00],
            ['chap' => '25', 'art' => '05', 'title' => 'اللوازم المكتبية والطباعة وسجلات الأرشيف والوثائق', 'allocated' => 450000.00],
            ['chap' => '26', 'art' => '06', 'title' => 'النشاطات الرياضية والثقافية والجوائز المدرسية', 'allocated' => 300000.00],
        ];

        foreach ($budgetChapters as $idx => $bc) {
            $lineId = DB::table('school_budget_lines')->insertGetId([
                'academic_year' => $academicYear,
                'fiscal_year' => 2026,
                'budget_type' => 'operating',
                'chapter' => $bc['chap'],
                'article' => $bc['art'],
                'allocated_amount' => $bc['allocated'],
                'committed_amount' => $bc['allocated'] * 0.45,
                'liquidated_amount' => $bc['allocated'] * 0.40,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $commId = DB::table('budget_commitments')->insertGetId([
                'commitment_number' => 'ENG-'.$academicYear.'-'.str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'budget_line_id' => $lineId,
                'supplier_name' => 'شركة التجهيزات المدرسية واللوازم التربوية — الوادي',
                'order_reference' => 'BC-2026-'.str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                'order_date' => $today->copy()->subDays(40),
                'subject' => $bc['title'],
                'estimated_amount' => 85000.00,
                'status' => 'approved',
                'created_by' => $bursar->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('payment_mandates')->insert([
                'mandate_number' => 'MAND-'.$academicYear.'-'.str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'commitment_id' => $commId,
                'mandate_date' => $today->copy()->subDays(20),
                'invoice_number' => 'FAC-2026-'.rand(100, 999),
                'invoice_date' => $today->copy()->subDays(25),
                'liquidated_amount' => 85000.00,
                'payment_method' => 'transfer',
                'payment_reference' => 'CCP-TR-'.rand(100000, 999999),
                'status' => 'paid',
                'authorized_by' => $principal->id,
                'paid_by' => $bursar->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 15. Official Examination Center (BEM)
        DB::table('official_exam_incidents')->delete();
        DB::table('official_exam_supervisors')->delete();
        DB::table('official_exam_rooms')->delete();
        DB::table('official_exam_centers')->delete();

        $centerId = DB::table('official_exam_centers')->insertGetId([
            'exam_type' => 'bem',
            'session_year' => 2026,
            'center_code' => 'BEM-39-012',
            'center_name' => 'مركز متوسطة الشهيد العربي بن مهيدي — الوادي',
            'head_user_id' => $principal->id,
            'vice_head_name' => 'الأستاذ البشير الإبراهيمي',
            'secretariat_chief_name' => 'الأستاذ مولود قاسم',
            'candidate_count' => 240,
            'room_count' => 10,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
            'status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($r = 1; $r <= 10; $r++) {
            $roomId = DB::table('official_exam_rooms')->insertGetId([
                'center_id' => $centerId,
                'room_number' => $r,
                'room_label' => 'القاعة رقم '.str_pad($r, 2, '0', STR_PAD_LEFT),
                'physical_location' => 'الجناح البيداغوجي أ — الطابق الأول',
                'candidate_start_number' => 3901000 + (($r - 1) * 24) + 1,
                'candidate_end_number' => 3901000 + ($r * 24),
                'candidate_count' => 24,
                'supervisors_required' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('official_exam_supervisors')->insert([
                'center_id' => $centerId,
                'room_id' => $roomId,
                'user_id' => $teachers[$r % $teachers->count()]->id,
                'supervisor_name' => $teachers[$r % $teachers->count()]->name,
                'origin_institution' => 'متوسطة 08 ماي 1945 — الوادي',
                'duty_role' => 'chief_supervisor',
                'attendance_status' => 'confirmed',
                'convocation_reference' => 'CONV-BEM-'.str_pad($r, 3, '0', STR_PAD_LEFT),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('official_exam_incidents')->insert([
            'center_id' => $centerId,
            'room_id' => 1,
            'incident_number' => 'INC-BEM-01',
            'incident_type' => 'tardiness',
            'incident_date' => '2026-06-08',
            'session_time' => '08:30',
            'subject_name' => 'اللغة العربية',
            'candidate_registration_number' => '39010005',
            'candidate_name' => 'سالم عبد الرحيم',
            'description' => 'تأخر مبرر بسبب عطب وسيلة النقل وتم إدخال المترشح وفق التنظيم المعمول به.',
            'measures_taken' => 'التأكد من سلامة الظرف وتدوين الملاحظة بتقرير المركز.',
            'reported_by' => $principal->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 16. School Solidarity & Aid
        DB::table('school_solidarity_aid_distributions')->delete();
        DB::table('student_solidarity_beneficiaries')->delete();
        DB::table('school_solidarity_committee_members')->delete();
        DB::table('school_solidarity_campaigns')->delete();

        $campaignId = DB::table('school_solidarity_campaigns')->insertGetId([
            'campaign_year' => 2026,
            'academic_year' => $academicYear,
            'title' => 'حملة التضامن المدرسي وصرف منحة 5000 دج ومحفظة التلميذ للمعوزين',
            'start_date' => $today->copy()->subMonths(3),
            'end_date' => $today->copy()->addMonths(2),
            'grant_amount' => 5000.00,
            'status' => 'disbursing',
            'allocated_budget' => 125000.00,
            'approved_beneficiaries_count' => 25,
            'total_disbursed_amount' => 125000.00,
            'created_by' => $principal->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $comRoles = [
            'مدير المتوسطة — رئيساً للجنة',
            'المقتصد / مسير مالي — مقرراً',
            'مستشار التربية — عضواً',
            'أستاذ ممثل منتخب — عضواً',
            'رئيس جمعية أولياء التلاميذ — عضواً',
        ];

        foreach ($comRoles as $cr) {
            DB::table('school_solidarity_committee_members')->insert([
                'campaign_id' => $campaignId,
                'member_name' => $principal->name,
                'committee_role' => 'member',
                'institution_role_label' => $cr,
                'attended_deliberation' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($students->take(25) as $i => $st) {
            DB::table('student_solidarity_beneficiaries')->insert([
                'campaign_id' => $campaignId,
                'student_id' => $st->id,
                'file_number' => 'DOS-SOL-'.rand(1000, 9999),
                'eligibility_category' => ($i % 3 === 0) ? 'orphan' : 'low_income',
                'guardian_type' => 'father',
                'guardian_national_id' => '109988'.rand(100000, 999999),
                'guardian_full_name' => 'ولي التلميذ '.$st->name,
                'guardian_profession' => 'عامل يومي بقطاع الفلاحة والبناء',
                'monthly_income' => 18000.00,
                'ccp_account_number' => '00'.rand(1000000, 9999999),
                'status' => 'approved',
                'verified_by' => $principal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('school_solidarity_aid_distributions')->insert([
                'campaign_id' => $campaignId,
                'student_id' => $st->id,
                'aid_type' => 'grant_5000',
                'voucher_number' => 'VCH-AID-'.rand(1000, 9999),
                'distribution_date' => $today->copy()->subDays(20),
                'items_description' => 'المنحة المالية المدرسية الخاصة 5000 دج + محفظة بالأدوات المدرسية الكاملة',
                'estimated_value' => 5000.00,
                'received_by_guardian' => true,
                'issued_by' => $bursar->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 17. School Boarding & Transport
        DB::table('student_bus_subscriptions')->delete();
        DB::table('school_bus_routes')->delete();
        DB::table('boarding_exit_passes')->delete();
        DB::table('boarding_night_sessions')->delete();
        DB::table('student_dormitory_allocations')->delete();
        DB::table('dormitory_rooms')->delete();

        for ($drm = 1; $drm <= 4; $drm++) {
            $dormId = DB::table('dormitory_rooms')->insertGetId([
                'wing' => ($drm <= 2) ? 'A' : 'B',
                'room_code' => 'G-'.str_pad($drm, 2, '0', STR_PAD_LEFT),
                'room_name' => 'مرقد الجناح '.($drm <= 2 ? 'الشرقي' : 'الغربي').' — غرفة 0'.$drm,
                'floor_level' => 1,
                'bed_capacity' => 8,
                'active_occupants_count' => 4,
                'supervisor_in_charge' => $supervisor->name,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($students->slice($drm * 2, 2) as $st) {
                DB::table('student_dormitory_allocations')->insert([
                    'dormitory_room_id' => $dormId,
                    'student_id' => $st->id,
                    'academic_year' => $academicYear,
                    'bed_number' => 'B-'.rand(1, 8),
                    'locker_number' => 'L-'.rand(1, 8),
                    'check_in_date' => $today->copy()->subMonths(3),
                    'status' => 'active',
                    'linen_received' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('boarding_night_sessions')->insert([
            'academic_year' => $academicYear,
            'session_date' => $today->copy()->subDays(2),
            'session_type' => 'night_study',
            'supervisor_user_id' => $supervisor->id,
            'supervisor_name' => $supervisor->name,
            'boarders_present_count' => 16,
            'boarders_absent_count' => 0,
            'incident_report' => 'انضباط تام للمقيمين أثناء حصة المذاكرة المسائية وإنجاز الواجبات المنزلية.',
            'recorded_by' => $supervisor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('boarding_exit_passes')->insert([
            'pass_number' => 'EXIT-2026-001',
            'student_id' => $students->first()?->id ?? 1,
            'academic_year' => $academicYear,
            'pass_type' => 'weekend_leave',
            'departure_datetime' => $today->copy()->subDays(4)->setTime(16, 0),
            'expected_return_datetime' => $today->copy()->subDays(2)->setTime(18, 0),
            'escort_guardian_name' => 'ولي التلميذ الرسمي',
            'destination' => 'مقر الإقامة العائلية بالبلدية المجاورة',
            'status' => 'returned',
            'authorized_by' => $supervisor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $busRouteId = DB::table('school_bus_routes')->insertGetId([
            'route_code' => 'BUS-LINE-01',
            'route_name' => 'خط النقل المدرسي رقم 01 — قرى ومداشر البلدية',
            'bus_license_plate' => '00123-112-39',
            'provider_entity' => 'بلدية أم الطيور — حظيرة النقل المدرسي',
            'driver_name' => 'عيسى بلقاسم',
            'driver_phone' => '0661998877',
            'passenger_capacity' => 35,
            'subscribed_students_count' => 20,
            'morning_departure_time' => '07:15',
            'evening_return_time' => '16:45',
            'stops_list' => 'القرية الفلاحية، مدشر النخيل، حي الشهداء، المتوسطة',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($students->take(12) as $st) {
            DB::table('student_bus_subscriptions')->insert([
                'bus_route_id' => $busRouteId,
                'student_id' => $st->id,
                'academic_year' => $academicYear,
                'card_number' => 'BUS-CARD-'.rand(1000, 9999),
                'pickup_stop' => 'حي الشهداء — المحطة المركزية',
                'issue_date' => $today->copy()->subMonths(3),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 18. Safety & Emergency Plans (PSI)
        DB::table('school_safety_incidents')->delete();
        DB::table('safety_committee_meetings')->delete();
        DB::table('safety_evacuation_drills')->delete();
        DB::table('safety_fire_equipment')->delete();
        DB::table('safety_intervention_teams')->delete();
        DB::table('safety_emergency_plans')->delete();

        $planId = DB::table('safety_emergency_plans')->insertGetId([
            'academic_year' => $academicYear,
            'plan_code' => 'PSI-'.$academicYear.'-01',
            'title' => 'مخطط الأمن الداخلي والوقاية من الكوارث والمخاطر الكبرى (PSI) 2026-2027',
            'crisis_cell_leader' => $principal->name,
            'evacuation_coordinator' => $supervisor->name,
            'first_aid_leader' => $teachers[0]->name,
            'technical_safety_officer' => $bursar->name,
            'assembly_points' => 'ساحة العلم المركزية + الملعب الرياضي المعشب',
            'emergency_contacts' => 'الحماية المدنية: 14 | الشرطة: 17 | الدرك: 1055 | طبيب المؤسسة: 115',
            'status' => 'approved',
            'approval_date' => $today->copy()->subMonths(2),
            'approved_by' => $principal->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teamTypes = ['evacuation', 'first_aid', 'fire_fighting'];
        foreach ($teamTypes as $idx => $tt) {
            DB::table('safety_intervention_teams')->insert([
                'safety_plan_id' => $planId,
                'team_type' => $tt,
                'member_name' => $teachers[$idx]->name,
                'institution_role' => 'أستاذ منسق فريق السلامة',
                'user_id' => $teachers[$idx]->id,
                'assigned_zone' => 'الجناح البيداغوجي أ والمخابر',
                'phone_number' => '0661223344',
                'is_certified_first_aider' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        for ($eq = 1; $eq <= 8; $eq++) {
            DB::table('safety_fire_equipment')->insert([
                'equipment_code' => 'EXT-2026-'.str_pad($eq, 3, '0', STR_PAD_LEFT),
                'equipment_type' => ($eq % 2 === 0) ? 'powder' : 'co2',
                'exact_location' => ['الجناح الإداري', 'المخبر العلمي أ', 'مخبر الفيزياء', 'المطعم المدرسي'][$eq % 4],
                'installation_date' => $today->copy()->subMonths(6),
                'last_inspection_date' => $today->copy()->subDays(30),
                'next_inspection_due_date' => $today->copy()->addMonths(5),
                'inspection_status' => 'operational',
                'inspector_entity' => 'شركة صيانة تجهيزات السلامة والحريق المعتمدة',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('safety_evacuation_drills')->insert([
            'academic_year' => $academicYear,
            'drill_code' => 'DRILL-2026-01',
            'drill_date' => $today->copy()->subDays(20),
            'drill_time' => '10:15',
            'simulation_scenario' => 'هزة أرضية افتراضية مع نشوب حريق في ورشة الصيانة',
            'evacuation_time_seconds' => 145,
            'total_persons_evacuated' => 620,
            'assembly_zone_used' => 'ساحة العلم المركزية',
            'civil_protection_partner' => 'الوحدة الرئيسية للحماية المدنية — الوادي',
            'evaluation_rating' => 'good',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('safety_committee_meetings')->insert([
            'academic_year' => $academicYear,
            'session_code' => 'COM-SAF-2026-01',
            'meeting_date' => $today->copy()->subDays(40),
            'session_type' => 'ordinary',
            'meeting_chair' => $principal->name,
            'members_present' => 'المدير، المقتصد، مستشار التربية، طبيب الصحة المدرسية، ممثل الأساتذة',
            'agenda_items' => 'معاينة مخارج الطوارئ ومطافئ الحريق وسلامة شبكة الغاز والتدفئة.',
            'decisions_and_recommendations' => 'المصادقة على مخطط الأمن الداخلي واستكمال اللوحات الإرشادية.',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('school_safety_incidents')->insert([
            'academic_year' => $academicYear,
            'incident_number' => 'INC-SAF-2026-001',
            'incident_type' => 'sports_accident',
            'severity_level' => 'minor',
            'incident_date' => $today->copy()->subDays(12)->setTime(10, 30),
            'location' => 'الملعب الرياضي المعشب',
            'victim_type' => 'student',
            'student_id' => $students->first()?->id,
            'victim_name' => $students->first()?->name ?? 'سالم عبد الرحيم',
            'circumstances_description' => 'سقوط خفيف أثناء حصة التربية البدنية والرياضية (جري سريع) أدى إلى التواء خفيف بالكاحل الأيمن.',
            'bodily_injuries_description' => 'التواء طفيف بالكاحل مع كدمة سطحية دون أي كسر عظمي.',
            'immediate_care_administered' => 'تقديم الإسعافات الأولية وتضميد الكاحل برباط ضاغط بقاعة التمريض المدرسية.',
            'hospital_evacuation' => false,
            'temporary_incapacity_days' => 2,
            'supervising_personnel_at_time' => 'أستاذ التربية البدنية والرياضية',
            'insurance_declared' => true,
            'declaration_date' => $today->copy()->subDays(11),
            'directorate_notified' => true,
            'status' => 'closed',
            'investigating_officer_name' => $principal->name,
            'notes' => 'التلميذ تماثل للشفاء وعاد لمقاعد الدراسة بصفة طبيعية.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 19. School Board & Governance
        DB::table('parents_association_meetings')->delete();
        DB::table('parents_associations')->delete();
        DB::table('school_internal_regulations')->delete();
        DB::table('school_projects')->delete();
        DB::table('school_board_members')->delete();
        DB::table('school_board_sessions')->delete();

        $boardId = DB::table('school_board_sessions')->insertGetId([
            'academic_year' => $academicYear,
            'session_code' => 'SES-BOD-2026-01',
            'board_type' => 'guidance_management',
            'session_term' => 'ordinary_term_1',
            'session_date' => $today->copy()->subDays(50),
            'president_user_id' => $principal->id,
            'president_name' => $principal->name,
            'rapporteur_user_id' => $bursar->id,
            'rapporteur_name' => $bursar->name,
            'quorum_required' => 5,
            'members_present_count' => 5,
            'is_quorum_reached' => true,
            'agenda_items' => 'المصادقة على الحساب المالي للمؤسسة، مشروع المؤسسة، وتدابير الاستقرار التربوي.',
            'decisions_summary' => 'المصادقة بالإجماع على الحساب المالي والتنظيم التربوي للموسم الدراسي.',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bMembers = [
            ['name' => $principal->name, 'cat' => 'administrative', 'role' => 'مدير المؤسسة — رئيساً'],
            ['name' => $bursar->name, 'cat' => 'financial', 'role' => 'المقتصد — كاتباً ومقرراً'],
            ['name' => $teachers[0]->name, 'cat' => 'teaching_staff', 'role' => 'ممثل الأساتذة المنتخب'],
            ['name' => $supervisor->name, 'cat' => 'education_staff', 'role' => 'مستشار التربية'],
            ['name' => 'السيد لخضر قسومي', 'cat' => 'parents', 'role' => 'رئيس جمعية أولياء التلاميذ'],
        ];

        foreach ($bMembers as $bm) {
            DB::table('school_board_members')->insert([
                'academic_year' => $academicYear,
                'member_category' => $bm['cat'],
                'institution_role' => $bm['role'],
                'user_id' => $principal->id,
                'member_name' => $bm['name'],
                'mandate_start_date' => $today->copy()->subMonths(6),
                'mandate_end_date' => $today->copy()->addMonths(6),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('school_projects')->insert([
            'project_code' => 'PRJ-'.$academicYear.'-01',
            'title' => 'مشروع المؤسسة: التميز المدرسي، الرقمنة الشاملة، ومكافحة التسرب 2024-2027',
            'start_year' => '2024',
            'end_year' => '2027',
            'strategic_axes' => 'المحور البيداغوجي، محور الحوكمة والإدارة الرقمية، ومحور الحياة المدرسية والتفتح.',
            'action_plan' => 'تفعيل المعالجة البيداغوجية، دعم التلاميذ في الامتحانات، تفعيل منظومة سَجِّل والأنشطة.',
            'status' => 'approved',
            'completion_rate_percentage' => 72.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('school_internal_regulations')->insert([
            'academic_year' => $academicYear,
            'regulation_code' => 'REG-INT-2026',
            'title' => 'النظام الداخلي لمتوسطة الشهيد العربي بن مهيدي',
            'version' => '1.0',
            'adoption_date' => $today->copy()->subMonths(5),
            'key_rules_summary' => 'تنظيم مواعيد الدخول والخروج، حظر الهاتف النقال، احترام الهندام المدرسي، والانضباط.',
            'is_current_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paId = DB::table('parents_associations')->insertGetId([
            'academic_year' => $academicYear,
            'association_name' => 'جمعية أولياء تلاميذ متوسطة الشهيد العربي بن مهيدي',
            'registration_number' => 'W-39-APE-'.rand(100, 999),
            'president_name' => 'السيد لخضر قسومي',
            'president_phone' => '0662334455',
            'president_email' => 'ape.benmhidi@gmail.com',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('parents_association_meetings')->insert([
            'parents_association_id' => $paId,
            'academic_year' => $academicYear,
            'meeting_code' => 'APE-MEET-2026-01',
            'meeting_type' => 'ordinary_assembly',
            'meeting_date' => $today->copy()->subDays(28),
            'attendees_count' => 65,
            'agenda_topics' => 'متابعة نتائج التلاميذ للفصل الأول ودعم النشاطات الثقافية والرياضية.',
            'deliberation_narrative' => 'تثمين الجهود المبذولة من الطاقم التربوي والموافقة على تقديم جوائز للمتفوقين.',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 20. School Facility Maintenance & Utilities
        DB::table('utility_consumption_records')->delete();
        DB::table('energy_utility_meters')->delete();
        DB::table('water_tank_hygiene_inspections')->delete();
        DB::table('central_heating_logs')->delete();
        DB::table('maintenance_work_orders')->delete();
        DB::table('school_building_facilities')->delete();

        $facilities = [
            ['code' => 'FAC-01', 'name' => 'الجناح البيداغوجي أ — القاعات من 01 إلى 10', 'type' => 'pedagogical_block', 'block' => 'A'],
            ['code' => 'FAC-02', 'name' => 'الجناح البيداغوجي ب — القاعات من 11 إلى 20', 'type' => 'pedagogical_block', 'block' => 'B'],
            ['code' => 'FAC-03', 'name' => 'مجمع المخابر العلمية والإعلام الآلي', 'type' => 'science_lab_complex', 'block' => 'C'],
            ['code' => 'FAC-04', 'name' => 'المطعم المدرسي نصف الداخلي والمطبخ', 'type' => 'canteen_facility', 'block' => 'D'],
            ['code' => 'FAC-05', 'name' => 'الملعب الرياضي المعشب وساحة العلم', 'type' => 'sports_grounds', 'block' => 'E'],
        ];

        $facilityIds = [];
        foreach ($facilities as $f) {
            $fid = DB::table('school_building_facilities')->insertGetId([
                'facility_code' => $f['code'],
                'facility_name' => $f['name'],
                'facility_type' => $f['type'],
                'building_block' => $f['block'],
                'total_rooms_count' => 10,
                'condition_rating' => 'good',
                'has_heating' => true,
                'has_water_connection' => true,
                'has_gas_connection' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $facilityIds[] = $fid;
        }

        DB::table('maintenance_work_orders')->insert([
            'work_order_number' => 'WO-'.$academicYear.'-001',
            'academic_year' => $academicYear,
            'facility_id' => $facilityIds[0],
            'exact_location' => 'القاعة 04 والقاعة 07 بالجناح أ',
            'category' => 'electrical',
            'priority_level' => 'Normal',
            'issue_description' => 'تغيير مصابيح الإنارة الفلورية واستبدال قواطع الكهرباء الفرعية.',
            'reported_by_user_id' => $teachers[0]->id,
            'reported_by_name' => $teachers[0]->name,
            'report_date' => $today->copy()->subDays(15),
            'status' => 'Completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('central_heating_logs')->insert([
            'log_reference' => 'HEAT-2026-01',
            'academic_year' => $academicYear,
            'boiler_code' => 'BLR-01',
            'boiler_brand_model' => 'Chappée / De Dietrich 250 kW',
            'installation_year' => 2021,
            'fuel_type' => 'natural_gas',
            'inspection_date' => $today->copy()->subDays(8),
            'inspector_entity' => 'مؤسسة الصيانة المعتمدة لشبكات التدفئة',
            'burner_operational_state' => 'good',
            'pressure_bar' => 1.8,
            'status' => 'compliant',
            'certified_by_name' => $principal->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('water_tank_hygiene_inspections')->insert([
            'inspection_reference' => 'WT-2026-01',
            'academic_year' => $academicYear,
            'tank_name_code' => 'TANK-UPPER-01',
            'tank_type' => 'elevated_rooftop',
            'capacity_cubic_meters' => 20.0,
            'inspection_date' => $today->copy()->subDays(30),
            'operation_type' => 'chlorination_disinfection',
            'executed_by' => 'مصالح النظافة والوقاية الصحية البلدية',
            'residual_chlorine_ppm' => 0.45,
            'water_potable_clearance' => 'cleared_potable',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $meterId = DB::table('energy_utility_meters')->insertGetId([
            'meter_code' => 'MTR-ELEC-01',
            'meter_type' => 'electricity',
            'utility_provider' => 'شركة سونلغاز — مديرية التوزيع بالوادي',
            'meter_serial_number' => 'SON-39-'.rand(100000, 999999),
            'meter_location' => 'اللوحة العامة للمدخل الإداري',
            'unit_of_measure' => 'kWh',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('utility_consumption_records')->insert([
            'bill_reference' => 'BILL-ELEC-2026-T1',
            'academic_year' => $academicYear,
            'energy_utility_meter_id' => $meterId,
            'billing_period_quarter' => 'Q1',
            'reading_date_start' => $today->copy()->subMonths(3),
            'reading_date_end' => $today->copy()->subDays(5),
            'previous_index' => 12450,
            'current_index' => 13680,
            'consumption_units' => 1230,
            'invoiced_amount_dzd' => 18450.00,
            'payment_status' => 'paid',
            'meter_auditor_name' => 'المهندس مصطفى عون',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 21. School Sports Association & Activities (ASCS)
        DB::table('sports_equipment_inventories')->delete();
        DB::table('ascs_financial_transactions')->delete();
        DB::table('sports_competitions_events')->delete();
        DB::table('student_sports_licenses')->delete();
        DB::table('sports_cultural_disciplines')->delete();
        DB::table('school_sports_associations')->delete();

        DB::table('school_sports_associations')->insert([
            'association_name' => 'الجمعية الثقافية والرياضية المدرسية (ASCS) — متوسطة العربي بن مهيدي',
            'academic_year' => $academicYear,
            'affiliation_number' => 'ASCS-39-'.rand(100, 999),
            'approval_receipt_number' => 'REC-ASCS-2026',
            'approval_date' => $today->copy()->subMonths(5),
            'principal_president_name' => $principal->name,
            'secretary_general_name' => $teachers[0]->name,
            'treasurer_name' => $bursar->name,
            'annual_subscription_fee' => 200.00,
            'active_members_count' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ascsDisciplines = [
            ['code' => 'DISC-HB', 'name' => 'كرة اليد (Handball)', 'fr' => 'Handball', 'cat' => 'team_sport', 'gender' => 'mixed'],
            ['code' => 'DISC-FUT', 'name' => 'كرة القدم المصغرة (Futsal)', 'fr' => 'Futsal', 'cat' => 'team_sport', 'gender' => 'boys'],
            ['code' => 'DISC-ATH', 'name' => 'العدو الريفي وألعاب القوى (Cross-Country)', 'fr' => 'Athlétisme', 'cat' => 'individual_athletics', 'gender' => 'mixed'],
            ['code' => 'DISC-CHS', 'name' => 'الشطرنج الفكري والرياضات الذهنية', 'fr' => 'Échecs', 'cat' => 'cultural_intellectual', 'gender' => 'mixed'],
        ];

        $ascsDiscIds = [];
        foreach ($ascsDisciplines as $ad) {
            $did = DB::table('sports_cultural_disciplines')->insertGetId([
                'discipline_code' => $ad['code'],
                'name_ar' => $ad['name'],
                'name_fr' => $ad['fr'],
                'category' => $ad['cat'],
                'supervising_teacher_name' => $teachers[0]->name,
                'supervising_user_id' => $teachers[0]->id,
                'training_schedule' => 'كل ثلاثاء وأربعاء مساء من 16:30 إلى 18:00',
                'target_genders' => $ad['gender'],
                'age_category' => 'U14',
                'max_participants' => 25,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ascsDiscIds[] = $did;
        }

        foreach ($students->take(20) as $i => $st) {
            DB::table('student_sports_licenses')->insert([
                'license_number' => 'ASCS-LIC-2026-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'academic_year' => $academicYear,
                'student_id' => $st->id,
                'discipline_id' => $ascsDiscIds[$i % count($ascsDiscIds)],
                'license_type' => 'middle_school_championship',
                'medical_certificate_issued' => true,
                'medical_certificate_date' => $today->copy()->subMonths(4),
                'medical_examiner_name' => 'طبيب وحدة الكشف والمتابعة المدرسية',
                'medical_aptitude_verdict' => 'fit_for_sports',
                'parental_consent_signed' => true,
                'insurance_covered' => true,
                'fee_paid' => true,
                'issue_date' => $today->copy()->subMonths(3),
                'expiry_date' => $today->copy()->addMonths(7),
                'status' => 'valid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('sports_competitions_events')->insert([
            'event_code' => 'EVT-CROSS-2026',
            'academic_year' => $academicYear,
            'discipline_id' => $ascsDiscIds[2],
            'event_name' => 'البطولة المدرسية الولائية للعدو الريفي للمتوسطات — دورة 2026',
            'competition_level' => 'wilaya',
            'event_date' => $today->copy()->subDays(18),
            'event_time' => '09:00',
            'location_venue' => 'مضمار المركب الرياضي أول نوفمبر — الوادي',
            'host_institution' => 'الرابطة الولائية للرياضة المدرسية بالوادي',
            'opponent_team_name' => 'متوسطات دائرة قمار وأم الطيور',
            'our_score' => 45,
            'opponent_score' => 38,
            'match_result' => 'victory',
            'ranking_achieved' => 'المرتبة الثانية ولائياً (الميدالية الفضية)',
            'fair_play_score' => 10,
            'transport_arranged' => true,
            'participation_notes' => 'مشاركة مشرفة لمتوسطتنا وتأهل فريق العدو للبطولة الجهوية.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ascs_financial_transactions')->insert([
            'transaction_reference' => 'TX-ASCS-2026-001',
            'academic_year' => $academicYear,
            'transaction_type' => 'income_dues',
            'amount_dzd' => 45000.00,
            'transaction_date' => $today->copy()->subDays(60),
            'payment_method' => 'cash',
            'invoice_or_receipt_ref' => 'BON-ASCS-01',
            'description' => 'اشتراكات انخراط التلاميذ في الجمعية الرياضية المدرسية',
            'recorded_by_name' => $bursar->name,
            'treasurer_approved' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sports_equipment_inventories')->insert([
            'equipment_code' => 'EQP-HB-01',
            'item_name' => 'كرات يد قياس 2 + صدريات رياضية ملونة + شباك مرمى',
            'discipline_id' => $ascsDiscIds[0],
            'quantity_total' => 20,
            'quantity_operational' => 20,
            'quantity_damaged' => 0,
            'storage_location' => 'مستودع التربية البدنية والرياضية',
            'condition_rating' => 'good',
            'last_inventory_date' => $today->copy()->subMonths(2),
            'purchased_academic_year' => $academicYear,
            'acquisition_source' => 'شراء من ميزانية الجمعية الرياضية المدرسية',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 22. School Cooperative, Pedagogical Garden & Eco-Club
        DB::table('eco_green_club_actions')->delete();
        DB::table('botanical_plants_inventory')->delete();
        DB::table('pedagogical_garden_plots')->delete();
        DB::table('cooperative_financial_ledger')->delete();
        DB::table('cooperative_projects_initiatives')->delete();
        DB::table('school_cooperatives')->delete();

        $coopId = DB::table('school_cooperatives')->insertGetId([
            'academic_year' => $academicYear,
            'cooperative_name' => 'التعاونية المدرسية لمتوسطة الشهيد العربي بن مهيدي',
            'approval_decree_number' => 'COOP-39-'.rand(100, 999),
            'foundation_date' => $today->copy()->subMonths(8),
            'motto_or_slogan' => 'بالتعاون نبني مدرستنا ونرعى بيئتنا',
            'coordinator_teacher_id' => $teachers[0]->id,
            'student_president_name' => 'التلميذ أنس قادري',
            'total_members_count' => 45,
            'treasury_balance' => 38500.00,
            'annual_action_plan' => 'غرس الأشجار، العناية بالحديقة البيداغوجية، وتنظيم معارض إعادة التدوير.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cooperative_projects_initiatives')->insert([
            'project_code' => 'COOP-PRJ-2026-01',
            'academic_year' => $academicYear,
            'school_cooperative_id' => $coopId,
            'title' => 'مشروع الحديقة البيداغوجية وتثمين النباتات الصحراوية العطرية والطبية',
            'project_type' => 'pedagogical_garden',
            'target_audience' => 'تلاميذ نادي البيئة وأقسام السنة الأولى والثانية',
            'start_date' => $today->copy()->subMonths(2),
            'end_date' => $today->copy()->addMonths(4),
            'budget_allocated' => 25000.00,
            'budget_spent' => 18500.00,
            'status' => 'in_progress',
            'coordinator_user_id' => $teachers[0]->id,
            'achievements_notes' => 'تهيئة 4 أحواض زراعية بيداغوجية، تركيب نظام سقي بالتقطير، وإشراك التلاميذ في الزراعة والاعتناء بالنباتات.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cooperative_financial_ledger')->insert([
            'transaction_reference' => 'FIN-COOP-2026-01',
            'academic_year' => $academicYear,
            'school_cooperative_id' => $coopId,
            'transaction_date' => $today->copy()->subDays(25),
            'transaction_type' => 'income_dues',
            'amount_dzd' => 35000.00,
            'beneficiary_or_source' => 'اشتراكات التلاميذ ومداخيل المعرض البيئي السنوي',
            'payment_method' => 'cash',
            'receipt_voucher_number' => 'REC-COOP-01',
            'recorded_by_user_id' => $principal->id,
            'description' => 'مداخيل بيع شتلات ونباتات عطرية واشتراكات المعرض المدرسي',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $plots = [
            ['code' => 'PLOT-01', 'name' => 'الحوض رقم 01 — النباتات العطرية والطبية', 'area' => 35.5, 'irrigation' => 'drip_irrigation'],
            ['code' => 'PLOT-02', 'name' => 'الحوض رقم 02 — الخضروات الموسمية', 'area' => 45.0, 'irrigation' => 'drip_irrigation'],
            ['code' => 'PLOT-03', 'name' => 'الحوض رقم 03 — شجيرات الزينة وأشجار الزيتون والنخيل', 'area' => 60.0, 'irrigation' => 'sprinkler'],
            ['code' => 'PLOT-04', 'name' => 'الحوض رقم 04 — وحدة التسميد العضوي وتدوير المخلفات', 'area' => 20.0, 'irrigation' => 'manual_watering'],
        ];

        foreach ($plots as $idx => $p) {
            $plotId = DB::table('pedagogical_garden_plots')->insertGetId([
                'plot_code' => $p['code'],
                'plot_name' => $p['name'],
                'surface_area_sqm' => $p['area'],
                'soil_type' => 'رملية طميية مسمدة',
                'irrigation_type' => $p['irrigation'],
                'location_in_school' => 'الجهة الجنوبية المحاذية للمخبر والمطعم',
                'assigned_classroom_id' => $classrooms->first()?->id,
                'supervising_teacher_id' => $teachers[0]->id,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('botanical_plants_inventory')->insert([
                'plant_code' => 'PLT-0'.($idx + 1),
                'pedagogical_garden_plot_id' => $plotId,
                'botanical_name' => ['Rosmarinus officinalis', 'Mentha spicata', 'Lavandula angustifolia', 'Olea europaea'][$idx],
                'common_name_ar' => ['إكليل الجبل (الروزماري)', 'النعناع البلدي الفواح', 'الخزامى والزعتر البري', 'شجيرات الدفلة والزيتون'][$idx],
                'common_name_fr' => ['Romarin', 'Menthe', 'Lavande', 'Olivier'][$idx],
                'plant_category' => 'aromatic_medicinal',
                'quantity_planted' => rand(15, 40),
                'planting_date' => $today->copy()->subDays(40),
                'expected_harvest_date' => $today->copy()->addMonths(3),
                'health_status' => 'thriving',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('eco_green_club_actions')->insert([
            'action_code' => 'ECO-ACT-2026-01',
            'academic_year' => $academicYear,
            'action_title' => 'مبادرة غرس 50 شجيرة بمحيط ساحة المؤسسة احتفالاً باليوم الوطني للشجرة',
            'action_category' => 'tree_planting',
            'execution_date' => $today->copy()->subDays(14),
            'participants_count' => 45,
            'trees_planted_count' => 50,
            'waste_collected_kg' => 120.0,
            'supervising_teacher_id' => $teachers[0]->id,
            'location_details' => 'المحيط الأخضر لساحة العلم والملاعب الرياضية',
            'partner_organization' => 'محافظة الغابات لولاية الوادي والبلدية',
            'impact_summary' => 'إضفاء مظهر جمالي على المؤسسة وغرس ثقافة حماية الطبيعة لدى الناشئة.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
