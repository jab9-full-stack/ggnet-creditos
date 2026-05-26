<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoreServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logger_creates_audit_log_for_model(): void
    {
        $agency = Agency::query()->create([
            'code' => 'CENTRAL',
            'name' => 'Agencia Central',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'agency_id' => $agency->id,
            'name' => 'Auditor Test',
            'email' => 'auditor@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $log = app(AuditLogger::class)->log(
            event: 'test.event',
            module: 'testing',
            auditable: $agency,
            newValues: ['name' => 'Agencia Central'],
            context: ['source' => 'phpunit'],
            user: $user,
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'event' => 'test.event',
            'module' => 'testing',
            'auditable_type' => Agency::class,
            'auditable_id' => $agency->id,
        ]);

        $this->assertSame('phpunit', $log->context['source']);
    }

    public function test_settings_repository_sets_gets_and_audits_values(): void
    {
        $repository = app(SettingsRepository::class);

        $setting = $repository->set(
            key: 'testing.enabled',
            value: true,
            group: 'testing',
            type: 'boolean',
            description: 'Setting de prueba',
            isPublic: true,
        );

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertTrue($repository->get('testing.enabled'));
        $this->assertTrue(setting_value('testing.enabled'));
        $this->assertArrayHasKey('testing.enabled', $repository->public());

        $this->assertDatabaseHas('settings', [
            'key' => 'testing.enabled',
            'group' => 'testing',
            'type' => 'boolean',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'setting.created',
            'module' => 'settings',
            'auditable_type' => Setting::class,
            'auditable_id' => $setting->id,
        ]);
    }

    public function test_locked_setting_cannot_be_changed_without_locked_flag(): void
    {
        $repository = app(SettingsRepository::class);

        $repository->set(
            key: 'testing.locked',
            value: 'original',
            group: 'testing',
            type: 'string',
            isLocked: true,
        );

        $this->expectException(\InvalidArgumentException::class);

        $repository->set(
            key: 'testing.locked',
            value: 'changed',
            group: 'testing',
            type: 'string',
            isLocked: false,
        );
    }
}
