<?php

namespace Tests\Feature;

use App\Livewire\Admin\ContentEditor;
use App\Livewire\Admin\ProductOrderInbox;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_publishes_the_nineteen_image_products_with_editable_initial_prices(): void
    {
        $this->seed();

        $this->assertDatabaseCount('products', 19);
        $this->assertDatabaseCount('product_categories', 3);
        $this->assertDatabaseCount('media_assets', 19);
        $this->assertSame(19, Product::published()->count());
        $this->assertSame(16, Product::feedReady()->count());
        $this->assertSame(19, Product::query()->whereNotNull('price')->count());
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('فلتر جامبو M-PURE ثلاث مراحل بقاعدة بيضاء')
            ->assertSee('750.00')
            ->assertDontSee('اختر المنتج المناسب واطلب السعر والتوفر مباشرة عبر واتساب.')
            ->assertSee('<meta name="robots" content="index,follow,max-image-preview:large">', false);
    }

    public function test_product_and_category_pages_have_independent_meta_and_dynamic_schema(): void
    {
        $this->seed();
        [$category, $product] = $this->catalogProduct();
        $tag = ProductTag::query()->create(['name' => 'أصلي', 'is_active' => true]);
        $product->tags()->attach($tag);
        $category->seo()->create([
            'meta_title' => 'عنوان تصنيف مستقل',
            'meta_description' => 'وصف مستقل لتصنيف المنتجات المنشور في المتجر.',
            'og_title' => 'مشاركة التصنيف',
            'og_image' => 'products/category.webp',
        ]);
        $product->seo()->create([
            'meta_title' => 'عنوان منتج مستقل',
            'meta_description' => 'وصف مستقل للمنتج المعتمد والمنشور في المتجر.',
            'og_title' => 'مشاركة المنتج',
            'og_image' => 'products/product.webp',
        ]);

        $this->get(route('products.category', $category->slug))
            ->assertOk()
            ->assertSee('<title>عنوان تصنيف مستقل</title>', false)
            ->assertSee('property="og:title" content="مشاركة التصنيف"', false)
            ->assertSee('property="og:image" content="'.asset('storage/products/category.webp').'"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"@type":"LocalBusiness"', false);

        $response = $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('<title>عنوان منتج مستقل</title>', false)
            ->assertSee('property="og:title" content="مشاركة المنتج"', false)
            ->assertSee('property="og:image" content="'.asset('storage/products/product.webp').'"', false)
            ->assertSee('<link rel="canonical" href="'.route('products.show', $product->slug).'">', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"price":"99.90"', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('view_item', false)
            ->assertSee('أصلي');

        $this->assertStringNotContainsString('/وسوم/', $response->getContent());
    }

    public function test_sitemap_and_catalog_feeds_only_expose_feed_ready_products_with_absolute_images(): void
    {
        $this->seed();
        [$category, $product] = $this->catalogProduct();
        Product::query()->create([
            'product_category_id' => $category->id,
            'name' => 'منتج مسودة',
            'brand' => null,
            'status' => 'draft',
        ]);

        $this->get(route('sitemaps.products'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('products.show', $product->slug), false)
            ->assertSee(route('products.category', $category->slug), false)
            ->assertDontSee('منتج-مسوده');

        $this->get(route('feeds.google-products'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<g:title>'.$product->name.'</g:title>', false)
            ->assertSee('<g:image_link>'.$product->imageUrl().'</g:image_link>', false)
            ->assertSee('<g:price>99.90 SAR</g:price>', false)
            ->assertSee('<g:availability>in_stock</g:availability>', false)
            ->assertSee('<g:brand>علامة موثقة</g:brand>', false)
            ->assertDontSee('منتج مسودة');

        $meta = $this->get(route('feeds.meta-products'));
        $meta->assertOk()->assertDownload('meta-products.csv');
        $csv = $meta->streamedContent();
        $this->assertStringContainsString('image_link,brand,inventory,product_type', $csv);
        $this->assertStringContainsString($product->imageUrl(), $csv);
        $this->assertStringContainsString('99.90 SAR', $csv);
        $this->assertStringNotContainsString('منتج مسودة', $csv);
    }

    public function test_cart_and_saved_order_recalculate_prices_from_database_and_update_stock_atomically(): void
    {
        $this->seed();
        [, $product] = $this->catalogProduct();

        $this->post(route('cart.add', $product->slug), ['quantity' => 2])
            ->assertRedirect()
            ->assertSessionHas('data_layer_events.0.event', 'add_to_cart');

        $product->update(['price' => '110.00']);
        $this->get(route('cart.show'))
            ->assertOk()
            ->assertSee('220.00 ر.س');

        $response = $this->post(route('checkout.submit'), [
            'action' => 'store',
            'name' => 'محمد أحمد',
            'phone' => '0501234567',
            'area' => 'حي المونسية',
            'address' => 'الرياض',
            'notes' => 'يرجى التواصل قبل الوصول',
            'terms' => '1',
            'price' => '1.00',
            'total' => '1.00',
        ]);

        $order = Order::query()->with('items')->sole();
        $response->assertRedirect(route('checkout.success', $order->public_token));
        $this->assertSame('220.00', $order->total);
        $this->assertSame('110.00', $order->items->sole()->unit_price);
        $this->assertSame(2, $order->items->sole()->quantity);
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas(InventoryMovement::class, [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'quantity_change' => -2,
            'balance_after' => 3,
        ]);
        $this->assertEmpty(session('store.cart.v1', []));

        $this->get(route('checkout.success', $order->public_token))
            ->assertOk()
            ->assertSee('purchase', false)
            ->assertSee($order->order_number)
            ->assertSee('220.00 ر.س')
            ->assertSee('التحويل البنكي');
    }

    public function test_whatsapp_checkout_emits_event_without_creating_or_decrementing_an_order(): void
    {
        $this->seed();
        [, $product] = $this->catalogProduct();
        $this->post(route('cart.add', $product->slug), ['quantity' => 1]);

        $this->post(route('checkout.submit'), [
            'action' => 'whatsapp',
            'name' => 'محمد أحمد',
            'phone' => '966501234567',
            'area' => 'حي المونسية',
            'terms' => '1',
        ])->assertOk()
            ->assertSee('whatsapp_order', false)
            ->assertSee('https://wa.me/', false)
            ->assertSee(rawurlencode($product->name), false);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertSame([$product->id => 1], session('store.cart.v1'));
    }

    public function test_direct_product_whatsapp_message_contains_the_product_details(): void
    {
        $this->seed();
        $product = Product::published()->with('category')->orderBy('sort_order')->firstOrFail();
        $response = $this->get(route('products.whatsapp', $product->slug))->assertOk();
        $decoded = rawurldecode(html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5));

        $this->assertStringContainsString('المنتج: '.$product->name, $decoded);
        $this->assertStringContainsString('التصنيف: '.$product->category->name, $decoded);
        $this->assertStringContainsString('العلامة التجارية: '.$product->brand, $decoded);
        $this->assertStringContainsString('السعر: 750.00 ر.س', $decoded);
        $this->assertStringContainsString('رابط المنتج: '.route('products.show', $product->slug), $decoded);
        $response->assertSee('whatsapp_order', false);
    }

    public function test_customer_can_upload_a_private_transfer_receipt_and_admin_can_download_it(): void
    {
        Storage::fake('local');
        $this->seed();
        [, $product] = $this->catalogProduct();
        $this->post(route('cart.add', $product->slug), ['quantity' => 1]);
        $this->post(route('checkout.submit'), [
            'action' => 'store',
            'name' => 'محمد أحمد',
            'phone' => '0501234567',
            'area' => 'حي المونسية',
            'terms' => '1',
        ]);
        $order = Order::query()->sole();

        $this->post(route('checkout.receipt.store', $order->public_token), [
            'receipt' => UploadedFile::fake()->create('سند-التحويل.pdf', 180, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('payment_review', $order->status);
        $this->assertNotNull($order->receipt_uploaded_at);
        Storage::disk('local')->assertExists($order->receipt_path);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.orders.receipt', $order))
            ->assertOk()
            ->assertDownload();
    }

    public function test_price_and_stock_validation_reject_unavailable_quantities(): void
    {
        $this->seed();
        [, $product] = $this->catalogProduct(['stock_quantity' => 1]);

        $this->post(route('cart.add', $product->slug), ['quantity' => 2])
            ->assertSessionHasErrors('cart');
        $this->assertEmpty(session('store.cart.v1', []));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_changing_a_published_product_slug_creates_a_working_permanent_redirect(): void
    {
        $this->seed();
        [, $product] = $this->catalogProduct();
        $oldPath = '/المنتجات/'.$product->slug;

        $product->update(['slug' => 'فلتر-جديد']);

        $this->assertDatabaseHas('redirects', [
            'old_path' => $oldPath,
            'new_path' => '/المنتجات/فلتر-جديد',
            'status_code' => 301,
        ]);
        $encodedPath = '/'.implode('/', array_map('rawurlencode', explode('/', trim($oldPath, '/'))));
        $this->get($encodedPath)->assertMovedPermanently()->assertRedirect('/المنتجات/فلتر-جديد');
    }

    public function test_cancelling_an_order_from_admin_restores_tracked_stock_once(): void
    {
        $this->seed();
        [, $product] = $this->catalogProduct();
        $this->post(route('cart.add', $product->slug), ['quantity' => 2]);
        $this->post(route('checkout.submit'), [
            'action' => 'store',
            'name' => 'محمد أحمد',
            'phone' => '0501234567',
            'area' => 'حي المونسية',
            'terms' => '1',
        ]);
        $order = Order::query()->sole();
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)->test(ProductOrderInbox::class)
            ->call('updateStatus', $order->id, 'cancelled')
            ->assertHasNoErrors();

        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_admin_can_create_a_product_draft_and_stock_changes_are_audited(): void
    {
        $this->seed();
        $category = ProductCategory::query()->create(['name' => 'فلاتر منزلية', 'is_active' => true]);
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)->test(ContentEditor::class, ['type' => 'products'])
            ->set('data.product_category_id', $category->id)
            ->set('data.name', 'منتج حقيقي قيد الإعداد')
            ->set('data.stock_quantity', 4)
            ->set('data.status', 'draft')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('name', 'منتج حقيقي قيد الإعداد')->sole();
        $this->assertNull($product->brand);
        $this->assertSame(4, $product->stock_quantity);
        $this->assertDatabaseHas(InventoryMovement::class, [
            'product_id' => $product->id,
            'type' => 'opening_stock',
            'quantity_change' => 4,
            'balance_after' => 4,
        ]);

        Livewire::actingAs($admin)->test(ContentEditor::class, ['type' => 'products', 'record' => $product->id])
            ->set('data.stock_quantity', 7)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas(InventoryMovement::class, [
            'product_id' => $product->id,
            'type' => 'manual_adjustment',
            'quantity_change' => 3,
            'balance_after' => 7,
        ]);
    }

    private function catalogProduct(array $overrides = []): array
    {
        $category = ProductCategory::query()->create([
            'name' => 'فلاتر منزلية',
            'description' => 'منتجات فلاتر المياه المنزلية المعتمدة.',
            'is_active' => true,
        ]);
        $product = Product::query()->create(array_merge([
            'product_category_id' => $category->id,
            'name' => 'فلتر مياه منزلي سبع مراحل مع التركيب في الرياض',
            'sku' => 'FILTER-7',
            'mpn' => 'MODEL-7',
            'brand' => 'علامة موثقة',
            'excerpt' => 'فلتر مياه منزلي بالمواصفات المعتمدة وخدمة التركيب.',
            'description' => 'تفاصيل المنتج الحقيقية المعتمدة من الإدارة.',
            'price' => '99.90',
            'currency' => 'SAR',
            'stock_quantity' => 5,
            'track_stock' => true,
            'allow_backorder' => false,
            'featured_image' => 'products/product.webp',
            'featured_image_alt' => 'فلتر مياه منزلي سبع مراحل',
            'condition' => 'new',
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));

        return [$category, $product];
    }
}
