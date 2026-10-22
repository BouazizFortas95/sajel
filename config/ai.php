<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | Which provider powers the AI lesson planner: "gemini" or "openai".
    | Each driver reads its own credentials and model from the sections
    | below, so switching providers only takes one environment change.
    |
    */

    'provider' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeouts (seconds)
    |--------------------------------------------------------------------------
    |
    | Lesson plan generation is an interactive request, so keep the request
    | timeout below your PHP max_execution_time (60s by default on most
    | web SAPIs). The connect timeout only covers reaching the API.
    |
    */

    'timeout' => (int) env('AI_TIMEOUT', 60),

    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Providers occasionally return transient capacity or gateway errors
    | (HTTP 429, 500, 502, 503, 504). `retry_times` is the total number of
    | attempts, and `retry_delay` is the pause between them in milliseconds.
    |
    */

    'retry_times' => (int) env('AI_RETRY_TIMES', 2),

    'retry_delay' => (int) env('AI_RETRY_DELAY', 500),

    /*
    |--------------------------------------------------------------------------
    | Gemini
    |--------------------------------------------------------------------------
    |
    | Uses the Google AI Studio REST API. The key is sent through the
    | x-goog-api-key header. Get one at https://aistudio.google.com/.
    |
    | When the primary model is overloaded (HTTP 503), the service tries
    | `fallback_model` once. Leave it empty to disable the fallback.
    |
    */

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
        'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-3.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI
    |--------------------------------------------------------------------------
    |
    | Uses the Chat Completions API with JSON output mode. The key is sent
    | through the Authorization: Bearer header.
    |
    */

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

];
