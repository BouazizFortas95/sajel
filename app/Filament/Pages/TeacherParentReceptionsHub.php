<?php

namespace App\Filament\Pages;

use App\Models\Subject;
use App\Models\User;
use App\Services\TeacherParentReceptionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class TeacherParentReceptionsHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.teacher-parent-receptions-hub';

    #[Url(as: 'q')]
    public string $searchQuery = '';

    #[Url(as: 'subject')]
    public ?int $selectedSubjectId = null;

    #[Url(as: 'status')]
    public string $selectedStatus = 'all'; // 'all', 'assigned', 'pending'

    #[Url(as: 'day')]
    public ?string $selectedDay = null;

    public ?int $editingTeacherId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && (
            $user->isAdmin() ||
            $user->isLeadership() ||
            $user->isTeacher() ||
            $user->isGeneralSupervisor() ||
            $user->canManageDiscipline()
        );
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('الحياة المدرسية وشؤون التلاميذ');
    }

    public static function getNavigationLabel(): string
    {
        return __('استقبال أولياء التلاميذ');
    }

    public function getTitle(): string
    {
        return __('جدول ومواعيد استقبال أولياء التلاميذ — أوقات الفراغ الأسبوعية');
    }

    public function getSubheading(): ?string
    {
        return __('تنظيم المقابلات الدورية مع أولياء التلاميذ في أوقات الفراغ بين الحصص، واستخراج الوثيقة الرسمية المعتمدة.');
    }

    public function getHeaderActions(): array
    {
        $canManage = Auth::user()?->isAdmin() || Auth::user()?->isLeadership();

        return [
            Action::make('auto_assign_gaps')
                ->label(__('⚡ توليد واعتماد المواعيد من أوقات الفراغ'))
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible($canManage)
                ->form([
                    Toggle::make('overwrite')
                        ->label(__('إعادة التعيين والتحديث حتى للأساتذة الذين لديهم مواعيد محددة مسبقاً'))
                        ->helperText(__('في حال عدم التفعيل، سيتم فقط تحديد مواعيد للأساتذة الذين ليس لديهم موعد استقبال حالياً.'))
                        ->default(false),
                ])
                ->action(function (array $data, TeacherParentReceptionService $service): void {
                    $count = $service->autoAssignReceptions(overwriteExisting: (bool) ($data['overwrite'] ?? false));

                    Notification::make()
                        ->title(__('تم اعتماد مواعيد الاستقبال بنجاح'))
                        ->body(__('تم تحديد وتحديث موعد الاستقبال الأسبوعي لـ :count أستاذ(ة) استناداً إلى أوقات فراغهم بين الحصص.', ['count' => $count]))
                        ->success()
                        ->send();
                }),

            Action::make('print_official')
                ->label(__('🖨️ طباعة الجدول الرسمي (A4)'))
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn (): string => route('print.teacher-parent-receptions', ['locale' => app()->getLocale()]))
                ->openUrlInNewTab(),

            Action::make('download_pdf')
                ->label(__('📥 تحميل PDF'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('print.teacher-parent-receptions.pdf', ['locale' => app()->getLocale()]))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Get teachers with their schedules and reception slots according to active filters.
     *
     * @return Collection<int, User>
     */
    public function getTeachersProperty(): Collection
    {
        return User::query()
            ->where(function ($q): void {
                $q->where('role', 'teacher')
                    ->orWhereHas('schedules');
            })
            ->with(['primarySubject', 'schedules.classroom', 'schedules.group.classroom'])
            ->when(filled($this->searchQuery), function ($q): void {
                $q->where('name', 'like', '%'.$this->searchQuery.'%');
            })
            ->when($this->selectedSubjectId, function ($q): void {
                $q->where('primary_subject_id', $this->selectedSubjectId);
            })
            ->when($this->selectedDay, function ($q): void {
                $q->where('parent_reception_day', $this->selectedDay);
            })
            ->when($this->selectedStatus === 'assigned', function ($q): void {
                $q->whereNotNull('parent_reception_day')
                    ->whereNotNull('parent_reception_start_time');
            })
            ->when($this->selectedStatus === 'pending', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('parent_reception_day')
                        ->orWhereNull('parent_reception_start_time');
                });
            })
            ->get()
            ->sortBy([
                ['primarySubject.name', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    /**
     * Quick-apply a detected gap slot for a teacher.
     */
    public function applySlot(int $teacherId, string $day, string $start, string $end, TeacherParentReceptionService $service): void
    {
        $teacher = User::findOrFail($teacherId);

        $teacher->update([
            'parent_reception_day' => $day,
            'parent_reception_start_time' => $start,
            'parent_reception_end_time' => $end,
            'parent_reception_location' => $teacher->parent_reception_location ?: 'مكتب الاستشارة التربوية',
            'parent_reception_notes' => sprintf('تم اعتماد فترة الفراغ الأسبوعية: %s (%s - %s)', $service->dayLabel($day), $start, $end),
        ]);

        Notification::make()
            ->title(__('تم اعتماد موعد الاستقبال للأستاذ(ة)'))
            ->body(__('تم تحديد يوم :day من :start إلى :end كموعد استقبال أسبوعي للأستاذ(ة) :name.', [
                'day' => $service->dayLabel($day),
                'start' => $start,
                'end' => $end,
                'name' => $teacher->name,
            ]))
            ->success()
            ->send();
    }

    /**
     * Clear reception slot for a teacher.
     */
    public function clearSlot(int $teacherId): void
    {
        $teacher = User::findOrFail($teacherId);

        $teacher->update([
            'parent_reception_day' => null,
            'parent_reception_start_time' => null,
            'parent_reception_end_time' => null,
        ]);

        Notification::make()
            ->title(__('تم إلغاء موعد الاستقبال للأستاذ(ة)'))
            ->success()
            ->send();
    }

    /**
     * Get statistics for the dashboard cards.
     *
     * @return array{total: int, assigned: int, pending: int}
     */
    public function getStatsProperty(): array
    {
        $all = User::query()
            ->where(function ($q): void {
                $q->where('role', 'teacher')
                    ->orWhereHas('schedules');
            })
            ->get();

        $total = $all->count();
        $assigned = $all->filter(fn ($t) => $t->hasParentReceptionSlot())->count();

        return [
            'total' => $total,
            'assigned' => $assigned,
            'pending' => $total - $assigned,
        ];
    }

    /**
     * Available subjects for filtering.
     *
     * @return Collection<int, Subject>
     */
    public function getSubjectsProperty(): Collection
    {
        return Subject::query()->orderBy('name')->get();
    }
}
