<?php

namespace App\Services;

use App\Enums\OfficialMailType;
use App\Models\AdministrativeHandover;
use App\Models\OfficialMail;
use App\Models\SchoolSetting;
use App\Models\StaffInstallation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdministrativeArchiveService
{
    // ───────────────────────────────────────────────────────────────
    // STAFF INSTALLATIONS (محاضر التنصيب)
    // ───────────────────────────────────────────────────────────────

    /**
     * Record a new staff installation minute.
     */
    public function recordInstallation(array $data): StaffInstallation
    {
        $data['recorded_by'] = Auth::id();

        return StaffInstallation::create($data);
    }

    /**
     * Assemble all data needed for the official installation print sheet.
     */
    public function assembleInstallationSheetData(StaffInstallation $installation): array
    {
        $setting = SchoolSetting::current();
        $user = $installation->user;

        return [
            'installation' => $installation,
            'user' => $user,
            'school' => $setting,
            'printed_at' => now()->locale('ar')->isoFormat('LL'),
        ];
    }

    // ───────────────────────────────────────────────────────────────
    // ADMINISTRATIVE HANDOVERS (محاضر تسليم المهام)
    // ───────────────────────────────────────────────────────────────

    /**
     * Record a new handover / passation de consignes.
     */
    public function recordHandover(array $data): AdministrativeHandover
    {
        // Persist outgoing/incoming names from linked users when not explicitly supplied
        if (! empty($data['outgoing_user_id']) && empty($data['outgoing_name'])) {
            $outgoing = User::find($data['outgoing_user_id']);
            $data['outgoing_name'] = $outgoing?->name;
        }
        if (! empty($data['incoming_user_id']) && empty($data['incoming_name'])) {
            $incoming = User::find($data['incoming_user_id']);
            $data['incoming_name'] = $incoming?->name;
        }

        $data['recorded_by'] = Auth::id();

        return AdministrativeHandover::create($data);
    }

    /**
     * Mark signing status on a handover record.
     */
    public function signHandover(AdministrativeHandover $handover, string $signee): AdministrativeHandover
    {
        $map = [
            'outgoing' => 'outgoing_signed',
            'incoming' => 'incoming_signed',
            'director' => 'director_signed',
        ];

        if (isset($map[$signee])) {
            $handover->update([$map[$signee] => true]);
        }

        return $handover->fresh();
    }

    /**
     * Assemble all data needed for the official handover print sheet.
     */
    public function assembleHandoverSheetData(AdministrativeHandover $handover): array
    {
        $setting = SchoolSetting::current();

        return [
            'handover' => $handover,
            'school' => $setting,
            'printed_at' => now()->locale('ar')->isoFormat('LL'),
        ];
    }

    // ───────────────────────────────────────────────────────────────
    // OFFICIAL MAIL REGISTRY (سجل البريد الصادر والوارد)
    // ───────────────────────────────────────────────────────────────

    /**
     * Generate the next sequential mail number for a given type and year.
     * Format: ARR/2026/001  or  DEP/2026/001
     */
    public function generateMailNumber(OfficialMailType $type, ?int $year = null): string
    {
        $year = $year ?? now()->year;
        $prefix = $type->prefix();

        $lastNumber = OfficialMail::where('mail_type', $type->value)
            ->whereYear('mail_date', $year)
            ->count();

        $seq = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return "{$prefix}/{$year}/{$seq}";
    }

    /**
     * Register a new mail item (incoming or outgoing).
     */
    public function registerMail(array $data): OfficialMail
    {
        $type = OfficialMailType::from($data['mail_type']);

        // Auto-generate mail number if not provided
        if (empty($data['mail_number'])) {
            $year = isset($data['mail_date'])
                ? (int) date('Y', strtotime($data['mail_date']))
                : now()->year;
            $data['mail_number'] = $this->generateMailNumber($type, $year);
        }

        $data['recorded_by'] = Auth::id();

        return OfficialMail::create($data);
    }

    /**
     * Update mail status (pending → processed → archived).
     */
    public function updateMailStatus(OfficialMail $mail, string $status): OfficialMail
    {
        $mail->update(['status' => $status]);

        return $mail->fresh();
    }

    /**
     * Assemble all data needed for the official mail ledger print view.
     */
    public function assembleMailLedgerData(string $mailType, ?int $year = null): array
    {
        $setting = SchoolSetting::current();
        $year = $year ?? now()->year;

        $mails = OfficialMail::where('mail_type', $mailType)
            ->whereYear('mail_date', $year)
            ->orderBy('mail_number')
            ->get();

        return [
            'mails' => $mails,
            'mail_type' => OfficialMailType::from($mailType),
            'year' => $year,
            'school' => $setting,
            'printed_at' => now()->locale('ar')->isoFormat('LL'),
        ];
    }

    // ───────────────────────────────────────────────────────────────
    // DASHBOARD KPI HELPERS
    // ───────────────────────────────────────────────────────────────

    public function getInstallationStats(): array
    {
        return [
            'total' => StaffInstallation::count(),
            'this_year' => StaffInstallation::whereYear('installation_date', now()->year)->count(),
            'this_month' => StaffInstallation::whereYear('installation_date', now()->year)
                ->whereMonth('installation_date', now()->month)->count(),
        ];
    }

    public function getHandoverStats(): array
    {
        return [
            'total' => AdministrativeHandover::count(),
            'unsigned' => AdministrativeHandover::where('outgoing_signed', false)
                ->orWhere('incoming_signed', false)
                ->orWhere('director_signed', false)
                ->count(),
        ];
    }

    public function getMailStats(?int $year = null): array
    {
        $year = $year ?? now()->year;

        return [
            'incoming_total' => OfficialMail::where('mail_type', OfficialMailType::Incoming->value)
                ->whereYear('mail_date', $year)->count(),
            'outgoing_total' => OfficialMail::where('mail_type', OfficialMailType::Outgoing->value)
                ->whereYear('mail_date', $year)->count(),
            'pending' => OfficialMail::where('status', 'pending')
                ->whereYear('mail_date', $year)->count(),
            'overdue' => OfficialMail::where('status', 'pending')
                ->whereNotNull('response_deadline')
                ->where('response_deadline', '<', now()->toDateString())
                ->count(),
        ];
    }

    /**
     * Archive an official pedagogical receipt register (PDF) into the school administrative archive.
     *
     * @return array{mail: OfficialMail, relative_path: string, mail_number: string}
     */
    public function archivePedagogicalReceiptRegister(
        string $registerType,
        string $pdfBinary,
        string $academicYear,
        ?string $subjectName = null
    ): array {
        $setting = SchoolSetting::current();
        $typeLabel = $registerType === 'plans'
            ? 'سجل استلام وتأشير المخططات والتدرجات البيداغوجية'
            : 'سجل تسليم الأعمال والوثائق التقويمية';

        $subjectSuffix = $subjectName ? " لمادة {$subjectName}" : ' لجميع المواد التعليمية';
        $fullSubject = "أرشيف رسمي: {$typeLabel}{$subjectSuffix} للسنة الدراسية {$academicYear}";

        $cleanYear = str_replace(['/', '\\'], '-', $academicYear);
        $fileName = sprintf('%s_%s_%s.pdf', $registerType, $cleanYear, now()->format('Ymd_His'));
        $relativePath = "archives/pedagogical/{$cleanYear}/{$fileName}";

        Storage::disk('public')->put($relativePath, $pdfBinary);

        $mailNumber = $this->generateMailNumber(OfficialMailType::Outgoing);

        $mail = OfficialMail::create([
            'mail_number' => $mailNumber,
            'mail_type' => OfficialMailType::Outgoing->value,
            'mail_date' => now()->toDateString(),
            'sender_name' => $setting?->school_name ?? 'إدارة المؤسسة',
            'recipient_name' => 'الأرشيف الإداري والتربوي الداخلي للمؤسسة',
            'subject' => $fullSubject,
            'reference_number' => 'ARCH-PED-'.now()->format('Ymd'),
            'attachment_path' => $relativePath,
            'status' => 'archived',
            'notes' => 'نسخة رقمية مؤرشفة ومعتمدة صادرة من منظومة سجل المتابعة البيداغوجية بتاريخ '.now()->translatedFormat('d F Y H:i'),
            'recorded_by' => Auth::id(),
        ]);

        return [
            'mail' => $mail,
            'relative_path' => $relativePath,
            'mail_number' => $mailNumber,
        ];
    }
}
