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
     * Build the authentic Algerian Annual Distribution dataset matching the official ministerial model.
     *
     * @return array<string, mixed>
     */
    public function getDistributionData(
        ?int $subjectId = null,
        ?int $classroomId = null,
        ?int $teacherId = null,
        ?string $academicYear = null,
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
        $levelName = $classroom?->level?->name ?? 'السنة الأولى من التعليم المتوسط';

        // Official Algerian Middle-School Distribution for Informatics - 1AM
        $domains = [
            [
                'name' => 'استخدام الحاسوب',
                'competence' => 'يميز بين مختلف مصطلحات المعلوماتية (العتاد والبرامج)',
                'rowspan' => 17, // 13 lesson rows + 4 breaks/events (خريف، اختبار ف1، تصحيح، شتاء)
                'sequences' => [
                    [
                        'name' => 'بيئة التعامل مع الحاسوب',
                        'rowspan' => 6,
                        'items' => [
                            ['week' => 1, 'date' => 'من 21 سبتمبر إلى 24 سبتمبر', 'hours' => '/', 'lesson' => 'استقبال التلاميذ', 'type' => 'normal'],
                            ['week' => 2, 'date' => 'من 27 سبتمبر إلى 01 أكتوبر', 'hours' => '01 ساعة', 'lesson' => 'حصة تعارف', 'type' => 'normal'],
                            ['week' => 3, 'date' => 'من 04 أكتوبر إلى 08 أكتوبر', 'hours' => '01 ساعة', 'lesson' => 'تعريف المعلوماتية والحاسوب', 'type' => 'normal'],
                            ['week' => 4, 'date' => 'من 11 أكتوبر إلى 15 أكتوبر', 'hours' => '01 ساعة', 'lesson' => 'مكونات الحاسوب', 'type' => 'normal'],
                            ['week' => 5, 'date' => 'من 18 أكتوبر إلى 22 أكتوبر', 'hours' => '01 ساعة', 'lesson' => 'وسائط التخزين', 'type' => 'normal'],
                            ['week' => 6, 'date' => 'من 25 أكتوبر إلى 29 أكتوبر', 'hours' => '01 ساعة', 'lesson' => 'مفهوم نظام التشغيل', 'type' => 'normal'],
                        ],
                    ],
                ],
                'breaks_and_sequences' => [
                    ['type' => 'break', 'break_type' => 'vacation', 'text' => 'عطلة الخريف من مساء يوم الثلاثاء 2026/10/27 إلى صباح يوم الإثنين 2026/11/02 (7)'],
                    [
                        'type' => 'sequence',
                        'name' => 'نظام التشغيل',
                        'rowspan' => 4,
                        'items' => [
                            ['week' => 8, 'date' => 'من 02 نوفمبر إلى 05 نوفمبر', 'hours' => '01 ساعة', 'lesson' => 'واجهة نظام التشغيل ويندوز', 'type' => 'normal'],
                            ['week' => 9, 'date' => 'من 08 نوفمبر إلى 12 نوفمبر', 'hours' => '01 ساعة', 'lesson' => 'فرض الثلاثي الأول', 'type' => 'exam_red'],
                            ['week' => 10, 'date' => 'من 15 نوفمبر إلى 19 نوفمبر', 'hours' => '01 ساعة', 'lesson' => 'برنامج الرسام', 'type' => 'normal'],
                            ['week' => 11, 'date' => 'من 22 نوفمبر إلى 26 نوفمبر', 'hours' => '01 ساعة', 'lesson' => 'برنامج الدفتر', 'type' => 'normal'],
                        ],
                    ],
                    [
                        'type' => 'sequence',
                        'name' => 'تنظيم المعلومات في الحاسوب',
                        'rowspan' => 1,
                        'items' => [
                            ['week' => 12, 'date' => 'من 29 نوفمبر إلى 03 ديسمبر', 'hours' => '01 ساعة', 'lesson' => 'مفهوم الملف والمجلد', 'type' => 'normal'],
                        ],
                    ],
                    ['type' => 'break', 'break_type' => 'term_exam', 'text' => 'اختبارات الفصل الأول من 06 إلى 10 ديسمبر 2026'],
                    [
                        'type' => 'sequence',
                        'name' => 'تقويم ومعالجة',
                        'rowspan' => 1,
                        'items' => [
                            ['week' => 13, 'date' => 'من 13 ديسمبر إلى 17 ديسمبر', 'hours' => '01 ساعة', 'lesson' => 'تصحيح الاختبار والتقويم والمعالجة', 'type' => 'remedial_yellow'],
                        ],
                    ],
                    ['type' => 'break', 'break_type' => 'vacation', 'text' => 'عطلة الشتاء من مساء يوم الخميس 2026/12/17 إلى صباح يوم الأحد 2027/01/03'],
                    [
                        'type' => 'sequence',
                        'name' => 'تنظيم المعلومات في الحاسوب',
                        'rowspan' => 2,
                        'items' => [
                            ['week' => 14, 'date' => 'من 03 جانفي إلى 07 جانفي', 'hours' => '01 ساعة', 'lesson' => 'عمليات على الملف والمجلد', 'type' => 'normal'],
                            ['week' => 15, 'date' => 'من 10 جانفي إلى 14 جانفي', 'hours' => '01 ساعة', 'lesson' => 'مختلف طرق العرض', 'type' => 'normal'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'معالج النصوص',
                'competence' => 'يتدرب على استعمال برنامج معالج النصوص وإنشاء وثيقة به وكيفية طباعتها',
                'rowspan' => 18, // 15 lesson rows + 3 breaks (اختبار ف2، عطلة الربيع، اختبار ف3)
                'sequences' => [
                    [
                        'name' => 'تقديم معالج النصوص',
                        'rowspan' => 1,
                        'items' => [
                            ['week' => 16, 'date' => 'من 17 جانفي إلى 21 جانفي', 'hours' => '01 ساعة', 'lesson' => 'تقديم معالج النصوص', 'type' => 'normal'],
                        ],
                    ],
                ],
                'breaks_and_sequences' => [
                    [
                        'type' => 'sequence',
                        'name' => 'عمليات على الوثيقة',
                        'rowspan' => 5,
                        'items' => [
                            ['week' => 17, 'date' => 'من 24 جانفي إلى 28 جانفي', 'hours' => '01 ساعة', 'lesson' => 'عمليات عامة على الوثيقة', 'type' => 'normal'],
                            ['week' => 18, 'date' => 'من 31 جانفي إلى 04 فيفري', 'hours' => '01 ساعة', 'lesson' => 'كتابة نص مع توظيف المدقق الإملائي', 'type' => 'normal'],
                            ['week' => 19, 'date' => 'من 07 فيفري إلى 11 فيفري', 'hours' => '01 ساعة', 'lesson' => 'فرض الثلاثي الثاني', 'type' => 'exam_red'],
                            ['week' => 20, 'date' => 'من 14 فيفري إلى 18 فيفري', 'hours' => '01 ساعة', 'lesson' => 'البحث عن كلمة وتعويضها', 'type' => 'normal'],
                            ['week' => 21, 'date' => 'من 21 فيفري إلى 25 فيفري', 'hours' => '01 ساعة', 'lesson' => 'تنسيق الخط', 'type' => 'normal'],
                        ],
                    ],
                    ['type' => 'break', 'break_type' => 'term_exam', 'text' => 'اختبارات الفصل الثاني: أيام 02، 03، 04 و07، 08 مارس 2027'],
                    [
                        'type' => 'sequence',
                        'name' => 'تنسيق الخط والفقرة',
                        'rowspan' => 2,
                        'items' => [
                            ['week' => 22, 'date' => 'من 07 مارس إلى 11 مارس', 'hours' => '01 ساعة', 'lesson' => 'تصحيح الاختبار وتقويم ومعالجة', 'type' => 'remedial_yellow'],
                            ['week' => 23, 'date' => 'من 14 مارس إلى 18 مارس', 'hours' => '01 ساعة', 'lesson' => 'تنسيق الفقرة', 'type' => 'normal'],
                        ],
                    ],
                    ['type' => 'break', 'break_type' => 'vacation', 'text' => 'عطلة الربيع من مساء يوم الخميس 2027/03/18 إلى صباح يوم الأحد 2027/04/04'],
                    [
                        'type' => 'sequence',
                        'name' => 'إعداد وطباعة وثيقة',
                        'rowspan' => 5,
                        'items' => [
                            ['week' => 24, 'date' => 'من 04 أفريل إلى 08 أفريل', 'hours' => '01 ساعة', 'lesson' => 'التعداد النقطي والرقمي', 'type' => 'normal'],
                            ['week' => 25, 'date' => 'من 11 أفريل إلى 15 أفريل', 'hours' => '01 ساعة', 'lesson' => 'إعداد الصفحة', 'type' => 'normal'],
                            ['week' => 26, 'date' => 'من 18 أفريل إلى 22 أفريل', 'hours' => '01 ساعة', 'lesson' => 'فرض الثلاثي الثالث', 'type' => 'exam_red'],
                            ['week' => 27, 'date' => 'من 25 أفريل إلى 29 أفريل', 'hours' => '01 ساعة', 'lesson' => 'كتابة نص على شكل أعمدة', 'type' => 'normal'],
                            ['week' => 28, 'date' => 'من 02 ماي إلى 06 ماي', 'hours' => '01 ساعة', 'lesson' => 'طباعة وثيقة', 'type' => 'normal'],
                        ],
                    ],
                    [
                        'type' => 'sequence',
                        'name' => 'إدماج ومراجعة',
                        'rowspan' => 2,
                        'items' => [
                            ['week' => 29, 'date' => 'من 09 ماي إلى 13 ماي', 'hours' => '01 ساعة', 'lesson' => 'مشروع إدماجي للتعلمات', 'type' => 'normal'],
                            ['week' => 30, 'date' => 'من 16 ماي إلى 20 ماي', 'hours' => '01 ساعة', 'lesson' => 'مراجعة عامة وتحضير للاختبارات', 'type' => 'normal'],
                        ],
                    ],
                    ['type' => 'break', 'break_type' => 'term_exam', 'text' => 'اختبارات الفصل الثالث من 23 إلى 27 ماي 2027'],
                ],
            ],
        ];

        return [
            'title' => 'التوزيع السنوي للسنة الأولى من التعليم المتوسط',
            'school_name' => $schoolName,
            'subject_name' => $subjectName,
            'teacher_name' => $teacherName,
            'academic_year' => $formattedYear,
            'weekly_hours' => '01 ساعة أسبوعياً',
            'coefficient' => '01',
            'domains' => $domains,
            'level_name' => $levelName,
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
            ->margins(6, 6, 6, 6)
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
            $filename = 'annual-curriculum-distribution.pdf';

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
