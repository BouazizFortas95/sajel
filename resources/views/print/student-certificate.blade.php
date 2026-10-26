<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('شهادة التميز') }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 14mm 22mm 14mm; @bottom-right { content: counter(page); font-size: 9pt; } }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', 'Tahoma', 'Arial', sans-serif; background: #fff8e7; color: #5d3a00; line-height: 1.7; padding: 10mm; }
        .frame { border: 3pt double #c28a2a; padding: 10mm; border-radius: 6pt; background: linear-gradient(135deg, #fffdf5 0%, #fdf5e6 100%); position: relative; }
        .frame::before { content: ''; position: absolute; top: 4pt; left: 4pt; right: 4pt; bottom: 4pt; border: 1pt solid #d4a843; border-radius: 4pt; pointer-events: none; }
        .header { text-align: center; padding-bottom: 6mm; border-bottom: 2pt solid #c28a2a; margin-bottom: 8mm; }
        .header h1 { font-size: 26pt; color: #b8860b; letter-spacing: 2pt; text-shadow: 0 1pt 0 rgba(180,130,20,0.15); }
        .header .sub { font-size: 11pt; color: #7a5a20; margin-top: 2pt; font-weight: 600; }
        .school-name { font-size: 14pt; font-weight: 800; color: #8b5a2b; letter-spacing: 1pt; }
        .student-box { text-align: center; margin: 8mm 0; padding: 6mm; background: rgba(200,160,60,0.08); border: 1.5pt dashed #c28a2a; border-radius: 8pt; }
        .student-box h2 { font-size: 22pt; color: #5d3a00; font-weight: 800; }
        .student-box .group { font-size: 12pt; color: #8b5a2b; font-weight: 600; }
        .badges { display: flex; flex-wrap: wrap; gap: 4pt; justify-content: center; margin: 6mm 0; }
        .badge { display: inline-block; padding: 3pt 8pt; border-radius: 12pt; font-weight: 700; font-size: 10pt; border: 1pt solid #d4a843; background: #fff; color: #7a5a20; }
        .badge.fire { background: #fff0e0; border-color: #e26b2a; color: #c23a10; }
        .badge.rocket { background: #e8f5e9; border-color: #2e7d32; color: #1b5e20; }
        .badge.star { background: #fff9c4; border-color: #f0c02e; color: #7a6a00; }
        .badge.hand { background: #e3f2fd; border-color: #1565c0; color: #0d47a1; }
        .reason { font-size: 13pt; text-align: center; padding: 4mm 6mm; background: rgba(200,160,60,0.08); border-right: 3pt solid #c28a2a; border-radius: 4pt; color: #5d3a00; font-weight: 600; }
        .signature-row { display: flex; justify-content: space-between; margin-top: 10mm; padding-top: 4mm; border-top: 1.5pt solid #c28a2a; }
        .sig { text-align: center; width: 45%; font-size: 11pt; }
        .sig strong { display: block; font-size: 13pt; color: #7a5a20; margin-top: 2mm; border-bottom: 1pt solid #c28a2a; padding-bottom: 2mm; }
        .date-label { font-size: 10pt; color: #999; text-align: center; margin-top: 4mm; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="header">
            <div class="school-name">{{ $schoolSetting?->school_name ?? __('المدرسة المتوسطة') }}</div>
            <div class="sub">{{ __('الجمهورية الجزائرية الديمقراطية الشعبية — وزارة التربية الوطنية') }} · {{ $schoolSetting?->academic_year ?? now()->year }}/{{ now()->year + 1 }}</div>
            <h1>📜 {{ __('شهادة التميز والتحفيز التربوي') }}</h1>
            <div class="sub">{{ __('سجِّل — منصة التسجيل اليومي والتفاعل الصفي') }} · {{ now()->format('d/m/Y') }}</div>
        </div>

        <div class="student-box">
            <h2>{{ $student->full_name }}</h2>
            <div class="group">{{ $student->group?->name ?? __('القسم كاملاً') }} · {{ $student->group?->classroom?->name ?? __('—') }}</div>
        </div>

        <div class="badges" aria-label="Earned badges">
            @forelse ($badges as $key => $badge)
                <span class="badge {{ match($key) { 'attendance_streak' => 'fire', 'comeback_champion' => 'rocket', 'academic_excellence' => 'star', 'positive_role_model' => 'hand', default => '' } }}">{{ $badge['label'] ?? $key }}</span>
            @empty
                <span class="badge">{{ __('لا توجد أوسمة بعد') }}</span>
            @endforelse
        </div>

        <div class="reason">
            {{ __('نُمنح هذه الشهادة تقديراً للجهد المتميز والمشاركة الإيجابية والتقدم الملحوظ في المسار التعليمي.') }}
        </div>

        <div class="signature-row">
            <div class="sig">
                <strong>{{ __('الأستاذ(ة)') }}</strong>
                <div>{{ __('التوقيع') }}</div>
            </div>
            <div class="sig">
                <strong>{{ __('مدير(ة) المتوسطة') }}</strong>
                <div>{{ __('التوقيع والموافقة') }}</div>
            </div>
        </div>

        <div class="date-label">{{ __('تاريخ الإصدار') }}: {{ now()->format('d/m/Y — H:i') }}</div>
    </div>
</body>
</html>
