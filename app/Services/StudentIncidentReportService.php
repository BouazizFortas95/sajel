<?php

namespace App\Services;

use App\Models\BehaviorObservation;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;

class StudentIncidentReportService
{
    /**
     * Categories of classroom management reports.
     *
     * @return array<string, string>
     */
    public function getCategories(): array
    {
        return [
            'disciplinary' => __('مخالفة انضباطية وسلوكية'),
            'negative' => __('إهمال بيداغوجي وتقاعس متكرر'),
            'observation' => __('صعوبات تعلم وملاحظة بيداغوجية'),
            'positive' => __('تميز وسلوك إيجابي وتحفيز'),
        ];
    }

    /**
     * Preset observed behaviors / incidents scoped by category to prevent logical conflict.
     *
     * @return array<string, string>
     */
    public function getObservedFactsOptions(?string $category = 'disciplinary'): array
    {
        return match ($category) {
            'positive' => [
                'academic_excellence' => __('تفوق ملحوظ وحرص دائم على إتقان الأنشطة والواجبات المدرسية.'),
                'positive_cooperation' => __('مشاركة بيداغوجية استثنائية وسلوك نموذجي متميز في التعاون والانضباط.'),
                'leadership_initiative' => __('روح مبادرة ومساعدة زملائه في الفوج التربوي بإيجابية وروح مسؤولة.'),
                'remarkable_progress' => __('تحسن بيداغوجي وسلوكي لافت وتطور ملموس في النتائج والاهتمام.'),
                'exemplary_respect' => __('انضباط تام وأخلاق عالية واحترام مثالي للأساتذة والزملاء والنظام الداخلي.'),
            ],
            'observation' => [
                'sudden_drop' => __('تراجع ملحوظ ومفاجئ في التحصيل والمشاركة ومؤشرات قلق بيداغوجي.'),
                'comprehension_difficulty' => __('صعوبة في استيعاب المفاهيم ومسايرة نسق التعلمات في القسم.'),
                'attention_deficit' => __('تشتت انتباه متكرر وصعوبة في التركيز والمتابعة أثناء الحصة.'),
                'frequent_fatigue' => __('علامات إرهاق مستمر أو انطواء وانعزال تستدعي تقصياً نفسياً وصحياً.'),
            ],
            default => [
                'disruption' => __('تشويش مستمر ومقاطعة الشرح وعرقلة السير الحسن للحصة.'),
                'missing_tools' => __('عدم إحضار الأدوات المدرسية، الكراس، والكتب بصفة متكررة.'),
                'refusing_work' => __('رفض إنجاز الأنشطة والواجبات التعليمية المكلف بها داخل القسم.'),
                'disrespect' => __('التلفظ بعبارات غير لائقة وعدم احترام النظام الداخلي والأستاذ.'),
                'leaving_seat' => __('مغادرة المكان المخصص أو محاولة مغادرة القسم دون إذن مسبق.'),
                'phone_usage' => __('استخدام الهاتف النقال أو الأجهزة غير المصرح بها أثناء الدرس.'),
                'quarrel' => __('شجار واعتداء لفظي أو جسدي على أحد الزملاء داخل فضاء القسم.'),
                'equipment_misuse' => __('إتلاف أو سوء استخدام تجهيزات وممتلكات القسم أو المخبر.'),
                'sleeping_isolation' => __('النوم المتكرر أو إظهار اللامبالاة والامتناع عن التجاوب.'),
            ],
        };
    }

    /**
     * Preset pedagogical measures taken by teacher prior to report.
     *
     * @return array<string, string>
     */
    public function getPedagogicalActionsOptions(?string $category = 'disciplinary'): array
    {
        return match ($category) {
            'positive' => [
                'positive_encouragement' => __('تم تشجيع التلميذ(ة) شفوياً وتثمين مبادرته(ها) ومجهوده(ها) أمام زملائه(ها).'),
                'peer_recognition' => __('تكليف التلميذ(ة) بمهام ريادية تحفيزية داخل الفوج لدعم ومساعدة زملائه(ها).'),
                'positive_correspondence' => __('تدوين تهنئة وتشجيع رسمي على كراس المراسلة لإعلام الولي بتميز ابنه(ها).'),
            ],
            'observation' => [
                'simplified_tasks' => __('تكييف الأنشطة وتبسيط التعليمات ومرافقة التلميذ(ة) بشكل فردي.'),
                'support_dialogue' => __('إجراء حديث استكشافي ودي لمعرفة المعوقات الشخصية أو الدراسية.'),
                'front_seat' => __('تقريب مكان جلوس التلميذ(ة) إلى الصفوف الأولى لمتابعته(ها) عن قرب.'),
            ],
            default => [
                'verbal_warning' => __('تم تنبيه التلميذ(ة) شفوياً وتذكيره(ها) بالقواعد الصفية والانضباط المدرسي.'),
                'seat_change' => __('تم تغيير مكان جلوس التلميذ(ة) داخل القسم لعزله(ها) عن مسببات التشتت والتشويش.'),
                'private_dialogue' => __('تم إجراء محاورة وتوجيه فردي مع التلميذ(ة) على انفراد عند نهاية الحصة.'),
                'correspondence_note' => __('تم تدوين ملاحظة تنبيه على كراس المراسلة لإشعار ولي الأمر بالوضعية.'),
                'prior_counselor_notice' => __('تم إشعار السيد(ة) مستشار(ة) التربية / ناظر(ة) الدروس شفوياً بالحالة مسبقاً.'),
            ],
        };
    }

