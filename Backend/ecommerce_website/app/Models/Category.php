<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'description',
        'is_active',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'item_count',
        'slug',
        'image_url',
        'badge',
    ];

    /**
     * Dynamic product count.
     */
    public function getItemCountAttribute(): int
    {
        return $this->relationLoaded('products') 
            ? $this->products->count() 
            : $this->products()->count();
    }

    /**
     * Dynamic slug.
     */
    public function getSlugAttribute(): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name), '-'));
    }

    /**
     * Representative image URL for frontend categories.
     */
    public function getImageUrlAttribute(): string
    {
        $primary = $this->relationLoaded('products') 
            ? $this->products->first()?->image_url 
            : $this->products()->first()?->image_url;

        return $primary ?? match (strtolower($this->name)) {
            'tees'      => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800&auto=format&fit=crop',
            'originals' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?q=80&w=800&auto=format&fit=crop',
            'hoodies'   => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?q=80&w=800&auto=format&fit=crop',
            'pants'     => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?q=80&w=800&auto=format&fit=crop',
            'caps'      => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?q=80&w=800&auto=format&fit=crop',
            default     => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800&auto=format&fit=crop',
        };
    }

    /**
     * Highlight badge for frontend cards.
     */
    public function getBadgeAttribute(): string
    {
        return match (strtolower($this->name)) {
            'tees'      => 'Series 01',
            'originals' => 'Originals Edition',
            'hoodies'   => '520GSM Fleece',
            'pants'     => 'Ripstop & Denim',
            'caps'      => 'Limited Headwear',
            default     => 'Collection',
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Parent category in the hierarchy.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Subcategories (children) of this category.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Products belonging to this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}

