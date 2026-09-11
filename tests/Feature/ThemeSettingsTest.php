<?php

namespace Tests\Feature;

use App\Livewire\Admin\SettingsEditor;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_default_theme_is_stored_and_rendered_as_css_variables(): void
    {
        $this->assertSame('#0B4F9C', Setting::where('key', 'theme_primary')->value('value'));

        $this->get(route('home'))->assertOk()
            ->assertSee('--color-primary:#0B4F9C', false)
            ->assertSee('--color-secondary:#20C4E8', false)
            ->assertSee('<meta name="theme-color" content="#0B4F9C">', false);
    }

    public function test_admin_can_save_an_accessible_palette_and_cache_is_cleared(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->get(route('home'))->assertOk();

        Livewire::actingAs($admin)->test(SettingsEditor::class)
            ->set('values.theme_primary', '#000000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('#000000', Setting::where('key', 'theme_primary')->value('value'));
        $this->get(route('home'))->assertSee('--color-primary:#000000', false);
    }

    public function test_invalid_hex_and_inaccessible_contrast_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)->test(SettingsEditor::class)
            ->set('values.theme_primary', 'purple')
            ->call('save')
            ->assertHasErrors(['values.theme_primary']);

        Livewire::actingAs($admin)->test(SettingsEditor::class)
            ->set('values.theme_primary', '#FFFFFF')
            ->set('values.theme_primary_content', '#FFFFFF')
            ->call('save')
            ->assertHasErrors(['values.theme_primary_content']);

        $this->assertSame('#0B4F9C', Setting::where('key', 'theme_primary')->value('value'));
    }

    public function test_admin_can_restore_default_palette(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Setting::where('key', 'theme_primary')->update(['value' => '#000000']);

        Livewire::actingAs($admin)->test(SettingsEditor::class)
            ->call('resetAppearance')
            ->assertSet('values.theme_primary', '#0B4F9C');

        $this->assertSame('#0B4F9C', Setting::where('key', 'theme_primary')->value('value'));
    }
}
