<?php

namespace App\Services;

use App\Enums\DailyLogStatus;
use App\Models\Classroom;
use App\Models\CurriculumResource;
use App\Models\DailyLog;
use App\Models\InspectionReport;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\User;
use App\Services\Concerns\ConfiguresBrowsershot;
use App\Support\BrowsershotFactory;
use App\Support\DocxInteractiveControlPostProcessor;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class PedagogicalInspectionService
{
    use ConfiguresBrowsershot;

    /**
     * Compute curriculum velocity and parallel section pacing.
     *
     * @return array<string, mixed>
     */
    public function getCurriculumVelocity(int $subjectId, int $levelId): array
    {
        $subject = Subject::find($subjectId);
        $level = Level::with('educationPhase')->find($levelId);

        if (! $subject || ! $level) {
            return [
                'subject' => null,
                'level' => null,
                'total_resources' => 0,
                'max_completed' => 0,
                'classrooms' => [],
            ];
        }

        // Count total curriculum resources for this subject and level
        $totalResources = CurriculumResource::query()
            ->whereHas('curriculumSequence.curriculumDomain', function ($q) use ($subjectId, $levelId) {
                $q->where('subject_id', $subjectId)->where('level_id', $levelId);
            })
            ->count();

        $classrooms = Classroom::query()
            ->where('level_id', $levelId)
            ->orderBy('name')
            ->get();

        $classroomData = [];
        $maxCompleted = 0;

        foreach ($classrooms as $classroom) {
            // Find distinct completed resources for this classroom & subject
            $completedCount = DailyLog::query()
                ->where('status', DailyLogStatus::Completed)
                ->whereNotNull('curriculum_resource_id')
                ->whereHas('schedule', function ($q) use ($subjectId, $classroom) {
                    $q->where('subject_id', $subjectId)
                        ->where(function ($sq) use ($classroom) {
                            $sq->where('classroom_id', $classroom->id)
                                ->orWhereHas('group', fn ($gq) => $gq->where('classroom_id', $classroom->id));
                        });
                })
                ->distinct('curriculum_resource_id')
                ->count('curriculum_resource_id');

            // Find the primary teacher assigned to this classroom for this subject
            $schedule = Schedule::query()
                ->where('subject_id', $subjectId)
                ->where(function ($q) use ($classroom) {
                    $q->where('classroom_id', $classroom->id)
                        ->orWhereHas('group', fn ($gq) => $gq->where('classroom_id', $classroom->id));
                })
                ->with('user')
                ->first();

            $teacher = $schedule?->user;

            if ($completedCount > $maxCompleted) {
                $maxCompleted = $completedCount;
            }

            $percent = $totalResources > 0 ? round(($completedCount / $totalResources) * 100, 1) : 0;

            $classroomData[] = [
                'classroom' => $classroom,
                'teacher' => $teacher,
                'completed_resources' => $completedCount,
                'progress_percent' => $percent,
            ];
        }

        // Compute pacing status and resource gaps
        foreach ($classroomData as &$item) {
            $gap = $maxCompleted - $item['completed_resources'];
            $item['resource_gap'] = $gap;

            if ($maxCompleted === 0) {
                $item['pacing_status'] = 'on_track';
            } elseif ($gap >= 2) {
                $item['pacing_status'] = 'lagging';
            } elseif ($item['completed_resources'] === $maxCompleted && $maxCompleted > 0) {
                $item['pacing_status'] = 'ahead';
            } else {
                $item['pacing_status'] = 'on_track';
            }
        }
        unset($item);

        return [
            'subject' => $subject,
            'level' => $level,
            'total_resources' => $totalResources,
            'max_completed' => $maxCompleted,
            'classrooms' => $classroomData,
        ];
    }

    /**
     * Compute teacher pedagogical compliance scorecard.
     *
     * @return array<string, mixed>
     */
    public function getTeacherPedagogicalScorecard(User $teacher): array
    {
        $teacherId = $teacher->id;

        $completedLogsCount = DailyLog::query()
            ->where('status', DailyLogStatus::Completed)
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacherId))
            ->count();

        $visaCount = DailyLog::query()
            ->where('status', DailyLogStatus::Completed)
            ->whereNotNull('reviewed_at')
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacherId))
            ->count();

        $visaRate = $completedLogsCount > 0 ? round(($visaCount / $completedLogsCount) * 100, 1) : 100.0;

        $memosCount = DB::table('pedagogical_memos')
            ->where('user_id', $teacherId)
            ->count();

        $assessmentsCount = DB::table('assessments')
            ->whereHas('schedule', fn ($q) => $q->where('user_id', $teacherId))
            ->count();

        $inspections = InspectionReport::query()
            ->where('teacher_id', $teacherId)
            ->orderByDesc('visit_date')
            ->get();

        $avgScore = $inspections->count() > 0
            ? round((float) $inspections->avg('pedagogical_score'), 2)
            : null;

        $latestInspection = $inspections->first();

        return [
            'teacher' => $teacher,
            'completed_logs_count' => $completedLogsCount,
            'visa_count' => $visaCount,
            'visa_rate' => $visaRate,
            'memos_count' => $memosCount,
            'assessments_count' => $assessmentsCount,
            'inspections_count' => $inspections->count(),
            'average_score' => $avgScore,
            'latest_inspection' => $latestInspection,
            'inspections' => $inspections,
        ];
    }

    /**
     * Global inspection summary and KPIs.
     *
     * @return array<string, mixed>
     */
    public function getInspectionMetrics(): array
    {
        $totalInspections = InspectionReport::count();
        $inspectedTeachersCount = InspectionReport::distinct('teacher_id')->count('teacher_id');
        $totalTeachersCount = User::where('role', 'teacher')->count();

        $avgScore = $totalInspections > 0
            ? round((float) InspectionReport::avg('pedagogical_score'), 2)
            : null;

        $appreciations = [
            'excellent' => InspectionReport::where('pedagogical_appreciation', 'excellent')->count(),
            'very_good' => InspectionReport::where('pedagogical_appreciation', 'very_good')->count(),
            'good' => InspectionReport::where('pedagogical_appreciation', 'good')->count(),
            'acceptable' => InspectionReport::where('pedagogical_appreciation', 'acceptable')->count(),
            'needs_improvement' => InspectionReport::where('pedagogical_appreciation', 'needs_improvement')->count(),
        ];

        return [
            'total_inspections' => $totalInspections,
            'inspected_teachers_count' => $inspectedTeachersCount,
            'total_teachers_count' => $totalTeachersCount,
            'average_score' => $avgScore,
            'appreciations' => $appreciations,
        ];
    }

    public function renderHtml(InspectionReport $report): string
    {
        return view('print.inspection-report', [
            'report' => $report->load(['teacher', 'subject', 'classroom', 'schedule', 'dailyLog', 'curriculumResource']),
            'schoolSetting' => SchoolSetting::current(),
            'generatedAt' => now(),
            'autoPrint' => false,
        ])->render();
    }

    public function generatePdf(InspectionReport $report): string
    {
        $html = $this->renderHtml($report);

        return $this->browsershot($html)->pdf();
    }

    /**
     * Gather comprehensive data for the Teacher Pedagogical Inspection & Audit Dossier.
     *
     * @return array<string, mixed>
     */
    public function getTeacherInspectionDossierData(int|User $teacher, ?string $academicYear = null): array
    {
        $teacherModel = $teacher instanceof User
            ? $teacher->loadMissing(['primarySubject', 'secondarySubject'])
            : User::with(['primarySubject', 'secondarySubject'])->findOrFail($teacher);

        $school = SchoolSetting::current();
        $year = $academicYear ?: ($school?->academic_year ?? '2026-2027');

        $receiptsData = app(TeacherReceiptsService::class)->viewData($teacherModel, $year);
        $scorecard = $this->getTeacherPedagogicalScorecard($teacherModel);

        $assignedClassrooms = Classroom::query()
            ->whereHas('schedules', fn ($q) => $q->where('user_id', $teacherModel->id))
            ->with('level')
            ->orderBy('name')
            ->get();

        return array_merge($receiptsData, [
            'scorecard' => $scorecard,
            'assignedClassrooms' => $assignedClassrooms,
            'inspectionHistory' => $scorecard['inspections'],
            'latestInspection' => $scorecard['latest_inspection'],
            'generatedAt' => now(),
        ]);
    }

    /**
     * Generate printable PDF for the complete Teacher Pedagogical Inspection Dossier.
     */
    public function generateDossierPdf(int|User $teacher, ?string $academicYear = null): string
    {
        $data = $this->getTeacherInspectionDossierData($teacher, $academicYear);
        $html = view('print.teacher-inspection-dossier', $data)->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->portrait()
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->pdf();
    }

    /**
     * Generate official Word document (.docx) for Teacher Inspection Dossier with interactive OpenXML controls.
     */
    public function generateDossierWord(int|User $teacher, ?string $academicYear = null): string
    {
        $data = $this->getTeacherInspectionDossierData($teacher, $academicYear);
        $teacherModel = $data['teacher'];
        $school = $data['school'];
        $year = $data['academicYear'];

        $phpWord = new PhpWord;
        $phpWord->getCompatibility()->setOoxmlVersion(15);
        $phpWord->setDefaultFontName('Cairo');
        $phpWord->setDefaultFontSize(9.5);
        $phpWord->setDefaultParagraphStyle([
            'bidi' => true,
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            'lineHeight' => 1.0,
        ]);

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 500,
            'marginBottom' => 500,
            'marginLeft' => 600,
            'marginRight' => 600,
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
        ]);

        // 1. Official Algerian Ministerial Header (3 Columns)
        $headerTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMarginTop' => 30,
            'cellMarginBottom' => 30,
            'cellMarginLeft' => 50,
            'cellMarginRight' => 50,
        ]);
        $hr = $headerTable->addRow();

        // Left cell: Academic Year & Dates
        $hl = $hr->addCell(3200, ['borderSize' => 0]);
        $hl->addText('السنة الدراسية: '.$year, ['name' => 'Cairo', 'size' => 9.5, 'bold' => true, 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);
        $hl->addText('تاريخ الإصدار: '.now()->locale('ar')->translatedFormat('d F Y'), ['name' => 'Cairo', 'size' => 8.5, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);
        $hl->addText('ملف المتابعة والتفتيش التربوي الرسمي', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::END, 'bidi' => true]);

        // Center cell: Republic & Ministry & Directorate
        $hc = $hr->addCell(4200, ['borderSize' => 0]);
        $hc->addText('الجمهورية الجزائرية الديمقراطية الشعبية', ['name' => 'Amiri', 'size' => 12.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $hc->addText('وزارة التربية الوطنية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $hc->addText('مديرية التربية لولاية: '.($school?->municipality_name ?? 'المغير'), ['name' => 'Amiri', 'size' => 10, 'bold' => true, 'color' => '334155', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Right cell: School & Municipality
        $hrCell = $hr->addCell(3000, ['borderSize' => 0]);
        $hrCell->addText($school?->school_name ?? 'متوسطة حي 900 مسكن', ['name' => 'Amiri', 'size' => 11.5, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $schoolMeta = $school?->municipality_name ?? 'المغير';
        if (! empty($school?->school_code)) {
            $schoolMeta .= ' · الرقم: '.$school->school_code;
        }
        $hrCell->addText($schoolMeta, ['name' => 'Cairo', 'size' => 8.5, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 80, 'spaceAfter' => 0]);

        // 2. Document Title Banner
        $titleTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 12,
            'borderColor' => '1E3A8A',
            'cellMarginTop' => 80,
            'cellMarginBottom' => 80,
            'cellMarginLeft' => 120,
            'cellMarginRight' => 120,
        ]);
        $tr = $titleTable->addRow();
        $tc = $tr->addCell(10400, ['bgColor' => 'F8FAFC']);
        $tc->addText('ملف المتابعة والتفتيش التربوي للأستاذ(ة)', ['name' => 'Amiri', 'size' => 14, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 100, 'spaceAfter' => 0]);

        // 3. Teacher Identity Banner (Single Line)
        $idTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => 'CBD5E1',
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 100,
            'cellMarginRight' => 100,
        ]);
        $idRow = $idTable->addRow();

        // Left: Compliance & Progress
        $idLeft = $idRow->addCell(3400, ['bgColor' => 'F1F5F9']);
        $idLeftRun = $idLeft->addTextRun(['alignment' => Jc::END, 'bidi' => true]);
        $idLeftRun->addText('نسبة الامتثال العام: ', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '64748B', 'rtl' => true]);
        $idLeftRun->addText($data['overallPercentage'].'% ('.$data['totalSubmitted'].'/'.$data['totalItems'].')', ['name' => 'Cairo', 'size' => 9.5, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true]);

        // Center: Subject & Hours
        $idCenter = $idRow->addCell(3400, ['bgColor' => 'F1F5F9']);
        $idCenterRun = $idCenter->addTextRun(['alignment' => Jc::CENTER, 'bidi' => true]);
        $idCenterRun->addText('المادة المقررة: ', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '64748B', 'rtl' => true]);
        $idCenterRun->addText($teacherModel->primarySubject?->name ?? 'غير محدد', ['name' => 'Cairo', 'size' => 10, 'bold' => true, 'color' => '0F172A', 'rtl' => true]);

        // Right: Teacher Name
        $idRight = $idRow->addCell(3600, ['bgColor' => 'F1F5F9']);
        $idRightRun = $idRight->addTextRun(['alignment' => Jc::START, 'bidi' => true]);
        $idRightRun->addText('اسم ولقب الأستاذ: ', ['name' => 'Cairo', 'size' => 9, 'bold' => true, 'color' => '64748B', 'rtl' => true]);
        $idRightRun->addText($teacherModel->name, ['name' => 'Amiri', 'size' => 11.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true]);

        $section->addText('', [], ['spaceBefore' => 120, 'spaceAfter' => 0]);

        // 4. Summary Ribbon (4 KPI Cards)
        $kpiTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => 'E2E8F0',
            'cellMarginTop' => 40,
            'cellMarginBottom' => 40,
            'cellMarginLeft' => 60,
            'cellMarginRight' => 60,
        ]);
        $kpiRow = $kpiTable->addRow();

        // Visa Rate
        $k1 = $kpiRow->addCell(2600, ['bgColor' => 'F8FAFC']);
        $k1->addText('نسبة التأشير الإداري', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $k1->addText($data['scorecard']['visa_rate'].'%', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => '0F766E', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Curriculum Progress
        $k2 = $kpiRow->addCell(2600, ['bgColor' => 'F8FAFC']);
        $k2->addText('وتيرة إنجاز المنهاج', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $k2->addText($data['curriculumPercentage'].'%', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => '6D28D9', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Evaluation Deliverables
        $k3 = $kpiRow->addCell(2600, ['bgColor' => 'F8FAFC']);
        $k3->addText('الأعمال التقويمية', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $k3->addText($data['submittedEvalsCount'].'/10 ('.$data['evalsPercentage'].'%)', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => 'B45309', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Pedagogical Plans
        $k4 = $kpiRow->addCell(2600, ['bgColor' => 'F8FAFC']);
        $s1Count = $data['submittedMonthsCount'] + ($data['annualPlan']['submitted'] ? 1 : 0);
        $k4->addText('المخططات والتدرجات', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $k4->addText($s1Count.'/10 ('.round(($s1Count / 10) * 100).'%)', ['name' => 'Cairo', 'size' => 11, 'bold' => true, 'color' => '15803D', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 120, 'spaceAfter' => 0]);

        // 5. Section 1: Pedagogical Plans Table
        $section->addText('أولاً: سجل استلام وتأشير المخططات والتدرجات البيداغوجية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $section->addText('', [], ['spaceBefore' => 40, 'spaceAfter' => 0]);

        $planTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => '334155',
            'cellMarginTop' => 30,
            'cellMarginBottom' => 30,
            'cellMarginLeft' => 30,
            'cellMarginRight' => 30,
        ]);

        $pHead = $planTable->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        $pHead->addCell(1260, ['bgColor' => '0F172A'])->addText('تأشيرة المدير', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $pHead->addCell(1260, ['bgColor' => '0F172A'])->addText('تأشيرة الناظر', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        foreach (array_reverse($data['monthlyPlans']) as $m) {
            $pHead->addCell(770, ['bgColor' => '1E3A8A'])->addText($m['label'], ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        }
        $pHead->addCell(950, ['bgColor' => '1E3A8A'])->addText('المخطط السنوي', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        // Status Row
        $pRow = $planTable->addRow(null, ['cantSplit' => true]);
        $dirCell = $pRow->addCell(1260, ['bgColor' => 'FFFFFF']);
        $dirCell->addText('[CHECKBOX:'.($data['annualPlan']['director_visa'] ? 'true' : 'false').':plan_director_visa]', ['rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $dirCell->addText($data['annualPlan']['director_visa_at'] ? $data['annualPlan']['director_visa_at']->format('Y-m-d') : 'قيد الاعتماد', ['name' => 'Cairo', 'size' => 7, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $cenCell = $pRow->addCell(1260, ['bgColor' => 'FFFFFF']);
        $cenCell->addText('[CHECKBOX:'.($data['annualPlan']['censor_visa'] ? 'true' : 'false').':plan_censor_visa]', ['rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $cenCell->addText($data['annualPlan']['censor_visa_at'] ? $data['annualPlan']['censor_visa_at']->format('Y-m-d') : 'قيد التأشير', ['name' => 'Cairo', 'size' => 7, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        foreach (array_reverse($data['monthlyPlans']) as $m) {
            $mc = $pRow->addCell(770, ['bgColor' => 'FFFFFF']);
            $mc->addText('[CHECKBOX:'.($m['submitted'] ? 'true' : 'false').':plan_'.$m['key'].']', ['rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $mc->addText($m['date'] ? $m['date']->format('d/m') : '—', ['name' => 'Cairo', 'size' => 7, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        }

        $ac = $pRow->addCell(950, ['bgColor' => 'FFFFFF']);
        $ac->addText('[CHECKBOX:'.($data['annualPlan']['submitted'] ? 'true' : 'false').':plan_annual]', ['rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $ac->addText($data['annualPlan']['date'] ? $data['annualPlan']['date']->format('d/m') : '—', ['name' => 'Cairo', 'size' => 7, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 120, 'spaceAfter' => 0]);

        // 6. Section 2: Evaluation Deliverables Table
        $section->addText('ثانياً: سجل تسليم ومتابعة الأعمال والوثائق التقويمية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $section->addText('', [], ['spaceBefore' => 40, 'spaceAfter' => 0]);

        $evalTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => '334155',
            'cellMarginTop' => 30,
            'cellMarginBottom' => 30,
            'cellMarginLeft' => 30,
            'cellMarginRight' => 30,
        ]);

        $eHead = $evalTable->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        $eHead->addCell(1000, ['bgColor' => '9333EA'])->addText('اختبار 3', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1000, ['bgColor' => '9333EA'])->addText('اختبار 2', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1000, ['bgColor' => '9333EA'])->addText('اختبار 1', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1040, ['bgColor' => '2563EB'])->addText('فرض 3', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1040, ['bgColor' => '2563EB'])->addText('فرض 2', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1040, ['bgColor' => '2563EB'])->addText('فرض 1', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1040, ['bgColor' => '0284C7'])->addText('واجب 3', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1040, ['bgColor' => '0284C7'])->addText('واجب 2', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1040, ['bgColor' => '0284C7'])->addText('واجب 1', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $eHead->addCell(1160, ['bgColor' => '0F172A'])->addText('كشف المستوى', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $eRow = $evalTable->addRow(null, ['cantSplit' => true]);
        foreach (['exam_3', 'exam_2', 'exam_1', 'quiz_3', 'quiz_2', 'quiz_1', 'homework_3', 'homework_2', 'homework_1'] as $ek) {
            $ec = $eRow->addCell($ek === 'exam_3' || $ek === 'exam_2' || $ek === 'exam_1' ? 1000 : 1040, ['bgColor' => 'FFFFFF']);
            $ec->addText('[CHECKBOX:'.($data['evalItems'][$ek]['submitted'] ? 'true' : 'false').':eval_'.$ek.']', ['rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $ec->addText($data['evalItems'][$ek]['date'] ? $data['evalItems'][$ek]['date']->format('d/m') : '—', ['name' => 'Cairo', 'size' => 7, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        }
        $diagCell = $eRow->addCell(1160, ['bgColor' => 'FFFFFF']);
        $diagCell->addText('[CHECKBOX:'.($data['evalItems']['diagnostic_test']['submitted'] ? 'true' : 'false').':eval_diag]', ['rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $diagCell->addText($data['evalItems']['diagnostic_test']['date'] ? $data['evalItems']['diagnostic_test']['date']->format('d/m') : '—', ['name' => 'Cairo', 'size' => 7, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 120, 'spaceAfter' => 0]);

        // 7. Section 3: Curriculum Progress by Classrooms
        $section->addText('ثالثاً: وضعية تقدم المنهاج من واقع دفتر النصوص (الموارد المعرفية والمقاطع)', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $section->addText('', [], ['spaceBefore' => 40, 'spaceAfter' => 0]);

        if (isset($data['curriculumOverview']) && $data['curriculumOverview']->isNotEmpty()) {
            $cTable = $section->addTable([
                'alignment' => JcTable::CENTER,
                'unit' => 'dxa',
                'width' => 10400,
                'borderSize' => 6,
                'borderColor' => 'CBD5E1',
                'cellMarginTop' => 30,
                'cellMarginBottom' => 30,
                'cellMarginLeft' => 40,
                'cellMarginRight' => 40,
            ]);

            $cHead = $cTable->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
            $cHead->addCell(2000, ['bgColor' => '6D28D9'])->addText('نسبة الإنجاز', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $cHead->addCell(2400, ['bgColor' => '6D28D9'])->addText('المقاطع المكتملة', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $cHead->addCell(2400, ['bgColor' => '6D28D9'])->addText('الموارد المنجزة', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $cHead->addCell(1800, ['bgColor' => '6D28D9'])->addText('المستوى الدراسي', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            $cHead->addCell(1800, ['bgColor' => '6D28D9'])->addText('القسم المسند', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

            foreach ($data['curriculumOverview'] as $row) {
                $cRow = $cTable->addRow(null, ['cantSplit' => true]);
                $cRow->addCell(2000, ['bgColor' => 'FFFFFF'])->addText($row['percentage'].'%', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '6D28D9', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                $cRow->addCell(2400, ['bgColor' => 'FFFFFF'])->addText($row['completed_sequences'].' / '.$row['total_sequences'], ['name' => 'Cairo', 'size' => 8.5, 'color' => '334155', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                $cRow->addCell(2400, ['bgColor' => 'FFFFFF'])->addText($row['completed_resources'].' / '.$row['total_resources'], ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                $cRow->addCell(1800, ['bgColor' => 'FFFFFF'])->addText($row['level']?->name ?? '—', ['name' => 'Cairo', 'size' => 8.5, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                $cRow->addCell(1800, ['bgColor' => 'FFFFFF'])->addText($row['classroom']->name, ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            }
        }

        $section->addText('', [], ['spaceBefore' => 120, 'spaceAfter' => 0]);

        // 8. Section 4: History of Previous Inspections
        $section->addText('رابعاً: سجل زيارات التفتيش البيداغوجي السابقة وتوجيهات المفتش', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $section->addText('', [], ['spaceBefore' => 40, 'spaceAfter' => 0]);

        $insTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => 'CBD5E1',
            'cellMarginTop' => 30,
            'cellMarginBottom' => 30,
            'cellMarginLeft' => 40,
            'cellMarginRight' => 40,
        ]);

        $iHead = $insTable->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        $iHead->addCell(3000, ['bgColor' => 'D97706'])->addText('أهم الملاحظات والتوجيهات', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $iHead->addCell(1600, ['bgColor' => 'D97706'])->addText('التقدير العام', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $iHead->addCell(1400, ['bgColor' => 'D97706'])->addText('النقطة التربوية', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $iHead->addCell(2400, ['bgColor' => 'D97706'])->addText('موضوع الحصة والقسم', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $iHead->addCell(2000, ['bgColor' => 'D97706'])->addText('تاريخ الزيارة والمفتش', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        if (isset($data['inspectionHistory']) && $data['inspectionHistory']->isNotEmpty()) {
            foreach ($data['inspectionHistory']->take(3) as $insp) {
                $iRow = $insTable->addRow(null, ['cantSplit' => true]);
                $iRow->addCell(3000, ['bgColor' => 'FFFFFF'])->addText($insp->strengths ?? $insp->areas_for_improvement ?? 'توجيهات مسجلة بالتقرير', ['name' => 'Cairo', 'size' => 8, 'color' => '334155', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
                $iRow->addCell(1600, ['bgColor' => 'FFFFFF'])->addText($insp->pedagogical_appreciation ?? '—', ['name' => 'Cairo', 'size' => 8, 'bold' => true, 'color' => '047857', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                $iRow->addCell(1400, ['bgColor' => 'FFFFFF'])->addText($insp->pedagogical_score ? number_format($insp->pedagogical_score, 2).' / 20' : '—', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                $iRow->addCell(2400, ['bgColor' => 'FFFFFF'])->addText($insp->lesson_topic.($insp->classroom ? ' ('.$insp->classroom->name.')' : ''), ['name' => 'Cairo', 'size' => 8, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
                $iRow->addCell(2000, ['bgColor' => 'FFFFFF'])->addText(($insp->visit_date?->format('Y-m-d') ?? '—').' - '.$insp->inspector_name, ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
            }
        } else {
            $iRow = $insTable->addRow(null, ['cantSplit' => true]);
            $iRow->addCell(10400, ['gridSpan' => 5, 'bgColor' => 'FFFFFF'])->addText('لم تُسجّل زيارات تفتيش سابقة خلال الموسم الحالي.', ['name' => 'Cairo', 'size' => 8.5, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        }

        $section->addText('', [], ['spaceBefore' => 120, 'spaceAfter' => 0]);

        // 9. Section 5: Current Visit Evaluation Field (Official Box for Today's Inspection)
        $section->addText('خامساً: بطاقة المعاينة وشبكة التقييم لزيارة التفتيش الميدانية الحالية', ['name' => 'Amiri', 'size' => 11, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $section->addText('', [], ['spaceBefore' => 40, 'spaceAfter' => 0]);

        $evalBox = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 6,
            'borderColor' => '0284C7',
            'cellMarginTop' => 40,
            'cellMarginBottom' => 40,
            'cellMarginLeft' => 50,
            'cellMarginRight' => 50,
        ]);

        $ebMeta = $evalBox->addRow();
        $ebMeta->addCell(2600, ['bgColor' => 'F0F9FF'])->addText('التقدير المقترح: [ ] ممتاز [ ] جيد جداً [ ] جيد [ ] مقبول', ['name' => 'Cairo', 'size' => 7.5, 'color' => '0369A1', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $ebMeta->addCell(2600, ['bgColor' => 'F0F9FF'])->addText('النقطة التربوية الممنوحة: ......... / 20', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '0369A1', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $ebMeta->addCell(2600, ['bgColor' => 'F0F9FF'])->addText('القسم المعاين: ......................', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '0369A1', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $ebMeta->addCell(2600, ['bgColor' => 'F0F9FF'])->addText('موضوع الحصة: .......................................', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '0369A1', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $ebObs = $evalBox->addRow();
        $obsCell = $ebObs->addCell(10400, ['gridSpan' => 4, 'bgColor' => 'FFFFFF']);
        $obsCell->addText('ملاحظات وتوجيهات مفتش التربية الوطنية لمادة '.($teacherModel->primarySubject?->name ?? '').':', ['name' => 'Cairo', 'size' => 8.5, 'bold' => true, 'color' => '1E3A8A', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $obsCell->addText('.........................................................................................................................................................................................................................................', ['name' => 'Cairo', 'size' => 8, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);
        $obsCell->addText('.........................................................................................................................................................................................................................................', ['name' => 'Cairo', 'size' => 8, 'color' => '94A3B8', 'rtl' => true], ['alignment' => Jc::START, 'bidi' => true]);

        $section->addText('', [], ['spaceBefore' => 140, 'spaceAfter' => 0]);

        // 10. Official Quadruple Visa Box
        $sigTable = $section->addTable([
            'alignment' => JcTable::CENTER,
            'unit' => 'dxa',
            'width' => 10400,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);
        $sigRow = $sigTable->addRow();

        $s4 = $sigRow->addCell(2600, ['borderSize' => 0]);
        $s4->addText('توقيع وخاتم مفتش التربية الوطنية', ['name' => 'Amiri', 'size' => 10.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $s4->addText('(التأشير والملاحظة)', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $s3 = $sigRow->addCell(2600, ['borderSize' => 0]);
        $s3->addText('مدير المؤسسة', ['name' => 'Amiri', 'size' => 10.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $s3->addText('(الختم والمصادقة الرسمية)', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $s2 = $sigRow->addCell(2600, ['borderSize' => 0]);
        $s2->addText('ناظر الدروس', ['name' => 'Amiri', 'size' => 10.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $s2->addText('(التأشير والختم)', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $s1 = $sigRow->addCell(2600, ['borderSize' => 0]);
        $s1->addText('توقيع الأستاذ(ة) المعني(ة)', ['name' => 'Amiri', 'size' => 10.5, 'bold' => true, 'color' => '0F172A', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);
        $s1->addText('(اطلع وصودق عليه)', ['name' => 'Cairo', 'size' => 8, 'color' => '64748B', 'rtl' => true], ['alignment' => Jc::CENTER, 'bidi' => true]);

        $tempFile = tempnam(sys_get_temp_dir(), 'inspection_dossier_').'.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        DocxInteractiveControlPostProcessor::process($tempFile);

        $content = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }
}
