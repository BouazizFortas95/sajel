<?php

namespace App\Services;

use App\Enums\AscsDisciplineCategory;
use App\Enums\AscsTransactionType;
use App\Enums\SportsCompetitionLevel;
use App\Enums\SportsEquipmentCondition;
use App\Enums\SportsGenderTarget;
use App\Enums\SportsLicenseType;
use App\Enums\SportsMatchResult;
use App\Enums\SportsMedicalAptitude;
use App\Enums\StudentAgeCategory;
use App\Models\AscsFinancialTransaction;
use App\Models\SchoolSportsAssociation;
use App\Models\SportsCompetitionEvent;
use App\Models\SportsCulturalDiscipline;
use App\Models\SportsEquipmentInventory;
use App\Models\StudentSportsLicense;
use Carbon\Carbon;

class SchoolSportsService
{
    public function currentAcademicYear(): string
    {
        $now = Carbon::now();
        $year = $now->year;

        return $now->month >= 9 ? "{$year}/".($year + 1) : ($year - 1)."/{$year}";
    }

    public function generateLicenseNumber(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = StudentSportsLicense::where('academic_year', $year)->count() + 1;

        return sprintf('LIC-%s-%03d', $cleanYear, $count);
    }

    public function generateDisciplineCode(): string
    {
        $count = SportsCulturalDiscipline::count() + 1;

        return sprintf('DISC-%02d', $count);
    }

    public function generateEventCode(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = SportsCompetitionEvent::where('academic_year', $year)->count() + 1;

        return sprintf('EVT-%s-%03d', $cleanYear, $count);
    }

    public function generateTransactionReference(?string $academicYear = null): string
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $cleanYear = str_replace('/', '-', $year);

        $count = AscsFinancialTransaction::where('academic_year', $year)->count() + 1;

