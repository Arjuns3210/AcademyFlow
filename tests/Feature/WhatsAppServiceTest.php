<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppSetting;
use App\Services\WhatsAppService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_sends_a_template_successfully_and_logs_it(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;
        WhatsAppSetting::create([
            'institute_id' => $institute->id, 'phone_number_id' => '1234567890',
            'access_token' => 'test-token', 'is_active' => true,
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST123']]], 200)]);

        $result = app(WhatsAppService::class)->sendTemplate(
            $institute, '919876543210', 'fee_reminder',
            ['recipient_name' => 'Ravi', 'amount' => '1000', 'month' => 'August', 'institute_name' => 'Bright Coaching', 'due_date' => '2026-08-05'],
            studentId: null,
        );

        $this->assertTrue($result);
        $log = WhatsAppLog::firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertSame('wamid.TEST123', $log->meta_message_id);
        $this->assertSame($institute->id, $log->institute_id);
        $this->assertNull($log->error_message);
    }

    public function test_params_are_sent_to_meta_in_the_templates_defined_order(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;
        WhatsAppSetting::create([
            'institute_id' => $institute->id, 'phone_number_id' => '1234567890',
            'access_token' => 'test-token', 'is_active' => true,
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

        app(WhatsAppService::class)->sendTemplate(
            $institute, '919876543210', 'attendance_absent',
            ['institute_name' => 'Bright Coaching', 'recipient_name' => 'Ravi', 'date' => '2026-08-05', 'student_name' => 'Aarav'],
        );

        Http::assertSent(function ($request) {
            $params = $request['template']['components'][0]['parameters'];

            // attendance_absent order: recipient_name, student_name, date, institute_name
            return $params[0]['text'] === 'Ravi'
                && $params[1]['text'] === 'Aarav'
                && $params[2]['text'] === '2026-08-05'
                && $params[3]['text'] === 'Bright Coaching';
        });
    }

    public function test_meta_rejecting_the_request_logs_a_failure_with_the_error_message(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;
        WhatsAppSetting::create([
            'institute_id' => $institute->id, 'phone_number_id' => 'bad-id',
            'access_token' => 'bad-token', 'is_active' => true,
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token.']], 401)]);

        $result = app(WhatsAppService::class)->sendTemplate(
            $institute, '919876543210', 'fee_reminder',
            ['recipient_name' => 'Ravi', 'amount' => '1000', 'month' => 'August', 'institute_name' => 'X', 'due_date' => '2026-08-05'],
        );

        $this->assertFalse($result);
        $log = WhatsAppLog::firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertSame('Invalid OAuth access token.', $log->error_message);
        $this->assertNull($log->meta_message_id);
    }

    public function test_connection_failure_logs_a_failure_instead_of_throwing(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;
        WhatsAppSetting::create([
            'institute_id' => $institute->id, 'phone_number_id' => '1234567890',
            'access_token' => 'test-token', 'is_active' => true,
        ]);

        Http::fake(function () {
            throw new ConnectionException('Could not resolve host.');
        });

        $result = app(WhatsAppService::class)->sendTemplate(
            $institute, '919876543210', 'fee_reminder',
            ['recipient_name' => 'Ravi', 'amount' => '1000', 'month' => 'August', 'institute_name' => 'X', 'due_date' => '2026-08-05'],
        );

        $this->assertFalse($result);
        $this->assertSame('failed', WhatsAppLog::firstOrFail()->status);
    }

    public function test_institutes_own_settings_take_priority_over_the_platform_default(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;
        WhatsAppSetting::create(['institute_id' => null, 'phone_number_id' => 'PLATFORM_DEFAULT', 'access_token' => 'default-token', 'is_active' => true]);
        WhatsAppSetting::create(['institute_id' => $institute->id, 'phone_number_id' => 'INSTITUTE_OWN', 'access_token' => 'own-token', 'is_active' => true]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

        app(WhatsAppService::class)->sendTemplate(
            $institute, '919876543210', 'fee_reminder',
            ['recipient_name' => 'Ravi', 'amount' => '1000', 'month' => 'August', 'institute_name' => 'X', 'due_date' => '2026-08-05'],
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), 'INSTITUTE_OWN'));
    }

    public function test_falls_back_to_platform_default_when_institute_has_no_settings_of_its_own(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;
        WhatsAppSetting::create(['institute_id' => null, 'phone_number_id' => 'PLATFORM_DEFAULT', 'access_token' => 'default-token', 'is_active' => true]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

        $result = app(WhatsAppService::class)->sendTemplate(
            $institute, '919876543210', 'fee_reminder',
            ['recipient_name' => 'Ravi', 'amount' => '1000', 'month' => 'August', 'institute_name' => 'X', 'due_date' => '2026-08-05'],
        );

        $this->assertTrue($result);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'PLATFORM_DEFAULT'));
    }

    public function test_no_settings_at_all_fails_without_calling_the_api(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;

        Http::fake();

        $result = app(WhatsAppService::class)->sendTemplate(
            $institute, '919876543210', 'fee_reminder',
            ['recipient_name' => 'Ravi', 'amount' => '1000', 'month' => 'August', 'institute_name' => 'X', 'due_date' => '2026-08-05'],
        );

        $this->assertFalse($result);
        Http::assertNothingSent();
        $this->assertStringContainsString('No active WhatsApp number', WhatsAppLog::firstOrFail()->error_message);
    }

    public function test_an_inactive_settings_row_is_not_used(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;
        WhatsAppSetting::create(['institute_id' => $institute->id, 'phone_number_id' => 'X', 'access_token' => 'Y', 'is_active' => false]);

        Http::fake();

        app(WhatsAppService::class)->sendTemplate(
            $institute, '919876543210', 'fee_reminder',
            ['recipient_name' => 'Ravi', 'amount' => '1000', 'month' => 'August', 'institute_name' => 'X', 'due_date' => '2026-08-05'],
        );

        Http::assertNothingSent();
    }

    public function test_unknown_template_name_throws(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->institute;

        $this->expectException(InvalidArgumentException::class);

        app(WhatsAppService::class)->sendTemplate($institute, '919876543210', 'not_a_real_template', []);
    }
}
