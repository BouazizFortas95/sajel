<?php

namespace App\Filament\Pages;

use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\TeacherReceiptsService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class TeacherReceiptsHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.teacher-receipts-hub';

    #[Url(as: 'teacher')]
    public ?int $selectedTeacherId = null;

    #[Url(as: 'year')]
    public ?string $academicYear = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && (
            $user->isTeacher() ||
            $user->isAdmin() ||
            $user->isPrincipal() ||
            $user->isCensor() ||
            $user->isLeadership() ||
            $user->isDeveloper()
        );
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        $user = Auth::user();

        return $user?->isTeacher()
            ? __('متابعة مخططاتي وأعمالي')
            : __('كشف التأشير الفردي للأستاذ');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('التعليم والتدريس اليومي');
    }

    public function getTitle(): string
    {
        $user = Auth::user();

        return $user?->isTeacher()
            ? __('كشف وضعية استلام وتأشير المخططات والأعمال التقويمية')
            : __('معاينة وضعية التأشير الفردي للأستاذ');
    }

    public function mount(): void
    {
        $user = Auth::user();

        if ($user?->isTeacher()) {
            $this->selectedTeacherId = $user->id;
        } elseif (! $this->selectedTeacherId) {
            $this->selectedTeacherId = User::where('role', 'teacher')->orderBy('name')->value('id');
        }

        $this->academicYear = $this->academicYear ?: (SchoolSetting::current()?->academic_year ?? '2026-2027');
    }

    public function getEffectiveTeacherId(): ?int
    {
        $user = Auth::user();

        if ($user?->isTeacher()) {
            return $user->id;
        }

        return $this->selectedTeacherId ?: $user?->id;
    }

    public function updatedSelectedTeacherId(): void
    {
        $user = Auth::user();
        if ($user?->isTeacher()) {
            $this->selectedTeacherId = $user->id;
        }
    }

    protected function getHeaderActions(): array
    {
        $effectiveId = $this->getEffectiveTeacherId();

        if (! $effectiveId) {
            return [];
        }

        return [
            Action::make('printSeparate')
                ->label('معاينة للطباعة')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('print.teacher-receipts', [
                    'locale' => app()->getLocale(),
                    'user' => $effectiveId,
                    'year' => $this->academicYear,
                ]))
                ->openUrlInNewTab(),

            Action::make('pdf')
                ->label('تحميل كشف الحالة (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('print.teacher-receipts.pdf', [
                    'locale' => app()->getLocale(),
                    'user' => $effectiveId,
                    'year' => $this->academicYear,
                ]))
                ->openUrlInNewTab(),

            Action::make('word')
                ->label('تصدير كشف رسمي (Word)')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn () => route('print.teacher-receipts.word', [
                    'locale' => app()->getLocale(),
                    'user' => $effectiveId,
                    'year' => $this->academicYear,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getViewData(): array
    {
        $effectiveId = $this->getEffectiveTeacherId();

        if (! $effectiveId) {
            return [
                'hasTeacher' => false,
                'canSelectOther' => ! Auth::user()?->isTeacher(),
                'teachersList' => User::where('role', 'teacher')->orderBy('name')->get(),
            ];
        }

        $service = app(TeacherReceiptsService::class);
        $data = $service->viewData($effectiveId, $this->academicYear);

        $canSelectOther = ! Auth::user()?->isTeacher();

        return array_merge($data, [
            'hasTeacher' => true,
            'canSelectOther' => $canSelectOther,
            'teachersList' => $canSelectOther ? User::where('role', 'teacher')->orderBy('name')->get() : collect(),
        ]);
    }
}
