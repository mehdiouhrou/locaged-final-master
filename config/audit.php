<?php

return [
    // Default retention for most audit entries.
    'log_retention_months' => (int) env('AUDIT_LOG_RETENTION_MONTHS', 6),

    // Long-term retention for destruction-grade traces.
    'destroy_retention_years' => (int) env('AUDIT_DESTROY_RETENTION_YEARS', 30),

    // Secret used to seal each audit log entry.
    // Set AUDIT_HMAC_KEY in production (different from APP_KEY).
    'hmac_key' => env('AUDIT_HMAC_KEY'),

    // Legal evidence export & immutable archive settings.
    'evidence' => [
        'private_key' => env('AUDIT_EVIDENCE_PRIVATE_KEY'),
        'public_key' => env('AUDIT_EVIDENCE_PUBLIC_KEY'),
        'worm_enabled' => env('AUDIT_WORM_ENABLED', false),
        'worm_disk' => env('AUDIT_WORM_DISK', 's3'),
        'worm_prefix' => env('AUDIT_WORM_PREFIX', 'audit-evidence/'),
        'worm_mode' => env('AUDIT_WORM_MODE', 'COMPLIANCE'),
        'worm_retention_years' => (int) env('AUDIT_WORM_RETENTION_YEARS', 10),
    ],
];
