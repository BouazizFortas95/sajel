<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class LocalizationCompletenessTest extends TestCase
{
    /**
     * Literal keys that intentionally do not need an English/French entry.
     *
     * Keep this list empty whenever possible. Add a key only when it is never
     * rendered to users (for example a machine-readable identifier).
     *
     * @var list<string>
     */
    private const UNTRANSLATED_KEYS = [];

    public function test_every_literal_translation_key_is_available_in_english_and_french(): void
    {
        $keys = $this->literalTranslationKeys();

        $this->assertNotEmpty($keys, 'The translation key scanner did not find any keys.');

        foreach (['en', 'fr'] as $locale) {
            $translations = $this->translations($locale);
            $missing = [];

            foreach ($keys as $key => $locations) {
                if (! $this->requiresTranslation($key) || in_array($key, self::UNTRANSLATED_KEYS, true)) {
                    continue;
                }

                if (! array_key_exists($key, $translations)) {
                    $missing[] = '  - '.$key.' ('.implode(', ', array_slice($locations, 0, 3)).')';
                }
            }

            $this->assertSame(
                [],
                $missing,
                "Missing {$locale} translations for user-facing keys:\n".implode("\n", $missing),
            );
        }
    }

    public function test_non_arabic_translation_keys_are_available_in_arabic(): void
    {
        $translations = $this->translations('ar');
        $missing = [];

        foreach ($this->literalTranslationKeys() as $key => $locations) {
            if (! $this->requiresTranslation($key) || preg_match('/\p{Arabic}/u', $key) === 1) {
                continue;
            }

            if (! array_key_exists($key, $translations)) {
                $missing[] = '  - '.$key.' ('.implode(', ', array_slice($locations, 0, 3)).')';
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Arabic keys fall back to themselves, but non-Arabic keys need an Arabic entry:\n".implode("\n", $missing),
        );
    }

    /**
     * Collect literal keys passed to __(), trans() and @lang().
     *
     * @return array<string, list<string>>
     */
    private function literalTranslationKeys(): array
    {
        $root = base_path();
        $keys = [];

        foreach (['app', 'resources/views', 'routes', 'config'] as $directory) {
            $path = $root.DIRECTORY_SEPARATOR.$directory;

            if (! is_dir($path)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($files as $file) {
                if (! $file->isFile() || ! preg_match('/\.php$/', $file->getFilename())) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());

                if ($content === false) {
                    continue;
                }

                $relative = str_replace($root.DIRECTORY_SEPARATOR, '', $file->getPathname());

                preg_match_all(
                    '/\b(?:__|trans|@lang)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*[,)]/su',
                    $content,
                    $matches,
                    PREG_OFFSET_CAPTURE,
                );

                foreach ($matches[2] as $match) {
                    $key = stripcslashes($match[0]);
                    $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $keys[$key][] = $relative.':'.$line;
                }
            }
        }

        ksort($keys, SORT_STRING);

        return $keys;
    }

    /**
     * @return array<string, mixed>
     */
    private function translations(string $locale): array
    {
        $path = lang_path($locale.'.json');

        $this->assertFileExists($path);

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Numeric placeholders and punctuation are identical in every locale.
     */
    private function requiresTranslation(string $key): bool
    {
        return preg_match('/\p{L}/u', $key) === 1;
    }
}
