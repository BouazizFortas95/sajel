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
    | Gemini
    |--------------------------------------------------------------------------
    |
    | Uses the Google AI Studio REST API. The key is sent through the
    | x-goog-api-key header. Get one at https://aistudio.google.com/.
    |
    */

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
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
