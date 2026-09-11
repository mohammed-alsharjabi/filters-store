<?php

namespace App\Models;

use App\Models\Concerns\HasArabicSlug;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasArabicSlug, HasSeo, SoftDeletes;

    protected $fillable = [
        'product_category_id', 'name', 'slug', 'sku', 'gtin', 'mpn', 'brand', 'excerpt', 'description',
        'price', 'compare_at_price', 'currency', 'stock_quantity', 'track_stock', 'allow_backorder',
        'featured_image', 'featured_image_alt', 'featured_image_caption', 'condition', 'status',
        'is_featured', 'sort_order', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function routePrefix(): string
    {
        return 'المنتجات';
    }

    public function slugCandidate(string $source): string
    {
        return Str::words($source, 6, '');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('featured_image')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->whereNotNull('price')
            ->where('price', '>', 0);
    }

    public function scopeFeedReady(Builder $query): Builder
    {
        return $query->published();
    }

    public function isAvailable(): bool
    {
        return ! $this->track_stock || $this->stock_quantity > 0 || $this->allow_backorder;
    }

    public function availableQuantity(): int
    {
        return $this->track_stock && ! $this->allow_backorder ? max(0, $this->stock_quantity) : 99;
    }

    public function availabilityForFeed(): string
    {
        if ($this->isAvailable()) {
            return $this->stock_quantity > 0 || ! $this->track_stock ? 'in_stock' : 'preorder';
        }

        return 'out_of_stock';
    }

    public function schemaAvailability(): string
    {
        return match ($this->availabilityForFeed()) {
            'in_stock' => 'https://schema.org/InStock',
            'preorder' => 'https://schema.org/PreOrder',
            default => 'https://schema.org/OutOfStock',
        };
    }

    public function imageUrl(): ?string
    {
        return $this->featured_image ? asset('storage/'.ltrim($this->featured_image, '/')) : null;
    }
}
