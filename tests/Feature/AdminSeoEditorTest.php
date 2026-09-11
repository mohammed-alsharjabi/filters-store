<?php

namespace Tests\Feature;

use App\Livewire\Admin\ContentEditor;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSeoEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_simple_service_editor_generates_optional_seo_fields_and_keeps_draft_noindex(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true]);
        $category = ServiceCategory::query()->firstOrFail();

        Livewire::actingAs($admin)->test(ContentEditor::class, ['type' => 'services'])
            ->set('data.name', 'خدمة فلتر اختبارية')
            ->set('data.excerpt', 'وصف فريد ومبسط للخدمة المقترحة.')
            ->set('data.service_category_id', $category->id)
            ->set('data.status', 'draft')
            ->call('save')
            ->assertHasNoErrors();

        $service = Service::query()->where('name', 'خدمة فلتر اختبارية')->with('seo')->firstOrFail();
        $this->assertSame('خدمه-فلتر-اختباريه', $service->slug);
        $this->assertSame('draft', $service->status);
        $this->assertSame('noindex,follow', $service->seo->robots);
        $this->assertNotEmpty($service->seo->meta_title);
        $this->assertNotEmpty($service->seo->meta_description);
        $this->assertNotEmpty($service->seo->focus_keyword);
        $this->assertNotEmpty($service->seo->canonical_url);
        $this->assertNotEmpty($service->seo->internal_links);
        $this->assertDatabaseCount('services', 11);
    }

    public function test_duplicate_meta_title_and_focus_keyword_generate_admin_warnings(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true]);
        $service = Service::query()->firstOrFail();
        SeoMetadata::query()->create([
            'route_name' => 'about',
            'meta_title' => 'عنوان SEO مكرر',
            'focus_keyword' => 'فلاتر مياه الرياض',
        ]);

        Livewire::actingAs($admin)->test(ContentEditor::class, ['type' => 'services', 'record' => $service->id])
            ->set('seo.meta_title', 'عنوان SEO مكرر')
            ->set('seo.focus_keyword', 'فلاتر مياه الرياض')
            ->assertSee('عنوان SEO مستخدم في صفحة أخرى')
            ->assertSee('الكلمة المستهدفة مستخدمة في صفحة أخرى');
    }

    public function test_slug_uniqueness_is_enforced_by_database_and_model_generation(): void
    {
        $this->seed();
        $category = ServiceCategory::query()->firstOrFail();
        $first = Service::create(['service_category_id' => $category->id, 'name' => 'خدمة Slug فريدة', 'status' => 'draft']);
        $second = Service::create(['service_category_id' => $category->id, 'name' => 'خدمة Slug فريدة', 'status' => 'draft']);

        $this->assertNotSame($first->slug, $second->slug);
        $this->assertSame($first->slug.'-2', $second->slug);
    }
}
