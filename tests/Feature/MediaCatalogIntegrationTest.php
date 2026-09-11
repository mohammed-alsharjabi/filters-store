<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaCatalogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_all_nineteen_sources_are_shared_by_products_without_duplicate_public_paths(): void
    {
        $this->assertSame(19, MediaAsset::query()->count());
        $this->assertSame(19, Product::query()->count());
        $this->assertSame(19, MediaAsset::query()->distinct()->count('path'));
        $this->assertSame(19, MediaAsset::query()->distinct()->count('content_hash'));

        MediaAsset::query()->with('products')->each(function (MediaAsset $asset): void {
            $this->assertFileExists(storage_path('app/public/'.$asset->path));
            $this->assertNotEmpty($asset->alt_text);
            $this->assertSame('image/webp', $asset->mime_type);
            $this->assertCount(1, $asset->products);
            $this->assertSame($asset->path, $asset->products->first()->featured_image);
            $this->assertSame($asset->id, $asset->products->first()->media_asset_id);
        });
    }

    public function test_home_displays_all_media_and_links_featured_products_to_their_details(): void
    {
        $home = $this->get(route('home'))->assertOk()
            ->assertSee('منتجاتنا وأعمالنا')
            ->assertSee('عرض جميع المنتجات');

        $this->assertSame(19, substr_count($home->getContent(), 'data-lightbox-item'));
        $product = Product::published()->orderBy('sort_order')->firstOrFail();
        $home->assertSee(route('products.show', $product->slug), false);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($product->featured_image_alt)
            ->assertSee('750.00')
            ->assertSee('view_item', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"offers"', false);

        Product::published()->each(function (Product $publishedProduct): void {
            $this->get(route('products.show', $publishedProduct->slug))
                ->assertOk()
                ->assertSee($publishedProduct->name)
                ->assertSee(asset('storage/'.$publishedProduct->featured_image), false);
        });
    }

    public function test_every_service_has_ten_unique_linked_images_and_renders_ten_gallery_items(): void
    {
        Service::query()->with('mediaAssets')->each(function (Service $service): void {
            $this->assertCount(10, $service->mediaAssets, $service->name);
            $this->assertCount(10, $service->mediaAssets->unique('id'), $service->name);

            $response = $this->get(route('services.show', $service->slug))->assertOk();
            $dom = new DOMDocument;
            @$dom->loadHTML($response->getContent());
            $galleryItems = (new DOMXPath($dom))->query('//*[@data-service-gallery]//figure');
            $this->assertSame(10, $galleryItems?->length, $service->name);
        });
    }

    public function test_articles_and_admin_use_the_same_media_library_records(): void
    {
        Article::query()->with('mediaAssets')->each(function (Article $article): void {
            $this->assertCount(5, $article->mediaAssets);
            $this->assertSame($article->mediaAssets->first()->path, $article->featured_image);
            $this->get(route('guide.show', $article->slug))
                ->assertOk()
                ->assertSee(asset('storage/'.$article->featured_image), false);
        });

        $admin = User::factory()->create(['is_admin' => true]);
        $asset = MediaAsset::query()->orderBy('sort_order')->firstOrFail();
        $product = $asset->products()->firstOrFail();

        $this->actingAs($admin)->get(route('admin.content.index', 'media-assets'))
            ->assertOk()
            ->assertSee($asset->name)
            ->assertSee($asset->imageUrl(), false);
        $this->actingAs($admin)->get(route('admin.content.edit', ['type' => 'products', 'record' => $product->id]))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($asset->name);
    }

    public function test_reseeding_preserves_product_and_media_edits_from_the_admin(): void
    {
        $product = Product::query()->orderBy('sort_order')->firstOrFail();
        $asset = $product->mediaAsset;
        $product->update(['name' => 'اسم معتمد من الإدارة', 'price' => '875.00', 'stock_quantity' => 6, 'track_stock' => true]);
        $asset->update(['alt_text' => 'نص بديل معتمد من الإدارة']);

        $this->seed();

        $this->assertSame(19, Product::query()->count());
        $this->assertSame('اسم معتمد من الإدارة', $product->fresh()->name);
        $this->assertSame('875.00', $product->fresh()->price);
        $this->assertSame(6, $product->fresh()->stock_quantity);
        $this->assertSame('نص بديل معتمد من الإدارة', $asset->fresh()->alt_text);
    }
}
