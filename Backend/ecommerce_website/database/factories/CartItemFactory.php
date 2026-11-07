<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'product_id'    => Product::factory(),
            'selected_size' => fake()->randomElement(['S', 'M', 'L', 'XL', 'XXL']),
            'qty'           => fake()->numberBetween(1, 3),
        ];
    }
}

