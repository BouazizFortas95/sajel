<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AnnualCurriculumDistributionService
{
    /**
     * Build the authentic Algerian Annual Distribution dataset matching the official ministerial model
     * for all 4 middle school levels (1AM, 2AM, 3AM, 4AM).
     *
     * @return array<string, mixed>
     */
    public function getDistributionData(
        ?int $subjectId = null,
        ?int $classroomId = null,
        ?int $teacherId = null,
        ?string $academicYear = null,
        int|string|null $level = null,
    ): array {
        $school = SchoolSetting::current();
        $year = $academicYear ?? ($school?->academic_year ?? '2026 / 2027');
        $formattedYear = str_contains($year, '/') ? $year : str_replace('-', ' / ', $year);

        $subject = $subjectId ? Subject::find($subjectId) : null;
        $classroom = $classroomId ? Classroom::with('level')->find($classroomId) : null;
        $teacher = $teacherId ? User::find($teacherId) : null;

        $rawSchoolName = $school?->school_name ?? 'حي 940 مسكن - المغير';
        $cleanedSchool = trim(preg_replace('/^متوسطة\s*:?\s*/u', '', $rawSchoolName));
        $schoolName = 'متوسطة: '.($cleanedSchool ?: 'حي 940 مسكن - المغير');

        $teacherName = $teacher?->name ?? 'قرطاس بوعزيز';
        $subjectName = $subject?->name ?? 'مادة المعلوماتية';

        $levelNumber = $this->resolveLevelNumber($level, $classroom);
        $levelData = $this->getLevelData($levelNumber);

        return [
            'title' => $levelData['title'],
            'school_name' => $schoolName,
            'subject_name' => $subjectName,
            'teacher_name' => $teacherName,
            'academic_year' => $formattedYear,
            'weekly_hours' => '01 ساعة أسبوعياً',
            'coefficient' => '01',
            'domains' => $levelData['domains'],
            'level_name' => $levelData['level_name'],
            'level_number' => $levelNumber,
            'available_levels' => [
                1 => 'السنة الأولى',
                2 => 'السنة الثانية',
                3 => 'السنة الثالثة',
                4 => 'السنة الرابعة',
            ],
            'selected_subject_id' => $subjectId,
            'selected_classroom_id' => $classroomId,
        ];
    }

    /**
     * Resolve level number (1, 2, 3, or 4) from parameters or classroom relation.
     */
    public function resolveLevelNumber(int|string|null $level, ?Classroom $classroom): int
    {
        if ($level !== null) {
            $str = (string) $level;
            if (str_contains($str, '4') || str_contains($str, 'رابع')) {
                return 4;
            }
            if (str_contains($str, '3') || str_contains($str, 'ثالث')) {
                return 3;
            }
            if (str_contains($str, '2') || str_contains($str, 'ثاني')) {
                return 2;
            }
            if (str_contains($str, '1') || str_contains($str, 'أول')) {
                return 1;
            }
        }

        if ($classroom && $classroom->level) {
            $name = $classroom->level->name;
            if (str_contains($name, 'رابع') || str_contains($name, '4')) {
                return 4;
            }
            if (str_contains($name, 'ثالث') || str_contains($name, '3')) {
                return 3;
            }
            if (str_contains($name, 'ثاني') || str_contains($name, '2')) {
                return 2;
            }
            if (str_contains($name, 'أول') || str_contains($name, '1')) {
                return 1;
            }
        }

        return 1;
    }

    /**
     * Retrieve full pedagogical distribution dataset for the given middle school level.
     *
     * @return array<string, mixed>
     */
    public function getLevelData(int $level): array
    {
        $dates = [
            1 => 'من 21 سبتمبر إلى 24 سبتمبر',
            2 => 'من 27 سبتمبر إلى 01 أكتوبر',
            3 => 'من 04 أكتوبر إلى 08 أكتوبر',
            4 => 'من 11 أكتوبر إلى 15 أكتوبر',
            5 => 'من 18 أكتوبر إلى 22 أكتوبر',
            6 => 'من 25 أكتوبر إلى 29 أكتوبر',
            'vacation_autumn' => 'عطلة الخريف من مساء يوم الثلاثاء 2026/10/27 إلى صباح يوم الإثنين 2026/11/02 (7)',
            8 => 'من 02 نوفمبر إلى 05 نوفمبر',
            9 => 'من 08 نوفمبر إلى 12 نوفمبر',
            10 => 'من 15 نوفمبر إلى 19 نوفمبر',
            11 => 'من 22 نوفمبر إلى 26 نوفمبر',
            12 => 'من 29 نوفمبر إلى 03 ديسمبر',
            'exam_term_1' => 'اختبارات الفصل الأول من 06 إلى 10 ديسمبر 2026',
            13 => 'من 13 ديسمبر إلى 17 ديسمبر',
            'vacation_winter' => 'عطلة الشتاء من مساء يوم الخميس 2026/12/17 إلى صباح يوم الأحد 2027/01/03',
            14 => 'من 03 جانفي إلى 07 جانفي',
            15 => 'من 10 جانفي إلى 14 جانفي',
            16 => 'من 17 جانفي إلى 21 جانفي',
            17 => 'من 24 جانفي إلى 28 جانفي',
            18 => 'من 31 جانفي إلى 04 فيفري',
            19 => 'من 07 فيفري إلى 11 فيفري',
            20 => 'من 14 فيفري إلى 18 فيفري',
            21 => 'من 21 فيفري إلى 25 فيفري',
            'exam_term_2' => 'اختبارات الفصل الثاني: أيام 02، 03، 04 و07، 08 مارس 2027',
            22 => 'من 07 مارس إلى 11 مارس',
            23 => 'من 14 مارس إلى 18 مارس',
            'vacation_spring' => 'عطلة الربيع من مساء يوم الخميس 2027/03/18 إلى صباح يوم الأحد 2027/04/04',
            24 => 'من 04 أفريل إلى 08 أفريل',
            25 => 'من 11 أفريل إلى 15 أفريل',
            26 => 'من 18 أفريل إلى 22 أفريل',
            27 => 'من 25 أفريل إلى 29 أفريل',
            28 => 'من 02 ماي إلى 06 ماي',
            29 => 'من 09 ماي إلى 13 ماي',
            30 => 'من 16 ماي إلى 20 ماي',
            'exam_term_3' => 'اختبارات الفصل الثالث من 23 إلى 27 ماي 2027',
        ];

        // Level 1: السنة الأولى من التعليم المتوسط (1AM)
        if ($level === 1) {
            return [
                'level_name' => 'السنة الأولى من التعليم المتوسط',
                'title' => 'التوزيع السنوي للسنة الأولى من التعليم المتوسط',
                'domains' => [
                    [
                        'name' => 'استخدام الحاسوب',
                        'competence' => 'يميز بين مختلف مصطلحات المعلوماتية (العتاد والبرامج)',
                        'rowspan' => 17,
                        'rows' => [
                            ['sequence' => ['name' => 'بيئة التعامل مع الحاسوب', 'rowspan' => 5], 'lesson' => 'استقبال التلاميذ', 'hours' => '/', 'date' => $dates[1], 'week' => 1, 'type' => 'normal'],
                            ['lesson' => 'حصة تعارف', 'hours' => '01 ساعة', 'date' => $dates[2], 'week' => 2, 'type' => 'normal'],
                            ['lesson' => 'تعريف المعلوماتية والحاسوب', 'hours' => '01 ساعة', 'date' => $dates[3], 'week' => 3, 'type' => 'normal'],
                            ['lesson' => 'مكونات الحاسوب', 'hours' => '01 ساعة', 'date' => $dates[4], 'week' => 4, 'type' => 'normal'],
                            ['lesson' => 'وسائط التخزين', 'hours' => '01 ساعة', 'date' => $dates[5], 'week' => 5, 'type' => 'normal'],
                            ['sequence' => ['name' => 'نظام التشغيل', 'rowspan' => 6], 'lesson' => 'مفهوم نظام التشغيل', 'hours' => '01 ساعة', 'date' => $dates[6], 'week' => 6, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_autumn'], 'colspan' => 4],
                            ['lesson' => 'واجهة نظام التشغيل ويندوز', 'hours' => '01 ساعة', 'date' => $dates[8], 'week' => 8, 'type' => 'normal'],
                            ['lesson' => 'فرض الثلاثي الأول', 'hours' => '01 ساعة', 'date' => $dates[9], 'week' => 9, 'type' => 'exam_red'],
                            ['lesson' => 'برنامج الرسام', 'hours' => '01 ساعة', 'date' => $dates[10], 'week' => 10, 'type' => 'normal'],
                            ['lesson' => 'برنامج الدفتر', 'hours' => '01 ساعة', 'date' => $dates[11], 'week' => 11, 'type' => 'normal'],
                            ['sequence' => ['name' => 'تنظيم المعلومات في الحاسوب', 'rowspan' => 1], 'lesson' => 'مفهوم الملف والمجلد', 'hours' => '01 ساعة', 'date' => $dates[12], 'week' => 12, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_1'], 'colspan' => 5],
                            ['sequence' => ['name' => 'تقويم ومعالجة', 'rowspan' => 1], 'lesson' => 'تصحيح الاختبار والتقويم والمعالجة', 'hours' => '01 ساعة', 'date' => $dates[13], 'week' => 13, 'type' => 'remedial_yellow'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_winter'], 'colspan' => 5],
                            ['sequence' => ['name' => 'تنظيم المعلومات في الحاسوب', 'rowspan' => 2], 'lesson' => 'عمليات على الملف والمجلد', 'hours' => '01 ساعة', 'date' => $dates[14], 'week' => 14, 'type' => 'normal'],
                            ['lesson' => 'مختلف طرق العرض', 'hours' => '01 ساعة', 'date' => $dates[15], 'week' => 15, 'type' => 'normal'],
                        ],
                    ],
                    [
                        'name' => 'معالج النصوص',
                        'competence' => 'يتدرب على استعمال برنامج معالج النصوص وإنشاء وثيقة به وكيفية طباعتها',
                        'rowspan' => 18,
                        'rows' => [
                            ['sequence' => ['name' => 'تقديم معالج النصوص', 'rowspan' => 1], 'lesson' => 'تقديم معالج النصوص', 'hours' => '01 ساعة', 'date' => $dates[16], 'week' => 16, 'type' => 'normal'],
                            ['sequence' => ['name' => 'عمليات على الوثيقة', 'rowspan' => 5], 'lesson' => 'عمليات عامة على الوثيقة', 'hours' => '01 ساعة', 'date' => $dates[17], 'week' => 17, 'type' => 'normal'],
                            ['lesson' => 'كتابة نص مع توظيف المدقق الإملائي', 'hours' => '01 ساعة', 'date' => $dates[18], 'week' => 18, 'type' => 'normal'],
                            ['lesson' => 'فرض الثلاثي الثاني', 'hours' => '01 ساعة', 'date' => $dates[19], 'week' => 19, 'type' => 'exam_red'],
                            ['lesson' => 'البحث عن كلمة وتعويضها', 'hours' => '01 ساعة', 'date' => $dates[20], 'week' => 20, 'type' => 'normal'],
                            ['lesson' => 'تنسيق الخط', 'hours' => '01 ساعة', 'date' => $dates[21], 'week' => 21, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_2'], 'colspan' => 5],
                            ['sequence' => ['name' => 'تنسيق الخط والفقرة', 'rowspan' => 2], 'lesson' => 'تصحيح الاختبار وتقويم ومعالجة', 'hours' => '01 ساعة', 'date' => $dates[22], 'week' => 22, 'type' => 'remedial_yellow'],
                            ['lesson' => 'تنسيق الفقرة', 'hours' => '01 ساعة', 'date' => $dates[23], 'week' => 23, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_spring'], 'colspan' => 5],
                            ['sequence' => ['name' => 'إعداد وطباعة وثيقة', 'rowspan' => 5], 'lesson' => 'التعداد النقطي والرقمي', 'hours' => '01 ساعة', 'date' => $dates[24], 'week' => 24, 'type' => 'normal'],
                            ['lesson' => 'إعداد الصفحة', 'hours' => '01 ساعة', 'date' => $dates[25], 'week' => 25, 'type' => 'normal'],
                            ['lesson' => 'فرض الثلاثي الثالث', 'hours' => '01 ساعة', 'date' => $dates[26], 'week' => 26, 'type' => 'exam_red'],
                            ['lesson' => 'كتابة نص على شكل أعمدة', 'hours' => '01 ساعة', 'date' => $dates[27], 'week' => 27, 'type' => 'normal'],
                            ['lesson' => 'طباعة وثيقة', 'hours' => '01 ساعة', 'date' => $dates[28], 'week' => 28, 'type' => 'normal'],
                            ['sequence' => ['name' => 'إدماج ومراجعة', 'rowspan' => 2], 'lesson' => 'مشروع إدماجي للتعلمات', 'hours' => '01 ساعة', 'date' => $dates[29], 'week' => 29, 'type' => 'normal'],
                            ['lesson' => 'مراجعة عامة وتحضير للاختبارات', 'hours' => '01 ساعة', 'date' => $dates[30], 'week' => 30, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_3'], 'colspan' => 5],
                        ],
                    ],
                ],
            ];
        }

        // Level 2: السنة الثانية من التعليم المتوسط (2AM)
        if ($level === 2) {
            return [
                'level_name' => 'السنة الثانية من التعليم المتوسط',
                'title' => 'التوزيع السنوي للسنة الثانية من التعليم المتوسط',
                'domains' => [
                    [
                        'name' => 'معالج النصوص',
                        'competence' => 'يتمكن من إنجاز وثيقة شاملة تحتوي على جداول، صور، أشكال و تنظيمها.',
                        'rowspan' => 19,
                        'rows' => [
                            ['sequence' => ['name' => 'الجداول', 'rowspan' => 6], 'lesson' => 'استقبال التلاميذ', 'hours' => '/', 'date' => $dates[1], 'week' => 1, 'type' => 'normal'],
                            ['lesson' => 'حصة تعارف', 'hours' => '01 ساعة', 'date' => $dates[2], 'week' => 2, 'type' => 'normal'],
                            ['lesson' => 'إدراج جدول', 'hours' => '01 ساعة', 'date' => $dates[3], 'week' => 3, 'type' => 'normal'],
                            ['lesson' => 'تغيير عرض العمود وارتفاع السطر', 'hours' => '01 ساعة', 'date' => $dates[4], 'week' => 4, 'type' => 'normal'],
                            ['lesson' => 'التحديد والتنسيق في الجدول', 'hours' => '01 ساعة', 'date' => $dates[5], 'week' => 5, 'type' => 'normal'],
                            ['lesson' => 'إضافة وحذف أسطر وأعمدة', 'hours' => '01 ساعة', 'date' => $dates[6], 'week' => 6, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_autumn'], 'colspan' => 5],
                            ['sequence' => ['name' => 'الأشكال والصور', 'rowspan' => 5], 'lesson' => 'دمج وتقسيم الخلايا', 'hours' => '01 ساعة', 'date' => $dates[8], 'week' => 8, 'type' => 'normal'],
                            ['lesson' => 'فرض الثلاثي الأول', 'hours' => '01 ساعة', 'date' => $dates[9], 'week' => 9, 'type' => 'exam_red'],
                            ['lesson' => 'الحدود والتظليل', 'hours' => '01 ساعة', 'date' => $dates[10], 'week' => 10, 'type' => 'normal'],
                            ['lesson' => 'إدراج الأشكال وتغييرها', 'hours' => '01 ساعة', 'date' => $dates[11], 'week' => 11, 'type' => 'normal'],
                            ['lesson' => 'ترتيب الأشكال وتجميعها', 'hours' => '01 ساعة', 'date' => $dates[12], 'week' => 12, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_1'], 'colspan' => 5],
                            ['sequence' => ['name' => 'تقويم ومعالجة', 'rowspan' => 1], 'lesson' => 'تصحيح الاختبار والتقويم والمعالجة', 'hours' => '01 ساعة', 'date' => $dates[13], 'week' => 13, 'type' => 'remedial_yellow'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_winter'], 'colspan' => 5],
                            ['sequence' => ['name' => 'الأشكال والصور', 'rowspan' => 1], 'lesson' => 'إدراج صورة ونص فني', 'hours' => '01 ساعة', 'date' => $dates[14], 'week' => 14, 'type' => 'normal'],
                            ['sequence' => ['name' => 'الرموز والعبارات الرياضية', 'rowspan' => 1], 'lesson' => 'إدراج الرموز والعبارات الرياضية', 'hours' => '01 ساعة', 'date' => $dates[15], 'week' => 15, 'type' => 'normal'],
                            ['sequence' => ['name' => 'تنظيم الوثيقة', 'rowspan' => 2], 'lesson' => 'فواصل وأرقام الصفحات', 'hours' => '01 ساعة', 'date' => $dates[16], 'week' => 16, 'type' => 'normal'],
                            ['lesson' => 'رأس وتذييل الصفحة', 'hours' => '01 ساعة', 'date' => $dates[17], 'week' => 17, 'type' => 'normal'],
                        ],
                    ],
                    [
                        'name' => 'بناء المشاريع',
                        'competence' => 'يبني مهاراته الفكرية بطريقة متسلسلة ومنطقية، عبر<br>القدرة على التفكير السلس والمنطقي، بما يسمح له<br>بالتدرج في عالم البرمجة.',
                        'rowspan' => 9,
                        'rows' => [
                            ['sequence' => ['name' => 'تقديم سكراتش', 'rowspan' => 3], 'lesson' => 'مدخل إلى سكراتش', 'hours' => '01 ساعة', 'date' => $dates[18], 'week' => 18, 'type' => 'normal'],
                            ['lesson' => 'فرض الثلاثي الثاني', 'hours' => '01 ساعة', 'date' => $dates[19], 'week' => 19, 'type' => 'exam_red'],
                            ['lesson' => 'عمليات على مشروع سكراتش', 'hours' => '01 ساعة', 'date' => $dates[20], 'week' => 20, 'type' => 'normal'],
                            ['sequence' => ['name' => 'التحكم', 'rowspan' => 1], 'lesson' => 'خاصية التحكم في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[21], 'week' => 21, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_2'], 'colspan' => 5],
                            ['sequence' => ['name' => 'تقويم ومعالجة', 'rowspan' => 1], 'lesson' => 'تصحيح الاختبار وتقويم ومعالجة', 'hours' => '01 ساعة', 'date' => $dates[22], 'week' => 22, 'type' => 'remedial_yellow'],
                            ['sequence' => ['name' => 'الحركة', 'rowspan' => 1], 'lesson' => 'خاصية الحركة في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[23], 'week' => 23, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_spring'], 'colspan' => 5],
                            ['sequence' => ['name' => 'المظاهر', 'rowspan' => 1], 'lesson' => 'المظاهر في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[24], 'week' => 24, 'type' => 'normal'],
                        ],
                    ],
                    [
                        'name' => 'الشبكات',
                        'competence' => 'يتعرف على شبكة الإنترنت وبعض طرق الاتصال بها<br>وكيفية التصفح في الإنترنت.',
                        'rowspan' => 7,
                        'rows' => [
                            ['sequence' => ['name' => 'مفهوم الشبكة وأنواعها', 'rowspan' => 1], 'lesson' => 'الشبكة', 'hours' => '01 ساعة', 'date' => $dates[25], 'week' => 25, 'type' => 'normal'],
                            ['sequence' => ['name' => 'شبكة الأنترنت', 'rowspan' => 4], 'lesson' => 'فرض الثلاثي الثالث', 'hours' => '01 ساعة', 'date' => $dates[26], 'week' => 26, 'type' => 'exam_red'],
                            ['lesson' => 'شبكة الأنترنت', 'hours' => '01 ساعة', 'date' => $dates[27], 'week' => 27, 'type' => 'normal'],
                            ['lesson' => 'الملاحة في الأنترنت', 'hours' => '01 ساعة', 'date' => $dates[28], 'week' => 28, 'type' => 'normal'],
                            ['lesson' => 'التصفح في الأنترنت', 'hours' => '01 ساعة', 'date' => $dates[29], 'week' => 29, 'type' => 'normal'],
                            ['sequence' => ['name' => 'إدماج ومراجعة', 'rowspan' => 1], 'lesson' => 'إدماج كلي ومراجعة شاملة', 'hours' => '01 ساعة', 'date' => $dates[30], 'week' => 30, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_3'], 'colspan' => 5],
                        ],
                    ],
                ],
            ];
        }

        // Level 3: السنة الثالثة من التعليم المتوسط (3AM)
        if ($level === 3) {
            return [
                'level_name' => 'السنة الثالثة من التعليم المتوسط',
                'title' => 'التوزيع السنوي للسنة الثالثة من التعليم المتوسط',
                'domains' => [
                    [
                        'name' => 'بناء المشاريع',
                        'competence' => 'يبني مهاراته الفكرية بطريقة متسلسلة ومنطقية، عبر إنجاز مشاريع متعددة الوسائط.',
                        'rowspan' => 7,
                        'rows' => [
                            ['sequence' => ['name' => 'استخدام الأصوات', 'rowspan' => 3], 'lesson' => 'استقبال التلاميذ', 'hours' => '/', 'date' => $dates[1], 'week' => 1, 'type' => 'normal'],
                            ['lesson' => 'حصة تعارف', 'hours' => '01 ساعة', 'date' => $dates[2], 'week' => 2, 'type' => 'normal'],
                            ['lesson' => 'الأصوات في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[3], 'week' => 3, 'type' => 'normal'],
                            ['sequence' => ['name' => 'الرسم بالقلم', 'rowspan' => 1], 'lesson' => 'الرسم بالقلم في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[4], 'week' => 4, 'type' => 'normal'],
                            ['sequence' => ['name' => 'خاصية التحسس', 'rowspan' => 2], 'lesson' => 'خاصية التحسس في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[5], 'week' => 5, 'type' => 'normal'],
                            ['lesson' => 'حصة تمارين', 'hours' => '01 ساعة', 'date' => $dates[6], 'week' => 6, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_autumn'], 'colspan' => 5],
                        ],
                    ],
                    [
                        'name' => 'المجدول',
                        'competence' => 'يتأقلم مع المجدول لإنجاز الحسابات.',
                        'rowspan' => 10,
                        'rows' => [
                            ['sequence' => ['name' => 'تقديم المجدول', 'rowspan' => 5], 'lesson' => 'مدخل إلى المجدول', 'hours' => '01 ساعة', 'date' => $dates[8], 'week' => 8, 'type' => 'normal'],
                            ['lesson' => 'فرض الثلاثي الأول', 'hours' => '01 ساعة', 'date' => $dates[9], 'week' => 9, 'type' => 'exam_red'],
                            ['lesson' => 'المصنف وورقة العمل', 'hours' => '01 ساعة', 'date' => $dates[10], 'week' => 10, 'type' => 'normal'],
                            ['lesson' => 'التعليمة format وتنسيق الكتابة', 'hours' => '01 ساعة', 'date' => $dates[11], 'week' => 11, 'type' => 'normal'],
                            ['lesson' => 'نقل ونسخ الخلايا', 'hours' => '01 ساعة', 'date' => $dates[12], 'week' => 12, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_1'], 'colspan' => 5],
                            ['sequence' => ['name' => 'تقويم ومعالجة', 'rowspan' => 1], 'lesson' => 'تصحيح الاختبار والتقويم والمعالجة', 'hours' => '01 ساعة', 'date' => $dates[13], 'week' => 13, 'type' => 'remedial_yellow'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_winter'], 'colspan' => 5],
                            ['sequence' => ['name' => 'الصيغ', 'rowspan' => 2], 'lesson' => 'الصيغ وكتابتها', 'hours' => '01 ساعة', 'date' => $dates[14], 'week' => 14, 'type' => 'normal'],
                            ['lesson' => 'نسخ الصيغ', 'hours' => '01 ساعة', 'date' => $dates[15], 'week' => 15, 'type' => 'normal'],
                        ],
                    ],
                    [
                        'name' => 'العروض التقديمية',
                        'competence' => 'يصمم عروضا تقديمية لعرض مشاريعه.',
                        'rowspan' => 8,
                        'rows' => [
                            ['sequence' => ['name' => 'تصميم عرض تقديمي', 'rowspan' => 6], 'lesson' => 'تقديم برنامج العروض التقديمية', 'hours' => '01 ساعة', 'date' => $dates[16], 'week' => 16, 'type' => 'normal'],
                            ['lesson' => 'عمليات على الشرائح', 'hours' => '01 ساعة', 'date' => $dates[17], 'week' => 17, 'type' => 'normal'],
                            ['lesson' => 'إدراج مربعات النص والصور', 'hours' => '01 ساعة', 'date' => $dates[18], 'week' => 18, 'type' => 'normal'],
                            ['lesson' => 'فرض الثلاثي الثاني', 'hours' => '01 ساعة', 'date' => $dates[19], 'week' => 19, 'type' => 'exam_red'],
                            ['lesson' => 'إدراج الصوت والفيديو', 'hours' => '01 ساعة', 'date' => $dates[20], 'week' => 20, 'type' => 'normal'],
                            ['lesson' => 'مراحل الانتقال بين الشرائح وطرق الحفظ', 'hours' => '01 ساعة', 'date' => $dates[21], 'week' => 21, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_2'], 'colspan' => 5],
                            ['sequence' => ['name' => 'تقويم ومعالجة', 'rowspan' => 1], 'lesson' => 'تصحيح الاختبار وتقويم ومعالجة', 'hours' => '01 ساعة', 'date' => $dates[22], 'week' => 22, 'type' => 'remedial_yellow'],
                        ],
                    ],
                    [
                        'name' => 'الشبكات',
                        'competence' => 'يتواصل عبر الإنترنت باستعمال البريد الإلكتروني.',
                        'rowspan' => 10,
                        'rows' => [
                            ['sequence' => ['name' => 'البريد الإلكتروني', 'rowspan' => 1], 'lesson' => 'البريد الإلكتروني', 'hours' => '01 ساعة', 'date' => $dates[23], 'week' => 23, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_spring'], 'colspan' => 5],
                            ['sequence' => ['name' => 'التواصل باستعمال الإنترنت', 'rowspan' => 5], 'lesson' => 'إنشاء حساب بريد إلكتروني', 'hours' => '01 ساعة', 'date' => $dates[24], 'week' => 24, 'type' => 'normal'],
                            ['lesson' => 'إرسال واستقبال الرسائل', 'hours' => '01 ساعة', 'date' => $dates[25], 'week' => 25, 'type' => 'normal'],
                            ['lesson' => 'فرض الثلاثي الثالث', 'hours' => '01 ساعة', 'date' => $dates[26], 'week' => 26, 'type' => 'exam_red'],
                            ['lesson' => 'الملف المرفق', 'hours' => '01 ساعة', 'date' => $dates[27], 'week' => 27, 'type' => 'normal'],
                            ['lesson' => 'الرسائل متعددة الوجهات', 'hours' => '01 ساعة', 'date' => $dates[28], 'week' => 28, 'type' => 'normal'],
                            ['sequence' => ['name' => 'إدماج ومشاريع', 'rowspan' => 2], 'lesson' => 'إدماج ومشاريع ختامية', 'hours' => '01 ساعة', 'date' => $dates[29], 'week' => 29, 'type' => 'normal'],
                            ['lesson' => 'مراجعة شاملة وتحضير للاختبارات', 'hours' => '01 ساعة', 'date' => $dates[30], 'week' => 30, 'type' => 'normal'],
                            ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_3'], 'colspan' => 5],
                        ],
                    ],
                ],
            ];
        }

        // Level 4: السنة الرابعة من التعليم المتوسط (4AM)
        return [
            'level_name' => 'السنة الرابعة من التعليم المتوسط',
            'title' => 'التوزيع السنوي للسنة الرابعة من التعليم المتوسط',
            'domains' => [
                [
                    'name' => 'بناء المشاريع',
                    'competence' => 'يبني مهاراته التحليلية والبرمجية ويكتسب فلسفة البرمجة عبر إنجاز مشاريع متعددة الوسائط.',
                    'rowspan' => 11,
                    'rows' => [
                        ['sequence' => ['name' => 'المتغيرات', 'rowspan' => 6], 'lesson' => 'استقبال التلاميذ', 'hours' => '/', 'date' => $dates[1], 'week' => 1, 'type' => 'normal'],
                        ['lesson' => 'حصة تعارف', 'hours' => '01 ساعة', 'date' => $dates[2], 'week' => 2, 'type' => 'normal'],
                        ['lesson' => 'المتغيرات في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[3], 'week' => 3, 'type' => 'normal'],
                        ['lesson' => 'العمليات الحسابية في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[4], 'week' => 4, 'type' => 'normal'],
                        ['lesson' => 'العمليات المنطقية في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[5], 'week' => 5, 'type' => 'normal'],
                        ['lesson' => 'حصة تمارين', 'hours' => '01 ساعة', 'date' => $dates[6], 'week' => 6, 'type' => 'normal'],
                        ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_autumn'], 'colspan' => 5],
                        ['sequence' => ['name' => 'العمليات', 'rowspan' => 4], 'lesson' => 'العمليات على النصوص في سكراتش', 'hours' => '01 ساعة', 'date' => $dates[8], 'week' => 8, 'type' => 'normal'],
                        ['lesson' => 'فرض الثلاثي الأول', 'hours' => '01 ساعة', 'date' => $dates[9], 'week' => 9, 'type' => 'exam_red'],
                        ['lesson' => 'حصة تمارين', 'hours' => '01 ساعة', 'date' => $dates[10], 'week' => 10, 'type' => 'normal'],
                        ['lesson' => 'إنجاز مشروع شامل', 'hours' => '01 ساعة', 'date' => $dates[11], 'week' => 11, 'type' => 'normal'],
                    ],
                ],
                [
                    'name' => 'المجدول',
                    'competence' => 'يوظف المجدول بذكاء ويدرس دوالا مختلفة وبسيطة.',
                    'rowspan' => 9,
                    'rows' => [
                        ['sequence' => ['name' => 'الدوال', 'rowspan' => 1], 'lesson' => 'الجمع التلقائي', 'hours' => '01 ساعة', 'date' => $dates[12], 'week' => 12, 'type' => 'normal'],
                        ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_1'], 'colspan' => 5],
                        ['sequence' => ['name' => 'تقويم ومعالجة', 'rowspan' => 1], 'lesson' => 'تصحيح الاختبار والتقويم والمعالجة', 'hours' => '01 ساعة', 'date' => $dates[13], 'week' => 13, 'type' => 'remedial_yellow'],
                        ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_winter'], 'colspan' => 5],
                        ['sequence' => ['name' => 'الدوال', 'rowspan' => 3], 'lesson' => 'إدراج دالة وبعض الدوال البسيطة', 'hours' => '01 ساعة', 'date' => $dates[14], 'week' => 14, 'type' => 'normal'],
                        ['lesson' => 'نسخ الدوال ونسخ الخلايا بارتباط', 'hours' => '01 ساعة', 'date' => $dates[15], 'week' => 15, 'type' => 'normal'],
                        ['lesson' => 'برمجة القاسم المشترك الأكبر', 'hours' => '01 ساعة', 'date' => $dates[16], 'week' => 16, 'type' => 'normal'],
                        ['sequence' => ['name' => 'التخطيطات', 'rowspan' => 2], 'lesson' => 'المخططات البيانية', 'hours' => '01 ساعة', 'date' => $dates[17], 'week' => 17, 'type' => 'normal'],
                        ['lesson' => 'تغيير بيانات وإضافة معطيات للتخطيط', 'hours' => '01 ساعة', 'date' => $dates[18], 'week' => 18, 'type' => 'normal'],
                    ],
                ],
                [
                    'name' => 'العروض التقديمية',
                    'competence' => 'يصمم عروضا تقديمية متقدمة لخدمة مشاريعه وذات أثر بصرية.',
                    'rowspan' => 6,
                    'rows' => [
                        ['sequence' => ['name' => 'الحركة', 'rowspan' => 3], 'lesson' => 'فرض الثلاثي الثاني', 'hours' => '01 ساعة', 'date' => $dates[19], 'week' => 19, 'type' => 'exam_red'],
                        ['lesson' => 'الحركة في العروض التقديمية', 'hours' => '01 ساعة', 'date' => $dates[20], 'week' => 20, 'type' => 'normal'],
                        ['lesson' => 'تنسيق الحركة في العروض التقديمية', 'hours' => '01 ساعة', 'date' => $dates[21], 'week' => 21, 'type' => 'normal'],
                        ['is_break' => true, 'break_type' => 'exam', 'text' => $dates['exam_term_2'], 'colspan' => 5],
                        ['sequence' => ['name' => 'تقويم ومعالجة', 'rowspan' => 1], 'lesson' => 'تصحيح الاختبار وتقويم ومعالجة', 'hours' => '01 ساعة', 'date' => $dates[22], 'week' => 22, 'type' => 'remedial_yellow'],
                        ['sequence' => ['name' => 'الارتباطات التشعبية', 'rowspan' => 1], 'lesson' => 'الارتباط التشعبي', 'hours' => '01 ساعة', 'date' => $dates[23], 'week' => 23, 'type' => 'normal'],
                    ],
                ],
                [
                    'name' => 'الشبكات',
                    'competence' => 'يستعمل الإنترنت للبحث عن مختلف المعلومات المفيدة وطرق تحميلها.',
                    'rowspan' => 9,
                    'rows' => [
                        ['is_break' => true, 'break_type' => 'vacation', 'text' => $dates['vacation_spring'], 'colspan' => 5],
                        ['sequence' => ['name' => 'التواصل باستعمال الإنترنت', 'rowspan' => 5], 'lesson' => 'محركات البحث في الأنترنت', 'hours' => '01 ساعة', 'date' => $dates[24], 'week' => 24, 'type' => 'normal'],
                        ['lesson' => 'طرق البحث في الأنترنت', 'hours' => '01 ساعة', 'date' => $dates[25], 'week' => 25, 'type' => 'normal'],
                        ['lesson' => 'فرض الثلاثي الثالث', 'hours' => '01 ساعة', 'date' => $dates[26], 'week' => 26, 'type' => 'exam_red'],
                        ['lesson' => 'حفظ الصور وصفحات الـ WEB', 'hours' => '01 ساعة', 'date' => $dates[27], 'week' => 27, 'type' => 'normal'],
                        ['lesson' => 'تحميل الملفات', 'hours' => '01 ساعة', 'date' => $dates[28], 'week' => 28, 'type' => 'normal'],
                        ['is_break' => true, 'break_type' => 'exam', 'text' => 'اختبارات الفصل الثالث للسنة الرابعة متوسط من 09 إلى 11 ماي 2027', 'colspan' => 5],
                        ['sequence' => ['name' => 'تحضير شهادة BEM', 'rowspan' => 1], 'lesson' => 'امتحان شهادة التعليم المتوسط التجريبي والمراجعة الشاملة', 'hours' => '01 ساعة', 'date' => 'من 16 ماي إلى 20 ماي', 'week' => 29, 'type' => 'normal'],
                        ['is_break' => true, 'break_type' => 'exam', 'text' => 'مجالس الأقسام وتحضير امتحان شهادة BEM من 23 إلى 27 ماي 2027', 'colspan' => 5],
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate printable PDF using Browsershot.
     */
    public function generatePdf(array $data): string
    {
        $html = View::make('print.annual-curriculum-distribution', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(5, 6, 5, 6)
            ->showBackground()
            ->pdf();
    }

    /**
     * Stream download PDF response.
     */
    public function pdfResponse(array $data): StreamedResponse
    {
        try {
            $pdf = $this->generatePdf($data);
            $filename = 'annual-curriculum-distribution-'.($data['level_number'] ?? 1).'am.pdf';

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                $filename,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "inline; filename=\"{$filename}\"",
                ]
            );
        } catch (Throwable) {
            $html = View::make('print.annual-curriculum-distribution', $data)->render();

            return response()->streamDownload(
                function () use ($html): void {
                    echo $html;
                },
                'annual-curriculum-distribution.html',
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }
    }
}
