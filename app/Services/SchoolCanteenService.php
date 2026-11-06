<?php

namespace App\Services;

use App\Enums\CanteenSheetStatus;
use App\Enums\FoodItemCategory;
use App\Models\CanteenConsumptionEntry;
use App\Models\CanteenDailySheet;
use App\Models\CanteenFoodItem;
use App\Models\CanteenWeeklyMenu;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SchoolCanteenService
{
    /**
     * Resolve academic year with fallback to current school setting.
     */
    public function resolveAcademicYear(?string $year = null): string
    {
        if ($year) {
            return $year;
        }

        $current = SchoolSetting::current()?->academic_year ?? '2026/2027';

        return str_replace('-', '/', $current);
    }

    /**
     * Seed or synchronize the standard Algerian public middle school food inventory.
     */
    public function seedStandardAlgerianPantryItems(): int
    {
        $standards = [
            // Meat & Fish
            ['name' => 'لحم بقر طازج', 'category' => FoodItemCategory::MeatFish, 'unit' => 'كغ', 'price' => 1800.00, 'stock' => 50, 'threshold' => 15],
            ['name' => 'دجاج مجمد حلال', 'category' => FoodItemCategory::MeatFish, 'unit' => 'كغ', 'price' => 480.00, 'stock' => 120, 'threshold' => 25],
            ['name' => 'سمك السردين الطازج', 'category' => FoodItemCategory::MeatFish, 'unit' => 'كغ', 'price' => 600.00, 'stock' => 40, 'threshold' => 10],

            // Fresh Produce
            ['name' => 'بطاطا استهلاك', 'category' => FoodItemCategory::Produce, 'unit' => 'كغ', 'price' => 80.00, 'stock' => 300, 'threshold' => 50],
            ['name' => 'طماطم طازجة', 'category' => FoodItemCategory::Produce, 'unit' => 'كغ', 'price' => 100.00, 'stock' => 80, 'threshold' => 20],
            ['name' => 'بصل جاف', 'category' => FoodItemCategory::Produce, 'unit' => 'كغ', 'price' => 70.00, 'stock' => 100, 'threshold' => 25],
            ['name' => 'جزر طازج', 'category' => FoodItemCategory::Produce, 'unit' => 'كغ', 'price' => 60.00, 'stock' => 80, 'threshold' => 20],
            ['name' => 'كوسة (جريوات)', 'category' => FoodItemCategory::Produce, 'unit' => 'كغ', 'price' => 90.00, 'stock' => 60, 'threshold' => 15],
            ['name' => 'سلطة خضراء', 'category' => FoodItemCategory::Produce, 'unit' => 'كغ', 'price' => 120.00, 'stock' => 30, 'threshold' => 10],
            ['name' => 'تفاح محلي موسمي', 'category' => FoodItemCategory::Produce, 'unit' => 'كغ', 'price' => 220.00, 'stock' => 150, 'threshold' => 30],
            ['name' => 'برتقال موسمي', 'category' => FoodItemCategory::Produce, 'unit' => 'كغ', 'price' => 180.00, 'stock' => 150, 'threshold' => 30],

            // Dry Goods & Pasta
            ['name' => 'أرز أبيض مفور', 'category' => FoodItemCategory::DryGoods, 'unit' => 'كغ', 'price' => 140.00, 'stock' => 150, 'threshold' => 30],
            ['name' => 'كسكسي متوسط', 'category' => FoodItemCategory::DryGoods, 'unit' => 'كغ', 'price' => 110.00, 'stock' => 120, 'threshold' => 25],
            ['name' => 'مقرونة غذائية', 'category' => FoodItemCategory::DryGoods, 'unit' => 'كغ', 'price' => 90.00, 'stock' => 140, 'threshold' => 30],
            ['name' => 'عدس يابس ممتاز', 'category' => FoodItemCategory::DryGoods, 'unit' => 'كغ', 'price' => 160.00, 'stock' => 100, 'threshold' => 20],
            ['name' => 'حمص حب يابس', 'category' => FoodItemCategory::DryGoods, 'unit' => 'كغ', 'price' => 250.00, 'stock' => 80, 'threshold' => 20],
            ['name' => 'لوبيا بيضاء جافة', 'category' => FoodItemCategory::DryGoods, 'unit' => 'كغ', 'price' => 280.00, 'stock' => 80, 'threshold' => 20],

            // Dairy & Eggs
            ['name' => 'حليب مبستر معقم', 'category' => FoodItemCategory::Dairy, 'unit' => 'لتر', 'price' => 25.00, 'stock' => 200, 'threshold' => 40],
            ['name' => 'جبن أبيض مثلثات', 'category' => FoodItemCategory::Dairy, 'unit' => 'قطعة', 'price' => 15.00, 'stock' => 600, 'threshold' => 100],
            ['name' => 'بيض طازج', 'category' => FoodItemCategory::Dairy, 'unit' => 'بيضة', 'price' => 22.00, 'stock' => 500, 'threshold' => 90],

            // Bakery
            ['name' => 'خبز عادي نصف مدعم', 'category' => FoodItemCategory::Bakery, 'unit' => 'خبزة', 'price' => 10.00, 'stock' => 350, 'threshold' => 50],

            // Oils & Condiments
            ['name' => 'زيت المائدة النباتي', 'category' => FoodItemCategory::OilsCondiments, 'unit' => 'لتر', 'price' => 125.00, 'stock' => 100, 'threshold' => 20],
            ['name' => 'طماطم مصبرة معجون', 'category' => FoodItemCategory::OilsCondiments, 'unit' => 'علبة', 'price' => 180.00, 'stock' => 60, 'threshold' => 15],
            ['name' => 'ملح طعام يودي', 'category' => FoodItemCategory::OilsCondiments, 'unit' => 'كغ', 'price' => 30.00, 'stock' => 40, 'threshold' => 10],
            ['name' => 'توابل مشكلة للطبخ', 'category' => FoodItemCategory::OilsCondiments, 'unit' => 'كغ', 'price' => 450.00, 'stock' => 15, 'threshold' => 5],
        ];

        $created = 0;
        foreach ($standards as $item) {
            $record = CanteenFoodItem::firstOrCreate(
                ['name' => $item['name']],
                [
                    'category' => $item['category'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['price'],
                    'current_stock' => $item['stock'],
                    'safety_threshold' => $item['threshold'],
                    'is_active' => true,
                ]
            );

            if ($record->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Compute institutional canteen overview KPIs and telemetry.
     *
     * @return array<string, mixed>
     */
    public function getCanteenOverview(?string $academicYear = null, ?string $date = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);
        $targetDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        // 1. Boarding counts from student roster
        $allStudents = Student::all();
        $totalHalfBoardEnrolled = $allStudents->filter(fn (Student $s) => $s->isHalfBoard())->count();
        $totalBoardersEnrolled = $allStudents->filter(fn (Student $s) => $s->isBoarder())->count();

        // 2. Identify students with dietary allergies or PAI protocols
        $studentsWithDietaryAlerts = $allStudents
            ->filter(function (Student $s) {
                if (! $s->isHalfBoard() && ! $s->isBoarder()) {
                    return false;
                }
                $record = $s->medicalRecords()->latest('id')->first();
                if (! $record) {
                    return false;
                }

                return ($record->has_chronic_illness && in_array($record->chronic_condition_type?->value, ['severe_allergy', 'diabetes', 'other'], true))
                    || ! empty($record->emergency_protocol_notes);
            })
            ->values();

        // 3. Current Daily Sheet
        $currentSheet = $this->getOrCreateDailySheet($targetDate, $year);

        // 4. Monthly aggregates
        $currentMonth = (int) Carbon::parse($targetDate)->format('n');
        $monthlySheets = CanteenDailySheet::forYear($year)->forMonth($currentMonth)->get();

        $monthlyMealsServed = $monthlySheets->sum('total_beneficiaries_count');
        $monthlyExpenditure = (float) $monthlySheets->sum('total_cost');
        $averageCostPerMeal = $monthlyMealsServed > 0 ? round($monthlyExpenditure / $monthlyMealsServed, 2) : 0.00;

        // 5. Pantry inventory status
        $pantryItems = CanteenFoodItem::active()->orderBy('category')->get();
        $lowStockItems = $pantryItems->filter(fn ($item) => $item->isStockLow());

        // 6. Latest weekly menu
        $currentWeekNumber = (int) Carbon::parse($targetDate)->weekOfYear;
        $weeklyMenu = CanteenWeeklyMenu::where('academic_year', $year)
            ->where('week_number', $currentWeekNumber)
            ->first();

        return [
            'academicYear' => $year,
            'targetDate' => $targetDate,
            'totalHalfBoardEnrolled' => $totalHalfBoardEnrolled,
            'totalBoardersEnrolled' => $totalBoardersEnrolled,
            'totalBeneficiariesEnrolled' => $totalHalfBoardEnrolled + $totalBoardersEnrolled,
            'studentsWithDietaryAlerts' => $studentsWithDietaryAlerts,
            'currentSheet' => $currentSheet,
            'monthlyMealsServed' => $monthlyMealsServed,
            'monthlyExpenditure' => $monthlyExpenditure,
            'averageCostPerMeal' => $averageCostPerMeal,
            'pantryItems' => $pantryItems,
            'lowStockItems' => $lowStockItems,
            'weeklyMenu' => $weeklyMenu,
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Get or initialize today's Canteen Daily Consumption Sheet.
     */
    public function getOrCreateDailySheet(string $date, ?string $academicYear = null): CanteenDailySheet
    {
        $year = $this->resolveAcademicYear($academicYear);
        $dateStr = Carbon::parse($date)->toDateString();

        $sheet = CanteenDailySheet::where('academic_year', $year)
            ->whereDate('sheet_date', $dateStr)
            ->with(['entries.foodItem', 'certifiedBy'])
            ->first();

        if ($sheet) {
            return $sheet;
        }

        // Auto-calculate default half-board present count
        $allStudents = Student::all();
        $defaultPresent = $allStudents->filter(fn (Student $s) => $s->isHalfBoard())->count();
        if ($defaultPresent === 0) {
            $defaultPresent = min(180, $allStudents->count() > 0 ? (int) ($allStudents->count() * 0.45) : 150);
        }

        $refNumber = 'FJC-'.str_replace(['/', '-'], '', $year).'-'.Carbon::parse($dateStr)->format('md');

        return CanteenDailySheet::create([
            'academic_year' => $year,
            'sheet_date' => $dateStr,
            'reference_number' => $refNumber,
            'half_board_students_present' => $defaultPresent,
            'boarder_students_present' => 0,
            'staff_beneficiaries_count' => 12,
            'service_workers_count' => 6,
            'total_beneficiaries_count' => $defaultPresent + 18,
            'menu_description' => 'طبق رئيسي: أرز مفور بصلصة اللحم أو الدجاج · سلطة خضراء متنوعة · فاكهة موسمية (تفاح أو برتقال) · خبز عادي نصف مدعم',
            'storekeeper_name' => 'مسير مخزن المطعم',
            'kitchen_hygiene_status' => 'ممتازة ومطابقة للمعايير الصحية',
            'status' => CanteenSheetStatus::Draft,
        ]);
    }

    /**
     * Add or update a consumption entry on a daily sheet.
     */
    public function addConsumptionEntry(
        CanteenDailySheet $sheet,
        int $foodItemId,
        float $quantity,
        ?float $unitPrice = null
    ): CanteenConsumptionEntry {
        return DB::transaction(function () use ($sheet, $foodItemId, $quantity, $unitPrice) {
            $foodItem = CanteenFoodItem::findOrFail($foodItemId);
            $effectivePrice = $unitPrice ?? (float) $foodItem->unit_price;
            $totalCost = round($quantity * $effectivePrice, 2);

            $entry = CanteenConsumptionEntry::updateOrCreate(
                [
                    'canteen_daily_sheet_id' => $sheet->id,
                    'canteen_food_item_id' => $foodItem->id,
                ],
                [
                    'quantity_consumed' => $quantity,
                    'unit_price' => $effectivePrice,
                    'total_item_cost' => $totalCost,
                ]
            );

            // Deduct stock safely
            $foodItem->current_stock = max(0, (float) $foodItem->current_stock - $quantity);
            $foodItem->save();

            $sheet->recalculateTotals();

            return $entry;
        });
    }

    /**
     * Remove a consumption entry and restore pantry stock.
     */
    public function removeConsumptionEntry(CanteenConsumptionEntry $entry): void
    {
        DB::transaction(function () use ($entry) {
            $sheet = $entry->sheet;
            $foodItem = $entry->foodItem;

            if ($foodItem) {
                $foodItem->current_stock += (float) $entry->quantity_consumed;
                $foodItem->save();
            }

            $entry->delete();

            if ($sheet) {
                $sheet->recalculateTotals();
            }
        });
    }

    /**
     * Certify and freeze a daily consumption sheet.
     */
    public function certifyDailySheet(CanteenDailySheet $sheet, ?User $certifier = null): CanteenDailySheet
    {
        $sheet->status = CanteenSheetStatus::Certified;
        $sheet->certified_at = now();
        $sheet->certified_by_id = $certifier?->id ?? User::where('role', 'admin')->first()?->id;
        $sheet->save();

        return $sheet->fresh(['entries.foodItem', 'certifiedBy']);
    }

    /**
     * Save or update an approved weekly canteen menu.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveWeeklyMenu(array $data): CanteenWeeklyMenu
    {
        $data['academic_year'] = $this->resolveAcademicYear($data['academic_year'] ?? null);

        return CanteenWeeklyMenu::updateOrCreate(
            [
                'academic_year' => $data['academic_year'],
                'week_number' => $data['week_number'],
            ],
            $data
        );
    }

    /**
     * Assemble printable data for the official Feuille Journalière de Consommation.
     *
     * @return array<string, mixed>
     */
    public function assembleDailySheetData(CanteenDailySheet $sheet): array
    {
        $sheet->loadMissing(['entries.foodItem', 'certifiedBy']);

        $groupedEntries = $sheet->entries->groupBy(function ($entry) {
            return $entry->foodItem?->category?->label() ?? __('مواد غذائية عامة');
        });

        return [
            'sheet' => $sheet,
            'groupedEntries' => $groupedEntries,
            'totalItemsCount' => $sheet->entries->count(),
            'school' => SchoolSetting::current(),
            'academicYear' => $sheet->academic_year,
        ];
    }

    /**
     * Assemble printable data for the official Weekly Menu bulletin board poster.
     *
     * @return array<string, mixed>
     */
    public function assembleWeeklyMenuData(CanteenWeeklyMenu $menu): array
    {
        return [
            'menu' => $menu,
            'school' => SchoolSetting::current(),
            'academicYear' => $menu->academic_year,
        ];
    }

    /**
     * Assemble printable periodic catering report for Directorate of Education.
     *
     * @return array<string, mixed>
     */
    public function assemblePeriodicBilanData(?string $academicYear = null, ?int $month = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);
        $targetMonth = $month ?? (int) now()->format('n');

        $sheets = CanteenDailySheet::forYear($year)
            ->forMonth($targetMonth)
            ->with('entries.foodItem')
            ->orderBy('sheet_date')
            ->get();

        $totalDays = $sheets->count();
        $totalHalfBoardMeals = $sheets->sum('half_board_students_present');
        $totalBoarderMeals = $sheets->sum('boarder_students_present');
        $totalStaffMeals = $sheets->sum('staff_beneficiaries_count');
        $totalWorkersMeals = $sheets->sum('service_workers_count');
        $totalAllMeals = $sheets->sum('total_beneficiaries_count');
        $totalExpenditure = (float) $sheets->sum('total_cost');
        $avgCostPerMeal = $totalAllMeals > 0 ? round($totalExpenditure / $totalAllMeals, 2) : 0.00;

        return [
            'academicYear' => $year,
            'month' => $targetMonth,
            'sheets' => $sheets,
            'totalDays' => $totalDays,
            'totalHalfBoardMeals' => $totalHalfBoardMeals,
            'totalBoarderMeals' => $totalBoarderMeals,
            'totalStaffMeals' => $totalStaffMeals,
            'totalWorkersMeals' => $totalWorkersMeals,
            'totalAllMeals' => $totalAllMeals,
            'totalExpenditure' => $totalExpenditure,
            'avgCostPerMeal' => $avgCostPerMeal,
            'school' => SchoolSetting::current(),
        ];
    }

    /**
     * Generate printable A4 Landscape PDF for Daily Consumption Sheet (FJC).
     */
    public function generateDailyConsumptionSheetPdf(CanteenDailySheet $sheet): string
    {
        $data = $this->assembleDailySheetData($sheet);
        $data['inlineCss'] = true;

        $html = view('print.canteen-daily-sheet', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(6, 6, 6, 6)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable A4 Portrait PDF for Weekly Menu Poster.
     */
    public function generateWeeklyMenuPdf(CanteenWeeklyMenu $menu): string
    {
        $data = $this->assembleWeeklyMenuData($menu);
        $data['inlineCss'] = true;

        $html = view('print.canteen-weekly-menu', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(false)
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate printable A4 Landscape PDF for Periodic Catering Bilan.
     */
    public function generatePeriodicBilanPdf(?string $academicYear = null, ?int $month = null): string
    {
        $data = $this->assemblePeriodicBilanData($academicYear, $month);
        $data['inlineCss'] = true;

        $html = view('print.canteen-periodic-bilan', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape(true)
            ->margins(6, 6, 6, 6)
            ->showBackground()
            ->pdf();
    }
}
