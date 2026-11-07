<?php

namespace Database\Seeders;

use App\Models\EducationalInstitution;
use Illuminate\Database\Seeder;

class EducationalInstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            [
                'name' => 'متوسطة الشهيد العربي بن مهيدي',
                'code' => '30-1102-0007',
                'stage' => 'middle',
                'directorate_name' => 'مديرية التربية لولاية الوادي',
                'wilaya' => 'الوادي',
                'municipality' => 'أم الطيور',
                'principal_name' => 'عمر بن سالم',
                'counselor_name' => 'سليمان عبد الحي',
                'phone' => '032-21-44-12',
                'email' => 'cem.benmhidi@education.dz',
                'address' => 'حي 05 جويلية، بلدية أم الطيور، ولاية الوادي',
                'capacity' => 650,
                'regime' => 'half_board',
                'status' => 'active',
                'is_current' => true,
                'academic_year' => '2026-2027',
                'notes' => 'المؤسسة المعتمدة الحالية للمنظومة (متوسطة نظامية مزودة بنصف داخلية ووحدة كشف مدرسي).',
            ],
            [
                'name' => 'ثانوية مفدي زكريا',
                'code' => '47-0201-0015',
                'stage' => 'secondary',
                'directorate_name' => 'مديرية التربية لولاية غرداية',
                'wilaya' => 'غرداية',
                'municipality' => 'بني يزقن',
                'principal_name' => 'إبراهيم باحمد',
                'counselor_name' => 'مصطفى بلحاج',
                'phone' => '029-88-12-30',
                'email' => 'lycee.moufdi@education.dz',
                'address' => 'شارع الشهداء، بني يزقن، ولاية غرداية',
                'capacity' => 900,
                'regime' => 'half_board',
                'status' => 'active',
                'is_current' => false,
                'academic_year' => '2026-2027',
                'notes' => 'ثانوية عامة وتكنولوجية مرخصة على المنصة.',
            ],
            [
                'name' => 'متوسطة الأمير عبد القادر',
                'code' => '25-0103-0022',
                'stage' => 'middle',
                'directorate_name' => 'مديرية التربية لولاية قسنطينة',
                'wilaya' => 'قسنطينة',
                'municipality' => 'سيدي مبروك',
                'principal_name' => 'عبد الرزاق بوشامة',
                'counselor_name' => 'نادية بن عيسى',
                'phone' => '031-64-55-80',
                'email' => 'cem.emir@education.dz',
                'address' => 'حي سيدي مبروك الأعلى، قسنطينة',
                'capacity' => 720,
                'regime' => 'externat',
                'status' => 'active',
                'is_current' => false,
                'academic_year' => '2026-2027',
                'notes' => 'مؤسسة نموذجية تابعة لولاية قسنطينة.',
            ],
            [
                'name' => 'ابتدائية الشيخ عبد الحميد بن باديس',
                'code' => '31-0305-0011',
                'stage' => 'primary',
                'directorate_name' => 'مديرية التربية لولاية وهران',
                'wilaya' => 'وهران',
                'municipality' => 'السانية',
                'principal_name' => 'فاطمة الزهراء قادري',
                'counselor_name' => null,
                'phone' => '041-52-11-90',
                'email' => 'ecole.benbadis@education.dz',
                'address' => 'حي السلام، السانية، وهران',
                'capacity' => 420,
                'regime' => 'half_board',
                'status' => 'active',
                'is_current' => false,
                'academic_year' => '2026-2027',
                'notes' => 'مدرسة ابتدائية مزودة بمطعم مدرسي.',
            ],
            [
                'name' => 'ثانوية العقيد لطفي',
                'code' => '16-0204-0033',
                'stage' => 'secondary',
                'directorate_name' => 'مديرية التربية لولاية الجزائر وسط',
                'wilaya' => 'الجزائر',
                'municipality' => 'القبة',
                'principal_name' => 'كمال الدين بوخالفة',
                'counselor_name' => 'أمينة مزيان',
                'phone' => '021-28-70-14',
                'email' => 'lycee.colonellotfi@education.dz',
                'address' => 'شارع الإخوة بوعدو، القبة، الجزائر العاصمة',
                'capacity' => 1100,
                'regime' => 'half_board',
                'status' => 'pending',
                'is_current' => false,
                'academic_year' => '2026-2027',
                'notes' => 'ثانوية قيد التجهيز الرقمي تمهيداً لبدء التشغيل الفعلي.',
            ],
        ];

        foreach ($institutions as $data) {
            EducationalInstitution::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
