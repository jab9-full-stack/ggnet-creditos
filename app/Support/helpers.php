<?php

use App\Support\Audit\AuditLogger;
use App\Support\Settings\SettingsRepository;

if (! function_exists('audit_logger')) {
    function audit_logger(): AuditLogger
    {
        return app(AuditLogger::class);
    }
}

if (! function_exists('setting_value')) {
    function setting_value(string $key, mixed $default = null): mixed
    {
        return app(SettingsRepository::class)->get($key, $default);
    }
}
