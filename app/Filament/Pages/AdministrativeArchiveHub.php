<?php

namespace App\Filament\Pages;

use App\Enums\HandoverRole;
use App\Enums\MailConfidentiality;
use App\Enums\MailStatus;
use App\Enums\OfficialMailType;
use App\Models\AdministrativeHandover;
use App\Models\OfficialMail;
use App\Models\SchoolSetting;
use App\Models\StaffInstallation;
use App\Models\User;
use App\Services\AdministrativeArchiveService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class AdministrativeArchiveHub extends Page
{
    protected static string $view = 'filament.pages.administrative-archive-hub';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?int $navigationSort = 7;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->canManageAdministrativeArchive();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الإدارة المدرسية والرقمنة والأرشيف');
    }

    public static function getNavigationLabel(): string
    {
        return __('الأرشيف الإداري والبريد الرسمي');
    }

    public function getTitle(): string|Htmlable
    {
        return __('منظومة الأرشيف الإداري: التنصيب، تسليم المهام، وسجل البريد الرسمي');
    }

    // ─── Active Tab ──────────────────────────────────────────────────────────
    public string $activeTab = 'installations';

    // ─── Search & Filters ────────────────────────────────────────────────────
    public string $searchQuery    = '';
    public string $mailTypeFilter = 'incoming';
    public int    $yearFilter     = 2026;

    // ─── Installation Modal ─────────────────────────────────────────────────
    public bool    $isInstallationModalOpen = false;
    public ?int    $installUserId           = null;
    public string  $installDate             = '';
    public string  $installOrderRef         = '';
    public string  $installRoleLabel        = '';
    public string  $installGrade            = '';
    public string  $installPrevInstitution  = '';
    public string  $installDecisionRef      = '';
    public string  $installNotes            = '';

    // ─── Handover Modal ─────────────────────────────────────────────────────
    public bool    $isHandoverModalOpen   = false;
    public string  $handoverRole          = 'principal';
    public string  $handoverDate          = '';
    public ?int    $outgoingUserId        = null;
    public ?int    $incomingUserId        = null;
    public string  $outgoingName          = '';
    public string  $incomingName          = '';
    public string  $inventorySummary      = '';
    public string  $inventoryRef          = '';
    public string  $pendingDossiers       = '';
    public string  $handoverRemarks       = '';

    // ─── Mail Modal ─────────────────────────────────────────────────────────
    public bool    $isMailModalOpen       = false;
    public string  $mailType              = 'incoming';
    public string  $mailDate              = '';
    public string  $mailSender            = '';
    public string  $mailRecipient         = '';
    public string  $mailSubject           = '';
    public string  $mailRefNumber         = '';
    public string  $mailConfidentiality   = 'normal';
    public ?string $mailDeadline          = null;
    public string  $mailNotes             = '';
    public ?int    $mailAssignedTo        = null;

    public function mount(): void
    {
        $this->yearFilter = now()->year;
        $this->installDate = now()->toDateString();
        $this->handoverDate = now()->toDateString();
        $this->mailDate    = now()->toDateString();
    }

    // ─── Computed Properties ─────────────────────────────────────────────────

    public function getInstallationsProperty()
    {
        return StaffInstallation::with('user')
            ->when($this->searchQuery, fn ($q) => $q->whereHas('user', fn ($u) =>
                $u->where('name', 'like', "%{$this->searchQuery}%")
            ))
            ->orderByDesc('installation_date')
            ->get();
    }

    public function getHandoversProperty()
    {
        return AdministrativeHandover::with(['outgoingUser', 'incomingUser'])
            ->orderByDesc('handover_date')
            ->get();
    }

    public function getMailsProperty()
    {
        return OfficialMail::with('assignedUser')
            ->where('mail_type', $this->mailTypeFilter)
            ->whereYear('mail_date', $this->yearFilter)
            ->when($this->searchQuery, fn ($q) =>
                $q->where('subject', 'like', "%{$this->searchQuery}%")
                  ->orWhere('mail_number', 'like', "%{$this->searchQuery}%")
            )
            ->orderBy('mail_number')
            ->get();
    }

    public function getKpiStatsProperty(): array
    {
        $service = app(AdministrativeArchiveService::class);

        return [
            'installations' => $service->getInstallationStats(),
            'handovers'     => $service->getHandoverStats(),
            'mails'         => $service->getMailStats($this->yearFilter),
        ];
    }

    public function getStaffOptionsProperty(): array
    {
        return User::orderBy('name')->pluck('name', 'id')->toArray();
    }

    // ─── Actions: Installations ──────────────────────────────────────────────

    public function openInstallationModal(): void
    {
        $this->resetInstallationModal();
        $this->isInstallationModalOpen = true;
    }

    public function closeInstallationModal(): void
    {
        $this->isInstallationModalOpen = false;
    }

    public function saveInstallation(): void
    {
        $this->validate([
            'installUserId' => 'required|exists:users,id',
            'installDate'   => 'required|date',
            'installRoleLabel' => 'required|string|max:200',
        ]);

        app(AdministrativeArchiveService::class)->recordInstallation([
            'user_id'              => $this->installUserId,
            'installation_date'    => $this->installDate,
            'recruitment_order_ref' => $this->installOrderRef ?: null,
            'role_label'           => $this->installRoleLabel,
            'statutory_grade'      => $this->installGrade ?: null,
            'previous_institution' => $this->installPrevInstitution ?: null,
            'ministry_decision_ref' => $this->installDecisionRef ?: null,
            'notes'                => $this->installNotes ?: null,
        ]);

        $this->closeInstallationModal();

        Notification::make()
            ->title(__('تم تسجيل محضر التنصيب بنجاح'))
            ->success()
            ->send();
    }

    private function resetInstallationModal(): void
    {
        $this->installUserId = null;
        $this->installDate = now()->toDateString();
        $this->installOrderRef = '';
        $this->installRoleLabel = '';
        $this->installGrade = '';
        $this->installPrevInstitution = '';
        $this->installDecisionRef = '';
        $this->installNotes = '';
    }

    // ─── Actions: Handovers ──────────────────────────────────────────────────

    public function openHandoverModal(): void
    {
        $this->resetHandoverModal();
        $this->isHandoverModalOpen = true;
    }

    public function closeHandoverModal(): void
    {
        $this->isHandoverModalOpen = false;
    }

    public function saveHandover(): void
    {
        $this->validate([
            'handoverDate' => 'required|date',
            'handoverRole' => 'required|string',
        ]);

        app(AdministrativeArchiveService::class)->recordHandover([
            'role'              => $this->handoverRole,
            'handover_date'     => $this->handoverDate,
            'outgoing_user_id'  => $this->outgoingUserId ?: null,
            'incoming_user_id'  => $this->incomingUserId ?: null,
            'outgoing_name'     => $this->outgoingName ?: null,
            'incoming_name'     => $this->incomingName ?: null,
            'inventory_summary' => $this->inventorySummary ?: null,
            'inventory_ref'     => $this->inventoryRef ?: null,
            'pending_dossiers'  => $this->pendingDossiers ?: null,
            'remarks'           => $this->handoverRemarks ?: null,
        ]);

        $this->closeHandoverModal();

        Notification::make()
            ->title(__('تم تسجيل محضر تسليم المهام بنجاح'))
            ->success()
            ->send();
    }

    private function resetHandoverModal(): void
    {
        $this->handoverRole = 'principal';
        $this->handoverDate = now()->toDateString();
        $this->outgoingUserId = null;
        $this->incomingUserId = null;
        $this->outgoingName = '';
        $this->incomingName = '';
        $this->inventorySummary = '';
        $this->inventoryRef = '';
        $this->pendingDossiers = '';
        $this->handoverRemarks = '';
    }

    public function markSigned(int $handoverId, string $signee): void
    {
        $handover = AdministrativeHandover::findOrFail($handoverId);
        app(AdministrativeArchiveService::class)->signHandover($handover, $signee);

        Notification::make()
            ->title(__('تم تسجيل التوقيع بنجاح'))
            ->success()
            ->send();
    }

    // ─── Actions: Mail ───────────────────────────────────────────────────────

    public function openMailModal(): void
    {
        $this->resetMailModal();
        $this->isMailModalOpen = true;
    }

    public function closeMailModal(): void
    {
        $this->isMailModalOpen = false;
    }

    public function saveMail(): void
    {
        $this->validate([
            'mailType'    => 'required|in:incoming,outgoing',
            'mailDate'    => 'required|date',
            'mailSubject' => 'required|string|max:500',
        ]);

        app(AdministrativeArchiveService::class)->registerMail([
            'mail_type'       => $this->mailType,
            'mail_date'       => $this->mailDate,
            'sender_name'     => $this->mailSender ?: null,
            'recipient_name'  => $this->mailRecipient ?: null,
            'subject'         => $this->mailSubject,
            'reference_number' => $this->mailRefNumber ?: null,
            'confidentiality' => $this->mailConfidentiality,
            'response_deadline' => $this->mailDeadline ?: null,
            'notes'           => $this->mailNotes ?: null,
            'assigned_to'     => $this->mailAssignedTo ?: null,
        ]);

        $this->closeMailModal();

        Notification::make()
            ->title(__('تم تسجيل البريد في السجل الرسمي'))
            ->success()
            ->send();
    }

    public function updateMailStatus(int $mailId, string $status): void
    {
        $mail = OfficialMail::findOrFail($mailId);
        app(AdministrativeArchiveService::class)->updateMailStatus($mail, $status);

        Notification::make()
            ->title(__('تم تحديث حالة البريد'))
            ->success()
            ->send();
    }

    private function resetMailModal(): void
    {
        $this->mailType = $this->mailTypeFilter;
        $this->mailDate = now()->toDateString();
        $this->mailSender = '';
        $this->mailRecipient = '';
        $this->mailSubject = '';
        $this->mailRefNumber = '';
        $this->mailConfidentiality = 'normal';
        $this->mailDeadline = null;
        $this->mailNotes = '';
        $this->mailAssignedTo = null;
    }

    // ─── Print URL Helpers ───────────────────────────────────────────────────

    public function getInstallationPrintUrl(int $id): string
    {
        return route('print.admin-archive.installation-sheet', [
            'locale'       => app()->getLocale(),
            'installation' => $id,
        ]);
    }

    public function getHandoverPrintUrl(int $id): string
    {
        return route('print.admin-archive.handover-sheet', [
            'locale'   => app()->getLocale(),
            'handover' => $id,
        ]);
    }

    public function getMailLedgerPrintUrl(string $type, int $year): string
    {
        return route('print.admin-archive.mail-ledger', [
            'locale' => app()->getLocale(),
            'type'   => $type,
            'year'   => $year,
        ]);
    }
}

