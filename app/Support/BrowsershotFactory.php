<?php

namespace App\Support;

use Illuminate\Support\Facades\Vite;
use Spatie\Browsershot\Browsershot;

class BrowsershotFactory
{
    /**
     * Create and pre-configure a Browsershot instance for an HTML payload.
     */
    public static function html(string $html): mixed
    {
        $html = static::ensureStylesInlined($html);

        return static::configure(Browsershot::html($html));
    }

    /**
     * Ensure compiled app.css and base URL are embedded inline for headless Chromium rendering.
     */
    public static function ensureStylesInlined(string $html): string
    {
        if (str_contains($html, 'sajel-inlined-app-css') || str_contains($html, 'data-sajel-inlined')) {
            return $html;
        }

        $css = null;
        try {
            if (class_exists(Vite::class)) {
                $css = Vite::content('resources/css/app.css');
            }
        } catch (\Throwable) {
            $css = null;
        }

        if (! $css) {
            $builtCss = glob(public_path('build/assets/app-*.css'));
            if (! empty($builtCss) && file_exists($builtCss[0])) {
                $css = @file_get_contents($builtCss[0]);
            }
        }

        if (! $css) {
            return $html;
        }

        $baseUrl = rtrim((string) config('app.url', 'http://sajel.dev'), '/').'/';
        $injected = '<base href="'.htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8').'"><style id="sajel-inlined-app-css">'.str_replace('</style', '<\/style', $css).'</style>';

        if (stripos($html, '<head>') !== false) {
            return preg_replace('/<head>/i', "<head>\n".$injected, $html, 1);
        }

        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $injected."\n</head>", $html);
        }

        return $injected."\n".$html;
    }

    /**
     * Create and pre-configure a Browsershot instance for a URL.
     */
    public static function url(string $url): mixed
    {
        return static::configure(Browsershot::url($url));
    }

    /**
     * Pre-configure Browsershot with Node, NPM, Chrome/Edge, and environment flags.
     */
    public static function configure(mixed $browsershot): mixed
    {
        $browsershot
            ->format('A4')
            ->showBackground()
            ->margins(10, 10, 10, 10)
            ->setOption('preferCSSPageSize', true)
            ->newHeadless()
            ->waitUntilNetworkIdle()
            ->timeout(120);

        if (is_dir(base_path('node_modules'))) {
            $browsershot->setNodeModulePath(base_path('node_modules'));
        }

        // 1. Node Binary
        $node = config('services.browsershot.node_binary') ?: static::detectNodeBinary();
        if ($node) {
            $normalizedNode = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $node);
            if (file_exists($normalizedNode)) {
                $browsershot->setNodeBinary($normalizedNode);
            }
        }

        // 2. NPM Binary
        $npm = config('services.browsershot.npm_binary') ?: static::detectNpmBinary();
        if ($npm) {
            $normalizedNpm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $npm);
            if (file_exists($normalizedNpm)) {
                $browsershot->setNpmBinary($normalizedNpm);
            }
        }

        // 3. Chrome / Edge Binary
        $chrome = config('services.browsershot.chrome_path') ?: static::detectChromePath();
        if ($chrome) {
            $normalizedChrome = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $chrome);
            if (file_exists($normalizedChrome)) {
                $browsershot->setChromePath($normalizedChrome);
            }
        }

        // Essential Chromium sandbox arguments for Windows, IIS, Apache, and container environments
        $browsershot->addChromiumArguments([
            'no-sandbox',
            'disable-setuid-sandbox',
            'disable-dev-shm-usage',
            'disable-gpu',
        ]);

        return $browsershot;
    }

    /**
     * Auto-detect Chrome or Edge executable on host system.
     */
    public static function detectChromePath(): ?string
    {
        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            getenv('LOCALAPPDATA').'\\Google\\Chrome\\Application\\chrome.exe',
            getenv('PROGRAMFILES').'\\Google\\Chrome\\Application\\chrome.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
        ];

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Auto-detect Node.js binary on host system.
     */
    public static function detectNodeBinary(): ?string
    {
        $candidates = [
            'C:\\Program Files\\nodejs\\node.exe',
            'C:\\Program Files (x86)\\nodejs\\node.exe',
            getenv('PROGRAMFILES').'\\nodejs\\node.exe',
            '/usr/bin/node',
            '/usr/local/bin/node',
        ];

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Auto-detect NPM binary on host system.
     */
    public static function detectNpmBinary(): ?string
    {
        $candidates = [
            'C:\\Program Files\\nodejs\\npm.cmd',
            'C:\\Program Files (x86)\\nodejs\\npm.cmd',
            getenv('PROGRAMFILES').'\\nodejs\\npm.cmd',
            '/usr/bin/npm',
            '/usr/local/bin/npm',
        ];

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
