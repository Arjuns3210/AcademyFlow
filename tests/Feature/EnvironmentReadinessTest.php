<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class EnvironmentReadinessTest extends TestCase
{
    public function test_error_pages_do_not_leak_stack_traces_when_app_debug_is_false(): void
    {
        Route::get('/test-8d-exception-trigger', function () {
            throw new RuntimeException('Deliberate test exception for APP_DEBUG=false verification');
        })->middleware('web');

        config(['app.debug' => false]);

        $response = $this->get('/test-8d-exception-trigger');

        $response->assertStatus(500);
        $response->assertDontSee('Deliberate test exception', false);
        $response->assertDontSee(__FILE__, false);
        $response->assertDontSee('RuntimeException', false);
    }

    /**
     * Without this, the test above could "pass" for the wrong reason —
     * e.g. an assertion checking the wrong string entirely would pass
     * regardless of whether debug info actually leaked. Confirming the
     * opposite case genuinely DOES show the exception proves the
     * assertion technique can tell the difference at all.
     */
    public function test_error_pages_do_show_details_when_app_debug_is_true(): void
    {
        Route::get('/test-8d-exception-trigger-debug', function () {
            throw new RuntimeException('Deliberate test exception for APP_DEBUG=true verification');
        })->middleware('web');

        config(['app.debug' => true]);

        $response = $this->get('/test-8d-exception-trigger-debug');

        $response->assertStatus(500);
        $response->assertSee('Deliberate test exception', false);
    }
}
