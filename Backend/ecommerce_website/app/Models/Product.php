<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'sku',
        'description',
        'price',
        'compare_at_price',
        'stock_quantity',
        'is_active',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'image_url',
        'stock',
        'gallery',
        'slug',
        'rating',
        'reviews_count',
        'sizes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Primary image URL accessor with full URL resolution for local files.
     */
    public function getImageUrlAttribute(): ?string
    {
        $url = $this->primaryImage?->image_url ?? $this->images->first()?->image_url;
        if ($url && str_starts_with($url, '/images/')) {
            return url($url);
        }
        return $url;
    }

    /**
     * Stock alias matching frontend expectations.
     */
    public function getStockAttribute(): int
    {
        return (int) $this->stock_quantity;
    }

    /**
     * Array of gallery image URLs with full URL resolution.
     */
    public function getGalleryAttribute(): array
    {
        return $this->images->map(function ($img) {
            $url = $img->image_url;
            if ($url && str_starts_with($url, '/images/')) {
                return url($url);
            }
            return $url;
        })->toArray();
    }

    /**
     * URL slug generated from product name.
     */
    public function getSlugAttribute(): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name), '-'));
    }

    /**
     * Standard rating for e-commerce showcase.
     */
    public function getRatingAttribute(): float
    {
        return 4.9;
    }

    /**
     * Standard review count for e-commerce showcase.
     */
    public function getReviewsCountAttribute(): int
    {
        return 128;
    }

    /**
     * Default apparel sizes.
     */
    public function getSizesAttribute(): array
    {
        return ['S', 'M', 'L', 'XL', 'XXL'];
    }

    /**
     * Category that this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Brand that this product belongs to.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Images associated with this product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('display_order');
    }

    /**
     * The primary image for this product.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_id')->where('is_primary', true);
    }

    /**
     * Order items referencing this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    /**
     * Cart items referencing this product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'product_id');
    }
}

