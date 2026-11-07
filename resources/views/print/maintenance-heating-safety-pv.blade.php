<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('بطاقة المراقبة التقنية والأمنية للتدفئة المركزية') }} - {{ $log_reference }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Amiri', serif; font-size: 9.5pt; color: #111; background: #fff; direction: rtl; padding: 8mm; }
  @page { size: A4 landscape; margin: 6mm; }
  @media print { body { padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

  .republic-header { text-align: center; border-bottom: 2px solid #c62828; padding-bottom: 4px; margin-bottom: 6px; }
  .republic-header .republic { font-size: 11pt; color: #b71c1c; font-weight: bold; }
  .republic-header .ministry { font-size: 9.5pt; color: #333; }
  .republic-header .meta-row { display: flex; justify-content: space-between; font-size: 8.5pt; margin-top: 2px; }

  .doc-title { text-align: center; margin: 4px 0 8px; }
  .doc-title h1 { font-size: 12.5pt; font-weight: bold; color: #b71c1c; border: 2px solid #c62828; display: inline-block; padding: 3px 20px; background: #ffebee; border-radius: 4px; }
  .doc-title .decree { font-size: 8pt; color: #555; margin-top: 2px; }

  .ref-banner { display: flex; justify-content: space-between; background: #fafafa; border: 1px solid #ddd; border-radius: 4px; padding: 4px 10px; margin-bottom: 8px; font-size: 8.5pt; }

  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }

  .section-box { border: 1px solid #c5cae9; border-radius: 4px; overflow: hidden; margin-bottom: 6px; }
  .section-title { background: #e8eaf6; color: #1a237e; font-weight: bold; font-size: 9pt; padding: 3px 8px; border-bottom: 1px solid #c5cae9; }
  .section-body { padding: 5px 8px; font-size: 8.5pt; line-height: 1.4; }

  .table-clean { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8.5pt; }
  .table-clean th, .table-clean td { border: 1px solid #ddd; padding: 4px 6px; text-align: center; }
  .table-clean th { background: #f5f5f5; font-weight: bold; color: #333; }

  .badge-pass { background: #e8f5e9; color: #2e7d32; font-weight: bold; padding: 2px 6px; border-radius: 3px; border: 1px solid #a5d6a7; }
  .badge-fail { background: #ffebee; color: #c62828; font-weight: bold; padding: 2px 6px; border-radius: 3px; border: 1px solid #ef9a9a; }

  .signatures { display: flex; justify-content: space-between; gap: 8px; margin-top: 10px; }
  .sig-box { flex: 1; border: 1px solid #999; padding: 4px; text-align: center; min-height: 70px; background: #fafafa; border-radius: 4px; font-size: 8pt; }
  .sig-box .title { font-weight: bold; color: #b71c1c; font-size: 8.5pt; margin-bottom: 3px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }

  .print-btn { position: fixed; bottom: 15px; left: 15px; background: #c62828; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 9pt; font-weight: bold; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<div class="republic-header">
  <div class="republic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
  <div class="ministry">وزارة التربية الوطنية — مديرية التربية الوطنية</div>
  <div class="meta-row">
    <div>المؤسسة: <strong>{{ $school_name }}</strong></div>
    <div>المحطة المركزية للتدفئة والغلايات (Chaufferie Centrale)</div>
    <div>السنة الدراسية: <strong>{{ $academic_year }}</strong></div>
  </div>
</div>

<div class="doc-title">
  <h1>{{ __('بطاقة المراقبة التقنية والأمنية للتدفئة المركزية والغلايات') }}</h1>
  <div class="decree">Fiche de contrôle et de sécurité de la chaufferie centrale — التحضير لموسم الشتاء والوقاية من أخطار الغاز وأحادي أكسيد الكربون</div>
</div>

<div class="ref-banner">
  <div>رقم محضر المراقبة: <strong>{{ $log_reference }}</strong></div>
  <div>تاريخ المعاينة: <strong>{{ $heating_log->inspection_date?->format('Y-m-d') }}</strong></div>
  <div>رمز الغلاية: <strong>{{ $heating_log->boiler_code }}</strong></div>
  <div>نوع الوقود: <strong>{{ $heating_log->fuel_type?->label() }}</strong></div>
  <div>الهيئة القائمة بالمعاينة: <strong>{{ $heating_log->inspector_entity }}</strong></div>
  <div>القرار النهائي: <strong style="color: {{ $heating_log->status?->badgeColor() === 'success' ? '#2e7d32' : '#c62828' }}">{{ $heating_log->status?->label() }}</strong></div>
</div>

<div class="grid-2">
  <div class="section-box">
    <div class="section-title">1. المواصفات التقنية لمنظومة التدفئة والغلاية</div>
    <div class="section-body">
      <table class="table-clean">
        <tr>
          <th style="width: 35%;">العلامة والطراز:</th>
          <td>{{ $heating_log->boiler_brand_model ?: '—' }}</td>
          <th style="width: 30%;">سنة التركيب:</th>
          <td>{{ $heating_log->installation_year ?: '—' }}</td>
        </tr>
        <tr>
          <th>ضغط الماء بالشبكة:</th>
          <td><strong>{{ $heating_log->pressure_bar }} Bar</strong> (المعدل: 1.5 - 2.0 Bar)</td>
          <th>درجة حرارة الماء:</th>
          <td><strong>{{ $heating_log->water_temp_celsius }} °C</strong></td>
        </tr>
        <tr>
          <th>حالة المشعل (Brûleur):</th>
          <td colspan="3">{{ $heating_log->burner_operational_state }}</td>
        </tr>
      </table>
    </div>
  </div>

  <div class="section-box">
    <div class="section-title">2. فحص وتدقيق تدابير السلامة والوقاية الإلزامية</div>
    <div class="section-body">
      <table class="table-clean">
        <thead>
          <tr>
            <th>عنصر الفحص والمطابقة</th>
            <th>المعيار المطلوب</th>
            <th>النتيجة المعاينة</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>صمام الأمان (Soupape de sécurité)</td>
            <td>التفريغ والتجريب عند الضغط المحدد</td>
            <td>
              <span class="{{ $heating_log->safety_valve_tested ? 'badge-pass' : 'badge-fail' }}">
                {{ $heating_log->safety_valve_tested ? __('تمت التجربة وصالح') : __('غير صالح / خلل') }}
              </span>
            </td>
          </tr>
          <tr>
            <td>كاشف أحادي أكسيد الكربون (Détecteur CO)</td>
            <td>جاهزية البطارية وإشارة التحسس</td>
            <td>
              <span class="{{ $heating_log->co_detector_tested ? 'badge-pass' : 'badge-fail' }}">
                {{ $heating_log->co_detector_tested ? __('جاهز ومفعل') : __('معطل / غائب') }}
              </span>
            </td>
          </tr>
          <tr>
            <td>تهوية قاعة التدفئة (Ventilation haute/basse)</td>
            <td>منافذ تهوية طبيعية دائمة غير مسدودة</td>
            <td>
              <span class="{{ $heating_log->room_ventilation_ok ? 'badge-pass' : 'badge-fail' }}">
                {{ $heating_log->room_ventilation_ok ? __('مطابقة وسليمة') : __('غير مطابقة') }}
              </span>
            </td>
          </tr>
          <tr>
            <td>تنظيف وسلخ المداخن (Ramonage des conduits)</td>
            <td>قنوات خالية من الترسبات وتفريغ سليم</td>
            <td>
              <span class="{{ $heating_log->chimneys_cleaned ? 'badge-pass' : 'badge-fail' }}">
                {{ $heating_log->chimneys_cleaned ? __('مُنظفة ومطابقة') : __('بحاجة لتنظيف') }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="section-title">3. الملاحظات التقنية والتوصيات والإصلاحات المنجزة</div>
  <div class="section-body" style="min-height: 40px;">
    {{ $heating_log->observations_and_repairs ?: __('تمت مراقبة واختبار منظومة التدفئة المركزية وتطهيرها من الهواء، والتأكد من مطابقة شروط السلامة للوقاية من التسمم بأحادي أكسيد الكربون، وهي جاهزة للتشغيل الآمن.') }}
  </div>
</div>

<div class="signatures">
  <div class="sig-box">
    <div class="title">{{ __('التقني القائم بالمعاينة / المفتش') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">{{ $heating_log->certified_by_name }}</div>
  </div>
  <div class="sig-box">
    <div class="title">{{ __('المقتصد / رئيس المصالح الاقتصادية') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">مراقبة ومتابعة التدفئة المركزية</div>
  </div>
  <div class="sig-box">
    <div class="title">{{ __('مدير المؤسسة') }}</div>
    <div style="font-size: 7.5pt; margin-top: 25px;">الإذن بالتشغيل والمصادقة الرسمية</div>
  </div>
</div>

<button class="print-btn" onclick="window.print()">{{ __('طباعة المحضر (Imprimer)') }}</button>

</body>
</html>

