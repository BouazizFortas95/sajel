@php
    $schoolSetting = $schoolSetting ?? \App\Models\SchoolSetting::current();
    $isRtl = app()->getLocale() === 'ar';
    $register = $activeRegister ?? ($registers->first() ?? null);
    $classroom = $register['classroom'] ?? null;
    $registerLogs = $register['logs'] ?? collect();
    $phase = $register['phase'] ?? null;
    $groups = $register['groups'] ?? collect();
    $assignments = $register['teachers'] ?? collect();
    $periodFrom = $register['from'] ?? null;
    $periodTo = $register['to'] ?? null;
    $registerCount = $register['count'] ?? 0;
    $activeClassroomId = $activeClassroomId ?? ($classroom?->id ?? null);
@endphp

@include('print.partials.document-head', [
    'printTitle' => __('دفتر النصوص الرسمي'),
    'printSubtitle' => __('القسم: :classroom (:level)', [
        'classroom' => $classroom?->name ?? '—',
        'level' => $register['levelName'] ?? '—',
    ]),
    'printOrientation' => 'portrait',
    'printAcademicYear' => $register['academicYear'] ?? $schoolSetting->academic_year,
    'printReference' => __('المرجع'),
    'printMeta' => __('دفتر نصوص القسم'),
    'printAutoPrint' => $autoPrint ?? true,
])

    @php
        $extractCompetence = static function (\App\Models\DailyLog $log): ?string {
            if (is_array($log->content_data)) {
                foreach ($log->content_data as $block) {
                    if (($block['type'] ?? '') === 'lesson_structure' && filled($block['data']['competence'] ?? null)) {
                        return (string) $block['data']['competence'];
                    }
                }
            }

            if (filled($log->actual_content) && preg_match('/<h3[^>]*>.*?الكفاءة المستهدفة.*?<\/h3>\s*<p[^>]*>(.*?)<\/p>/uis', (string) $log->actual_content, $m)) {
                return trim(strip_tags($m[1]));
            }

            return null;
        };

        $cleanContent = static function (\App\Models\DailyLog $log, string $title, ?string $competence): string {
            $content = (string) $log->actual_content;
            if (blank($content)) {
                return '';
            }

            // Remove redundant title and competence if present
            $content = preg_replace('/<h2[^>]*>\s*'.preg_quote($title, '/').'\s*<\/h2>/ui', '', $content);
            if ($competence) {
                $content = preg_replace('/<h3[^>]*>.*?(?:الكفاءة المستهدفة|Target competency|Compétence visée).*?<\/h3>\s*<p[^>]*>.*?<\/p>/uis', '', $content);
            }

            return trim($content);
        };

        $extractStrategies = static function (\App\Models\DailyLog $log): array {
            $strategies = [];

            if (is_array($log->content_data)) {
                foreach ($log->content_data as $block) {
                    $phases = $block['data']['lesson_phases'] ?? $block['data']['phases'] ?? [];
                    if (is_array($phases)) {
                        foreach ($phases as $p) {
                            if (! is_array($p)) {
                                continue;
                            }
                            $strat = $p['strategy'] ?? null;
                            if (filled($strat)) {
                                if (is_numeric($strat)) {
                                    $name = \App\Models\ActiveLearningStrategy::query()->find($strat)?->name;
                                    if ($name) {
                                        $strategies[] = $name;
                                    }
                                } else {
                                    $strategies[] = (string) $strat;
                                }
                            }
                        }
                    }
                }
            }

            if (filled($log->actual_content)) {
                if (preg_match_all('/(?:class="strategy-badge"[^>]*>|استراتيجية:\s*)([^\s<][^<]*?)(?:<\/span>|<|$)/uis', (string) $log->actual_content, $m)) {
                    foreach ($m[1] as $val) {
                        $cleaned = trim(strip_tags(str_replace('استراتيجية:', '', $val)));
                        if ($cleaned !== '') {
                            $strategies[] = $cleaned;
                        }
                    }
                }
            }

            return array_values(array_unique(array_filter($strategies)));
        };
    @endphp

    <style>
        .strategy-badge {
            display: inline-block;
            font-size: 7pt;
            font-weight: 600;
            color: #1e40af;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 1px 5px;
            border-radius: 3px;
            margin-inline-start: 5px;
            vertical-align: middle;
            font-family: 'Cairo', sans-serif;
        }

        .phase-heading {
            display: inline-block;
            color: #1a237e;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            font-size: 8.5pt;
            margin-top: 4px;
            margin-bottom: 1px;
            border-bottom: 1.5px solid #93c5fd;
            padding-bottom: 1px;
        }

        .rich-content h2,
        .rich-content h3 {
            display: none !important;
        }

        .rich-content h4 {
            display: inline-block;
            color: #1a237e;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            font-size: 8.5pt;
            margin: 4px 0 1px;
            border-bottom: 1.5px solid #93c5fd;
            padding-bottom: 1px;
        }

        .rich-content p {
            margin: 0 0 5px;
            color: #1e293b;
            font-size: 9pt;
            line-height: 1.55;
            font-family: 'Amiri', serif;
        }

        .rich-content ul, .rich-content ol {
            margin: 2px 0 5px;
            padding-inline-start: 16px;
        }
    </style>

    {{-- Classroom Switcher Tabs for Multiple Classrooms (Screen Only) --}}
    @if ($registers->count() > 1)
        <div class="no-print" style="margin-bottom: 8px; padding: 4px 8px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; display: flex; align-items: center; gap: 6px; overflow-x: auto;">
            <span style="font-size: 8pt; font-weight: bold; color: #475569; font-family: 'Cairo', sans-serif; white-space: nowrap;">
                {{ __('الأقسام:') }}
            </span>
            @foreach ($registers as $reg)
                @php
                    $regClass = $reg['classroom'];
                    $isActive = ($regClass?->id ?? null) === $activeClassroomId;
                    $tabUrl = route('print.register', array_filter([
                        'classroom_id' => $regClass?->id,
                        'ids' => request()->input('ids'),
                    ]));
                @endphp
                <a
                    href="{{ $tabUrl }}"
                    style="text-decoration: none; padding: 2px 8px; border-radius: 3px; font-size: 8pt; font-family: 'Cairo', sans-serif; font-weight: bold; white-space: nowrap; {{ $isActive ? 'background: #1a237e; color: #fff;' : 'background: #fff; color: #1a237e; border: 1px solid #cbd5e1;' }}"
                >
                    {{ $regClass?->name ?? __('قسم غير محدد') }}
                    <span style="font-size: 7pt; opacity: 0.85;">({{ $reg['count'] }})</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ==================== 1. Standard Identity Card ==================== --}}
    <div class="identity-card" style="margin-top: 6px; margin-bottom: 8px;">
        <div class="identity-grid">
            <div class="identity-item">
                <span class="identity-label">{{ __('المستوى الدراسي') }}</span>
                <p class="identity-value">{{ $register['levelName'] ?? '—' }}</p>
            </div>
            <div class="identity-item">
                <span class="identity-label">{{ __('القسم') }}</span>
                <p class="identity-value">{{ $classroom?->name ?? '—' }}</p>
            </div>
            <div class="identity-item">
                <span class="identity-label">{{ __('الأفواج التربوية') }}</span>
                <p class="identity-value">
                    @if ($groups->isNotEmpty())
                        {{ $groups->pluck('name')->implode(' / ') }}
                    @else
                        {{ __('كامل القسم') }}
                    @endif
                </p>
            </div>
            <div class="identity-item">
                <span class="identity-label">{{ __('الأستاذ(ة) والمادة') }}</span>
                <p class="identity-value">
                    {{ $teacherName }}
                    @if ($primarySubject = ($assignments->first()['subject']?->name ?? null))
                        — <span style="color: #1a237e;">{{ $primarySubject }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- ==================== 2. Sessions Table Section ==================== --}}
    <div class="section-heading" style="margin-top: 8px; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
        <h3 class="section-title" style="margin: 0; font-size: 10pt; font-weight: bold; color: #1a237e; font-family: 'Cairo', sans-serif; display: inline-flex; align-items: center; gap: 6px;">
            <span style="display: inline-block; width: 3px; height: 12px; background: #1a237e; border-radius: 1px;"></span>
            {{ __('سجل الحصص المنجزة') }}
            @if ($periodFrom && $periodTo)
                <span style="font-size: 8pt; font-weight: normal; color: #64748b;">
                    ({{ __('الفترة:') }} <span dir="ltr">{{ $periodFrom->format('Y/m/d') }}</span> {{ __('إلى') }} <span dir="ltr">{{ $periodTo->format('Y/m/d') }}</span>)
                </span>
            @endif
        </h3>
        <span class="badge" style="background: #e2e8f0; color: #1e293b; font-size: 8pt; font-weight: bold; padding: 2px 8px; border-radius: 4px; font-family: 'Cairo', sans-serif;">
            {{ __('إجمالي الحصص: :count', ['count' => $registerCount]) }}
        </span>
    </div>

    <table class="report-table" style="width: 100%; border-collapse: collapse; font-size: 9pt; border: 1.5px solid #1a237e;">
        <thead>
            <tr style="background-color: #1a237e; color: #ffffff;">
                <th style="width: 12%; border: 1px solid #3b82f6; padding: 6px 4px; text-align: center; font-size: 8.5pt;">{{ __('التاريخ والتوقيت') }}</th>
                <th style="width: 31%; border: 1px solid #3b82f6; padding: 6px 6px; text-align: start; font-size: 8.5pt;">{{ __('الموضوع والنشاط التعليمي') }}</th>
                <th style="width: 44%; border: 1px solid #3b82f6; padding: 6px 6px; text-align: start; font-size: 8.5pt;">{{ __('سيرورة التعلمات والمحتوى المنجز فعلياً') }}</th>
                <th style="width: 13%; border: 1px solid #3b82f6; padding: 6px 4px; text-align: center; font-size: 8.5pt;">{{ __('الواجبات وتأشيرة الأستاذ') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registerLogs as $log)
                @php
                    $schedule = $log->schedule;
                    $rowSubject = $schedule?->subject;
                    $rowTeacher = $schedule?->user;
                    $rowGroup = $schedule?->group;
                    $rowDay = $schedule?->day_of_week;
                    $weekdayLabel = $rowDay instanceof \App\Enums\DayOfWeek ? $rowDay->label() : '—';
                    $competence = $extractCompetence($log);
                    $flowHtml = $cleanContent($log, $log->title, $competence);
                @endphp
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    {{-- 1. Date & Time --}}
                    <td style="border: 1px solid #cbd5e1; padding: 6px 4px; text-align: center; vertical-align: top; font-family: 'Cairo', sans-serif;">
                        <span style="display: inline-block; background: #e0f2fe; color: #0369a1; font-weight: bold; padding: 1px 5px; border-radius: 3px; font-size: 8pt;">
                            {{ $log->date?->format('Y/m/d') ?? '—' }}
                        </span>
                        <div style="font-weight: bold; color: #0f172a; font-size: 8.5pt; margin-top: 2px;">
                            {{ $weekdayLabel }}
                        </div>
                        @if ($schedule?->start_time)
                            <div style="font-size: 7.5pt; color: #64748b; margin-top: 1px;" dir="ltr">
                                {{ $schedule->start_time->format('H:i') }}
                                @if ($schedule->end_time)
                                    — {{ $schedule->end_time->format('H:i') }}
                                @endif
                            </div>
                        @endif
                        @if ($rowGroup)
                            <div style="font-size: 7pt; font-weight: bold; color: #1a237e; background: #f1f5f9; padding: 1px 3px; border-radius: 2px; margin-top: 2px; display: inline-block;">
                                {{ $rowGroup->name }}
                            </div>
                        @endif
                    </td>

                    {{-- 2. Topic & Competence --}}
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top;">
                        <strong style="color: #0f172a; font-size: 9.5pt; font-family: 'Cairo', sans-serif; display: block; line-height: 1.35;">
                            {{ $log->title }}
                        </strong>

                        @if ($competence)
                            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-inline-start: 3px solid #16a34a; padding: 3px 6px; border-radius: 2px; margin-top: 4px; font-size: 7.5pt; line-height: 1.35; font-family: 'Cairo', sans-serif;">
                                <span style="color: #166534; font-weight: bold; display: block;">🎯 {{ __('الكفاءة المستهدفة:') }}</span>
                                <span style="color: #14532d;">{{ $competence }}</span>
                            </div>
                        @endif

                        @php
                            $strategies = $extractStrategies($log);
                        @endphp
                        @if (! empty($strategies))
                            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-inline-start: 3px solid #2563eb; padding: 3px 6px; border-radius: 2px; margin-top: 4px; font-size: 7.5pt; line-height: 1.35; font-family: 'Cairo', sans-serif;">
                                <span style="color: #1e40af; font-weight: bold; display: block;">⚡ {{ __('استراتيجيات التعلم النشط:') }}</span>
                                <div style="display: flex; flex-wrap: wrap; gap: 3px; margin-top: 2px;">
                                    @foreach ($strategies as $strat)
                                        <span style="display: inline-block; background: #ffffff; color: #1d4ed8; border: 1px solid #93c5fd; padding: 1px 5px; border-radius: 2px; font-weight: 600; font-size: 7pt;">
                                            {{ $strat }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </td>

                    {{-- 3. Delivered Content Flow --}}
                    <td style="border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top;">
                        @if (filled($flowHtml))
                            <div class="rich-content">
                                {!! strip_tags($flowHtml, '<p><br><strong><b><em><u><ul><ol><li><h4><h5><span>') !!}
                            </div>
                        @else
                            <span style="color: #94a3b8; font-style: italic; font-size: 8pt;">{{ __('لم يُسجل محتوى تفصيلي.') }}</span>
                        @endif

                        @if ($log->isReviewed())
                            <div style="margin-top: 4px; padding: 2px 6px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 2px; font-size: 7.5pt; color: #065f46; font-family: 'Cairo', sans-serif;">
                                <strong>✓ {{ __('تأشيرة الإدارة') }}:</strong> {{ $log->reviewer?->name ?? '—' }} ({{ $log->reviewed_at?->format('Y/m/d') }})
                                @if (filled($log->admin_notes))
                                    — <span>{{ $log->admin_notes }}</span>
                                @endif
                            </div>
                        @endif
                    </td>

                    {{-- 4. Homework & Teacher Visa --}}
                    <td style="border: 1px solid #cbd5e1; padding: 6px 4px; vertical-align: top; font-family: 'Cairo', sans-serif;">
                        @if (filled($log->homework))
                            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-inline-start: 2px solid #d97706; padding: 2px 4px; border-radius: 2px; margin-bottom: 3px; font-size: 7.5pt; line-height: 1.25;">
                                <strong style="color: #92400e; display: block;">{{ __('الواجب:') }}</strong>
                                <span style="color: #78350f;">{!! nl2br(e($log->homework)) !!}</span>
                            </div>
                        @endif

                        @if (filled($log->observations))
                            <div style="font-size: 7pt; color: #64748b; margin-bottom: 3px;">
                                <strong>{{ __('ملاحظات:') }}</strong> {{ $log->observations }}
                            </div>
                        @endif

                        <div style="margin-top: 4px; border-top: 1px dashed #cbd5e1; padding-top: 2px; text-align: center;">
                            <span style="font-size: 7pt; font-weight: bold; color: #1a237e; display: block;">
                                {{ __('تأشيرة الأستاذ') }}
                            </span>
                            <span style="font-size: 6.5pt; color: #64748b;">
                                {{ $log->date?->format('Y/m/d') }}
                            </span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px; color: #64748b; font-family: 'Cairo', sans-serif;">
                        {{ __('لا توجد سجلات منجزة مسجلة لهذا القسم.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ==================== 3. Standard Signatures Grid ==================== --}}
    <div class="signature-grid" style="margin-top: 12px;">
        <div class="signature-box">
            <strong>{{ __('إمضاء الأستاذ(ة)') }}</strong>
            <span style="font-weight: bold; color: #0f172a; margin-top: 2px;">{{ $teacherName }}</span>
            <div class="signature-line"></div>
            <small style="font-size: 7pt; color: #94a3b8;">{{ __('التاريخ والإمضاء') }}</small>
        </div>
        <div class="signature-box">
            <strong>{{ __('تأشيرة السيد(ة) مدير(ة) المؤسسة') }}</strong>
            <span style="font-weight: bold; color: #0f172a; margin-top: 2px;">{{ $schoolSetting->principal_name ?: __('مدير المؤسسة') }}</span>
            <div class="signature-line"></div>
            <small style="font-size: 7pt; color: #94a3b8;">{{ __('المعاينة، الختم والتاريخ') }}</small>
        </div>
        <div class="signature-box">
            <strong>{{ __('تأشيرة السيد(ة) المفتش(ة)') }}</strong>
            <span style="color: #64748b; margin-top: 2px;">{{ __('مفتشية التعليم المتوسط') }}</span>
            <div class="signature-line"></div>
            <small style="font-size: 7pt; color: #94a3b8;">{{ __('التوجيهات التربوية، الختم والتاريخ') }}</small>
        </div>
    </div>

    {{-- ==================== 4. Standard Footer ==================== --}}
    <p class="doc-footer" style="margin-top: 8px; border-top: 1px solid #cbd5e1; padding-top: 3px; font-size: 7.5pt; color: #64748b; display: flex; justify-content: space-between;">
        <span>{{ __('دفتر النصوص الرسمي — قسم :classroom', ['classroom' => $classroom?->name ?? '—']) }} | {{ $schoolSetting->school_name }} | {{ $register['academicYear'] ?? $schoolSetting->academic_year }}</span>
        <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
    </p>

</main>
</body>
</html>
