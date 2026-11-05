<?php

namespace App\Services;

use App\Enums\TransferReason;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Support\BrowsershotFactory;
use Carbon\Carbon;
use Spatie\Browsershot\Browsershot;

class StudentMobilityService
{
    /**
     * Resolve and normalize academic year (e.g. '2026/2027').
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
     * Register a new student movement (Inward or Outward).
     *
     * @param  array<string, mixed>  $data
     */
    public function registerTransfer(Student $student, array $data, ?User $approvedBy = null): StudentTransfer
    {
        $year = $this->resolveAcademicYear($data['academic_year'] ?? null);
        $type = $data['transfer_type'] instanceof TransferType
            ? $data['transfer_type']
            : TransferType::from($data['transfer_type'] ?? 'outward');

        $status = isset($data['status'])
            ? ($data['status'] instanceof TransferStatus ? $data['status'] : TransferStatus::from($data['status']))
            : TransferStatus::Approved;

        $reason = isset($data['reason'])
            ? ($data['reason'] instanceof TransferReason ? $data['reason'] : TransferReason::from($data['reason']))
            : TransferReason::ChangeOfResidence;

        $transferDate = ! empty($data['transfer_date']) ? Carbon::parse($data['transfer_date'])->toDateString() : now()->toDateString();
        $referenceDate = ! empty($data['reference_date']) ? Carbon::parse($data['reference_date'])->toDateString() : null;
        $fileForwardedAt = ! empty($data['school_file_forwarded_at']) ? Carbon::parse($data['school_file_forwarded_at'])->toDateString() : null;

        $transfer = StudentTransfer::create([
            'student_id' => $student->id,
            'transfer_type' => $type,
            'academic_year' => $year,
            'transfer_date' => $transferDate,
            'reference_number' => $data['reference_number'] ?? null,
            'reference_date' => $referenceDate,
            'other_school_name' => $data['other_school_name'] ?? 'متوسطة أخرى',
            'other_directorate_name' => $data['other_directorate_name'] ?? (SchoolSetting::current()?->directorate_name ?? 'مديرية التربية'),
            'reason' => $reason,
            'status' => $status,
            'school_file_forwarded' => (bool) ($data['school_file_forwarded'] ?? false),
            'school_file_forwarded_at' => $fileForwardedAt,
            'clearance_attached' => (bool) ($data['clearance_attached'] ?? false),
            'notes' => $data['notes'] ?? null,
            'approved_by_id' => $approvedBy?->id ?? auth()->id(),
        ]);

        // Synchronize student status
        if ($status === TransferStatus::Approved) {
            if ($type === TransferType::Outward) {
                $student->update([
                    'is_active' => false,
                    'departure_date' => $transferDate,
                ]);
            } elseif ($type === TransferType::Inward) {
                $student->update([
                    'is_active' => true,
                    'enrollment_date' => $student->enrollment_date ?? $transferDate,
                ]);
            }
        }

        return $transfer;
    }

    /**
     * Update the transfer status and sync student active state.
     */
    public function updateTransferStatus(StudentTransfer $transfer, TransferStatus $status, ?User $approvedBy = null): void
    {
        $transfer->update([
            'status' => $status,
            'approved_by_id' => $status === TransferStatus::Approved ? ($approvedBy?->id ?? auth()->id()) : $transfer->approved_by_id,
        ]);

        $student = $transfer->student;
        if (! $student) {
            return;
        }

        if ($status === TransferStatus::Approved) {
            if ($transfer->isOutward()) {
                $student->update([
                    'is_active' => false,
                    'departure_date' => $transfer->transfer_date,
                ]);
            } elseif ($transfer->isInward()) {
                $student->update([
                    'is_active' => true,
                    'enrollment_date' => $student->enrollment_date ?? $transfer->transfer_date,
                ]);
            }
        } elseif ($status === TransferStatus::Cancelled) {
            if ($transfer->isOutward()) {
                $student->update([
                    'is_active' => true,
                    'departure_date' => null,
                ]);
            }
        }
    }

    /**
     * Mark physical school file as transmitted or received.
     */
    public function markSchoolFileForwarded(StudentTransfer $transfer, ?string $date = null): void
    {
        $transfer->update([
            'school_file_forwarded' => true,
            'school_file_forwarded_at' => $date ? Carbon::parse($date)->toDateString() : now()->toDateString(),
        ]);
    }

    /**
     * Compute institutional mobility and transfer KPIs.
     *
     * @return array<string, mixed>
     */
    public function getMobilityKpis(?string $academicYear = null): array
    {
        $year = $this->resolveAcademicYear($academicYear);
        $altYear = str_contains($year, '/') ? str_replace('/', '-', $year) : str_replace('-', '/', $year);

        $transfers = StudentTransfer::where(function ($q) use ($year, $altYear): void {
            $q->where('academic_year', $year)->orWhere('academic_year', $altYear);
        })->get();

        $totalTransfers = $transfers->count();
        $inwardCount = $transfers->where('transfer_type', TransferType::Inward)->where('status', TransferStatus::Approved)->count();
        $outwardCount = $transfers->where('transfer_type', TransferType::Outward)->where('status', TransferStatus::Approved)->count();
        $pendingCount = $transfers->where('status', TransferStatus::Pending)->count();
        $filesForwardedCount = $transfers->where('school_file_forwarded', true)->count();
        $netFlow = $inwardCount - $outwardCount;

        $totalActiveStudents = Student::where('is_active', true)->count();

        return [
            'academic_year' => $year,
            'total_transfers' => $totalTransfers,
            'inward_count' => $inwardCount,
            'outward_count' => $outwardCount,
            'pending_count' => $pendingCount,
            'files_forwarded_count' => $filesForwardedCount,
            'net_flow' => $netFlow,
            'total_active_students' => $totalActiveStudents,
        ];
    }

