<?php

namespace Tests\Feature;

use App\Livewire\Admin\ContentEditor;
use App\Livewire\Admin\SettingsEditor;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\TrustItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_server_rendered_service_from_livewire_editor(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true]);
        $category = ServiceCategory::query()->firstOrFail();

        Livewire::actingAs($admin)->test(ContentEditor::class, ['type' => 'services'])
            ->set('data.service_category_id', $category->id)
            ->set('data.name', 'مظلات مداخل بالرياض')
            ->set('data.excerpt', 'خدمة تظليل للمداخل حسب أبعاد الموقع.')
            ->set('data.content', 'تفاصيل الخدمة بعد المعاينة.')
            ->set('data.status', 'published')
            ->set('data.is_active', true)
            ->set('data.is_featured', false)
            ->set('data.is_price_published', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', ['name' => 'مظلات مداخل بالرياض', 'slug' => 'مظلات-مداخل-بالرياض']);
        $this->get(route('services.show', 'مظلات-مداخل-بالرياض'))->assertOk()->assertSee('تفاصيل الخدمة بعد المعاينة.');
    }

    public function test_admin_can_update_official_settings_from_livewire(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)->test(SettingsEditor::class)
            ->set('values.site_name', 'اسم موقع تجريبي')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('اسم موقع تجريبي', Setting::where('key', 'site_name')->value('value'));
        $this->get(route('home'))->assertSee('اسم موقع تجريبي');
    }

    public function test_admin_can_publish_a_real_trust_item_to_the_homepage(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)->test(ContentEditor::class, ['type' => 'trust-items'])
            ->set('data.label', 'نطاق التغطية')
            ->set('data.value', 'جميع أحياء الرياض')
            ->set('data.description', 'خدمة ميدانية داخل مدينة الرياض')
            ->set('data.sort_order', 1)
            ->set('data.is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(TrustItem::class, ['label' => 'نطاق التغطية', 'is_active' => true]);
        $this->get(route('home'))->assertOk()->assertSee('جميع أحياء الرياض');
    }

    public function test_home_hero_image_is_rendered_from_the_managed_setting(): void
    {
        $this->seed();
        $this->assertSame(config('site.hero_image'), Setting::where('key', 'hero_image')->value('value'));

        $this->get(route('home'))->assertOk()->assertSee('storage/'.config('site.hero_image'), false);

        Setting::where('key', 'hero_image')->update(['value' => 'brand/hero-from-settings.webp']);
        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');

        $this->get(route('home'))->assertOk()->assertSee('storage/brand/hero-from-settings.webp', false);
    }
}