    /**
     * Preset recommendations requested by teacher to the administration.
     *
     * @return array<string, string>
     */
    public function getRecommendationsOptions(?string $category = 'disciplinary'): array
    {
        return match ($category) {
            'positive' => [
                'honor_certificate' => __('منح شهادة تقدير وتشجيع وتدوينها في الملف البيداغوجي للتلميذ(ة).'),
                'guardian_congratulation' => __('توجيه رسالة شكر وتهنئة رسمية من إدارة المؤسسة إلى ولي أمر التلميذ(ة).'),
                'board_of_honor' => __('إدراج اسم التلميذ(ة) ضمن لوحة شرف المتفوقين والمنضبطين بالمؤسسة.'),
            ],
            'observation' => [
                'counselor_referral' => __('إحالة التلميذ(ة) على مستشار(ة) التوجيه والإرشاد المدرسي لدراسة الحالة ومتابعتها.'),
                'health_screening' => __('إحالة التلميذ(ة) على وحدة الكشف والمتابعة الصحية (UDS) للمعاينة والفحص.'),
                'pedagogical_remediation' => __('إدراج التلميذ(ة) ضمن حصص الاستدراك والمعالجة البيداغوجية بالمؤسسة.'),
            ],
            default => [
                'guardian_summons' => __('استدعاء ولي أمر التلميذ(ة) لمقابلة الإدارة والأستاذ(ة) المعني(ة) لاتخاذ الإجراء المناسب.'),
                'internal_regulation_action' => __('اتخاذ الإجراءات التأديبية المنصوص عليها في النظام الداخلي للمؤسسة.'),
                'disciplinary_council' => __('إحالة ملف التلميذ(ة) على مجلس التأديب للنظر فيه (في حال العود أو المخالفة الجسيمة).'),
                'written_warning_remedial' => __('توجيه إنذار كتابي وتكليف التلميذ(ة) بنشاط بيداغوجي استدراكي تعويضي.'),
            ],
        };
    }

    /**
     * Flat dictionary of all facts across all categories for key resolution.
     *
     * @return array<string, string>
     */
    public function allFactsDictionary(): array
    {
        return array_merge(
            $this->getObservedFactsOptions('disciplinary'),
            $this->getObservedFactsOptions('observation'),
            $this->getObservedFactsOptions('positive'),
        );
    }

    /**
     * Flat dictionary of all pedagogical actions across all categories.
     *
     * @return array<string, string>
     */
    public function allActionsDictionary(): array
    {
        return array_merge(
            $this->getPedagogicalActionsOptions('disciplinary'),
            $this->getPedagogicalActionsOptions('observation'),
            $this->getPedagogicalActionsOptions('positive'),
        );
    }

    /**
     * Flat dictionary of all recommendations across all categories.
     *
     * @return array<string, string>
     */
    public function allRecommendationsDictionary(): array
    {
        return array_merge(
            $this->getRecommendationsOptions('disciplinary'),
            $this->getRecommendationsOptions('observation'),
            $this->getRecommendationsOptions('positive'),
        );
    }