    /**
     * Assemble transfer certificate data for single student.
     *
     * @return array<string, mixed>
     */
    public function assembleTransferCertificate(StudentTransfer $transfer): array
    {
        $school = SchoolSetting::current();
        $student = $transfer->student()->with(['group.classroom.level'])->first();
        $classroom = $student?->group?->classroom;
        $level = $classroom?->level;

        return [
            'school' => $school,
            'transfer' => $transfer,
            'student' => $student,
            'classroom' => $classroom,
            'level' => $level,
            'year' => $transfer->academic_year,
            'isInward' => $transfer->isInward(),
            'isOutward' => $transfer->isOutward(),
        ];
    }

    /**
     * Assemble forwarding slip (إرسالية تحويل الملف المدرسي) data.
     *
     * @return array<string, mixed>
     */
    public function assembleForwardingSlip(StudentTransfer $transfer): array
    {
        $school = SchoolSetting::current();
        $student = $transfer->student()->with(['group.classroom.level'])->first();

        return [
            'school' => $school,
            'transfer' => $transfer,
            'student' => $student,
            'classroom' => $student?->group?->classroom,
            'year' => $transfer->academic_year,
        ];
    }

    /**
     * Assemble annual student mobility ledger.
     *
     * @return array<string, mixed>
     */
    public function assembleMobilityLedger(?string $academicYear = null, ?TransferType $type = null): array
    {
        $school = SchoolSetting::current();
        $year = $this->resolveAcademicYear($academicYear);
        $altYear = str_contains($year, '/') ? str_replace('/', '-', $year) : str_replace('-', '/', $year);

        $query = StudentTransfer::where(function ($q) use ($year, $altYear): void {
            $q->where('academic_year', $year)->orWhere('academic_year', $altYear);
        })->with(['student.group.classroom.level', 'approvedBy']);

        if ($type !== null) {
            $query->where('transfer_type', $type);
        }

        $transfers = $query->orderBy('transfer_date')->get();
        $kpis = $this->getMobilityKpis($year);

        return [
            'school' => $school,
            'academic_year' => $year,
            'transfers' => $transfers,
            'kpis' => $kpis,
            'filterType' => $type,
        ];
    }

    /**
     * Assemble general matriculation register data (سجل القيد العام).
     *
     * @return array<string, mixed>
     */
    public function assembleMatriculeRegister(?int $levelId = null, ?int $classroomId = null): array
    {
        $school = SchoolSetting::current();

        $query = Student::with(['group.classroom.level', 'latestTransfer']);

        if ($classroomId) {
            $query->whereHas('group', fn ($q) => $q->where('classroom_id', $classroomId));
        } elseif ($levelId) {
            $query->whereHas('group.classroom', fn ($q) => $q->where('level_id', $levelId));
        }

        $students = $query->orderBy('matricule_number')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return [
            'school' => $school,
            'students' => $students,
            'level' => $levelId ? Level::find($levelId) : null,
            'classroom' => $classroomId ? Classroom::find($classroomId) : null,
            'totalStudents' => $students->count(),
            'activeCount' => $students->where('is_active', true)->count(),
            'transferredOutCount' => $students->where('is_active', false)->count(),
        ];
    }

    /**
     * Generate printable PDF for student transfer certificate using Browsershot.
     */
    public function generateTransferCertificatePdf(StudentTransfer $transfer): string
    {
        $data = $this->assembleTransferCertificate($transfer);
        $html = view('print.student-transfer-certificate', array_merge($data, ['inlineCss' => true]))->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->portrait()
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->pdf();
    }

    /**
     * Generate printable PDF for mobility ledger using Browsershot.
     */
    public function generateMobilityLedgerPdf(?string $academicYear = null, ?TransferType $type = null): string
    {
        $data = $this->assembleMobilityLedger($academicYear, $type);
        $html = view('print.student-mobility-ledger', array_merge($data, ['inlineCss' => true]))->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->landscape()
            ->margins(8, 8, 8, 8)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->pdf();
    }

    /**
     * Generate printable PDF for school file forwarding slip using Browsershot.
     */
    public function generateForwardingSlipPdf(StudentTransfer $transfer): string
    {
        $data = $this->assembleForwardingSlip($transfer);
        $html = view('print.school-file-forwarding-slip', array_merge($data, ['inlineCss' => true]))->render();

        return BrowsershotFactory::html($html)
            ->format('A4')
            ->portrait()
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->pdf();
    }
}
