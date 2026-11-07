<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_items';

    /**
     * Order items table has no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    /**
     * Appends to JSON serialization.
     *
     * @var list<string>
     */
    protected $appends = [
        'product_name',
        'image_url',
        'selected_size',
    ];

    /**
     * Accessor for product name.
     */
    public function getProductNameAttribute(): string
    {
        return $this->product?->name ?? 'Fearless Atelier Garment';
    }

    /**
     * Accessor for product image url.
     */
    public function getImageUrlAttribute(): string
    {
        return $this->product?->image_url 
            ?? $this->product?->primaryImage?->image_url 
            ?? 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=600&auto=format&fit=crop';
    }

    /**
     * Default selected size.
     */
    public function getSelectedSizeAttribute(): string
    {
        return 'L';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'subtotal' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Order that this item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Product associated with this line item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

