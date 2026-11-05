<?php

namespace App\Support;

use Spatie\Browsershot\Browsershot;

class BrowsershotFactory
{
    /**
     * Create and pre-configure a Browsershot instance for an HTML payload.
     */
    public static function html(string $html): mixed
    {
        return static::configure(Browsershot::html($html));
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
            ->waitUntilNetworkIdle()
            ->timeout(120);

        if (is_dir(base_path('node_modules'))) {
            $browsershot->setNodeModulePath(base_path('node_modules'));
        }

        // 1. Node Binary
        $node = config('services.browsershot.node_binary') ?: static::detectNodeBinary();
        if ($node && file_exists($node)) {
            $browsershot->setNodeBinary($node);
        }

        // 2. NPM Binary
        $npm = config('services.browsershot.npm_binary') ?: static::detectNpmBinary();
        if ($npm && file_exists($npm)) {
            $browsershot->setNpmBinary($npm);
        }

        // 3. Chrome / Edge Binary
        $chrome = config('services.browsershot.chrome_path') ?: static::detectChromePath();
        if ($chrome && file_exists($chrome)) {
            $browsershot->setChromePath($chrome);
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
