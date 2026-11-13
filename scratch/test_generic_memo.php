<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Level;
use App\Models\PedagogicalMemo;
use App\Models\Subject;
use App\Models\User;
use App\Services\PedagogicalMemoPrintService;
use App\Support\BrowsershotFactory;
use Illuminate\Contracts\Console\Kernel;

$mathSubject = Subject::where('name', 'like', '%رياضيات%')->first() ?? Subject::first();
$level = Level::first();
$user = User::first();

$genericMemo = new PedagogicalMemo([
    'user_id' => $user->id,
    'subject_id' => $mathSubject->id,
    'level_id' => $level->id,
    'title' => 'المعادلات والمتراجحات من الدرجة الأولى',
    'phase' => 'أنشطة عددية — الحساب الحرفي',
    'competence' => 'يحل معادلة من الدرجة الأولى بمجهول واحد ويوظفها في ترييض مشكلات من الحياة اليومية بدقة.',
    'starting_situation' => 'اشترى يوسف 3 كتب وقلم بمبلغ 750 دج، إذا علمت أن سعر القلم 90 دج، فما هو سعر الكتاب الواحد؟ كيف نترجم هذه المسألة إلى مساواة رياضية لحلها؟',
    'resources' => 'الكتاب المدرسي، المنهاج، السبورة، الآلات الحاسبة، أوراق الأنشطة الفردية.',
    'evaluation' => 'حل المعادلة التالية: 4x - 12 = 20، ثم تحقق من صحة الحل عددياً.',
    'notes' => 'المنهاج الرسمي، الوثيقة المرافقة، مراعاة الفروق الفردية وخطوات حل المعادلات.',
    'activities' => [
        [
            'name' => 'وضعية الانطلاق والتهيئة النفسية',
            'duration' => 10,
            'strategy' => 'العصف الذهني والحوار الموجه',
            'teacher_role' => 'طرح المسألة وتوجيه التلاميذ لترجمة الكلمات إلى أرقام ورموز مجهولة.',
            'student_role' => 'استرجاع مفهوم المجهول والعمليات الأساسية وصياغة الفرضيات الأولية.',
            'written_trace' => 'تدوين المعادلة الأولية المقترحة على السبورة: 3x + 90 = 750.',
            'evaluation_indicator' => 'يعبر عن نص الوضعية بعبارة جبرية سليمة بمجهول واحد.',
            'content' => 'استرجاع مفهوم المجهول وترجمة المسألة الحياتية إلى مساواة رياضية.',
        ],
        [
            'name' => 'العرض (إرساء الموارد وبناء التعلمات)',
            'duration' => 35,
            'strategy' => 'التفكير — المشاركة — العرض',
            'teacher_role' => 'تقديم مراحل عزل المجهول وتطبيق خواص المساواة والمرافقة الفوجية.',
            'student_role' => 'تطبيق خواص المساواة بنقل الحدود وتغيير الإشارات واستنتاج قيمة المجهول.',
            'written_trace' => "1- قاعدة حل المعادلة: عزل الحدود المجهولة في طرف والمعلومة في طرف.\n2- خواص المساواة: إضافة أو طرح نفس العدد من طرفي المعادلة لا يغير صحتها.\n3- الخلاصة: حل المعادلة ax + b = c هو x = (c - b) / a.",
            'evaluation_indicator' => 'يتحكم في خطوات عزل المجهول وحساب الحل بدقة.',
            'content' => 'بناء قاعدة حل معادلة من الدرجة الأولى وتطبيق خواص العمليات على المساواة.',
        ],
        [
            'name' => 'التقويم والمعالجة (استثمار المكتسبات)',
            'duration' => 15,
            'strategy' => 'بطاقة الخروج (عمل فردي موجه)',
            'teacher_role' => 'طرح المعادلة التقويمية ومتابعة خطوات الحل ورصد الأخطاء الشائعة في الإشارات.',
            'student_role' => 'حل المعادلة فردياً والتحقق من صحة النتيجة بتعويض x بالحل المتوصل إليه.',
            'written_trace' => 'تسجيل الحل النموذجي: 4x = 32 إذن x = 8، والتحقق: 4(8) - 12 = 20 (صحيح).',
            'evaluation_indicator' => 'يجد حل المعادلة ويتحقق من صحته بنسبة تحكم تامة.',
            'content' => 'تطبيق فوري لتقويم حل المعادلات وتقديم معالجة فورية لأخطاء الإشارات.',
        ],
    ],
]);
$genericMemo->id = 15;
$genericMemo->setRelation('subject', $mathSubject);
$genericMemo->setRelation('level', $level);
$genericMemo->setRelation('user', $user);

$service = app(PedagogicalMemoPrintService::class);
$html = $service->pdfHtml($genericMemo);

$pdfPath = 'C:/Users/FIddler/.gemini/antigravity/brain/2c6a395c-f8e2-4594-be99-01de51ecf1c0/preview_generic_memo.pdf';
$pngPath = 'C:/Users/FIddler/.gemini/antigravity/brain/2c6a395c-f8e2-4594-be99-01de51ecf1c0/preview_generic_memo.png';

BrowsershotFactory::html($html)
    ->paperSize(210, 297, 'mm')
    ->margins(4, 5, 4, 5)
    ->savePdf($pdfPath);

BrowsershotFactory::html($html)
    ->windowSize(1200, 1697)
    ->deviceScaleFactor(1.5)
    ->save($pngPath);

echo "Generic memo preview rendered successfully!\n";
