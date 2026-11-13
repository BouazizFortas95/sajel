<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
app()->setLocale('ar');

use App\Models\PedagogicalMemo;
use App\Services\PedagogicalMemoPrintService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\Console\Kernel;

$memo = PedagogicalMemo::find(2);
if (! $memo) {
    echo "Memo 2 not found\n";
    exit(1);
}

// Clean and fix Memo 2 attributes
$clean = function (?string $text, int $limit = 350): string {
    if (! $text) {
        return '';
    }
    $s = trim($text);
    $s = preg_replace('/(\b.{10,80}\b)(?:\s*\1){2,}/u', '$1', $s) ?? $s;
    $patterns = [
        '/(?:دائماً وأبداً\s*)+(?:آم[يـ]ن يا رب العالمين)?/u',
        '/(?:بكل فخر واعتزاز|لا يتزعزع أبداً|عبر ربوع الوطن الحبيب والغالي|في متوسطات الوطن الحبيب)\s*/u',
        '/(?:وصلى الله وسلم على سيدنا محمد وعلى آله وصحبه وسلم تسليما كثيرا)\s*/u',
        '/(?:والحمد لله رب العالمين)\s*/u',
    ];
    $s = preg_replace($patterns, ' ', $s) ?? $s;
    $s = preg_replace('/\s{2,}/u', ' ', $s) ?? $s;

    return mb_substr(trim($s), 0, $limit);
};

$memo->title = 'تعريف المعلوماتية و الحاسوب ومكوناته';
$memo->phase = 'بيئة التعامل مع الحاسوب';
$memo->competence = 'أن يتعرف المتعلم على بيئة التعامل مع الحاسوب، مكوناته الأساسية، وطرق تشغيله وإطفائه بسلام، لتوظيفها في إنجاز أعماله الرقمية بكفاءة.';
$memo->starting_situation = 'شهد العالم تطوراً غير مسبوق في عالم التكنولوجيا وعلوم الحاسوب، فأصبحت الحواسيب مستعملة في التعليم، الطب، البنوك، والإدارات... فما المقصود بالمعلوماتية؟ وما هي المكونات الأساسية للحاسوب وكيف يعمل؟';
$memo->resources = 'الكتاب المدرسي، المنهاج الرسمي، السبورة، أجهزة الحواسيب، عارض البيانات (Data Show).';
$memo->evaluation = 'صنّف العناصر التالية في جدول ذي عمودين (العتاد / البرمجيات): لوحة المفاتيح، الفأرة، نظام ويندوز، برنامج الرسام، الطابعة، معالج النصوص Word.';
$memo->notes = 'المنهاج السنوي، الوثيقة المرافقة للأستاذ، مراعاة الفروق الفردية ودعم المتعثرين.';

$memo->activities = [
    [
        'name' => 'وضعية الانطلاق والتهيئة النفسية',
        'duration' => 10,
        'strategy' => 'العصف الذهني والحوار الموجه',
        'teacher_role' => 'طرح إشكالية استخدام التكنولوجيا في الحياة اليومية وتوجيه أسئلة لاستثارة الدافعية.',
        'student_role' => 'الملاحظة والتعبير الحر عن التصورات الأولية واقتراح فرضيات تفسيرية.',
        'written_trace' => 'تدوين الإشكالية والفرضيات الأولية المقترحة على السبورة.',
        'evaluation_indicator' => 'يسترجع المكتسبات القبلية ذات الصلة ويصوغ فرضيات منطقية وجيهة.',
        'content' => 'استدراج التلاميذ عبر أمثلة حية عن دور الحواسيب في المؤسسات والمنازل لصياغة الإشكالية.',
    ],
    [
        'name' => 'العرض (إرساء الموارد وبناء التعلمات)',
        'duration' => 35,
        'strategy' => 'التفكير — المشاركة — العرض',
        'teacher_role' => 'عرض السندات والمخططات وتوجيه مجموعات العمل لاستنتاج تعريف المعلوماتية والحاسوب.',
        'student_role' => 'الملاحظة والتمييز بين العتاد والبرمجيات والمشاركة في صياغة الخلاصة والمخطط.',
        'written_trace' => "1- تعريف المعلوماتية: علم معالجة المعلومات آلياً (عتاد + برمجيات).\n2- الفرق بين العتاد (مادي) والبرمجيات (لامادية).\n3- تعريف الحاسوب: جهاز إلكتروني مبرمج لمعالجة وتخزين البيانات.\n4- مخطط السيرورة: إدخال ⬅ معالجة وتخزين ⬅ إخراج.",
        'evaluation_indicator' => 'يميز بدقة بين العتاد والبرمجيات ويحدد وظائف مكونات الحاسوب.',
        'content' => 'استنتاج المفاهيم الأساسية للمعلوماتية والتمييز بين المكونات المادية والبرمجية.',
    ],
    [
        'name' => 'التقويم والمعالجة (استثمار المكتسبات)',
        'duration' => 15,
        'strategy' => 'بطاقة الخروج (عمل فردي موجه)',
        'teacher_role' => 'اقتراح تمرين تطبيقي لتصنيف المكونات ومتابعة الإنجاز الفردي وتقديم التغذية الراجعة.',
        'student_role' => 'تصنيف العناصر المقترحة في الجدول والتحقق الذاتي من صحة الحل.',
        'written_trace' => 'تسجيل الحل النموذجي لجدول تصنيف العتاد والبرمجيات في كراس الدروس.',
        'evaluation_indicator' => 'يصنف مكونات الحاسوب بدقة بنسبة تحكم لا تقل عن 80%.',
        'content' => 'تطبيق فردي فوري لتقويم التحكم في المورد المعرفي وتثبيت الحل النموذجي.',
    ],
];

$memo->save();
echo "Memo 2 updated in DB successfully.\n";

$service = app(PedagogicalMemoPrintService::class);
$html = $service->pdfHtml($memo);

$pdfPath = 'C:/Users/FIddler/.gemini/antigravity/brain/2c6a395c-f8e2-4594-be99-01de51ecf1c0/preview_modern_pedagogical_memo.pdf';
$pngPath = 'C:/Users/FIddler/.gemini/antigravity/brain/2c6a395c-f8e2-4594-be99-01de51ecf1c0/preview_modern_pedagogical_memo.png';

BrowsershotFactory::html($html)
    ->paperSize(210, 297, 'mm')
    ->margins(4, 5, 4, 5)
    ->savePdf($pdfPath);

BrowsershotFactory::html($html)
    ->windowSize(1200, 1697)
    ->deviceScaleFactor(1.5)
    ->save($pngPath);

echo "PDF and PNG rendered successfully!\n";
