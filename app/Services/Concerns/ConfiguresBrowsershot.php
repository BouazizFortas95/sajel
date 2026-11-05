<?php

namespace App\Services\Concerns;

use App\Support\BrowsershotFactory;
use Spatie\Browsershot\Browsershot;

trait ConfiguresBrowsershot
{
    /**
     * Create a pre-configured Browsershot instance for HTML payloads.
     */
    protected function browsershot(string $html): Browsershot
    {
        return BrowsershotFactory::html($html);
    }
}
