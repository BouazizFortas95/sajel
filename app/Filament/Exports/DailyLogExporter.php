<?php

namespace App\Filament\Exports;

use App\Enums\DailyLogStatus;
use App\Models\DailyLog;
use Carbon\CarbonInterface;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class DailyLogExporter extends Exporter
{
    protected static ?string $model = DailyLog::class;

    /**
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('date')
                ->label(__('التاريخ'))
                ->formatStateUsing(fn (?CarbonInterface $state): string => $state?->format('Y/m/d') ?? ''),

            ExportColumn::make('schedule.classroom.name')
                ->label(__('القسم')),

            ExportColumn::make('schedule.subject.name')
                ->label(__('المادة')),

            ExportColumn::make('title')
                ->label(__('عنوان الدرس')),

            ExportColumn::make('actual_content')
                ->label(__('المحتوى المنجز'))
                ->formatStateUsing(fn (?string $state): string => static::plainText($state)),

            ExportColumn::make('homework')
                ->label(__('الواجب'))
                ->formatStateUsing(fn (?string $state): string => trim((string) $state)),
        ];
    }

    /**
     * Only completed logs are part of the official class register.
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->where('status', DailyLogStatus::Completed->value);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('تم تصدير دفتر النصوص بنجاح: :count سجل.', [
            'count' => number_format($export->successful_rows),
        ]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.__('تعذّر تصدير :count سجل.', [
                'count' => number_format($failedRowsCount),
            ]);
        }

        return $body;
    }

    /**
     * Convert the rich-editor HTML stored in `actual_content` to readable
     * plain text for CSV/XLSX cells.
     */
    private static function plainText(?string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", (string) $html) ?? (string) $html;
        $text = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", $text) ?? $text;

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
