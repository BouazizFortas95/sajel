<?php

namespace App\Models;

use App\Enums\InfractionDegree;
use App\Services\DisciplinaryCouncilService;
use App\Services\StudentIncidentReportService;
use App\Services\TeacherParentReceptionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class BehaviorObservation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (BehaviorObservation $observation): void {
            $observation->recorded_by_id ??= Auth::id();
        });
    }

    protected $attributes = [
        'administrative_action' => 'pending',
        'is_guardian_receipt_returned' => false,
        'guardian_attendance_status' => 'pending',
    ];

    protected $fillable = [
        'student_id',
        'schedule_id',
        'disciplinary_record_id',
        'recorded_by_id',
        'category',
        'notes',
        'date',
        'observed_by',
        'administrative_action',
        'is_followed_up',
        'follow_up_notes',
        'guardian_summons_sent_at',
        'guardian_summons_appointment_at',
        'guardian_summons_notes',
        'is_guardian_receipt_returned',
        'guardian_receipt_returned_at',
        'guardian_attendance_status',
        'guardian_attendance_notes',
        'deleted_by_id',
        'deletion_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_followed_up' => 'boolean',
            'guardian_summons_sent_at' => 'date',
            'guardian_summons_appointment_at' => 'datetime',
            'is_guardian_receipt_returned' => 'boolean',
            'guardian_receipt_returned_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function disciplinaryRecord(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryRecord::class);
    }

    public function hasDisciplinaryRecord(): bool
    {
        return ! is_null($this->disciplinary_record_id);
    }

    public function hasGuardianSummons(): bool
    {
        return ! is_null($this->guardian_summons_sent_at);
    }

    public function getAdministrativeActionAttribute(?string $value = null): string
    {
        if (filled($value)) {
            return $value;
        }

        if ($this->hasDisciplinaryRecord()) {
            return 'disciplinary_council';
        }
        if ($this->hasGuardianSummons()) {
            return 'guardian_summons';
        }
        if ($this->is_followed_up) {
            return 'internal_followup';
        }

        return 'pending';
    }

    /**
     * Apply administrative action chosen from the select box directly.
     *
     * @return array{status: string, title: string, body: string, print_url?: string|null, pdf_url?: string|null}
     */
    public function applyAdministrativeAction(
        string $action,
        ?User $actor = null,
        ?DisciplinaryCouncilService $councilService = null,
    ): array {
        $actor ??= Auth::user();
        $councilService ??= app(DisciplinaryCouncilService::class);

        return match ($action) {
            'disciplinary_council' => $this->applyDisciplinaryCouncilReferral($actor, $councilService),
            'guardian_summons' => $this->applyGuardianSummons(),
            'internal_followup' => $this->applyInternalFollowup(),
            default => $this->applyPendingStatus(),
        };
    }

    protected function applyDisciplinaryCouncilReferral(User $actor, DisciplinaryCouncilService $councilService): array
    {
        $discRecord = $this->disciplinaryRecord;

        if (! $discRecord && $this->student) {
            $discRecord = $councilService->scheduleSession(
                student: $this->student,
                data: [
                    'session_date' => now()->addDays(3)->setTime(10, 0),
                    'infraction_degree' => InfractionDegree::SecondDegree,
                    'incident_description' => $this->notes,
                    'incident_date' => $this->date?->toDateString() ?? now()->toDateString(),
                    'reported_by' => $this->recorder ?? $actor,
                    'guardian_summoned' => true,
                ]
            );

            $notice = __('تمت الإحالة مباشرة إلى مجلس التأديب (جلسة رقم :session بتاريخ :date).', [
                'session' => $discRecord->session_number,
                'date' => $discRecord->session_date ? $discRecord->session_date->format('Y/m/d H:i') : '—',
            ]);

            $this->update([
                'administrative_action' => 'disciplinary_council',
                'disciplinary_record_id' => $discRecord->id,
                'is_followed_up' => true,
                'follow_up_notes' => trim(($this->follow_up_notes ? $this->follow_up_notes."\n" : '').$notice),
            ]);
        } else {
            $this->update([
                'administrative_action' => 'disciplinary_council',
                'is_followed_up' => true,
            ]);
        }

        $printUrl = $discRecord ? route('print.disciplinary.summons', [
            'locale' => app()->getLocale(),
            'record' => $discRecord->id,
        ]) : null;

        return [
            'status' => 'disciplinary_council',
            'title' => __('⚖️ تمت الإحالة إلى مجلس التأديب مباشرة'),
            'body' => __('تم فتح ملف تأديبي رقم :session وتحديد موعد الجلسة وإصدار استدعاء الولي.', [
                'session' => $discRecord?->session_number ?? '—',
            ]),
            'print_url' => $printUrl,
        ];
    }

    protected function applyGuardianSummons(): array
    {
        $teacher = $this->schedule?->user ?? $this->recorder;
        if (! $this->guardian_summons_appointment_at && $teacher) {
            $appointment = app(TeacherParentReceptionService::class)->calculateNextReceptionDate(
                $teacher,
                $this->date ? Carbon::parse($this->date) : now()
            );
        } else {
            $appointment = $this->guardian_summons_appointment_at ?? now()->addDay()->setTime(10, 0);
        }

        $motive = $this->guardian_summons_notes ?: app(StudentIncidentReportService::class)->formatSummonsMotive($this);

        $this->update([
            'administrative_action' => 'guardian_summons',
            'guardian_summons_sent_at' => now(),
            'guardian_summons_appointment_at' => $appointment,
            'guardian_summons_notes' => $motive,
            'is_followed_up' => true,
            'follow_up_notes' => trim(($this->follow_up_notes ? $this->follow_up_notes."\n" : '').__('تم إصدار استدعاء رسمي لولي الأمر لمقابلة الإدارة والأستاذ.')),
        ]);

        return [
            'status' => 'guardian_summons',
            'title' => __('✉️ تم تفعيل استدعاء ولي الأمر مباشرة'),
            'body' => __('تم تسجيل الاستدعاء وتحديد المقابلة يوم :date. يمكنك طباعة الاستدعاء فوراً.', [
                'date' => $appointment->format('Y/m/d H:i'),
            ]),
            'print_url' => route('print.student-behavior-report.guardian-summons', [
                'locale' => app()->getLocale(),
                'behaviorObservation' => $this,
            ]),
            'pdf_url' => route('print.student-behavior-report.guardian-summons.pdf', [
                'locale' => app()->getLocale(),
                'behaviorObservation' => $this,
            ]),
        ];
    }

    protected function applyInternalFollowup(): array
    {
        $this->update([
            'administrative_action' => 'internal_followup',
            'is_followed_up' => true,
            'follow_up_notes' => trim(($this->follow_up_notes ? $this->follow_up_notes."\n" : '').__('تمت المعالجة والمتابعة الداخلية البيداغوجية.')),
        ]);

        return [
            'status' => 'internal_followup',
            'title' => __('✅ تم تأكيد المعالجة الداخلية'),
            'body' => __('تم وسم التقرير كمعالج بيداغوجياً وداخلياً بنجاح.'),
        ];
    }

    protected function applyPendingStatus(): array
    {
        $this->update([
            'administrative_action' => 'pending',
            'is_followed_up' => false,
        ]);

        return [
            'status' => 'pending',
            'title' => __('⏳ الحالة: قيد المتابعة'),
            'body' => __('تم ضبط حالة التقرير كقيد المتابعة والانتظار.'),
        ];
    }

    public function hasGuardianReceiptReturned(): bool
    {
        return (bool) $this->is_guardian_receipt_returned;
    }

    public function guardianAttendanceStatusLabel(): string
    {
        return match ($this->guardian_attendance_status) {
            'attended' => __('حضر الولي'),
            'absent' => __('غائب / لم يحضر'),
            'excused' => __('معذور / تأجيل'),
            default => __('قيد الانتظار'),
        };
    }

    public function guardianAttendanceStatusColor(): string
    {
        return match ($this->guardian_attendance_status) {
            'attended' => 'success',
            'absent' => 'danger',
            'excused' => 'warning',
            default => 'gray',
        };
    }

    public function markReceiptReturned(bool $returned = true): void
    {
        $this->update([
            'is_guardian_receipt_returned' => $returned,
            'guardian_receipt_returned_at' => $returned ? now() : null,
        ]);
    }

    public function updateGuardianAttendance(string $status, ?string $notes = null): void
    {
        $updates = [
            'guardian_attendance_status' => $status,
        ];

        if ($notes !== null) {
            $updates['guardian_attendance_notes'] = $notes;
        }

        if ($status === 'attended') {
            $updates['is_followed_up'] = true;
            $updates['follow_up_notes'] = trim(($this->follow_up_notes ? $this->follow_up_notes."\n" : '').__('تمت مقابلة ولي الأمر والاستماع إليه والاتفاق على المتابعة.'));
        }

        $this->update($updates);
    }

    public function sendToTrash(?User $by = null, ?string $reason = null): bool
    {
        $this->deleted_by_id = $by?->id ?? Auth::id();
        $this->deletion_reason = $reason;
        $this->save();

        return (bool) $this->delete();
    }

    public function restoreFromTrash(): bool
    {
        $this->deleted_by_id = null;
        $this->deletion_reason = null;
        $this->save();

        return (bool) $this->restore();
    }
}
