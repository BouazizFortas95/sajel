<?php

namespace Database\Seeders;

use App\Enums\DailyLogStatus;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FoundationalReferenceDataSeeder extends Seeder
{
    /**
     * Standard official Algerian educational subjects.
     */
    public const SUBJECTS = [
        ['name' => 'اللغة العربية', 'color' => '#dc2626'],
        ['name' => 'الرياضيات', 'color' => '#2563eb'],
        ['name' => 'العلوم الفيزيائية والتكنولوجيا', 'color' => '#7c3aed'],
        ['name' => 'علوم الطبيعة والحياة', 'color' => '#16a34a'],
        ['name' => 'التاريخ والجغرافيا', 'color' => '#d97706'],
        ['name' => 'التربية الإسلامية', 'color' => '#0d9488'],
        ['name' => 'التربية المدنية', 'color' => '#0891b2'],
        ['name' => 'اللغة الفرنسية', 'color' => '#4f46e5'],
        ['name' => 'اللغة الإنجليزية', 'color' => '#c026d3'],
        ['name' => 'الإعلام الآلي', 'color' => '#0284c7'],
        ['name' => 'التربية البدنية والرياضية', 'color' => '#ea580c'],
        ['name' => 'اللغة الأمازيغية', 'color' => '#65a30d'],
        ['name' => 'التربية الفنية', 'color' => '#db2777'],
        ['name' => 'التربية الموسيقية', 'color' => '#059669'],
    ];

    /**
     * Run the foundational database seeds on the specified connection.
     */
    public function run(?string $connection = null): void
    {
        $db = $connection ? DB::connection($connection) : DB::connection();
        $now = now();

        // 1. Developer User Only
        $db->table('users')->updateOrInsert(
            ['email' => 'dev@sajel.test'],
            [
                'name' => 'مطور المنصة',
                'password' => Hash::make('password'),
                'role' => Role::Developer->value,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 2. Platform Settings
        $db->table('platform_settings')->updateOrInsert(
            ['id' => 1],
            [
                'platform_name' => 'سَجِّل — Sajel',
                'platform_slogan' => 'المنظومة الرقمية الشاملة لإدارة وتسيير المؤسسات التربوية',
                'meta_description' => 'منصة الرقمنة الشاملة للمؤسسات التعليمية الجزائرية',
                'primary_color' => '#d97706',
                'developer_name' => 'مطور المنصة',
                'developer_organization' => 'فريق التطوير والرقمنة',
                'developer_website' => 'https://sajel.dev',
                'developer_support_email' => 'support@sajel.dev',
                'version_tag' => 'v2.0.0-DZ',
                'footer_copyright_text' => 'جميع الحقوق محفوظة © 2026 — منصة سجّل الرقمية',
                'is_maintenance_mode' => 0,
                'enable_public_registration' => 0,
                'announcement_active' => 0,
                'enable_ai_assistant' => 1,
                'ai_default_model' => 'gemini-2.0-flash',
                'max_file_upload_mb' => 20,
                'session_lifetime_minutes' => 120,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 3. School Settings
        $db->table('school_settings')->updateOrInsert(
            ['id' => 1],
            [
                'school_name' => 'المؤسسة التربوية النموذجية',
                'directorate_name' => 'مديرية التربية الوطنية',
                'academic_year' => '2026-2027',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 4. Base Educational Institution
        $db->table('educational_institutions')->updateOrInsert(
            ['code' => '00-0000-0001'],
            [
                'name' => 'المؤسسة التربوية النموذجية',
                'code' => '00-0000-0001',
                'stage' => 'middle',
                'directorate_name' => 'مديرية التربية الوطنية',
                'status' => 'active',
                'is_current' => 1,
                'academic_year' => '2026-2027',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 5. Standard National Subjects
        foreach (self::SUBJECTS as $subject) {
            $db->table('subjects')->updateOrInsert(
                ['name' => $subject['name']],
                [
                    'color' => $subject['color'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 6. Standard Algerian Education Phases
        $phases = [
            [
                'code' => 'PRI',
                'name' => 'التعليم الابتدائي',
                'phase_abbreviation' => 'ب',
                'settings' => json_encode(['period_minutes' => 45, 'default_status' => DailyLogStatus::Planned->value]),
            ],
            [
                'code' => 'CEM',
                'name' => 'التعليم المتوسط',
                'phase_abbreviation' => 'م',
                'settings' => json_encode(['period_minutes' => 50, 'default_status' => DailyLogStatus::Planned->value]),
            ],
            [
                'code' => 'LYC',
                'name' => 'التعليم الثانوي',
                'phase_abbreviation' => 'ث',
                'settings' => json_encode(['period_minutes' => 55, 'default_status' => DailyLogStatus::Planned->value]),
            ],
        ];

        foreach ($phases as $phaseData) {
            $existing = $db->table('education_phases')->where('code', $phaseData['code'])->first();
            if ($existing) {
                $phaseId = $existing->id;
                $db->table('education_phases')->where('id', $phaseId)->update([
                    'name' => $phaseData['name'],
                    'phase_abbreviation' => $phaseData['phase_abbreviation'],
                    'settings' => $phaseData['settings'],
                    'updated_at' => $now,
                ]);
            } else {
                $phaseId = $db->table('education_phases')->insertGetId($phaseData + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Standard Levels per Phase
            $levels = match ($phaseData['code']) {
                'PRI' => [
                    ['name' => 'السنة الأولى ابتدائي', 'grade_number' => 1],
                    ['name' => 'السنة الثانية ابتدائي', 'grade_number' => 2],
                    ['name' => 'السنة الثالثة ابتدائي', 'grade_number' => 3],
                    ['name' => 'السنة الرابعة ابتدائي', 'grade_number' => 4],
                    ['name' => 'السنة الخامسة ابتدائي', 'grade_number' => 5],
                ],
                'CEM' => [
                    ['name' => 'السنة الأولى متوسط', 'grade_number' => 1],
                    ['name' => 'السنة الثانية متوسط', 'grade_number' => 2],
                    ['name' => 'السنة الثالثة متوسط', 'grade_number' => 3],
                    ['name' => 'السنة الرابعة متوسط', 'grade_number' => 4],
                ],
                'LYC' => [
                    ['name' => 'السنة الأولى ثانوي', 'grade_number' => 1],
                    ['name' => 'السنة الثانية ثانوي', 'grade_number' => 2],
                    ['name' => 'السنة الثالثة ثانوي', 'grade_number' => 3],
                ],
                default => [],
            };

            foreach ($levels as $levelData) {
                $db->table('levels')->updateOrInsert(
                    [
                        'education_phase_id' => $phaseId,
                        'name' => $levelData['name'],
                    ],
                    [
                        'grade_number' => $levelData['grade_number'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        // 7. Official National & Religious School Calendar
        $holidays = [
            [
                'title' => 'عطلة الخريف',
                'start_date' => '2026-10-29',
                'end_date' => '2026-11-02',
                'type' => 'seasonal',
                'notes' => 'عطلة فصلية رسمية — السنة الدراسية 2026-2027.',
            ],
            [
                'title' => 'عيد اندلاع الثورة التحريرية (01 نوفمبر)',
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-01',
                'type' => 'national',
                'notes' => 'عطلة وطنية رسمية مدفوعة الأجر.',
            ],
            [
                'title' => 'عطلة الشتاء',
                'start_date' => '2026-12-24',
                'end_date' => '2027-01-04',
                'type' => 'seasonal',
                'notes' => 'عطلة فصلية رسمية بين الفصل الأول والفصل الثاني.',
            ],
            [
                'title' => 'رأس السنة الأمازيغية (ينّاير)',
                'start_date' => '2027-01-12',
                'end_date' => '2027-01-12',
                'type' => 'national',
                'notes' => 'عطلة وطنية رسمية.',
            ],
            [
                'title' => 'عطلة الربيع',
                'start_date' => '2027-03-18',
                'end_date' => '2027-03-29',
                'type' => 'seasonal',
                'notes' => 'عطلة فصلية رسمية بين الفصل الثاني والفصل الثالث.',
            ],
            [
                'title' => 'عيد الفطر المبارك',
                'start_date' => '2027-03-31',
                'end_date' => '2027-04-02',
                'type' => 'religious',
                'notes' => 'عطلة دينية رسمية (3 أيام).',
            ],
            [
                'title' => 'عيد العمال العالمي (01 ماي)',
                'start_date' => '2027-05-01',
                'end_date' => '2027-05-01',
                'type' => 'national',
                'notes' => 'عطلة وطنية رسمية.',
            ],
            [
                'title' => 'عيد الأضحى المبارك',
                'start_date' => '2027-06-06',
                'end_date' => '2027-06-08',
                'type' => 'religious',
                'notes' => 'عطلة دينية رسمية (3 أيام).',
            ],
            [
                'title' => 'عيد الاستقلال والشباب (05 جويلية)',
                'start_date' => '2027-07-05',
                'end_date' => '2027-07-05',
                'type' => 'national',
                'notes' => 'عطلة وطنية رسمية.',
            ],
        ];

        foreach ($holidays as $holiday) {
            $db->table('school_holidays')->updateOrInsert(
                ['title' => $holiday['title'], 'start_date' => $holiday['start_date']],
                $holiday + ['created_at' => $now, 'updated_at' => $now]
            );
        }

        // 8. Approved Active Learning Strategies
        $strategies = [
            [
                'name' => 'التفكير — المشاركة — العرض (Think-Pair-Share)',
                'slug' => 'think-pair-share',
                'description' => 'يُطلب من التلاميذ التفكير فردياً، ثم المناقشة مع زميل، ثم عرض الأفكار على المجموعة.',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'color' => 'primary',
                'tags' => json_encode(['collaborative', 'discussion']),
                'is_active' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => 'بطاقة الخروج (Exit Ticket)',
                'slug' => 'exit-ticket',
                'description' => 'في نهاية الحصة، يُجيب كل تلميذ على سؤال سريع لتقييم الفهم واستخلاص المخرجات.',
                'icon' => 'heroicon-o-ticket',
                'color' => 'warning',
                'tags' => json_encode(['assessment', 'individual']),
                'is_active' => 1,
                'sort_order' => 2,
            ],
            [
                'name' => 'العصف الذهني المنظم (Brainstorming)',
                'slug' => 'brainstorming',
                'description' => 'توليد أكبر قدر من الأفكار والحلول الإبداعية حول إشكالية الدرس دون حكم مسبق.',
                'icon' => 'heroicon-o-light-bulb',
                'color' => 'info',
                'tags' => json_encode(['creativity', 'ideation']),
                'is_active' => 1,
                'sort_order' => 3,
            ],
            [
                'name' => 'الرؤوس المرقمة معاً (Numbered Heads)',
                'slug' => 'numbered-heads',
                'description' => 'تعاون جماعي في فرق رباعية حيث يحمل كل تلميذ رقماً، ويجيب صاحب الرقم المختار لتمثيل فريقه.',
                'icon' => 'heroicon-o-user-group',
                'color' => 'success',
                'tags' => json_encode(['teamwork', 'accountability']),
                'is_active' => 1,
                'sort_order' => 4,
            ],
            [
                'name' => 'الخرائط الذهنية والمفاهيمية (Mind Mapping)',
                'slug' => 'mind-mapping',
                'description' => 'تمثيل بصري تفاعلي للمفاهيم والمصطلحات والروابط المنطقية بين عناصر الدرس.',
                'icon' => 'heroicon-o-puzzle-piece',
                'color' => 'purple',
                'tags' => json_encode(['visual', 'structuring']),
                'is_active' => 1,
                'sort_order' => 5,
            ],
            [
                'name' => 'حل المشكلات والتعلم بالاكتشاف (PBL)',
                'slug' => 'problem-solving',
                'description' => 'وضع التلاميذ أمام وضعية مشكلة واقعية واستدراجهم للبحث والتحليل واستخلاص القواعد.',
                'icon' => 'heroicon-o-academic-cap',
                'color' => 'amber',
                'tags' => json_encode(['inquiry', 'critical-thinking']),
                'is_active' => 1,
                'sort_order' => 6,
            ],
        ];

        foreach ($strategies as $strategy) {
            $db->table('active_learning_strategies')->updateOrInsert(
                ['slug' => $strategy['slug']],
                $strategy + ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
