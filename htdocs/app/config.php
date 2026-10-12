<?php
/**
 * Configuration. Contains no secrets and can be committed to Git:
 * every password, key and database setting comes from app/.env
 * (template: app/.env.example).
 */
return [
    // Public address of the site, without a trailing slash.
    // Empty = the address the site is opened with.
    'site_url' => env('SITE_URL'),

    // MySQL database. Empty DB_HOST = no database: the guestbook shows a
    // "not connected" notice and never claims that a comment was saved.
    'db' => [
        'host' => env('DB_HOST'),
        'port' => (int) env('DB_PORT', '3306'),
        'name' => env('DB_NAME'),
        'user' => env('DB_USER'),
        'pass' => env('DB_PASS'),
        // Only for XAMPP: create the database and its tables on the first visit.
        'create_if_missing' => env('DB_CREATE_IF_MISSING') === 'true',
    ],

    // Ask Elias: any OpenAI-compatible chat completions API (default: OpenRouter).
    // Empty AI_API_KEY = the assistant is switched off.
    'ai' => [
        'api_key' => env('AI_API_KEY'),
        'base_url' => env('AI_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('AI_MODEL', 'openai/gpt-4o-mini'),
        // Maximum number of answers per day for all visitors together.
        'daily_limit' => (int) env('AI_DAILY_LIMIT', '400'),
    ],

    // Hash of the admin password (password_hash). Empty = /admin is switched off.
    'admin_password_hash' => env('ADMIN_PASSWORD_HASH'),

    // Random text used to hash visitor IP addresses before they are stored.
    'ip_salt' => env('IP_SALT'),

    // Only true if your host puts the visitor's address into X-Forwarded-For.
    'trust_forwarded_for' => env('TRUST_FORWARDED_FOR') === 'true',
];
