<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Self-registration was leftover Breeze scaffolding, never part of this
 * app's design — every account is provisioned either by the Super Admin
 * (institute creation) or by an Institute Admin (staff creation), never by
 * a visitor signing themselves up. Removed at the user's request since a
 * public /register let anyone create a User row with no tenant and no role
 * at all. This is a permanent regression test proving it stays gone.
 */
class SelfRegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_route_no_longer_exists(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('register'));
    }

    public function test_register_page_is_not_reachable(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_register_submission_is_not_reachable(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }
}