    /**
     * Assemble selected options into a cohesive, formal Arabic administrative report.
     * Enforces strict limits: max 3 facts, max 2 actions, max 2 recommendations
     * to guarantee fitting on a single A4 page.
     *
     * @param  list<string>  $facts
     * @param  list<string>  $actions
     * @param  list<string>  $recommendations
     */
    public function assembleReportText(
        ?Student $student = null,
        ?User $teacher = null,
        ?string $category = 'disciplinary',
        array $facts = [],
        array $actions = [],
        array $recommendations = [],
        ?string $incidentDate = null,
        ?string $subjectName = null,
        ?string $customNotes = null,
    ): string {
        $studentName = $student?->full_name ?? __('التلميذ(ة)');
        $classroomName = $student?->group?->classroom?->name ?? '—';
        $teacherName = $teacher?->name ?? __('الأستاذ(ة)');
        $subject = $subjectName ?? $teacher?->primarySubject?->name ?? __('المادة');
        $date = $incidentDate ?: today()->format('Y/m/d');

        $isPositive = $category === 'positive';
        $isObservation = $category === 'observation';
        $categoryLabel = $this->getCategories()[$category] ?? __('تقرير متابعة تربوية');

        // Strictly enforce caps: Max 3 facts, Max 2 actions, Max 2 recommendations
        $facts = array_slice($facts, 0, 3);
        $actions = array_slice($actions, 0, 2);
        $recommendations = array_slice($recommendations, 0, 2);

        $lines = [];

        // Heading & Addressee
        $lines[] = 'إلى السيد(ة): مدير(ة) المؤسسة / مستشار(ة) التربية';
        $lines[] = 'الموضوع: '.$categoryLabel.' بخصوص التلميذ(ة): '.$studentName.' (قسم: '.$classroomName.')';
        $lines[] = '';

        // Introductory statement
        if ($isPositive) {
            $lines[] = sprintf(
                'يشرفني أن أحيطكم علماً بالسلوك الإيجابي والتميز البيداغوجي الملحوظ للتلميذ(ة) المذكور(ة) أعلاه، وذلك خلال حصة مادة (%s) بتاريخ: %s، حيث تم تسجيل ما يلي:',
                $subject,
                $date,
            );
        } elseif ($isObservation) {
            $lines[] = sprintf(
                'يشرفني أن أرفع إلى عنايتكم هذا التقرير البيداغوجي بخصوص التلميذ(ة) المذكور(ة) أعلاه، قصد المرافقة والمتابعة، وذلك إثر ما تم ملاحظته في حصة (%s) بتاريخ: %s:',
                $subject,
                $date,
            );
        } else {
            $lines[] = sprintf(
                'يشرفني أن أرفع إلى عنايتكم هذا التقرير بخصوص التلميذ(ة) المذكور(ة) أعلاه، وذلك إثر ما تم تسجيله خلال حصة مادة (%s) بتاريخ: %s، حيث تم رصد الوقائع الآتية:',
                $subject,
                $date,
            );
        }
        $lines[] = '';

        // Section 1: Observed Facts
        $factsDict = $this->allFactsDictionary();
        $selectedFacts = array_filter(array_map(fn ($k) => $factsDict[$k] ?? (is_string($k) ? $k : null), $facts));

        if (! empty($selectedFacts)) {
            $lines[] = $isPositive ? 'أولاً: الملاحظات والسلوكيات الإيجابية المرصودة:' : 'أولاً: الوقائع والسلوكيات المرصودة:';
            foreach ($selectedFacts as $fact) {
                $lines[] = '  • '.$fact;
            }
            $lines[] = '';
        }

        // Section 2: Pedagogical Actions taken by teacher
        $actionsDict = $this->allActionsDictionary();
        $selectedActions = array_filter(array_map(fn ($k) => $actionsDict[$k] ?? (is_string($k) ? $k : null), $actions));

        if (! empty($selectedActions)) {
            $lines[] = $isPositive ? 'ثانياً: الإجراءات التحفيزية المتخذة داخل القسم:' : 'ثانياً: الإجراءات التربوية الأولية المتخذة من طرف الأستاذ(ة):';
            foreach ($selectedActions as $action) {
                $lines[] = '  • '.$action;
            }
            $lines[] = '';
        }

        // Section 3: Recommendations to Administration
        $recomDict = $this->allRecommendationsDictionary();
        $selectedRecoms = array_filter(array_map(fn ($k) => $recomDict[$k] ?? (is_string($k) ? $k : null), $recommendations));

        if (! empty($selectedRecoms)) {
            $lines[] = $isPositive ? 'ثالثاً: المقترحات لتثمين التميز والتشجيع:' : 'ثالثاً: المطلب والمقترحات المرفوعة لإدارة المؤسسة:';
            foreach ($selectedRecoms as $recom) {
                $lines[] = '  • '.$recom;
            }
            $lines[] = '';
        }

        // Section 4: Custom Notes (if any)
        if (filled($customNotes)) {
            $lines[] = 'ملاحظات وتفاصيل إضافية:';
            $lines[] = trim($customNotes);
            $lines[] = '';
        }

        // Closing
        $schoolName = SchoolSetting::current()?->school_name ?? __('المؤسسة التربوية');
        $lines[] = 'تقبلوا منا فائق التقدير والاحترام.';
        $lines[] = 'حرر بـ: '.$schoolName.' في: '.$date;
        $lines[] = 'الأستاذ(ة) المعني(ة): '.$teacherName;

        return implode("\n", $lines);
    }

    /**
     * View data for official printable student incident report.
     *
     * @return array<string, mixed>
     */
    public function viewData(BehaviorObservation $observation, User $viewer, bool $autoPrint = true): array
    {
        $schoolSetting = SchoolSetting::current();
        $student = $observation->student()->with(['group.classroom.level'])->first();
        $teacher = $observation->recorder ?? $viewer;
        $schedule = $observation->schedule()->with(['subject', 'classroom'])->first();
        $classroom = $student?->group?->classroom ?? $schedule?->classroom;
        $subject = $schedule?->subject?->name ?? $teacher?->primarySubject?->name ?? __('التعليم المتوسط');

        return [
            'observation' => $observation,
            'student' => $student,
            'teacher' => $teacher,
            'schedule' => $schedule,
            'classroom' => $classroom,
            'subject' => $subject,
            'schoolSetting' => $schoolSetting,
            'autoPrint' => $autoPrint,
            'academicYear' => $schoolSetting?->academic_year ?: '2026-2027',
        ];
    }
}
