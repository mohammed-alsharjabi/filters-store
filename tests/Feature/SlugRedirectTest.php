<?php

namespace Tests\Feature;

use App\Models\Redirect;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_are_applied_to_paths_without_a_matching_route(): void
    {
        Redirect::create([
            'old_path' => '/booking',
            'new_path' => '/',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/booking')->assertMovedPermanently()->assertRedirect('/');
        $this->assertSame(1, Redirect::where('old_path', '/booking')->value('hits'));
    }

    public function test_arabic_slugs_are_clean_unique_and_create_301_redirects_when_changed(): void
    {
        $category = ServiceCategory::create(['name' => 'المظلات', 'is_active' => true]);
        $first = Service::create(['service_category_id' => $category->id, 'name' => 'مظلات سيارات بالرياض؟', 'is_active' => true, 'status' => 'published', 'published_at' => now()]);
        $second = Service::create(['service_category_id' => $category->id, 'name' => 'مظلات سيارات بالرياض؟', 'is_active' => true, 'status' => 'published', 'published_at' => now()]);
        $this->assertSame('مظلات-سيارات-بالرياض', $first->slug);
        $this->assertSame('مظلات-سيارات-بالرياض-2', $second->slug);
        $oldPath = '/الخدمات/'.$first->slug;
        $first->update(['slug' => 'مظلات سيارات حديثة']);
        $this->assertDatabaseHas('redirects', ['old_path' => $oldPath, 'new_path' => '/الخدمات/مظلات-سيارات-حديثه', 'status_code' => 301]);
        $encodedOldPath = '/'.implode('/', array_map('rawurlencode', explode('/', trim($oldPath, '/'))));
        $this->get($encodedOldPath)->assertStatus(301)->assertRedirect('/الخدمات/مظلات-سيارات-حديثه');
        $this->assertSame(1, Redirect::where('old_path', $oldPath)->value('hits'));
    }
}
