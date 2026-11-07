<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company() . ' Atelier';

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'logo_url'    => fake()->imageUrl(300, 300, 'fashion'),
            'description' => fake()->paragraph(),
            'website'     => fake()->url(),
            'is_active'   => true,
        ];
    }
}

