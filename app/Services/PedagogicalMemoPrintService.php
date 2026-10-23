<?php

namespace App\Services;

use App\Models\PedagogicalMemo;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Facades\Vite;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds the standalone pedagogical-memo sheet and renders it to PDF with
 * headless Chrome, keeping Arabic shaping and Tailwind layout identical to
 * the browser print view.
 */
class PedagogicalMemoPrintService
{
    /**
     * @return array<string, mixed>
     */
    public function viewData(
        PedagogicalMemo $memo,
        bool $autoPrint = true,
        bool $inlineCss = false,
    ): array {
        $memo->loadMissing([
            'subject',
            'level.educationPhase',
            'user',
        ]);

        return [
            'memo' => $memo,
            'teacher' => $memo->user,
            'subject' => $memo->subject,
            'level' => $memo->level,
            'educationPhase' => $memo->level?->educationPhase,
            'academicYear' => $this->currentAcademicYear(),
            'autoPrint' => $autoPrint,
            'inlineCss' => $inlineCss,
        ];
    }

    /**
     * Render the memo and inline Vite's compiled Tailwind stylesheet.
     *
     * Browsershot receives an HTML string, so the compiled CSS is embedded
     * instead of relying on Chrome being able to resolve the application host.
     */
    public function pdfHtml(PedagogicalMemo $memo): string
    {
        $css = $this->compiledCss();

        $html = view('print.pedagogical-memo', $this->viewData(
            memo: $memo,
            autoPrint: false,
            inlineCss: $css !== null,
        ))->render();

        return $css === null ? $html : $this->inlineCss($html, $css);
    }

    public function pdfResponse(PedagogicalMemo $memo): StreamedResponse
    {
        $pdf = $this->browsershot($this->pdfHtml($memo))->pdf();

        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf;
            },
            'مذكرة-بيداغوجية-'.$memo->getKey().'-'.now()->format('Y-m-d').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function browsershot(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(10, 10, 10, 10)
            ->setOption('preferCSSPageSize', true)
            ->waitUntilNetworkIdle()
            ->timeout(120);

        if (is_dir(base_path('node_modules'))) {
            $browsershot->setNodeModulePath(base_path('node_modules'));
        }

        foreach ([
            'node_binary' => 'setNodeBinary',
            'npm_binary' => 'setNpmBinary',
            'chrome_path' => 'setChromePath',
        ] as $configKey => $method) {
            $value = config("services.browsershot.{$configKey}");

            if (filled($value)) {
                $browsershot->{$method}($value);
            }
        }

        return $browsershot;
    }

    private function inlineCss(string $html, string $css): string
    {
        $baseUrl = e(rtrim((string) config('app.url'), '/').'/');

        return str_replace(
            '</head>',
            '<base href="'.$baseUrl.'"><style>'.str_replace('</style', '<\/style', $css).'</style></head>',
            $html,
        );
    }

    private function compiledCss(): ?string
    {
        try {
            return Vite::content('resources/css/app.css');
        } catch (ViteException) {
            return null;
        }
    }

    private function currentAcademicYear(): string
    {
        $startYear = now()->month >= 9 ? now()->year : now()->year - 1;

        return "{$startYear}/".($startYear + 1);
    }
}
