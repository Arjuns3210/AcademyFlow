<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteType;
use App\Models\Module;
use App\Models\User;
use App\Support\ThemeColorPresets;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The color picker was replaced with a curated preset palette after a user
 * picked a blue sidebar color that made the institute name (a plain link,
 * not a .nav-link) nearly invisible against it — every preset here is
 * pre-checked against Institute::sidebarStyle()'s contrast heuristic, and
 * validation now rejects anything outside that list, closing the door on
 * a repeat even via a crafted request that bypasses the swatch UI entirely.
 */
class ThemeColorPresetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_create_page_renders_a_swatch_for_every_preset(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('super-admin.institutes.create'));

        $response->assertOk();

        foreach (ThemeColorPresets::colors() as $color) {
            $response->assertSee($color);
        }
    }

    public function test_a_preset_color_is_accepted_and_persists(): void
    {
        $type = InstituteType::firstOrFail();

        $this->actingAs($this->superAdmin())->post(route('super-admin.institutes.store'), [
            'name' => 'Preset Test Institute',
            'institute_type_id' => $type->id,
            'theme_color' => '#14532d',
            'admin_name' => 'Preset Admin',
            'admin_email' => 'preset-admin@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'modules' => [Module::where('slug', 'students')->first()->id],
        ])->assertRedirect(route('super-admin.institutes.index'));

        $institute = Institute::where('name', 'Preset Test Institute')->firstOrFail();
        $this->assertSame('#14532d', $institute->theme_color);
    }

    public function test_a_non_preset_color_is_rejected_even_via_a_crafted_request(): void
    {
        $type = InstituteType::firstOrFail();

        $response = $this->actingAs($this->superAdmin())->post(route('super-admin.institutes.store'), [
            'name' => 'Bypass Attempt Institute',
            'institute_type_id' => $type->id,
            'theme_color' => '#0080ff', // a real color, just not one of the curated presets
            'admin_name' => 'Bypass Admin',
            'admin_email' => 'bypass-admin@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('theme_color');
        $this->assertDatabaseMissing('institutes', ['name' => 'Bypass Attempt Institute']);
    }

    public function test_a_legacy_non_preset_color_from_before_this_change_still_renders_correctly(): void
    {
        $admin = $this->tenantAdmin();
        $admin->institute->update(['theme_color' => '#0080ff']);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('--sidebar-bg: #0080ff', false);
    }

    public function test_editing_an_institute_with_a_legacy_color_without_touching_the_selector_does_not_wipe_it(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->tenantAdmin();
        $institute = $admin->institute;
        $institute->update(['theme_color' => '#0080ff']);

        // The edit page must offer the institute's own current (non-preset)
        // color as a swatch, pre-selected.
        $editPage = $this->actingAs($superAdmin)->get(route('super-admin.institutes.edit', $institute));
        $editPage->assertOk();
        $editPage->assertSee('#0080ff');

        // Simulating a real browser submit of this exact form as rendered
        // (radio group naturally resubmits whichever option is checked) —
        // saving an unrelated field must not silently reset the color.
        $this->actingAs($superAdmin)->put(route('super-admin.institutes.update', $institute), [
            'name' => $institute->name,
            'institute_type_id' => $institute->institute_type_id,
            'theme_color' => '#0080ff',
            'contact' => '9999999999',
            'modules' => $institute->modules()->wherePivot('is_enabled', true)->pluck('modules.id')->all(),
        ])->assertRedirect(route('super-admin.institutes.index'));

        $this->assertSame('#0080ff', $institute->fresh()->theme_color);
        $this->assertSame('9999999999', $institute->fresh()->contact);
    }

    private function superAdmin(): User
    {
        return User::where('email', 'admin@academyflow.in')->firstOrFail();
    }

    private function tenantAdmin(): User
    {
        return User::where('email', 'admin@brightcoaching.com')->firstOrFail();
    }
}
