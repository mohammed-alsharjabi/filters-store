<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\LeadImageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductFeedController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StorefrontController;
use App\Livewire\Admin\ContentEditor;
use App\Livewire\Admin\ContentIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\LeadInbox;
use App\Livewire\Admin\ProductOrderInbox;
use App\Livewire\Admin\SettingsEditor;
use Illuminate\Support\Facades\Route;

Route::controller(PageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/من-نحن', 'about')->name('about');
    Route::get('/الخدمات', 'services')->name('services.index');
    Route::get('/الخدمات/تصنيف/{slug}', 'serviceCategory')->name('services.category');
    Route::get('/الخدمات/{slug}', 'service')->name('services.show');
    Route::get('/المشاريع', 'projects')->name('projects.index');
    Route::get('/المشاريع/{slug}', 'project')->name('projects.show');
    Route::get('/المناطق', 'areas')->name('areas.index');
    Route::get('/المناطق/{slug}', 'area')->name('areas.show');
    Route::get('/المقالات', 'guide')->name('guide.index');
    Route::get('/المقالات/{slug}', 'article')->name('guide.show');
    Route::get('/الأسعار', 'prices')->name('prices');
    Route::get('/احجز-الآن', 'quote')->name('quote');
    Route::get('/تواصل-معنا', 'contact')->name('contact');
    Route::get('/سياسة-الخصوصية', 'privacy')->name('privacy');
    Route::get('/الشروط-والأحكام', 'terms')->name('terms');
});

Route::post('/طلبات', [LeadController::class, 'store'])->middleware('throttle:leads')->name('leads.store');
Route::controller(StorefrontController::class)->group(function () {
    Route::get('/منتجات-الفلاتر', 'index')->name('products.index');
    Route::get('/منتجات-الفلاتر/تصنيف/{slug}', 'category')->name('products.category');
    Route::get('/المنتجات/{slug}', 'show')->name('products.show');
});
Route::get('/المنتجات/{slug}/طلب-واتساب', [CheckoutController::class, 'productWhatsapp'])->middleware('throttle:store-actions')->name('products.whatsapp');
Route::get('/السلة', [CartController::class, 'show'])->name('cart.show');
Route::post('/السلة/{slug}', [CartController::class, 'add'])->middleware('throttle:store-actions')->name('cart.add');
Route::patch('/السلة', [CartController::class, 'update'])->middleware('throttle:store-actions')->name('cart.update');
Route::delete('/السلة/{productId}', [CartController::class, 'remove'])->whereNumber('productId')->middleware('throttle:store-actions')->name('cart.remove');
Route::get('/إتمام-الطلب', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/إتمام-الطلب', [CheckoutController::class, 'submit'])->middleware('throttle:checkout')->name('checkout.submit');
Route::get('/تم-استلام-الطلب/{token}', [CheckoutController::class, 'success'])->whereUuid('token')->name('checkout.success');
Route::get('/feeds/google-products.xml', [ProductFeedController::class, 'google'])->name('feeds.google-products');
Route::get('/feeds/meta-products.csv', [ProductFeedController::class, 'meta'])->name('feeds.meta-products');
Route::get('/sitemap.xml', [SeoController::class, 'index'])->name('sitemap');
Route::get('/sitemap_index.xml', [SeoController::class, 'index'])->name('sitemaps.index');
Route::get('/pages-sitemap.xml', [SeoController::class, 'pages'])->name('sitemaps.pages');
Route::get('/services-sitemap.xml', [SeoController::class, 'services'])->name('sitemaps.services');
Route::get('/products-sitemap.xml', [SeoController::class, 'products'])->name('sitemaps.products');
Route::get('/projects-sitemap.xml', [SeoController::class, 'projects'])->name('sitemaps.projects');
Route::get('/areas-sitemap.xml', [SeoController::class, 'areas'])->name('sitemaps.areas');
Route::get('/articles-sitemap.xml', [SeoController::class, 'articles'])->name('sitemaps.articles');
Route::get('/image-sitemap.xml', [SeoController::class, 'images'])->name('sitemaps.images');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::middleware('guest')->prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:admin-login')->name('admin.login.store');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/settings', SettingsEditor::class)->name('settings');
    Route::get('/leads', LeadInbox::class)->name('leads');
    Route::get('/orders', ProductOrderInbox::class)->name('orders');
    Route::get('/leads/images/{leadImage}', LeadImageController::class)->name('leads.images.download');
    Route::get('/content/{type}', ContentIndex::class)->name('content.index');
    Route::get('/content/{type}/create', ContentEditor::class)->name('content.create');
    Route::get('/content/{type}/{record}/edit', ContentEditor::class)->whereNumber('record')->name('content.edit');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