        return sprintf('FIN-%s-%03d', $cleanYear, $count);
    }

    public function generateEquipmentCode(): string
    {
        $count = SportsEquipmentInventory::count() + 1;

        return sprintf('SPT-EQ-%02d', $count);
    }

    public function getOrCreateAssociation(?string $academicYear = null): SchoolSportsAssociation
    {
        $year = $academicYear ?? $this->currentAcademicYear();

        $association = SchoolSportsAssociation::where('academic_year', $year)->first();
        if ($association) {
            return $association;
        }

        return SchoolSportsAssociation::create([
            'association_name' => 'الجمعية الثقافية والرياضية المدرسية (ASCS)',
            'academic_year' => $year,
            'affiliation_number' => 'LWSS/FASS-'.str_replace('/', '-', $year).'-01',
            'approval_receipt_number' => 'وصل إيداع وتأسيس رقم 26/'.substr($year, 0, 4),
            'approval_date' => Carbon::parse(substr($year, 0, 4).'-09-25'),
            'principal_president_name' => 'مدير المؤسسة (رئيس الجمعية بحكم القانون)',
            'secretary_general_name' => 'أستاذ التربية البدنية والرياضية (الكاتب العام)',
            'treasurer_name' => 'المقتصد / مسير المصالح المالية (أمين المال)',
            'executive_bureau_members' => [
                ['role' => 'الرئيس', 'name' => 'مدير المؤسسة التعليمية'],
                ['role' => 'الكاتب العام', 'name' => 'أستاذ منسق مادة التربية البدنية'],
                ['role' => 'أمين المال', 'name' => 'المقتصد / نائب المقتصد'],
                ['role' => 'نائب الكاتب العام', 'name' => 'أستاذ التربية الموسيقية / الفنية'],
                ['role' => 'مساعد أمين المال', 'name' => 'مساعد رئيسي للتربية'],
                ['role' => 'مندوب التلاميذ', 'name' => 'تلميذ مندوب منتخب'],
            ],
            'annual_subscription_fee' => 200.00,
            'active_members_count' => 0,
            'status' => 'Active',
            'notes' => 'الجمعية الثقافية والرياضية المدرسية مؤسسة طبقاً للأمر 08-04 والمرسوم التنفيذي المنظم للنشاط الرياضي المدرسي.',
        ]);
    }

    public function createDiscipline(array $data): SportsCulturalDiscipline
    {
        $code = $data['discipline_code'] ?? $this->generateDisciplineCode();

        $category = $data['category'] instanceof AscsDisciplineCategory
            ? $data['category']
            : AscsDisciplineCategory::from($data['category']);

        $targetGenders = isset($data['target_genders'])
            ? ($data['target_genders'] instanceof SportsGenderTarget ? $data['target_genders'] : SportsGenderTarget::from($data['target_genders']))
            : SportsGenderTarget::Mixed;

        $ageCategory = isset($data['age_category'])
            ? ($data['age_category'] instanceof StudentAgeCategory ? $data['age_category'] : StudentAgeCategory::from($data['age_category']))
            : StudentAgeCategory::All;

        return SportsCulturalDiscipline::create([
            'discipline_code' => $code,
            'name_ar' => $data['name_ar'],
            'name_fr' => $data['name_fr'] ?? null,
            'category' => $category,
            'supervising_teacher_name' => $data['supervising_teacher_name'],
            'supervising_user_id' => $data['supervising_user_id'] ?? null,
            'training_schedule' => $data['training_schedule'] ?? 'الثلاثاء مساء / الجمعة صباحاً',
            'target_genders' => $targetGenders,
            'age_category' => $ageCategory,
            'max_participants' => $data['max_participants'] ?? 30,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function bootstrapDefaultDisciplines(): int
    {
        $defaults = [
            [
                'name_ar' => 'كرة القدم المدرسية المصغرة',
                'name_fr' => 'Futsal / Football scolaire',
                'category' => AscsDisciplineCategory::TeamSport,
                'supervising_teacher_name' => 'أستاذ التربية البدنية (ذكور)',
                'target_genders' => SportsGenderTarget::Boys,
                'age_category' => StudentAgeCategory::Minimes,
            ],
            [
                'name_ar' => 'كرة اليد المدرسية',
                'name_fr' => 'Handball scolaire',
                'category' => AscsDisciplineCategory::TeamSport,
                'supervising_teacher_name' => 'أستاذة التربية البدنية (إناث)',
                'target_genders' => SportsGenderTarget::Girls,
                'age_category' => StudentAgeCategory::Minimes,
            ],
            [
                'name_ar' => 'كرة السلة المدرسية',
                'name_fr' => 'Basketball scolaire',
                'category' => AscsDisciplineCategory::TeamSport,
                'supervising_teacher_name' => 'أستاذ التربية البدنية والرياضية',
                'target_genders' => SportsGenderTarget::Mixed,
                'age_category' => StudentAgeCategory::Cadets,
            ],
            [
                'name_ar' => 'الكرة الطائرة المدرسية',
                'name_fr' => 'Volleyball scolaire',
                'category' => AscsDisciplineCategory::TeamSport,
                'supervising_teacher_name' => 'أستاذ التربية البدنية والرياضية',
                'target_genders' => SportsGenderTarget::Mixed,
                'age_category' => StudentAgeCategory::All,
            ],
            [
                'name_ar' => 'ألعاب القوى والعدو الريفي (Cross)',
                'name_fr' => 'Athlétisme & Cross-country',
                'category' => AscsDisciplineCategory::IndividualSport,
                'supervising_teacher_name' => 'فريق أساتذة التربية البدنية',
                'target_genders' => SportsGenderTarget::Mixed,
                'age_category' => StudentAgeCategory::All,
            ],
            [
                'name_ar' => 'نادي الشطرنج والألعاب الفكرية',
                'name_fr' => 'Club d\'Échecs et jeux de réflexion',
                'category' => AscsDisciplineCategory::MindSport,
                'supervising_teacher_name' => 'أستاذ الرياضيات والمشرف على النادي',
                'target_genders' => SportsGenderTarget::Mixed,
                'age_category' => StudentAgeCategory::All,
            ],
            [
                'name_ar' => 'المسرح المدرسي والفنون الأدائية',
                'name_fr' => 'Théâtre scolaire et arts scéniques',
                'category' => AscsDisciplineCategory::ArtisticCultural,
                'supervising_teacher_name' => 'أستاذ اللغة العربية والأدب',
                'target_genders' => SportsGenderTarget::Mixed,
                'age_category' => StudentAgeCategory::All,
            ],
        ];

        $created = 0;
        foreach ($defaults as $item) {
            $existing = SportsCulturalDiscipline::where('name_ar', $item['name_ar'])->first();
            if (! $existing) {
                $this->createDiscipline($item);
                $created++;
            }
        }

        return $created;
    }

    public function issueStudentLicense(array $data): StudentSportsLicense
    {
        $academicYear = $data['academic_year'] ?? $this->currentAcademicYear();
        $licenseNumber = $data['license_number'] ?? $this->generateLicenseNumber($academicYear);

        $licenseType = isset($data['license_type'])
            ? ($data['license_type'] instanceof SportsLicenseType ? $data['license_type'] : SportsLicenseType::from($data['license_type']))
            : SportsLicenseType::Competitive;

        $medicalVerdict = isset($data['medical_aptitude_verdict'])
            ? ($data['medical_aptitude_verdict'] instanceof SportsMedicalAptitude ? $data['medical_aptitude_verdict'] : SportsMedicalAptitude::from($data['medical_aptitude_verdict']))
            : SportsMedicalAptitude::FitApt;

        $issueDate = isset($data['issue_date']) ? Carbon::parse($data['issue_date']) : Carbon::today();
        $expiryDate = isset($data['expiry_date'])
            ? Carbon::parse($data['expiry_date'])
            : Carbon::parse(substr($academicYear, -4).'-06-30');

        $license = StudentSportsLicense::create([
            'license_number' => $licenseNumber,
            'academic_year' => $academicYear,
            'student_id' => $data['student_id'],
            'discipline_id' => $data['discipline_id'],
            'license_type' => $licenseType,
            'medical_certificate_issued' => $data['medical_certificate_issued'] ?? true,
            'medical_certificate_date' => $data['medical_certificate_date'] ?? $issueDate,
            'medical_examiner_name' => $data['medical_examiner_name'] ?? 'طبيب الصحة المدرسية (وحدة الكشف UDS)',
            'medical_aptitude_verdict' => $medicalVerdict,
            'parental_consent_signed' => $data['parental_consent_signed'] ?? true,
            'insurance_covered' => $data['insurance_covered'] ?? true,
            'fee_paid' => $data['fee_paid'] ?? true,
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'status' => $data['status'] ?? 'Active',
            'photo_path' => $data['photo_path'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Increment association active members count
        $association = $this->getOrCreateAssociation($academicYear);
        $association->increment('active_members_count');

        return $license;
    }

    public function recordCompetitionEvent(array $data): SportsCompetitionEvent
    {
        $academicYear = $data['academic_year'] ?? $this->currentAcademicYear();
        $code = $data['event_code'] ?? $this->generateEventCode($academicYear);

        $level = $data['competition_level'] instanceof SportsCompetitionLevel
            ? $data['competition_level']
            : SportsCompetitionLevel::from($data['competition_level']);

        $ourScore = isset($data['our_score']) ? (int) $data['our_score'] : null;
        $oppScore = isset($data['opponent_score']) ? (int) $data['opponent_score'] : null;

        // Deduce result if scores are set and result not explicit
        if (! isset($data['match_result'])) {
            if ($ourScore !== null && $oppScore !== null) {
                if ($ourScore > $oppScore) {
                    $result = SportsMatchResult::Win;
                } elseif ($ourScore < $oppScore) {
                    $result = SportsMatchResult::Loss;
                } else {
                    $result = SportsMatchResult::Draw;
                }
            } else {
                $result = SportsMatchResult::Upcoming;
            }
        } else {
            $result = $data['match_result'] instanceof SportsMatchResult
                ? $data['match_result']
                : SportsMatchResult::from($data['match_result']);
        }

        return SportsCompetitionEvent::create([
            'event_code' => $code,
            'academic_year' => $academicYear,
            'discipline_id' => $data['discipline_id'] ?? null,
            'event_name' => $data['event_name'],
            'competition_level' => $level,
            'event_date' => $data['event_date'] ?? Carbon::today(),
            'event_time' => $data['event_time'] ?? '09:30',
            'location_venue' => $data['location_venue'],
            'host_institution' => $data['host_institution'] ?? null,
            'opponent_team_name' => $data['opponent_team_name'] ?? null,
            'our_score' => $ourScore,
            'opponent_score' => $oppScore,
            'match_result' => $result,
            'ranking_achieved' => $data['ranking_achieved'] ?? null,
            'fair_play_score' => $data['fair_play_score'] ?? 10,
            'chaperones_list' => $data['chaperones_list'] ?? null,
            'transport_arranged' => $data['transport_arranged'] ?? false,
            'participation_notes' => $data['participation_notes'] ?? null,
        ]);
    }

    public function recordFinancialTransaction(array $data): AscsFinancialTransaction
    {
        $academicYear = $data['academic_year'] ?? $this->currentAcademicYear();
        $ref = $data['transaction_reference'] ?? $this->generateTransactionReference($academicYear);

        $type = $data['transaction_type'] instanceof AscsTransactionType
            ? $data['transaction_type']
            : AscsTransactionType::from($data['transaction_type']);

        return AscsFinancialTransaction::create([
            'transaction_reference' => $ref,
            'academic_year' => $academicYear,
            'transaction_type' => $type,
            'amount_dzd' => $data['amount_dzd'],
            'transaction_date' => $data['transaction_date'] ?? Carbon::today(),
            'payment_method' => $data['payment_method'] ?? 'Cash',
            'invoice_or_receipt_ref' => $data['invoice_or_receipt_ref'] ?? null,
            'description' => $data['description'],
            'recorded_by_name' => $data['recorded_by_name'],
            'treasurer_approved' => $data['treasurer_approved'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function addOrUpdateSportsEquipment(array $data): SportsEquipmentInventory
    {
        $code = $data['equipment_code'] ?? $this->generateEquipmentCode();

        $condition = isset($data['condition_rating'])
            ? ($data['condition_rating'] instanceof SportsEquipmentCondition ? $data['condition_rating'] : SportsEquipmentCondition::from($data['condition_rating']))
            : SportsEquipmentCondition::Good;

        return SportsEquipmentInventory::create([
            'equipment_code' => $code,
            'item_name' => $data['item_name'],
            'discipline_id' => $data['discipline_id'] ?? null,
            'quantity_total' => $data['quantity_total'] ?? 1,
            'quantity_operational' => $data['quantity_operational'] ?? ($data['quantity_total'] ?? 1),
            'quantity_damaged' => $data['quantity_damaged'] ?? 0,
            'storage_location' => $data['storage_location'] ?? 'مخزن التربية البدنية والرياضية',
            'condition_rating' => $condition,
            'last_inventory_date' => $data['last_inventory_date'] ?? Carbon::today(),
            'purchased_academic_year' => $data['purchased_academic_year'] ?? $this->currentAcademicYear(),
            'acquisition_source' => $data['acquisition_source'] ?? 'PurchasedASCS',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function getSportsTelemetry(?string $academicYear = null): array
    {
        $year = $academicYear ?? $this->currentAcademicYear();
        $association = $this->getOrCreateAssociation($year);

        $licenses = StudentSportsLicense::with('student')->where('academic_year', $year)->get();
        $totalLicenses = $licenses->count();

        $boysLicensed = $licenses->filter(fn ($l) => $l->student && $l->student->gender === 'male')->count();
        $girlsLicensed = $licenses->filter(fn ($l) => $l->student && $l->student->gender === 'female')->count();
        $competitiveLicenses = $licenses->where('license_type', SportsLicenseType::Competitive)->count();
        $recreationalLicenses = $licenses->where('license_type', SportsLicenseType::RecreationalSchoolPractice)->count();

        $disciplinesCount = SportsCulturalDiscipline::where('is_active', true)->count();

        $events = SportsCompetitionEvent::where('academic_year', $year)->get();
        $totalCompetitions = $events->count();
        $matchesWon = $events->where('match_result', SportsMatchResult::Win)->count();

        $transactions = AscsFinancialTransaction::where('academic_year', $year)->get();
        $totalIncomeDzd = (float) $transactions->filter(fn ($t) => $t->transaction_type->isIncome())->sum('amount_dzd');
        $totalExpenseDzd = (float) $transactions->filter(fn ($t) => ! $t->transaction_type->isIncome())->sum('amount_dzd');
        $treasuryBalanceDzd = $totalIncomeDzd - $totalExpenseDzd;

        $totalEquipmentItems = (int) SportsEquipmentInventory::sum('quantity_total');

        return [
            'academic_year' => $year,
            'association' => $association,
            'total_licenses' => $totalLicenses,
            'boys_licensed' => $boysLicensed,
            'girls_licensed' => $girlsLicensed,
            'competitive_licenses' => $competitiveLicenses,
            'recreational_licenses' => $recreationalLicenses,
            'disciplines_count' => $disciplinesCount,
            'total_competitions' => $totalCompetitions,
            'matches_won' => $matchesWon,
            'treasury_income_dzd' => $totalIncomeDzd,
            'treasury_expense_dzd' => $totalExpenseDzd,
            'treasury_balance_dzd' => $treasuryBalanceDzd,
            'total_equipment_items' => $totalEquipmentItems,
        ];
    }

    public function assembleStudentLicenseData(StudentSportsLicense $license): array
    {
        $association = $this->getOrCreateAssociation($license->academic_year);

        return [
            'license' => $license,
            'student' => $license->student,
            'discipline' => $license->discipline,
            'association' => $association,
            'school_name' => config('app.name', 'المؤسسة التربوية'),
            'academic_year' => $license->academic_year,
            'print_date' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleParentalConsentAndMedicalData(StudentSportsLicense $license): array
    {
        $association = $this->getOrCreateAssociation($license->academic_year);

        return [
            'license' => $license,
            'student' => $license->student,
            'discipline' => $license->discipline,
            'association' => $association,
            'school_name' => config('app.name', 'المؤسسة التربوية'),
            'academic_year' => $license->academic_year,
            'print_date' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleCompetitionRosterPvData(SportsCompetitionEvent $event): array
    {
        $association = $this->getOrCreateAssociation($event->academic_year);
        $licensedStudents = StudentSportsLicense::with('student')
            ->where('academic_year', $event->academic_year)
            ->where('discipline_id', $event->discipline_id)
            ->where('status', 'Active')
            ->get();

        return [
            'event' => $event,
            'discipline' => $event->discipline,
            'association' => $association,
            'licensed_students' => $licensedStudents,
            'school_name' => config('app.name', 'المؤسسة التربوية'),
            'academic_year' => $event->academic_year,
            'print_date' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }

    public function assembleAscsAnnualBilanData(string $academicYear): array
    {
        $association = $this->getOrCreateAssociation($academicYear);
        $telemetry = $this->getSportsTelemetry($academicYear);
        $disciplines = SportsCulturalDiscipline::withCount(['licenses' => function ($q) use ($academicYear) {
            $q->where('academic_year', $academicYear);
        }])->get();
        $events = SportsCompetitionEvent::with('discipline')->where('academic_year', $academicYear)->latest('event_date')->get();
        $transactions = AscsFinancialTransaction::where('academic_year', $academicYear)->orderBy('transaction_date')->get();
        $equipment = SportsEquipmentInventory::with('discipline')->get();

        return [
            'academic_year' => $academicYear,
            'association' => $association,
            'telemetry' => $telemetry,
            'disciplines' => $disciplines,
            'events' => $events,
            'transactions' => $transactions,
            'equipment' => $equipment,
            'school_name' => config('app.name', 'المؤسسة التربوية'),
            'print_date' => Carbon::now()->format('Y-m-d H:i'),
        ];
    }
}
