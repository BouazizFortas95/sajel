<?php

namespace Database\Seeders\Support;

/**
 * Authentic Algerian middle-school curriculum catalogue (التعليم المتوسط — الجيل الثاني).
 *
 * The catalogue intentionally keeps the same structural footprint the seeder and
 * curriculum-tracking tests expect: two domains, four sequences and eight
 * knowledge resources for every subject/level pair. Content is enriched from
 * subject-specific didactic templates so every resource exposes the four
 * required lesson phases, a starting situation, a competence, register-ready
 * HTML and homework.
 */
final class AlgerianCurriculumCatalog
{
    /**
     * Subject → grade → domains + sequences.
     *
     * Lesson arrays always contain exactly two knowledge resources. The first
     * resource is the lesson itself; the second is a closely related practical
     * or integration resource, which is how the official programme presents
     * most middle-school sequences.
     *
     * @var array<string, array<int, array{
     *     domains: array<int, string>,
     *     sequences: array<int, array{domain: int, name: string, lessons: array{0: string, 1: string}}>
     * }>>
     */
    private const PROGRAMS = [
        'اللغة العربية' => [
            1 => [
                'domains' => ['فهم المكتوب', 'الظواهر اللغوية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'النص السردي', 'lessons' => ['نص: الأسرة والمدرسة', 'نص: حب الوطن']],
                    ['domain' => 0, 'name' => 'النص الوصفي', 'lessons' => ['نص: التعاون', 'نص: فصل الخريف']],
                    ['domain' => 1, 'name' => 'الجملة البسيطة', 'lessons' => ['المفرد والجمع', 'الجملة الاسمية والفعلية']],
                    ['domain' => 1, 'name' => 'بناء الفعل الماضي', 'lessons' => ['تصريف الفعل الماضي', 'الفاعل وأحكامه']],
                ],
            ],
            2 => [
                'domains' => ['فهم المكتوب', 'الظواهر اللغوية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'النص الحواري', 'lessons' => ['نص: الحوار داخل الأسرة', 'نص: آداب الحوار']],
                    ['domain' => 0, 'name' => 'النص الإخباري', 'lessons' => ['نص: التضامن الإنساني', 'نص: العلم والمواطنة']],
                    ['domain' => 1, 'name' => 'الجملة المركبة', 'lessons' => ['الجملة المعطوفة', 'الجملة الموصولة']],
                    ['domain' => 1, 'name' => 'الأسماء الخمسة', 'lessons' => ['إعراب الأسماء الخمسة', 'المفعول به']],
                ],
            ],
            3 => [
                'domains' => ['فهم المكتوب', 'الظواهر اللغوية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'النص التفسيري', 'lessons' => ['نص: البيئة والتنمية', 'نص: العمل التطوعي']],
                    ['domain' => 0, 'name' => 'النص الحجاجي', 'lessons' => ['نص: خطر التلوث', 'نص: حق التعليم']],
                    ['domain' => 1, 'name' => 'الاستعارة', 'lessons' => ['الاستعارة التصريحية', 'الاستعارة المكنية']],
                    ['domain' => 1, 'name' => 'الصورة البيانية', 'lessons' => ['التشبيه والكناية', 'المجاز المرسل']],
                ],
            ],
            4 => [
                'domains' => ['فهم المكتوب', 'الظواهر اللغوية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'النص الإخباري', 'lessons' => ['نص: الثورة التحريرية', 'نص: الاستقلال']],
                    ['domain' => 0, 'name' => 'النص الحجاجي', 'lessons' => ['نص: الوحدة الوطنية', 'نص: التضامن الإنساني']],
                    ['domain' => 1, 'name' => 'الجملة المركبة', 'lessons' => ['الجملة الشرطية', 'الجملة السببية']],
                    ['domain' => 1, 'name' => 'البلاغة', 'lessons' => ['الاستعارة المكنية', 'الكناية والمجاز']],
                ],
            ],
        ],
        'الرياضيات' => [
            1 => [
                'domains' => ['الأنشطة العددية', 'الأنشطة الهندسية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الأعداد الطبيعية', 'lessons' => ['قراءة وكتابة الأعداد الطبيعية', 'مقارنة وترتيب الأعداد']],
                    ['domain' => 0, 'name' => 'العمليات الأربع', 'lessons' => ['الجمع والطرح', 'الضرب والقسمة']],
                    ['domain' => 1, 'name' => 'المستقيم والمقطع', 'lessons' => ['النقطة والمستقيم', 'المقطع والطول']],
                    ['domain' => 1, 'name' => 'المجموعات والأشكال', 'lessons' => ['المثلث والرباعي', 'المحيط والمساحة']],
                ],
            ],
            2 => [
                'domains' => ['الأنشطة العددية', 'الأنشطة الهندسية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الأعداد النسبية', 'lessons' => ['الأعداد النسبية', 'جمع وطرح الأعداد النسبية']],
                    ['domain' => 0, 'name' => 'النسب والمعدلات', 'lessons' => ['النسبة المئوية', 'المعدل']],
                    ['domain' => 1, 'name' => 'التناظر المحوري', 'lessons' => ['محور التناظر', 'إنشاء نظير نقطة']],
                    ['domain' => 1, 'name' => 'المثلثات', 'lessons' => ['خاصية المثلث', 'المثلث القائم']],
                ],
            ],
            3 => [
                'domains' => ['الأنشطة العددية', 'الأنشطة الهندسية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الأعداد الناطقة', 'lessons' => ['الأعداد الناطقة', 'العمليات على الأعداد الناطقة']],
                    ['domain' => 0, 'name' => 'الحساب الحرفي', 'lessons' => ['المعادلات من الدرجة الأولى', 'تبسيط العبارات']],
                    ['domain' => 1, 'name' => 'نظرية فيثاغورس', 'lessons' => ['خاصية فيثاغورس', 'تطبيقات فيثاغورس']],
                    ['domain' => 1, 'name' => 'التناظر المركزي', 'lessons' => ['مركز التناظر', 'إنشاء نظير شكل']],
                ],
            ],
            4 => [
                'domains' => ['الأنشطة العددية', 'الأنشطة الهندسية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'PGCD', 'lessons' => ['القاسم المشترك الأكبر', 'تطبيقات PGCD']],
                    ['domain' => 0, 'name' => 'الأعداد الناطقة', 'lessons' => ['العمليات على الأعداد الناطقة', 'القوى']],
                    ['domain' => 1, 'name' => 'خاصية طالس', 'lessons' => ['خاصية طالس', 'التناسب في المثلث']],
                    ['domain' => 1, 'name' => 'فيثاغورس والهندسة', 'lessons' => ['نظرية فيثاغورس', 'حساب الأطوال والزوايا']],
                ],
            ],
        ],
        'العلوم الفيزيائية والتكنولوجيا' => [
            1 => [
                'domains' => ['الظواهر الكهربائية', 'المادة وتحولاتها'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الدارة الكهربائية', 'lessons' => ['الدارة الكهربائية البسيطة', 'التركيب على التسلسل']],
                    ['domain' => 0, 'name' => 'التكهرب والشحنة', 'lessons' => ['التكهرب بالدلك', 'الشحنة الكهربائية']],
                    ['domain' => 1, 'name' => 'حالات المادة', 'lessons' => ['حالات المادة', 'تغيرات الحالة']],
                    ['domain' => 1, 'name' => 'التحولات الكيميائية', 'lessons' => ['التحول الفيزيائي', 'التحول الكيميائي']],
                ],
            ],
            2 => [
                'domains' => ['الظواهر الكهربائية', 'المادة وتحولاتها'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'قياس التيار والتوتر', 'lessons' => ['قياس شدة التيار', 'قياس التوتر الكهربائي']],
                    ['domain' => 0, 'name' => 'المغناطيسية', 'lessons' => ['المغناطيس', 'المجال المغناطيسي']],
                    ['domain' => 1, 'name' => 'الخلائط', 'lessons' => ['الخلائط المتجانسة', 'فصل مكونات خليط']],
                    ['domain' => 1, 'name' => 'الاحتراق', 'lessons' => ['احتراق الشمعة', 'احتراق الكربون']],
                ],
            ],
            3 => [
                'domains' => ['الظواهر الكهربائية', 'الظواهر الميكانيكية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'المقاومة الكهربائية', 'lessons' => ['المقاومة الكهربائية', 'العلاقة بين التوتر وشدة التيار']],
                    ['domain' => 0, 'name' => 'الطاقة الكهربائية', 'lessons' => ['الطاقة الكهربائية', 'الاستطاعة الكهربائية']],
                    ['domain' => 1, 'name' => 'الحركة والسكون', 'lessons' => ['الحركة والسكون', 'السرعة المتوسطة']],
                    ['domain' => 1, 'name' => 'القوى والتوازن', 'lessons' => ['تمثيل القوى', 'توازن جسم صلب']],
                ],
            ],
            4 => [
                'domains' => ['المادة وتحولاتها', 'الظواهر الميكانيكية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'التفاعل الكيميائي', 'lessons' => ['انحفاظ الكتلة في التفاعل', 'التوازن الكيميائي']],
                    ['domain' => 0, 'name' => 'المحاليل الكيميائية', 'lessons' => ['المحلول الشاردي', 'قياس الحموضة']],
                    ['domain' => 1, 'name' => 'توازن جسم صلب', 'lessons' => ['شروط توازن جسم صلب', 'تطبيقات على التوازن']],
                    ['domain' => 1, 'name' => 'الحركة والقوة', 'lessons' => ['العلاقة بين القوة والحركة', 'العمل والطاقة']],
                ],
            ],
        ],
        'علوم الطبيعة والحياة' => [
            1 => [
                'domains' => ['التغذية عند الإنسان', 'التنسيق الوظيفي في العضوية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الهضم والامتصاص', 'lessons' => ['الهضم في الفم والمعدة', 'الامتصاص المعوي']],
                    ['domain' => 0, 'name' => 'التوازن الغذائي', 'lessons' => ['التغذية المتوازنة', 'الأغذية والطاقة']],
                    ['domain' => 1, 'name' => 'التنفس والتبادل الغازي', 'lessons' => ['التنفس الرئوي', 'التبادل الغازي في الرئتين']],
                    ['domain' => 1, 'name' => 'الدورة الدموية', 'lessons' => ['القلب والدورة الدموية', 'الدم ونقل المغذيات']],
                ],
            ],
            2 => [
                'domains' => ['التغذية عند النبات', 'التنسيق الوظيفي في العضوية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'التركيب الضوئي', 'lessons' => ['شروط التركيب الضوئي', 'نواتج التركيب الضوئي']],
                    ['domain' => 0, 'name' => 'التغذية المعدنية', 'lessons' => ['امتصاص الماء والأملاح', 'دور الأسمدة']],
                    ['domain' => 1, 'name' => 'التنسيق العصبي', 'lessons' => ['الجهاز العصبي', 'المنعكس العصبي']],
                    ['domain' => 1, 'name' => 'التنسيق الهرموني', 'lessons' => ['الغدد الهرمونية', 'تنظيم نسبة السكر في الدم']],
                ],
            ],
            3 => [
                'domains' => ['الاستجابة المناعية', 'التكاثر والوراثة'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'المناعة الطبيعية', 'lessons' => ['الحواجز الدفاعية', 'الالتهاب']],
                    ['domain' => 0, 'name' => 'المناعة المكتسبة', 'lessons' => ['الاستجابة المناعية النوعية', 'اللقاحات']],
                    ['domain' => 1, 'name' => 'التكاثر عند الإنسان', 'lessons' => ['الجهاز التناسلي', 'الإلقاح والحمل']],
                    ['domain' => 1, 'name' => 'التكاثر عند النبات', 'lessons' => ['التلقيح', 'الإخصاب وتكوين البذرة']],
                ],
            ],
            4 => [
                'domains' => ['التغذية والصحة', 'الوراثة وانتقال الصفات'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'التغذية والصحة', 'lessons' => ['السلوكات الغذائية', 'الأمراض المرتبطة بالتغذية']],
                    ['domain' => 0, 'name' => 'التركيب الضوئي والبيئة', 'lessons' => ['التركيب الضوئي عند النبات الأخضر', 'التوازن البيئي']],
                    ['domain' => 1, 'name' => 'الوراثة', 'lessons' => ['انتقال الصفات الوراثية', 'الصفات السائدة والمتنحية']],
                    ['domain' => 1, 'name' => 'الأمراض الوراثية', 'lessons' => ['الأمراض الوراثية', 'الوقاية والتوعية']],
                ],
            ],
        ],
        'التاريخ والجغرافيا' => [
            1 => [
                'domains' => ['التاريخ', 'الجغرافيا'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الخريطة التاريخية', 'lessons' => ['قراءة الخريطة التاريخية', 'الزمن التاريخي']],
                    ['domain' => 0, 'name' => 'الأسرة والمجتمع', 'lessons' => ['الأسرة في العصور القديمة', 'المجتمع الجزائري القديم']],
                    ['domain' => 1, 'name' => 'الموقع الجغرافي', 'lessons' => ['موقع الجزائر', 'الحدود والمساحة']],
                    ['domain' => 1, 'name' => 'المناخ والغطاء النباتي', 'lessons' => ['المناخ في الجزائر', 'الغطاء النباتي']],
                ],
            ],
            2 => [
                'domains' => ['التاريخ', 'الجغرافيا'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'العهد العثماني', 'lessons' => ['الدولة الجزائرية في العهد العثماني', 'المؤسسات العثمانية']],
                    ['domain' => 0, 'name' => 'المقاومة الشعبية', 'lessons' => ['المقاومة الشعبية', 'الثورات الشعبية']],
                    ['domain' => 1, 'name' => 'السكان', 'lessons' => ['توزيع السكان', 'النمو السكاني']],
                    ['domain' => 1, 'name' => 'التنمية والموارد', 'lessons' => ['الموارد الطبيعية', 'التنمية المحلية']],
                ],
            ],
            3 => [
                'domains' => ['التاريخ', 'الجغرافيا'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الحركة الوطنية', 'lessons' => ['الحركة الوطنية', 'المظاهرات الشعبية']],
                    ['domain' => 0, 'name' => 'الثورة التحريرية', 'lessons' => ['اندلاع الثورة', 'مراحل الثورة']],
                    ['domain' => 1, 'name' => 'المدن والريف', 'lessons' => ['المدن الجزائرية', 'الريف والهجرة']],
                    ['domain' => 1, 'name' => 'الصناعة والخدمات', 'lessons' => ['الصناعة', 'الخدمات والتجارة']],
                ],
            ],
            4 => [
                'domains' => ['التاريخ', 'الجغرافيا'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الاستقلال', 'lessons' => ['استقلال الجزائر', 'بناء الدولة الوطنية']],
                    ['domain' => 0, 'name' => 'المقاومة والثورة', 'lessons' => ['المقاومة الشعبية', 'الثورة التحريرية']],
                    ['domain' => 1, 'name' => 'السكان والتنمية', 'lessons' => ['السكان والتنمية', 'التنمية المستدامة']],
                    ['domain' => 1, 'name' => 'التهيئة الإقليمية', 'lessons' => ['التهيئة الإقليمية', 'التوازن الجهوي']],
                ],
            ],
        ],
        'التربية الإسلامية' => [
            1 => [
                'domains' => ['القرآن الكريم والحديث', 'العقيدة والعبادات'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'سورة النبأ', 'lessons' => ['تلاوة سورة النبأ', 'تفسير سورة النبأ']],
                    ['domain' => 0, 'name' => 'سورة عبس', 'lessons' => ['تلاوة سورة عبس', 'دروس سورة عبس']],
                    ['domain' => 1, 'name' => 'أركان الإسلام', 'lessons' => ['أركان الإسلام', 'الشهادتان']],
                    ['domain' => 1, 'name' => 'الطهارة', 'lessons' => ['أحكام الطهارة', 'الوضوء والصلاة']],
                ],
            ],
            2 => [
                'domains' => ['القرآن الكريم والحديث', 'العقيدة والعبادات'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'سورة الملك', 'lessons' => ['تلاوة سورة الملك', 'تفسير سورة الملك']],
                    ['domain' => 0, 'name' => 'أحكام التلاوة', 'lessons' => ['أحكام النون الساكنة', 'المدود']],
                    ['domain' => 1, 'name' => 'أركان الإيمان', 'lessons' => ['أركان الإيمان', 'الإيمان بالله']],
                    ['domain' => 1, 'name' => 'الصلاة', 'lessons' => ['أحكام الصلاة', 'سنن الصلاة']],
                ],
            ],
            3 => [
                'domains' => ['القرآن الكريم والحديث', 'السيرة النبوية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'سورة الرحمن', 'lessons' => ['تلاوة سورة الرحمن', 'تفسير سورة الرحمن']],
                    ['domain' => 0, 'name' => 'حديث النية', 'lessons' => ['حديث إنما الأعمال بالنيات', 'فوائد الحديث']],
                    ['domain' => 1, 'name' => 'الدعوة في مكة', 'lessons' => ['بداية الدعوة', 'صبر النبي في مكة']],
                    ['domain' => 1, 'name' => 'الزكاة والصيام', 'lessons' => ['أحكام الزكاة', 'أحكام الصيام']],
                ],
            ],
            4 => [
                'domains' => ['القرآن الكريم والحديث', 'السيرة النبوية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'سورة الحجرات', 'lessons' => ['تلاوة سورة الحجرات', 'تفسير سورة الحجرات']],
                    ['domain' => 0, 'name' => 'حديث الدين النصيحة', 'lessons' => ['حديث الدين النصيحة', 'فوائد الحديث']],
                    ['domain' => 1, 'name' => 'صلح الحديبية', 'lessons' => ['صلح الحديبية', 'فتح مكة']],
                    ['domain' => 1, 'name' => 'الحج والعمرة', 'lessons' => ['أحكام الحج', 'أحكام العمرة']],
                ],
            ],
        ],
        'التربية المدنية' => [
            1 => [
                'domains' => ['الحياة الجماعية', 'الحياة الديمقراطية والمؤسسات'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'التنوع الثقافي', 'lessons' => ['التنوع الثقافي في الجزائر', 'احترام الاختلاف']],
                    ['domain' => 0, 'name' => 'التعاون والتضامن', 'lessons' => ['التعاون', 'التضامن الاجتماعي']],
                    ['domain' => 1, 'name' => 'القانون الداخلي', 'lessons' => ['القانون الداخلي للمؤسسة', 'حقوق وواجبات التلميذ']],
                    ['domain' => 1, 'name' => 'المواطنة والمسؤولية', 'lessons' => ['المواطنة', 'المسؤولية المدنية']],
                ],
            ],
            2 => [
                'domains' => ['الحياة الجماعية', 'الحياة الديمقراطية والمؤسسات'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الأسرة والمجتمع', 'lessons' => ['الأسرة', 'المجتمع المدني']],
                    ['domain' => 0, 'name' => 'الهلال الأحمر الجزائري', 'lessons' => ['الهلال الأحمر الجزائري', 'العمل الإنساني']],
                    ['domain' => 1, 'name' => 'البلدية', 'lessons' => ['البلدية', 'المجلس البلدي']],
                    ['domain' => 1, 'name' => 'الحقوق والواجبات', 'lessons' => ['حقوق الطفل', 'الواجبات المدنية']],
                ],
            ],
            3 => [
                'domains' => ['الحياة الجماعية', 'الحياة الديمقراطية والمؤسسات'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الإعلام والرأي العام', 'lessons' => ['الإعلام', 'الرأي العام']],
                    ['domain' => 0, 'name' => 'البيئة والتنمية', 'lessons' => ['حماية البيئة', 'التنمية المستدامة']],
                    ['domain' => 1, 'name' => 'المؤسسات الدستورية', 'lessons' => ['الدستور', 'المؤسسات الدستورية']],
                    ['domain' => 1, 'name' => 'الانتخابات', 'lessons' => ['الانتخابات', 'الديمقراطية المحلية']],
                ],
            ],
            4 => [
                'domains' => ['الحياة الجماعية', 'الحياة الديمقراطية والمؤسسات'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'المواطنة في الجزائر', 'lessons' => ['المواطنة الجزائرية', 'الحفاظ على الممتلكات العامة']],
                    ['domain' => 0, 'name' => 'المجتمع المدني', 'lessons' => ['الجمعيات', 'العمل التطوعي']],
                    ['domain' => 1, 'name' => 'الدولة ومؤسساتها', 'lessons' => ['الدولة الجزائرية', 'المؤسسات الوطنية']],
                    ['domain' => 1, 'name' => 'الحقوق والحريات', 'lessons' => ['الحقوق الأساسية', 'الحريات العامة']],
                ],
            ],
        ],
        'اللغة الفرنسية' => [
            1 => [
                'domains' => ["Compréhension de l'écrit", "Production de l'écrit"],
                'sequences' => [
                    ['domain' => 0, 'name' => "L'hygiène et la santé", 'lessons' => ["Lire un texte sur l'hygiène", 'Écrire des conseils d’hygiène']],
                    ['domain' => 0, 'name' => "La famille et l'école", 'lessons' => ['Présenter sa famille', 'Décrire son école']],
                    ['domain' => 1, 'name' => "Le présent de l'indicatif", 'lessons' => ['Conjuguer les verbes en -er', 'Les verbes être et avoir']],
                    ['domain' => 1, 'name' => "Le nom et l'accord", 'lessons' => ['Le genre et le nombre', "L'accord de l'adjectif"]],
                ],
            ],
            2 => [
                'domains' => ["Compréhension de l'écrit", "Production de l'écrit"],
                'sequences' => [
                    ['domain' => 0, 'name' => 'La fable et le conte', 'lessons' => ['Lire une fable', 'Écrire un conte']],
                    ['domain' => 0, 'name' => "L'environnement", 'lessons' => ['Comprendre un texte sur la pollution', 'Produire un message écologique']],
                    ['domain' => 1, 'name' => 'Le passé composé', 'lessons' => ['Former le passé composé', 'Accord du participe passé']],
                    ['domain' => 1, 'name' => 'Les connecteurs', 'lessons' => ['Utiliser les connecteurs', 'La phrase complexe']],
                ],
            ],
            3 => [
                'domains' => ["Compréhension de l'écrit", "Production de l'écrit"],
                'sequences' => [
                    ['domain' => 0, 'name' => 'Le texte explicatif', 'lessons' => ['Lire une explication', 'Rédiger une explication']],
                    ['domain' => 0, 'name' => 'La santé', 'lessons' => ['Comprendre un document médical', 'Donner des conseils']],
                    ['domain' => 1, 'name' => "L'imparfait", 'lessons' => ["Conjuguer à l'imparfait", 'Imparfait et passé composé']],
                    ['domain' => 1, 'name' => 'La voix passive', 'lessons' => ['Transformer à la voix passive', "Le complément d'agent"]],
                ],
            ],
            4 => [
                'domains' => ["Compréhension de l'écrit", "Production de l'écrit"],
                'sequences' => [
                    ['domain' => 0, 'name' => 'La fable et le conte', 'lessons' => ['Analyser une fable', 'Réécrire un conte']],
                    ['domain' => 0, 'name' => 'Le texte argumentatif', 'lessons' => ['Lire un texte argumentatif', 'Défendre une opinion']],
                    ['domain' => 1, 'name' => "Le présent de l'indicatif", 'lessons' => ['Les valeurs du présent', 'Accord sujet-verbe']],
                    ['domain' => 1, 'name' => "L'accord du participe passé", 'lessons' => ['Participe passé avec avoir', 'Participe passé avec être']],
                ],
            ],
        ],
        'اللغة الإنجليزية' => [
            1 => [
                'domains' => ['Me, My Friends and My Family', 'Me and My Scientific World'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'Describing daily routines', 'lessons' => ['My daily routine', 'Telling the time']],
                    ['domain' => 0, 'name' => 'Family and friends', 'lessons' => ['Introduce my family', 'My best friend']],
                    ['domain' => 1, 'name' => 'Present simple', 'lessons' => ['Affirmative and negative', 'Questions with do and does']],
                    ['domain' => 1, 'name' => 'Healthy food habits', 'lessons' => ['Healthy food', 'At the school canteen']],
                ],
            ],
            2 => [
                'domains' => ['Me, My Friends and My Family', 'Me and My Scientific World'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'My school life', 'lessons' => ['My school day', 'After-school activities']],
                    ['domain' => 0, 'name' => 'Holidays and traditions', 'lessons' => ['Algerian traditions', 'Planning a holiday']],
                    ['domain' => 1, 'name' => 'Past simple', 'lessons' => ['Regular verbs', 'Irregular verbs']],
                    ['domain' => 1, 'name' => 'The environment', 'lessons' => ['Reduce, reuse, recycle', 'A clean school']],
                ],
            ],
            3 => [
                'domains' => ['Me, My Friends and My Family', 'Me and My Scientific World'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'Famous Algerian scientists', 'lessons' => ['Algerian scientists', 'Great inventions']],
                    ['domain' => 0, 'name' => 'Technology in daily life', 'lessons' => ['Smartphones', 'The internet']],
                    ['domain' => 1, 'name' => 'Present continuous', 'lessons' => ['Actions now', 'Present simple and continuous']],
                    ['domain' => 1, 'name' => 'Health and sport', 'lessons' => ['Sports and fitness', 'A healthy lifestyle']],
                ],
            ],
            4 => [
                'domains' => ['Me, My Friends and My Family', 'Me and My Scientific World'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'Space and astronomy', 'lessons' => ['The solar system', 'Space exploration']],
                    ['domain' => 0, 'name' => 'Great inventions', 'lessons' => ['The history of the telephone', 'Technology and society']],
                    ['domain' => 1, 'name' => 'Future tenses', 'lessons' => ['Will', 'Going to']],
                    ['domain' => 1, 'name' => 'Environment and climate', 'lessons' => ['Climate change', 'Protecting nature']],
                ],
            ],
        ],
        'الإعلام الآلي' => [
            1 => [
                'domains' => ['بيئة التعامل مع الحاسوب', 'معالج النصوص'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'مكونات الحاسوب', 'lessons' => ['مكونات الحاسوب المادية', 'مكونات الحاسوب البرمجية']],
                    ['domain' => 0, 'name' => 'نظام التشغيل', 'lessons' => ['نظام التشغيل', 'إدارة الملفات والمجلدات']],
                    ['domain' => 1, 'name' => 'لوحة المفاتيح والفأرة', 'lessons' => ['لوحة المفاتيح', 'الفأرة وواجهة المستخدم']],
                    ['domain' => 1, 'name' => 'الملفات والمجلدات', 'lessons' => ['إنشاء مجلد', 'نسخ ونقل الملفات']],
                ],
            ],
            2 => [
                'domains' => ['معالج النصوص', 'المجدول'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'تنسيق النصوص', 'lessons' => ['تنسيق النصوص', 'تنسيق الفقرات']],
                    ['domain' => 0, 'name' => 'إدراج العناصر', 'lessons' => ['إدراج الصور', 'إدراج الجداول']],
                    ['domain' => 1, 'name' => 'الصيغ والحسابات', 'lessons' => ['الصيغ الأساسية', 'الدوال البسيطة']],
                    ['domain' => 1, 'name' => 'الرسوم البيانية', 'lessons' => ['إنشاء رسم بياني', 'تنسيق المخططات']],
                ],
            ],
            3 => [
                'domains' => ['المجدول', 'الشبكات وأمن المعلومات'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الجداول الممتدة', 'lessons' => ['الجداول الممتدة', 'الفرز والتصفية']],
                    ['domain' => 0, 'name' => 'الدوال المتقدمة', 'lessons' => ['الدوال المتقدمة', 'المراجع النسبية والمطلقة']],
                    ['domain' => 1, 'name' => 'أمن المعلومات', 'lessons' => ['أمن المعلومات', 'كلمات المرور']],
                    ['domain' => 1, 'name' => 'الشبكات والإنترنت', 'lessons' => ['الشبكات', 'الإنترنت والبريد الإلكتروني']],
                ],
            ],
            4 => [
                'domains' => ['الخوارزميات', 'البرمجة'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'مدخل إلى الخوارزميات', 'lessons' => ['مفهوم الخوارزمية', 'تمثيل الخوارزمية']],
                    ['domain' => 0, 'name' => 'المخططات الانسيابية', 'lessons' => ['المخططات الانسيابية', 'الشروط والحلقات']],
                    ['domain' => 1, 'name' => 'البرمجة باستعمال Scratch', 'lessons' => ['واجهة Scratch', 'الكتل البرمجية']],
                    ['domain' => 1, 'name' => 'مشروع تطبيقي', 'lessons' => ['مشروع تطبيقي', 'اختبار المشروع']],
                ],
            ],
        ],
        'التربية البدنية والرياضية' => [
            1 => [
                'domains' => ['الوضعيات والتنقلات', 'الألعاب الجماعية والجمباز'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الجري السريع والتناوب', 'lessons' => ['الجري السريع', 'التناوب']],
                    ['domain' => 0, 'name' => 'التوازن والمرونة', 'lessons' => ['التوازن', 'المرونة']],
                    ['domain' => 1, 'name' => 'كرة اليد وكرة القدم', 'lessons' => ['التمرير والاستقبال في كرة اليد', 'التحكم بالكرة في كرة القدم']],
                    ['domain' => 1, 'name' => 'الجمباز', 'lessons' => ['الدحرجة', 'الوثب']],
                ],
            ],
            2 => [
                'domains' => ['الوضعيات والتنقلات', 'الألعاب الجماعية والجمباز'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الجري المتواصل والوثب الطويل', 'lessons' => ['الجري المتواصل', 'الوثب الطويل']],
                    ['domain' => 0, 'name' => 'رمي الجلة والمرونة', 'lessons' => ['رمي الجلة', 'تمارين المرونة']],
                    ['domain' => 1, 'name' => 'كرة السلة والكرة الطائرة', 'lessons' => ['التمرير في كرة السلة', 'الإرسال في الكرة الطائرة']],
                    ['domain' => 1, 'name' => 'الجمباز', 'lessons' => ['التوازن', 'السلسلة الحركية']],
                ],
            ],
            3 => [
                'domains' => ['الوضعيات والتنقلات', 'الألعاب الجماعية والجمباز'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'السرعة والتنسيق', 'lessons' => ['السرعة والتنسيق', 'التحمل']],
                    ['domain' => 0, 'name' => 'الوثب العالي ورمي الكرة', 'lessons' => ['الوثب العالي', 'رمي الكرة']],
                    ['domain' => 1, 'name' => 'كرة اليد وكرة القدم', 'lessons' => ['الهجوم في كرة اليد', 'التمرير في كرة القدم']],
                    ['domain' => 1, 'name' => 'الجمباز', 'lessons' => ['العجلة', 'الوثب']],
                ],
            ],
            4 => [
                'domains' => ['الوضعيات والتنقلات', 'الألعاب الجماعية والجمباز'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الجري السريع والتتابع', 'lessons' => ['الجري السريع', 'التتابع']],
                    ['domain' => 0, 'name' => 'الوثب الطويل ورمي الجلة', 'lessons' => ['الوثب الطويل', 'رمي الجلة']],
                    ['domain' => 1, 'name' => 'المباريات الجماعية', 'lessons' => ['مباراة كرة اليد', 'مباراة كرة السلة']],
                    ['domain' => 1, 'name' => 'الصحة والجهد البدني', 'lessons' => ['القواعد الصحية للجهد', 'الإحماء والاستطالة']],
                ],
            ],
        ],
        'اللغة الأمازيغية' => [
            1 => [
                'domains' => ['فهم المنطوق والمكتوب', 'القواعد والتراث'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'حروف تيفيناغ', 'lessons' => ['حروف تيفيناغ', 'الأصوات الأمازيغية']],
                    ['domain' => 0, 'name' => 'الأسرة والمدرسة', 'lessons' => ['الأسرة', 'المدرسة']],
                    ['domain' => 1, 'name' => 'المفرد والجمع', 'lessons' => ['المفرد والجمع', 'الضمائر']],
                    ['domain' => 1, 'name' => 'الألوان والأعداد', 'lessons' => ['الألوان', 'الأعداد']],
                ],
            ],
            2 => [
                'domains' => ['فهم المنطوق والمكتوب', 'القواعد والتراث'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'النص القصير', 'lessons' => ['النص القصير', 'الحوار']],
                    ['domain' => 0, 'name' => 'الحياة اليومية', 'lessons' => ['المدرسة والحياة اليومية', 'العائلة']],
                    ['domain' => 1, 'name' => 'تصريف الفعل في الحاضر', 'lessons' => ['تصريف الفعل في الحاضر', 'الجملة البسيطة']],
                    ['domain' => 1, 'name' => 'التراث الأمازيغي', 'lessons' => ['الأغنية الأمازيغية', 'التراث']],
                ],
            ],
            3 => [
                'domains' => ['فهم المنطوق والمكتوب', 'القواعد والتراث'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'النص الوصفي', 'lessons' => ['النص الوصفي', 'النص السردي']],
                    ['domain' => 0, 'name' => 'البيئة والطبيعة', 'lessons' => ['البيئة والطبيعة', 'الصناعات التقليدية']],
                    ['domain' => 1, 'name' => 'الفعل الماضي والنفي', 'lessons' => ['الفعل الماضي', 'النفي']],
                    ['domain' => 1, 'name' => 'القصة والأمثال', 'lessons' => ['القصة الأمازيغية', 'الأمثال']],
                ],
            ],
            4 => [
                'domains' => ['فهم المنطوق والمكتوب', 'القواعد والتراث'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'النص الإخباري', 'lessons' => ['النص الإخباري', 'النص الحجاجي']],
                    ['domain' => 0, 'name' => 'الهوية والتراث', 'lessons' => ['التراث الثقافي الجزائري', 'الهوية']],
                    ['domain' => 1, 'name' => 'الجملة المركبة', 'lessons' => ['الجملة المركبة', 'أدوات الربط']],
                    ['domain' => 1, 'name' => 'الشعر والمسرح', 'lessons' => ['الشعر الأمازيغي', 'المسرح']],
                ],
            ],
        ],
        'التربية الفنية' => [
            1 => [
                'domains' => ['الفن التشكيلي', 'التربية الموسيقية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الألوان', 'lessons' => ['الألوان الأساسية', 'الألوان الثانوية']],
                    ['domain' => 0, 'name' => 'الخطوط والأشكال', 'lessons' => ['الخطوط والأشكال', 'الرسم الحر']],
                    ['domain' => 1, 'name' => 'السلم الموسيقي', 'lessons' => ['السلم الموسيقي', 'الإيقاع']],
                    ['domain' => 1, 'name' => 'الأناشيد', 'lessons' => ['الأناشيد الوطنية', 'الأغنية المدرسية']],
                ],
            ],
            2 => [
                'domains' => ['الفن التشكيلي', 'التربية الموسيقية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'المنظور والظل والنور', 'lessons' => ['المنظور', 'الظل والنور']],
                    ['domain' => 0, 'name' => 'الرسم الزخرفي', 'lessons' => ['الرسم الزخرفي', 'الأشغال اليدوية']],
                    ['domain' => 1, 'name' => 'الآلات الموسيقية', 'lessons' => ['الآلات الموسيقية', 'الإيقاع الثنائي']],
                    ['domain' => 1, 'name' => 'الموسيقى التقليدية', 'lessons' => ['الموسيقى التقليدية', 'الأناشيد']],
                ],
            ],
            3 => [
                'domains' => ['الفن التشكيلي', 'التربية الموسيقية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'الفن الزخرفي الإسلامي', 'lessons' => ['الفن الزخرفي الإسلامي', 'الخط العربي']],
                    ['domain' => 0, 'name' => 'التلوين والتركيب', 'lessons' => ['التلوين والتركيب', 'المشروع الفني']],
                    ['domain' => 1, 'name' => 'السلم والإيقاع', 'lessons' => ['السلم الموسيقي', 'القراءة الإيقاعية']],
                    ['domain' => 1, 'name' => 'الأغنية الجزائرية', 'lessons' => ['الأغنية الجزائرية', 'العزف']],
                ],
            ],
            4 => [
                'domains' => ['الفن التشكيلي', 'التربية الموسيقية'],
                'sequences' => [
                    ['domain' => 0, 'name' => 'المنظور والظل', 'lessons' => ['المنظور والظل', 'تحليل عمل فني']],
                    ['domain' => 0, 'name' => 'الزخرفة والتصميم', 'lessons' => ['الزخرفة الإسلامية', 'تصميم ملصق']],
                    ['domain' => 1, 'name' => 'الإيقاع والتوزيع', 'lessons' => ['الإيقاع المركب', 'التوزيع الموسيقي']],
                    ['domain' => 1, 'name' => 'التراث الموسيقي', 'lessons' => ['التراث الموسيقي الجزائري', 'مشروع فني']],
                ],
            ],
        ],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const SUBJECT_CODES = [
        'اللغة العربية' => 'AR',
        'الرياضيات' => 'MATH',
        'العلوم الفيزيائية والتكنولوجيا' => 'PHY',
        'علوم الطبيعة والحياة' => 'SVT',
        'التاريخ والجغرافيا' => 'HIS',
        'التربية الإسلامية' => 'ISL',
        'التربية المدنية' => 'CIV',
        'اللغة الفرنسية' => 'FR',
        'اللغة الإنجليزية' => 'EN',
        'الإعلام الآلي' => 'INFO',
        'التربية البدنية والرياضية' => 'PE',
        'اللغة الأمازيغية' => 'AMZ',
        'التربية الفنية' => 'ART',
    ];

    /**
     * @return list<string>
     */
    public static function subjectNames(): array
    {
        return array_keys(self::PROGRAMS);
    }

    /**
     * Return a complete, enriched programme for one subject and grade.
     *
     * @return list<array{
     *     name: string,
     *     code: string,
     *     description: string,
     *     sort_order: int,
     *     sequences: list<array{
     *         name: string,
     *         code: string,
     *         description: string,
     *         sort_order: int,
     *         competence: string,
     *         starting_situation: string,
     *         resources: list<array{
     *             title: string,
     *             competence: string,
     *             starting_situation: string,
     *             phases: list<array{phase_name: string, content: string}>,
     *             actual_content: string,
     *             homework: string
     *         }>
     *     }>
     * }>
     */
    public static function program(string $subject, int $gradeNumber): array
    {
        $grade = self::PROGRAMS[$subject][$gradeNumber] ?? null;

        if ($grade === null) {
            throw new \InvalidArgumentException("No curriculum programme for {$subject} / grade {$gradeNumber}.");
        }

        $subjectCode = self::SUBJECT_CODES[$subject] ?? 'SUB';
        $domains = [];

        foreach ($grade['domains'] as $domainIndex => $domainName) {
            $domains[$domainIndex] = [
                'name' => $domainName,
                'code' => $subjectCode.'-D'.($domainIndex + 1),
                'description' => 'ميدان '.$domainName.' ضمن برنامج '.$subject.' للسنة '.$gradeNumber.' متوسط.',
                'sort_order' => $domainIndex + 1,
                'sequences' => [],
            ];
        }

        foreach ($grade['sequences'] as $sequenceIndex => $sequence) {
            $domainIndex = $sequence['domain'];
            $sequenceName = $sequence['name'];
            $competence = self::competence($subject, $sequenceName);
            $situation = self::startingSituation($subject, $sequenceName);
            $resources = [];

            foreach ($sequence['lessons'] as $lessonIndex => $lessonTitle) {
                $resources[] = [
                    'title' => $lessonTitle,
                    'competence' => $competence,
                    'starting_situation' => $situation,
                    'phases' => self::phases($subject, $lessonTitle, $sequenceName),
                    'actual_content' => self::actualContent(
                        $subject,
                        $lessonTitle,
                        $sequenceName,
                        $competence,
                        $situation,
                    ),
                    'homework' => self::homework($subject, $lessonTitle),
                ];
            }

            $domains[$domainIndex]['sequences'][] = [
                'name' => $sequenceName,
                'code' => $subjectCode.'-S'.($sequenceIndex + 1),
                'description' => 'المقطع التعلمي '.$sequenceName.' — '.self::domainNameFor($domains, $domainIndex),
                'sort_order' => $sequenceIndex + 1,
                'competence' => $competence,
                'starting_situation' => $situation,
                'resources' => $resources,
            ];
        }

        return array_values(array_filter(
            $domains,
            static fn (array $domain): bool => $domain['sequences'] !== [],
        ));
    }

    /**
     * @param  array<int, array{name: string}>  $domains
     */
    private static function domainNameFor(array $domains, int $domainIndex): string
    {
        return $domains[$domainIndex]['name'] ?? 'الميدان';
    }

    private static function competence(string $subject, string $sequence): string
    {
        return match ($subject) {
            'اللغة العربية' => "يتمكن المتعلم من فهم «{$sequence}» واستخراج مؤشراته اللغوية، والتعبير عنه شفهيا وكتابيا في وضعيات دالة.",
            'الرياضيات' => "يحل المتعلم مشكلات متعلقة بـ«{$sequence}» باستعمال المفاهيم والقواعد المناسبة، ويبرر خطوات الحل.",
            'العلوم الفيزيائية والتكنولوجيا' => "يستثمر المتعلم مكتسباته حول «{$sequence}» لتفسير ظواهر من محيطه وإنجاز تجارب بسيطة.",
            'علوم الطبيعة والحياة' => "يفسر المتعلم «{$sequence}» اعتمادا على الملاحظة والتجريب، ويستنتج آثارها على الصحة والبيئة.",
            'التاريخ والجغرافيا' => "يتمكن المتعلم من تحليل «{$sequence}» وتوظيف الوثائق والخريطة لتفسير الظاهرة.",
            'التربية الإسلامية' => "يستظهر المتعلم «{$sequence}» ويفهم أحكامه ودروسه، ويوظفها في سلوكه اليومي.",
            'التربية المدنية' => "يتبنى المتعلم قيم «{$sequence}» ويمارسها في محيطه المدرسي والاجتماعي.",
            'اللغة الفرنسية' => "L'élève comprend et produit des énoncés relatifs à « {$sequence} » en respectant les règles de la langue.",
            'اللغة الإنجليزية' => "The pupil can understand and produce simple texts about \"{$sequence}\" using appropriate vocabulary and structures.",
            'الإعلام الآلي' => "يوظف المتعلم مهارات «{$sequence}» في إنجاز مهام رقمية بسيطة بأمان.",
            'التربية البدنية والرياضية' => "يتمكن المتعلم من أداء مهارات «{$sequence}» بثقة واحترام قواعد اللعب والعمل الجماعي.",
            'اللغة الأمازيغية' => "يتمكن المتعلم من فهم «{$sequence}» والتعبير عنها شفهيا وكتابيا باللغة الأمازيغية.",
            'التربية الفنية' => "يعبر المتعلم عن «{$sequence}» باستعمال العناصر التشكيلية أو الموسيقية المناسبة.",
            default => "يتمكن المتعلم من استثمار مكتسباته حول «{$sequence}» في وضعيات دالة.",
        };
    }

    private static function startingSituation(string $subject, string $sequence): string
    {
        return match ($subject) {
            'اللغة العربية' => "يُعرض على المتعلم نص من محيطه حول «{$sequence}»، ويُطلب منه تحديد الفكرة العامة والمؤشرات اللغوية.",
            'الرياضيات' => "يواجه المتعلم وضعية-مشكلة من الحياة اليومية تتعلق بـ«{$sequence}»، ويُطلب منه اقتراح حل وتعليل خطواته.",
            'العلوم الفيزيائية والتكنولوجيا' => "يلاحظ المتعلم ظاهرة مرتبطة بـ«{$sequence}»، ويطرح فرضية ثم ينجز تجربة للتحقق منها.",
            'علوم الطبيعة والحياة' => "تُعرض حالة صحية أو بيئية مرتبطة بـ«{$sequence}»، ويُطلب من المتعلم تفسيرها واستنتاج السلوك المناسب.",
            'التاريخ والجغرافيا' => "تُعرض وثيقة أو خريطة حول «{$sequence}»، ويُطلب استخراج المعلومات وبناء استنتاج تاريخي/جغرافي.",
            'التربية الإسلامية' => "تُطرح وضعية من الحياة اليومية حول «{$sequence}»، ويُطلب البحث عن الحكم الشرعي والفوائد العملية.",
            'التربية المدنية' => "تُطرح مشكلة داخل المؤسسة مرتبطة بـ«{$sequence}»، ويُطلب اقتراح حل مدني ومسؤول.",
            'اللغة الفرنسية' => "On présente une situation de communication autour de « {$sequence} » ; l'élève doit comprendre et produire un message.",
            'اللغة الإنجليزية' => "Pupils are presented with a situation about \"{$sequence}\" and asked to understand and produce a short message.",
            'الإعلام الآلي' => "تُعرض مهمة رقمية متعلقة بـ«{$sequence}»، ويُطلب إنجازها باستعمال الحاسوب بأمان.",
            'التربية البدنية والرياضية' => "تُنظم وضعية لعب أو تنقل حول «{$sequence}»، ويُطلب من المتعلم تطبيق المهارات واحترام القواعد.",
            'اللغة الأمازيغية' => "يُعرض نص أو حوار أمازيغي حول «{$sequence}»، ويُطلب فهمه والتعبير عنه.",
            'التربية الفنية' => "تُعرض صورة أو عمل فني حول «{$sequence}»، ويُطلب تحليله وإنتاج عمل مشابه.",
            default => "تُعرض وضعية دالة حول «{$sequence}»، ويُطلب من المتعلم استثمار مكتسباته.",
        };
    }

    /**
     * @return list<array{phase_name: string, content: string}>
     */
    private static function phases(string $subject, string $lesson, string $sequence): array
    {
        $contents = match ($subject) {
            'اللغة العربية' => [
                "يُطرح نص «{$lesson}» ويُطلب تحديد الفكرة العامة والكلمات المفتاحية.",
                "يقرأ المتعلمون النص قراءة صاممة، ثم يعملون في أفواج على استخراج الأفكار وتوظيف «{$sequence}».",
                "يُنجز المتعلمون تمارين تطبيقية حول «{$lesson}» مع تصحيح جماعي وتدوين الخلاصة.",
                "يقيّم المتعلمون مكتسباتهم عبر وضعية إدماجية قصيرة حول «{$lesson}».",
            ],
            'الرياضيات' => [
                "تُطرح وضعية-مشكلة عددية أو هندسية مرتبطة بـ«{$lesson}» وتُستثمر المكتسبات السابقة.",
                'يعمل المتعلمون في أفواج على تجريب فرضيات وحلول، ثم يُقارنون النتائج ويستنتجون القاعدة.',
                "تُنجز تمارين تطبيقية متدرجة حول «{$lesson}» مع تصحيح فردي وجماعي.",
                "يُقيّم المتعلمون مكتسباتهم عبر وضعية إدماجية تتضمن «{$lesson}» و«{$sequence}».",
            ],
            'العلوم الفيزيائية والتكنولوجيا' => [
                "يلاحظ المتعلمون ظاهرة مرتبطة بـ«{$lesson}» ويطرحون فرضيات ويحددون الأدوات.",
                'ينجز المتعلمون التجربة/التركيب، ويسجلون القياسات والملاحظات في أفواج.',
                'يُفسرون النتائج ويستنتجون العلاقة العلمية، ثم يوثقون الخلاصة.',
                "يُقيّمون مكتسباتهم عبر تمرين تطبيقي ووضعية تفسيرية حول «{$lesson}».",
            ],
            'علوم الطبيعة والحياة' => [
                "تُعرض وثيقة أو حالة مرتبطة بـ«{$lesson}» ويُطرح سؤال علمي.",
                'يستكشف المتعلمون الوثائق/المجسمات في أفواج ويجمعون المعطيات.',
                'يُحللون النتائج ويستنتجون العلاقة الوظيفية أو الظاهرة المدروسة.',
                "يُقيّمون مكتسباتهم عبر وضعية صحية/بيئية قصيرة حول «{$lesson}».",
            ],
            'التاريخ والجغرافيا' => [
                "تُعرض وثيقة أو خريطة حول «{$lesson}» ويُطرح سؤال الانطلاق.",
                "يستخرج المتعلمون المعطيات ويوظفون مصطلحات «{$sequence}» في أفواج.",
                'يُنظمون المعلومات ويبنون استنتاجا تاريخيا/جغرافيا مدعما بالوثائق.',
                "يُقيّمون عبر وضعية إدماجية قصيرة حول «{$lesson}».",
            ],
            'التربية الإسلامية' => [
                "يُطرح نص قرآني أو حديثي مرتبط بـ«{$lesson}» ويُطلب فهم معناه العام.",
                'يقرأ المتعلمون النص ويستخرجون الأحكام والفوائد في أفواج.',
                'يُناقشون التطبيقات العملية ويُدوّنون الخلاصة الشرعية.',
                "يُقيّمون مكتسباتهم عبر وضعية سلوكية حول «{$lesson}».",
            ],
            'التربية المدنية' => [
                "تُطرح وضعية من الحياة المدرسية مرتبطة بـ«{$lesson}» ويُطلب تحديد المشكلة.",
                'يعمل المتعلمون في أفواج على تحليل الوضعية واقتراح حلول مدنية.',
                "يُناقشون الحلول ويربطونها بقيم «{$sequence}» والقانون الداخلي.",
                "يُقيّمون عبر التزام عملي أو بطاقة مشروع حول «{$lesson}».",
            ],
            'اللغة الفرنسية' => [
                "On présente un support autour de « {$lesson} » et on dégage la situation de communication.",
                "Les élèves lisent/écoutent le support et repèrent les éléments de « {$sequence} ».",
                'Ils appliquent la règle ou le lexique dans des exercices variés.',
                "Ils produisent un court message et l'auto-évaluent.",
            ],
            'اللغة الإنجليزية' => [
                "The teacher presents a short text/dialogue about \"{$lesson}\" and activates prior knowledge.",
                "Pupils read/listen and identify key vocabulary and structures related to \"{$sequence}\".",
                'They practise the target language through guided exercises and pair work.',
                'They produce a short written/oral message and self-assess.',
            ],
            'الإعلام الآلي' => [
                "تُعرض مهمة رقمية مرتبطة بـ«{$lesson}» ويُحدد الهدف والأدوات.",
                'يستكشف المتعلمون البرنامج/الجهاز وينجزون الخطوات guided في أفواج.',
                'يطبقون المهارة على ملف أو تمرين جديد مع متابعة الأستاذ.',
                'يُقيّمون أعمالهم ويحفظون الملف، ويُسند لهم تمرين منزلي.',
            ],
            'التربية البدنية والرياضية' => [
                "يُنجز المتعلمون إحماء مناسبا ويُطرح هدف الحصة حول «{$lesson}».",
                'يُطبقون المهارة/الوضعية في أفواج مع تصحيح فني تدريجي.',
                'يُوظفون المهارة في لعب أو تنقل مركب مع احترام القواعد.',
                'يُقيّمون أداءهم ويُنجزون تمارين استطالة وتهدئة.',
            ],
            'اللغة الأمازيغية' => [
                "يُعرض نص/تسجيل أمازيغي حول «{$lesson}» ويُطرح سؤال الفهم.",
                "يستخرج المتعلمون المفردات والتراكيب المرتبطة بـ«{$sequence}».",
                'يُطبقون القاعدة في تمارين قراءة وكتابة.',
                'يُنتجون نصا قصيرا ويُقيّمون مكتسباتهم.',
            ],
            'التربية الفنية' => [
                "يُعرض عمل فني/مقطع موسيقي حول «{$lesson}» ويُطلب وصف الانطباع الأول.",
                'يُحلل المتعلمون العناصر التشكيلية/الإيقاعية في أفواج.',
                "يُنجزون عملا تطبيقيا فرديا أو جماعيا باستعمال «{$sequence}».",
                'يُعرض العمل ويُقيّم حسب معايير بسيطة.',
            ],
            default => [
                "تُطرح وضعية انطلاق حول «{$lesson}».",
                'يبني المتعلمون المفاهيم في أفواج.',
                'يُطبقون المكتسبات في تمارين.',
                'يُقيّمون مكتسباتهم.',
            ],
        };

        $names = ['وضعية الانطلاق', 'بناء التعلمات', 'الترسيخ والممارسة', 'التقويم والاستثمار'];
        $result = [];

        foreach ($names as $index => $name) {
            $result[] = ['phase_name' => $name, 'content' => $contents[$index]];
        }

        return $result;
    }

    private static function homework(string $subject, string $lesson): string
    {
        return match ($subject) {
            'اللغة العربية' => "أنجز تمارين الظواهر اللغوية حول «{$lesson}»، ولخّص النص في فقرة.",
            'الرياضيات' => "حل التمارين المقترحة حول «{$lesson}»، واكتب خطوات الحل.",
            'العلوم الفيزيائية والتكنولوجيا' => "أنجز تقريرا قصيرا عن «{$lesson}»، واذكر الملاحظات والاستنتاج.",
            'علوم الطبيعة والحياة' => "ارسم مخططا يوضح «{$lesson}»، وأجب عن أسئلة الوثيقة.",
            'التاريخ والجغرافيا' => "اكتب فقرة حول «{$lesson}» اعتمادا على الخريطة أو الوثيقة.",
            'التربية الإسلامية' => "استظهر النص المتعلق بـ«{$lesson}»، واكتب ثلاث فوائد عملية.",
            'التربية المدنية' => "أعدّ بطاقة حول «{$lesson}»، واقترح مبادرة مدنية داخل المؤسسة.",
            'اللغة الفرنسية' => "Rédiger un court paragraphe sur « {$lesson} » et réviser la règle correspondante.",
            'اللغة الإنجليزية' => "Write five sentences about \"{$lesson}\" and practise the new vocabulary.",
            'الإعلام الآلي' => "أنجز التمرين العملي حول «{$lesson}» على الحاسوب، واحفظ الملف.",
            'التربية البدنية والرياضية' => 'طبّق تمارين الإحماء والاستطالة يوميا، وسجّل نشاطك البدني.',
            'اللغة الأمازيغية' => "أعد كتابة النص حول «{$lesson}» باللغة الأمازيغية، واستظهر المفردات الجديدة.",
            'التربية الفنية' => "أنجز عملا فنيا حول «{$lesson}»، وقدّمه في الحصة القادمة.",
            default => "أنجز التمارين المنزلية حول «{$lesson}».",
        };
    }

    private static function actualContent(
        string $subject,
        string $lesson,
        string $sequence,
        string $competence,
        string $situation,
    ): string {
        $phases = self::phases($subject, $lesson, $sequence);
        $html = [
            '<p><strong>العنوان:</strong> '.e($lesson).'</p>',
            '<p><strong>الميدان/المقطع:</strong> '.e($sequence).'</p>',
            '<p><strong>الكفاءة المستهدفة:</strong> '.e($competence).'</p>',
            '<p><strong>الوضعية الانطلاقية:</strong> '.e($situation).'</p>',
            '<ul>',
        ];

        foreach ($phases as $phase) {
            $html[] = '<li><strong>'.e($phase['phase_name']).':</strong> '.e($phase['content']).'</li>';
        }

        $html[] = '</ul>';
        $html[] = '<p><strong>الواجب المنزلي:</strong> '.e(self::homework($subject, $lesson)).'</p>';

        return implode('', $html);
    }
}
