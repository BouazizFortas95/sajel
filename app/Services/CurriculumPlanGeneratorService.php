<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\EducationPhase;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CurriculumPlanGeneratorService
{
    /**
     * Resolve level number (1, 2, 3, 4) from Level model.
     */
    public function resolveLevelNumber(Level $level): int
    {
        $name = $level->name;
        if (str_contains($name, 'رابع') || str_contains($name, '4')) {
            return 4;
        }
        if (str_contains($name, 'ثالث') || str_contains($name, '3')) {
            return 3;
        }
        if (str_contains($name, 'ثاني') || str_contains($name, '2')) {
            return 2;
        }

        return 1;
    }

    /**
     * Map level number (1..4) to the middle school Level model in the database.
     */
    public function findLevelByNumber(int $num): ?Level
    {
        $patterns = [
            1 => ['%الأولى%متوسط%', '%1%متوسط%', '%1AM%'],
            2 => ['%الثانية%متوسط%', '%2%متوسط%', '%2AM%'],
            3 => ['%الثالثة%متوسط%', '%3%متوسط%', '%3AM%'],
            4 => ['%الرابعة%متوسط%', '%4%متوسط%', '%4AM%'],
        ];

        foreach ($patterns[$num] ?? [] as $pattern) {
            $lvl = Level::where('name', 'like', $pattern)->first();
            if ($lvl) {
                return $lvl;
            }
        }

        $defaultNames = [
            1 => 'السنة الأولى متوسط',
            2 => 'السنة الثانية متوسط',
            3 => 'السنة الثالثة متوسط',
            4 => 'السنة الرابعة متوسط',
        ];

        $phase = EducationPhase::firstOrCreate(
            ['name' => 'التعليم المتوسط'],
            ['code' => 'middle', 'sort_order' => 2]
        );

        return Level::firstOrCreate(
            ['name' => $defaultNames[$num] ?? "السنة {$num} متوسط"],
            [
                'education_phase_id' => $phase->id,
                'grade_number' => $num,
            ]
        );
    }

    /**
     * Generate or seed curriculum domains, sequences, and resources for a subject and level.
     *
     * @return array<string, mixed>
     */
    public function generateForSubjectAndLevel(Subject $subject, Level $level, bool $force = false): array
    {
        $existingCount = CurriculumDomain::where('subject_id', $subject->id)
            ->where('level_id', $level->id)
            ->count();

        if ($existingCount > 0 && ! $force) {
            return [
                'status' => 'already_exists',
                'domains_count' => $existingCount,
                'subject' => $subject->name,
                'level' => $level->name,
            ];
        }

        $levelNum = $this->resolveLevelNumber($level);
        $curriculum = $this->getCurriculumData($subject->name, $levelNum);

        $domainOrder = 1;
        $totalSequences = 0;
        $totalResources = 0;

        DB::transaction(function () use ($subject, $level, $curriculum, &$domainOrder, &$totalSequences, &$totalResources): void {
            foreach ($curriculum as $dData) {
                $domain = CurriculumDomain::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'level_id' => $level->id,
                        'name' => $dData['name'],
                    ],
                    [
                        'description' => $dData['competence'] ?? $dData['name'],
                        'sort_order' => $domainOrder++,
                    ]
                );

                $sequenceOrder = 1;
                foreach ($dData['sequences'] as $sData) {
                    $sequence = CurriculumSequence::updateOrCreate(
                        [
                            'curriculum_domain_id' => $domain->id,
                            'name' => $sData['name'],
                        ],
                        [
                            'description' => $sData['description'] ?? $sData['name'],
                            'sort_order' => $sequenceOrder++,
                        ]
                    );
                    $totalSequences++;

                    $resourceOrder = 1;
                    foreach ($sData['resources'] as $resName) {
                        CurriculumResource::updateOrCreate(
                            [
                                'curriculum_sequence_id' => $sequence->id,
                                'name' => $resName,
                            ],
                            [
                                'sort_order' => $resourceOrder++,
                            ]
                        );
                        $totalResources++;
                    }
                }
            }
        });

        return [
            'status' => 'created',
            'subject' => $subject->name,
            'level' => $level->name,
            'domains_count' => count($curriculum),
            'sequences_count' => $totalSequences,
            'resources_count' => $totalResources,
        ];
    }

    /**
     * Generate complete curriculum and schedules for a specific teacher.
     *
     * @param  array<int>|null  $levels
     * @return array<string, mixed>
     */
    public function generateForTeacher(User $teacher, ?Subject $subject = null, ?array $levels = null): array
    {
        $teacherSubject = $subject ?: $this->resolveTeacherSubject($teacher);
        $targetLevels = $levels ?: [1, 2, 3, 4];

        $results = [];

        foreach ($targetLevels as $lvlNum) {
            $levelModel = $this->findLevelByNumber((int) $lvlNum);
            if (! $levelModel) {
                continue;
            }

            $genResult = $this->generateForSubjectAndLevel($teacherSubject, $levelModel, false);
            $results[$lvlNum] = $genResult;

            // Ensure teacher has at least one active schedule in a classroom of this level
            $this->ensureTeacherSchedule($teacher, $teacherSubject, $levelModel);
        }

        return [
            'teacher' => $teacher->name,
            'subject' => $teacherSubject->name,
            'levels' => $results,
        ];
    }

    /**
     * Ensure a teacher has at least one active timetable schedule for the given level.
     */
    public function ensureTeacherSchedule(User $teacher, Subject $subject, Level $level): void
    {
        $hasSchedule = Schedule::where('user_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->whereHas('classroom', fn ($q) => $q->where('level_id', $level->id))
            ->exists();

        if ($hasSchedule) {
            return;
        }

        $classroom = Classroom::where('level_id', $level->id)->orderBy('id')->first();
        if (! $classroom) {
            $classroom = Classroom::create([
                'name' => "{$level->name} 1",
                'level_id' => $level->id,
            ]);
        }

        Schedule::create([
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'day_of_week' => 'sunday',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'valid_from' => '2026-09-20',
        ]);
    }

    /**
     * Resolve the primary teaching subject of a teacher.
     */
    public function resolveTeacherSubject(User $teacher): Subject
    {
        if ($teacher->primary_subject_id) {
            $subj = Subject::find($teacher->primary_subject_id);
            if ($subj) {
                return $subj;
            }
        }

        $existingSubjectId = $teacher->schedules()->value('subject_id');
        if ($existingSubjectId) {
            $subj = Subject::find($existingSubjectId);
            if ($subj) {
                return $subj;
            }
        }

        if (str_contains($teacher->name, 'فرطاس') || str_contains($teacher->name, 'بوعزيز')) {
            $infoSubj = Subject::where('name', 'like', '%إعلام%')->orWhere('name', 'like', '%معلوماتية%')->first();
            if ($infoSubj) {
                return $infoSubj;
            }
        }

        // Try matching by subject or default
        return Subject::where('name', 'like', '%إعلام%')
            ->orWhere('name', 'like', '%معلوماتية%')
            ->orWhere('name', 'like', '%رياضيات%')
            ->orWhere('name', 'like', '%عربية%')
            ->orWhere('name', 'like', '%فيزياء%')
            ->first() ?? Subject::firstOrCreate(['name' => 'المعلوماتية', 'code' => 'INFO']);
    }

    /**
     * Retrieve authentic Algerian middle school curriculum definitions.
     *
     * @return list<array<string, mixed>>
     */
    public function getCurriculumData(string $subjectName, int $level): array
    {
        $clean = trim($subjectName);

        if (str_contains($clean, 'رياضيات')) {
            return $this->getMathCurriculum($level);
        }

        if (str_contains($clean, 'فيزياء') || str_contains($clean, 'فيزيائية')) {
            return $this->getPhysicsCurriculum($level);
        }

        if (str_contains($clean, 'طبيعة') || str_contains($clean, 'علوم الطبيعة') || str_contains($clean, 'أحياء')) {
            return $this->getNaturalSciencesCurriculum($level);
        }

        if (str_contains($clean, 'عربية') || str_contains($clean, 'لغة عربية')) {
            return $this->getArabicCurriculum($level);
        }

        if (str_contains($clean, 'تاريخ') || str_contains($clean, 'جغرافيا')) {
            return $this->getHistoryGeoCurriculum($level);
        }

        if (str_contains($clean, 'إسلامية')) {
            return $this->getIslamicEducationCurriculum($level);
        }

        if (str_contains($clean, 'مدنية')) {
            return $this->getCivicEducationCurriculum($level);
        }

        if (str_contains($clean, 'فرنسية') || str_contains($clean, 'français')) {
            return $this->getFrenchCurriculum($level);
        }

        if (str_contains($clean, 'إنجليزية') || str_contains($clean, 'english')) {
            return $this->getEnglishCurriculum($level);
        }

        // Default: Mathematics baseline curriculum structure
        return $this->getMathCurriculum($level);
    }

    /**
     * Mathematics (الرياضيات) authentic middle school curriculum.
     */
    private function getMathCurriculum(int $level): array
    {
        if ($level === 1) {
            return [
                [
                    'name' => 'أنشطة عددية',
                    'competence' => 'يتحكم في الحساب على الأعداد الطبيعية والعشرية ويوظف الكسور والأعداد النسبية لحل مسائل رياضية.',
                    'sequences' => [
                        [
                            'name' => 'الأعداد الطبيعية والأعداد العشرية',
                            'resources' => [
                                'استقبال التلاميذ والتذكير بالمكتسبات القبلية',
                                'الكتابات المختلفة لعدد عشري ودلالة الأرقام',
                                'مقارنة وترتيب أعداد عشرية ومستقيم مدرج',
                                'جمع وطرح أعداد عشرية ورتبة مقدار',
                                'ضرب أعداد عشرية والحساب على قوى 10',
                                'القسمة الإقليدية والقسمة العشرية وحاصل القسمة المقرب',
                            ],
                        ],
                        [
                            'name' => 'الكتابات الكسرية',
                            'resources' => [
                                'مفهوم حاصل القسمة والكسر',
                                'الكسر كحاصل قسمة وكتابات كسرية متكافئة',
                                'اختزال الكسور وأخذ كسر من عدد',
                                'مقارنة كسرين لهما نفس المقام والعمليات البسيطة',
                            ],
                        ],
                        [
                            'name' => 'الأعداد النسبية',
                            'resources' => [
                                'التعرف على الأعداد النسبية والمسافة إلى الصفر',
                                'تعليم نقطة على مستقيم مدرج ومقارنة عددين نسبيين',
                                'التعليم في المستوي بواسطة المعلم المتعامد والمتجانس',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'أنشطة هندسية',
                    'competence' => 'يتعرف على الأشكال والمجسمات الأساسية ويوظف خواص التناظر المحوري ومفاهيم القياس.',
                    'sequences' => [
                        [
                            'name' => 'المفاهيم الهندسية الأساسية',
                            'resources' => [
                                'المستقيم ونصف المستقيم والقطعة المستقيمة والمنتصف',
                                'المستقيمات المتعامدة والمتوازية ورسمها باستعمال الأدوات',
                                'الدائرة وعناصرها وقوس الدائرة',
                            ],
                        ],
                        [
                            'name' => 'التناظر المحوري',
                            'resources' => [
                                'مفهوم الشكلين المتناظرين بالنسبة إلى مستقيم',
                                'نظير نقطة وقطعة مستقيمة ومستقيم',
                                'نظير زاوية ونظير دائرة وخواص التناظر المحوري',
                                'محور قطعة مستقيمة ومنصف الزاوية وإنشاؤهما',
                            ],
                        ],
                        [
                            'name' => 'المثلثات والرباعيات الخاصة',
                            'resources' => [
                                'المثلثات الخاصة (القائم، المتساوي الساقين، المتقايس الأضلاع)',
                                'المستطيل والمربع والمعين وخواص الأقطار',
                                'حساب المحيطات والمساحات للأشكال البسيطة والمركبة',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'تنظيم معطيات ودوال',
                    'competence' => 'يوظف التناسبية والنسب المئوية ويستخرج ويعالج معطيات إحصائية بسيطة.',
                    'sequences' => [
                        [
                            'name' => 'التناسبية',
                            'resources' => [
                                'التعرف على وضعية تناسبية وجدول التناسبية',
                                'حساب معامل التناسبية وتطبيق النسبة المئوية',
                                'مقياس الرسم واستعماله في خرائط ومخططات مصغرة',
                            ],
                        ],
                        [
                            'name' => 'تنظيم وتمثيل معطيات',
                            'resources' => [
                                'قراءة واستخراج معلومات من جداول ومخططات إحصائية',
                                'تمثيل المعطيات بمخطط بالأعمدة ومخطط دائري',
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($level === 2) {
            return [
                [
                    'name' => 'أنشطة عددية',
                    'competence' => 'يمارس الحساب على الأعداد النسبية والكسور ويوظف المعادلات والتناسبية.',
                    'sequences' => [
                        [
                            'name' => 'العمليات على الأعداد النسبية',
                            'resources' => [
                                'جمع وطرح عددين نسبيين ومجموع جبري',
                                'المسافة بين نقطتين على مستقيم مدرج',
                                'ضرب وقسمة أعداد نسبية وقاعدة الإشارات',
                            ],
                        ],
                        [
                            'name' => 'الكسور والأعداد الناطقة',
                            'resources' => [
                                'مقارنة وترتيب كسور ذات مقامات مختلفة',
                                'جمع وطرح كسرين وضرب الكسور',
                            ],
                        ],
                        [
                            'name' => 'الحساب الحرفي والمعادلات',
                            'resources' => [
                                'كتابة وتبسيط عبارة حرفية واختبار صحة مساواة',
                                'حل معادلة من الشكل a+x=b و ax=b',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'أنشطة هندسية',
                    'competence' => 'يوظف خواص التناظر المركزي والزوايا ومتوازي الأضلاع لحل مشكلات هندسية برهانية.',
                    'sequences' => [
                        [
                            'name' => 'التناظر المركزي',
                            'resources' => [
                                'مفهوم التناظر المركزي ونظير شكل بالنسبة لنقطة',
                                'خواص التناظر المركزي وحفظ المسافات والمساحات والزوايا',
                                'مركز تناظر شكل هندسي ومحاور التناظر',
                            ],
                        ],
                        [
                            'name' => 'الزوايا والمثلثات',
                            'resources' => [
                                'الزوايا المتبادلة داخلياً والمتماثلة والمتقابلة بالرأس',
                                'مجموع قياسات زوايا مثلث والمتباينة المثلثية',
                                'المستقيمات الخاصة في مثلث (المنصفات والارتفاعات والمحاور)',
                            ],
                        ],
                        [
                            'name' => 'متوازي الأضلاع وحالاته الخاصة',
                            'resources' => [
                                'متوازي الأضلاع وخواص أضلاعه وزواياه وأقطاره',
                                'المستطيل والمربع والمعين واستعمال خواصها في البرهان',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'تنظيم معطيات',
                    'competence' => 'يوظف التناسبية ومقياس المخططات ويفسر مؤشرات السلاسل الإحصائية.',
                    'sequences' => [
                        [
                            'name' => 'التناسبية والنسب المئوية',
                            'resources' => [
                                'الرابع المتناسب ومقياس التصغير والتكبير',
                                'حركة منتظمة والسرعة المتوسطة وتحويل الوحدات',
                            ],
                        ],
                        [
                            'name' => 'الإحصاء',
                            'resources' => [
                                'التكرار والتكرار النسبي والتواتر المئوي',
                                'الوسط الحسابي لسلسلة إحصائية وتمثيلها بيانياً',
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($level === 3) {
            return [
                [
                    'name' => 'أنشطة عددية',
                    'competence' => 'يتحكم في العمليات على الأعداد الناطقة وقوى 10 والحساب الحرفي.',
                    'sequences' => [
                        [
                            'name' => 'الأعداد الناطقة والحساب عليها',
                            'resources' => [
                                'تعريف الأعداد الناطقة وتعيين إشارة عدد ناطق',
                                'جمع وطرح وضرب وقسمة أعداد ناطقة وتعيين مقلوب',
                            ],
                        ],
                        [
                            'name' => 'قوى العدد 10 والحساب العلمي',
                            'resources' => [
                                'قوى ذات أسس صحيحة نسبية وقواعد الحساب عليها',
                                'الكتابة العلمية وحصر عدد ورتبة قدر',
                            ],
                        ],
                        [
                            'name' => 'الحساب الحرفي والمعادلات',
                            'resources' => [
                                'نشر وتبسيط عبارة جبرية واستعمال خاصية التوزيع',
                                'حل معادلة من الدرجة الأولى بمجهول واحد وترييض مشكل',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'أنشطة هندسية',
                    'competence' => 'يبرهن باستعمال مبرهنة فيثاغورس وجيب تمام زاوية حادة وخواص المثلث القائم والدائرة والانسحاب.',
                    'sequences' => [
                        [
                            'name' => 'المثلث القائم والدائرة',
                            'resources' => [
                                'المثلث القائم والدائرة المحيطة به وخاصية المتوسط المتعلق بالوتر',
                                'مبرهنة فيثاغورس وخاصيتها العكسية لحساب الأطوال والبرهان',
                                'جيب تمام زاوية حادة (cos) وحساب الأطوال والزوايا بالآلة الحاسبة',
                            ],
                        ],
                        [
                            'name' => 'الانسحاب والأشعة والمجسمات',
                            'resources' => [
                                'مفهوم الانسحاب وصورة نقطة ومستقيم وشكل هندسي',
                                'الهرم ومخروط الدوران ووصفهما وحساب الحجوم',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'تنظيم معطيات والدوال',
                    'competence' => 'يميز الوضعيات التناسبية ويوظف الدوال الخطية والإحصاء في حل مسائل حياتية.',
                    'sequences' => [
                        [
                            'name' => 'التناسبية والتمثيل البياني',
                            'resources' => [
                                'التعرف على التناسبية بيانياً ومعامل التناسبية',
                                'المقادير المركبة والسرعة المتوسطة والتدفق',
                            ],
                        ],
                        [
                            'name' => 'تنظيم معطيات إحصائية',
                            'resources' => [
                                'تجميع المعطيات في فئات والمدرج التكراري',
                                'الوسط الحسابي المتوازن وتفسير النتائج',
                            ],
                        ],
                    ],
                ],
            ];
        }

        // Level 4 (4AM - BEM)
        return [
            [
                'name' => 'أنشطة عددية',
                'competence' => 'يتحكم في الحساب على الجذور التربيعية والـ PGCD والمتطابقات الشهيرة وحل جمل المعادلات.',
                'sequences' => [
                    [
                        'name' => 'الأعداد الطبيعية والناطقة والجذور التربيعية',
                        'resources' => [
                            'قواسم عدد طبيعي والبحث عن القاسم المشترك الأكبر PGCD بخوارزمية إقليدس',
                            'العددان الأوليان فيما بينهما والكسور غير القابلة للاختزال',
                            'مفهوم الجذر التربيعي وقواعد الحساب على الجذور',
                            'تبسيط عبارة تتضمن جذوراً تربيعية وجعل مقام نسبة ناطقاً',
                        ],
                    ],
                    [
                        'name' => 'الحساب الحرفي والمعادلات والمتراجحات',
                        'resources' => [
                            'المتطابقات الشهيرة ونشر وتبسيط عبارات جبرية',
                            'التحليل باستعمال العامل المشترك والمتطابقات الشهيرة',
                            'معادلة الجداء المعدوم وحل متراجحة من الدرجة الأولى وتمثيل حلولها بيانياً',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'أنشطة هندسية',
                'competence' => 'يوظف مبرهنة طالس والنسب المثلثية في المثلث القائم والأشعة والمعالم والدوران للبرهان الهندسي.',
                'sequences' => [
                    [
                        'name' => 'خاصية طالس وحساب المثلثات',
                        'resources' => [
                            'خاصية طالس لحساب الأطوال والخاصية العكسية لإثبات التوازي',
                            'النسب المثلثية في المثلث القائم (sin, cos, tan) والعلاقات الأساسية',
                            'حساب زوايا وأطوال وتوظيف النسب المثلثية في حل مشكلات واقعية',
                        ],
                    ],
                    [
                        'name' => 'الأشعة والمعالم والدوران',
                        'resources' => [
                            'مفهوم الشعاع وتساوي شعاعين ومحصلة شعاعين بعلاقة شال',
                            'إحداثيتا نقطة وإحداثيتا منتصف قطعة وإحداثيتا شعاع وحساب مسافة بين نقطتين',
                            'الدوران وخواصه والزوايا المحيطية والمركزية والرباعي الدائري',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'تنظيم معطيات والدوال',
                'competence' => 'ينمذج وضعيات واقعية بالدوال الخطية والتآلفية وجمل المعادلات ويفسر المؤشرات الإحصائية.',
                'sequences' => [
                    [
                        'name' => 'الدوال الخطية والتآلفية وجمل المعادلات',
                        'resources' => [
                            'تعريف الدالة الخطية والتمثيل البياني وقراءة معامل التوجيه',
                            'تعريف الدالة التآلفية والتمثيل البياني وإيجاد عبارة الدالة',
                            'حل جملة معادلتين من الدرجة الأولى بمجهولين جبرياً وبيانياً وترييض مشكلات',
                        ],
                    ],
                    [
                        'name' => 'الإحصاء والتحضير لامتحان BEM',
                        'resources' => [
                            'الوسيط والمدى والتكرار المجمع الصاعد والنازل',
                            'مسائل إدماجية شاملة وتحضير مكثف لشهادة التعليم المتوسط BEM',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Physical Sciences (العلوم الفيزيائية والتكنولوجيا) curriculum.
     */
    private function getPhysicsCurriculum(int $level): array
    {
        return [
            [
                'name' => 'المادة وتحولاتها',
                'competence' => 'يميز التحولات الفيزيائية والكيميائية ويفسر انحفاظ الكتلة على المستويين العياني والمجهري.',
                'sequences' => [
                    [
                        'name' => 'القياسات والتحولات الكيميائية',
                        'resources' => [
                            'استقبال التلاميذ والتذكير بقواعد السلامة في المخبر',
                            'التحول الفيزيائي والتحول الكيميائي ومقارنة الخواص',
                            'انحفاظ الكتلة أثناء التحولات الكيميائية وتجارب الإثبات',
                            'النموذج الحبيبي والذرات والجزيئات والصيغ الكيميائية البسيطة',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'الظواهر الميكانيكية',
                'competence' => 'يحدد حالة حركة الأجسام وسرعتها ويوظف مفاهيم المسار ونقل الحركة.',
                'sequences' => [
                    [
                        'name' => 'الحركة ونسبية المسار والسرعة',
                        'resources' => [
                            'نسبية الحركة والمرجع والمسار وتحديد نوع الحركة',
                            'أنواع السرعة (ثابتة، متزايدة، متناقصة) ومخطط السرعة',
                            'نقل الحركة بالاحتكاك والسيور والتعشيق وتطبيقاتها الصناعية',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'الظواهر الكهربائية',
                'competence' => 'يوظف القوانين الأساسية للدارات الكهربائية ومفاهيم التيار والتوتر وقواعد الأمن الكهربائي.',
                'sequences' => [
                    [
                        'name' => 'الدارات الكهربائية والأمن الكهربائي',
                        'resources' => [
                            'الدارة الكهربائية البسيطة والمخطط النظامي والرموز',
                            'الربط على التسلسل والربط على التفرع وخصائص كل منهما',
                            'الدارة المستقصرة والمخاطر وطرق الوقاية المنصهرات والعوازل',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Life & Natural Sciences (علوم الطبيعة والحياة) curriculum.
     */
    private function getNaturalSciencesCurriculum(int $level): array
    {
        return [
            [
                'name' => 'الإنسان والصحة',
                'competence' => 'يحدد دور التغذية والأجهزة الوظيفية في استمرار حياة الإنسان ونموه وتوازنه الصحي.',
                'sequences' => [
                    [
                        'name' => 'التغذية والوظائف الحيوية',
                        'resources' => [
                            'استقبال التلاميذ وتحديد شروط التغذية المتوازنة',
                            'تركيب الأغذية ودورها في البناء وتوفير الطاقة والوقاية',
                            'الجهاز الهضمي والتحولات الآلية والكيميائية للأغذية',
                            'الامتصاص المعوي ونقل المغذيات إلى كافة خلايا الجسم',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'المحيط والبيئة',
                'competence' => 'يفسر التفاعل بين الكائنات الحية والوسط البيئي ويدرك آليات التوازن الطبيعي والتكتونية.',
                'sequences' => [
                    [
                        'name' => 'الأنظمة البيئية وتوزع الكائنات الحية',
                        'resources' => [
                            'عناصر الوسط الحي والعلاقات القائمة بين الكائنات الحية',
                            'السلاسل والشبكات الغذائية وتدفق الطاقة والكتلة الحية',
                            'الدينامية الداخلية للأرض والزلازل والبراكين وتكتونية الصفائح',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Arabic Language (اللغة العربية) curriculum.
     */
    private function getArabicCurriculum(int $level): array
    {
        return [
            [
                'name' => 'فهم المنطوق والتعبير الشفوي',
                'competence' => 'يصغي بوعي لنصوص متنوعة ويعبر بطلاقة مستخدماً الرصيد اللغوي المناسب وأدوات الاتصال.',
                'sequences' => [
                    [
                        'name' => 'قضايا معاصرة والمجتمع',
                        'resources' => [
                            'استقبال التلاميذ وتوجيهات بيداغوجية لقراءة النصوص',
                            'الاستماع لنص منطوق ومناقشة فكرته العامة وأفكاره الأساسية',
                            'التعبير الشفوي وتوظيف تقنيات الحوار والمناقشة البناءة',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'فهم المكتوب وقواعد اللغة',
                'competence' => 'يقرأ نصوصاً أدبية وإعلامية ويوظف قواعد النحو والصرف والإملاء في بناء المعنى.',
                'sequences' => [
                    [
                        'name' => 'دراسة النص والظواهر اللغوية',
                        'resources' => [
                            'قراءة نص وتحليله وتحديد النمط والمحسنات البديعية',
                            'الظاهرة الإعرابية والبناء النحوي للأسماء والأفعال والحروف',
                            'الظواهر الصرفية والإملائية والهمزات وعلامات الترقيم',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'الإنتاج الكتابي',
                'competence' => 'ينتج نصوصاً وفق أنماط محددة (سردي، وصفي، تفسيري، حجاجي) مراعياً معايير الانسجام والسلامة اللغوية.',
                'sequences' => [
                    [
                        'name' => 'كتابة النصوص والتعبير الكتابي',
                        'resources' => [
                            'تقنيات كتابة مقال وتلخيص نص مع الحفاظ على الأفكار',
                            'إنتاج وضعيات إدماجية مركبة وتصحيحها الفردي والجماعي',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * History & Geography (التاريخ والجغرافيا) curriculum.
     */
    private function getHistoryGeoCurriculum(int $level): array
    {
        return [
            [
                'name' => 'التاريخ',
                'competence' => 'يدرس الوثائق التاريخية ويستخلص دلالاتها ويعتز بالهوية الوطنية والتاريخ المجيد للجزائر.',
                'sequences' => [
                    [
                        'name' => 'تاريخ الجزائر والحركة الوطنية',
                        'resources' => [
                            'استقبال التلاميذ والمنهجية العلمية لدراسة الوثيقة التاريخية',
                            'الجزائر في العصور القديمة والإسلامية والعثمانية',
                            'الاحتلال الفرنسي والمقاومة الشعبية والحركة الوطنية',
                            'الثورة التحريرية الكبرى 1954-1962 واسترجاع السيادة الوطنية',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'الجغرافيا',
                'competence' => 'يحدد موقع الجزائر الجغرافي ويفسر التنوع المناخي والموارد الاقتصادية وتحديات التنمية المستدامة.',
                'sequences' => [
                    [
                        'name' => 'المجال الجغرافي والسكان والتنمية',
                        'resources' => [
                            'الموقع الجغرافي والفلكي للجزائر وأهميته الإستراتيجية',
                            'التضاريس والمناخ والشبكة الهيدروغرافية بالجزائر',
                            'التوزيع السكاني والموارد الطبيعية ومشاريع التنمية المستدامة',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Islamic Education (التربية الإسلامية) curriculum.
     */
    private function getIslamicEducationCurriculum(int $level): array
    {
        return [
            [
                'name' => 'القرآن الكريم والحديث والعقيدة',
                'competence' => 'يتلو الآيات القرآنية والأحاديث ويفهم معانيها ويستشعر قيم الإيمان بالله واليوم الآخر.',
                'sequences' => [
                    [
                        'name' => 'القرآن الكريم والسيرة النبوية',
                        'resources' => [
                            'تلاوة وحفظ سورة مقررة مع شرح المفردات والمعنى الإجمالي',
                            'شرح حديث نبوي شريف واستخراج العبر والأحكام',
                            'محطات مضيئة من السيرة النبوية العطرة وهجرة النبي ﷺ',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'العبادات والأخلاق والآداب',
                'competence' => 'يتعرف على أحكام العبادات ويتحلى بالقيم الأخلاقية والآداب السامية في تعامله مع المجتمع.',
                'sequences' => [
                    [
                        'name' => 'فقه العبادات ومكارم الأخلاق',
                        'resources' => [
                            'أحكام الطهارة والصلاة والصيام وأركانها وسننها',
                            'الأخلاق الفاضلة وبر الوالدين وحسن الجوار والإحسان',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Civic Education (التربية المدنية) curriculum.
     */
    private function getCivicEducationCurriculum(int $level): array
    {
        return [
            [
                'name' => 'الحياة الجماعية ومؤسسات الجمهورية',
                'competence' => 'يعي حقوقه وواجباته في المؤسسة التربوية والمجتمع ويفهم المبادئ الدستورية للدولة الجزائرية.',
                'sequences' => [
                    [
                        'name' => 'المؤسسة التربوية والدستور',
                        'resources' => [
                            'الحياة الجماعية والنظام الداخلي للمؤسسة التربوية',
                            'الحوار وأهميته في حل النزاعات بطرق سلمية وديمقراطية',
                            'الدستور الجزائري ومؤسسات الجمهورية والدفاع الوطني',
                            'حقوق الإنسان والمواطنة ودور المجتمع المدني',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * French (اللغة الفرنسية) curriculum.
     */
    private function getFrenchCurriculum(int $level): array
    {
        return [
            [
                'name' => 'Compréhension et production de l’oral',
                'competence' => 'Comprendre et produire des messages oraux diversifiés dans des situations de communication scolaires et sociales.',
                'sequences' => [
                    [
                        'name' => 'Oral et communication',
                        'resources' => [
                            'Accueil des élèves et écoute d’un support audio-visuel',
                            'Prise de parole et interaction communicative',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Compréhension et production de l’écrit',
                'competence' => 'Lire des textes variés, analyser leurs fonctionnements linguistiques et rédiger des textes cohérents.',
                'sequences' => [
                    [
                        'name' => 'Lecture et grammaire de texte',
                        'resources' => [
                            'Compréhension de l’écrit et analyse textuelle',
                            'Points de langue (vocabulaire, grammaire, conjugaison, orthographe)',
                            'Atelier d’écriture et production finale',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * English (اللغة الإنجليزية) curriculum.
     */
    private function getEnglishCurriculum(int $level): array
    {
        return [
            [
                'name' => 'Oral Interaction and Listening',
                'competence' => 'Listen to simple spoken English texts, comprehend main ideas, and communicate effectively.',
                'sequences' => [
                    [
                        'name' => 'Listening and Speaking',
                        'resources' => [
                            'Pupils reception and ice-breaking English activities',
                            'Listen and consider: pronunciation, phonetics and intonation',
                            'Interactive communication and daily life expressions',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Reading Comprehension and Writing',
                'competence' => 'Read diverse English texts with proper pronunciation and write structured paragraphs and messages.',
                'sequences' => [
                    [
                        'name' => 'Reading and Practice',
                        'resources' => [
                            'Reading comprehension: skimming and scanning techniques',
                            'Grammar, vocabulary in context, spelling and punctuation',
                            'Guided and free writing tasks: producing clear paragraphs',
                        ],
                    ],
                ],
            ],
        ];
    }
}
