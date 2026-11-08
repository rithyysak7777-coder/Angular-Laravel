<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Contains ONLY:
     * - Users: Strictly 2 users (1 Admin and 1 User/Customer).
     * - Categories
     * - Products (with full starting stock & images)
     * - Zero fake orders (orders are NOT seeded; patrons place real orders).
     */
    public function run(): void
    {
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('  1. CLEANING DATABASE: CLEARING ALL PREVIOUS DATA & ORDERS    ');
        $this->command->info('═══════════════════════════════════════════════════════════════');

        // Delete any existing orders, order items, payments, cart items so order list starts completely clean (0 orders)
        Payment::query()->delete();
        OrderItem::query()->delete();
        Order::query()->delete();
        CartItem::query()->delete();

        // Delete all existing users
        $deletedUsersCount = User::query()->delete();
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            try {
                \Illuminate\Support\Facades\DB::statement('ALTER SEQUENCE users_id_seq RESTART WITH 1');
            } catch (\Throwable) {}
        }
        $this->command->info("  [CLEANUP] Deleted {$deletedUsersCount} old user accounts & cleared all previous orders.");

        $this->command->info('─── 2. SEEDING ROLES ───');

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'System Administrator with full management privileges']
        );

        $customerRole = Role::firstOrCreate(
            ['name' => 'customer'],
            ['description' => 'Retail Customer / Storefront Patron']
        );

        $this->command->info("  Role #{$adminRole->id}: admin (Administrator)");
        $this->command->info("  Role #{$customerRole->id}: customer (Retail User / Customer)");

        $this->command->info('─── 3. SEEDING USERS: STRICTLY 2 USERS (1 Admin + 1 User) ───');

        // USER 1: Administrator
        $admin = User::create([
            'email'         => 'admin@gmail.com',
            'fullName'      => 'Admin',
            'phone_number'  => '+85512000001',
            'role_id'       => $adminRole->id,
            'password_hash' => Hash::make('password123'),
            'is_active'     => true,
        ]);
        $this->command->info("  ✓ [USER 1 - ADMIN]: {$admin->fullName} ({$admin->email}) -> Role: admin | Password: password123");

        // USER 2: Retail User / Customer
        $customer = User::create([
            'email'         => 'customer@gmail.com',
            'fullName'      => 'User',
            'phone_number'  => '+85598765432',
            'role_id'       => $customerRole->id,
            'password_hash' => Hash::make('password123'),
            'is_active'     => true,
        ]);
        $this->command->info("  ✓ [USER 2 - USER]: {$customer->fullName} ({$customer->email}) -> Role: customer | Password: password123");

        $totalUsers = User::count();
        $this->command->info("  -> Total users in database: {$totalUsers} (Strictly 1 Admin, 1 User)");

        $this->command->info('─── 4. SEEDING CATEGORIES & BRANDS ───');

        $teesCategory = Category::firstOrCreate(
            ['name' => 'Tees'],
            ['description' => '320GSM custom combed ring-spun cotton streetwear tees', 'is_active' => true]
        );

        $hoodiesCategory = Category::firstOrCreate(
            ['name' => 'Hoodies'],
            ['description' => '520GSM heavy French terry fleece cyber silhouettes', 'is_active' => true]
        );

        $shirtsCategory = Category::firstOrCreate(
            ['name' => 'Shirts'],
            ['description' => 'Tailored architectural oxford & woven atelier button-downs', 'is_active' => true]
        );

        $pantsCategory = Category::firstOrCreate(
            ['name' => 'Pants'],
            ['description' => 'Architectural ripstop trousers & relaxed drop-crotch pants', 'is_active' => true]
        );

        $fearlessBrand = Brand::firstOrCreate(
            ['slug' => 'fearless-soul'],
            [
                'name'        => 'FEARLESS SOUL',
                'description' => 'Originals Atelier luxury streetwear guild',
                'website'     => 'https://fearlesssoul.com',
                'is_active'   => true,
            ]
        );

        $this->command->info('─── 5. SEEDING PRODUCTS WITH FULL STOCK ───');

        $productsData = [
            [
                'name'             => 'UNISEX REGULAR FIT OXFORD SHIRT',
                'sku'              => 'FS-SHT-OXF01',
                'category_id'      => $shirtsCategory->id,
                'brand_id'         => $fearlessBrand->id,
                'price'            => 23.00,
                'compare_at_price' => 35.00,
                'stock_quantity'   => 15,
                'description'      => 'Clean tailored silhouette in crisp combed Oxford cotton with architectural button placket.',
                'image_url'        => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'name'             => 'PANELLED ATHLETIC RAGLAN TEE',
                'sku'              => 'FS-TEE-RAG02',
                'category_id'      => $teesCategory->id,
                'brand_id'         => $fearlessBrand->id,
                'price'            => 25.00,
                'compare_at_price' => 38.00,
                'stock_quantity'   => 12,
                'description'      => '320GSM heavy cotton contrast panelled tee with articulated raglan shoulder construction.',
                'image_url'        => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'name'             => 'CYBER MATRIX OVERSIZED HOODIE',
                'sku'              => 'FS-HOD-CYB03',
                'category_id'      => $hoodiesCategory->id,
                'brand_id'         => $fearlessBrand->id,
                'price'            => 52.00,
                'compare_at_price' => 75.00,
                'stock_quantity'   => 20,
                'description'      => '520GSM French terry double-layered hoodie with drop-shoulder cut and high-density discharge print.',
                'image_url'        => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'name'             => 'ARCHITECTURAL CARGO TROUSERS',
                'sku'              => 'FS-PNT-CRG04',
                'category_id'      => $pantsCategory->id,
                'brand_id'         => $fearlessBrand->id,
                'price'            => 40.00,
                'compare_at_price' => 58.00,
                'stock_quantity'   => 18,
                'description'      => 'Heavy ripstop cargo trousers engineered with modular utility pockets and magnetic closures.',
                'image_url'        => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'name'             => 'ORIGINALS ATELIER DISCHARGE TEE',
                'sku'              => 'FS-TEE-ORG05',
                'category_id'      => $teesCategory->id,
                'brand_id'         => $fearlessBrand->id,
                'price'            => 28.00,
                'compare_at_price' => 42.00,
                'stock_quantity'   => 25,
                'description'      => 'Subtle tonal typographic silkscreen on custom milled ring-spun jersey built for statement presence.',
                'image_url'        => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?q=80&w=800&auto=format&fit=crop',
            ],
        ];

        foreach ($productsData as $pData) {
            $product = Product::updateOrCreate(
                ['sku' => $pData['sku']],
                [
                    'name'             => $pData['name'],
                    'category_id'      => $pData['category_id'],
                    'brand_id'         => $pData['brand_id'],
                    'price'            => $pData['price'],
                    'compare_at_price' => $pData['compare_at_price'],
                    'stock_quantity'   => $pData['stock_quantity'],
                    'description'      => $pData['description'],
                    'is_active'        => true,
                ]
            );

            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'is_primary' => true],
                [
                    'image_url'     => $pData['image_url'],
                    'public_id'     => 'img_' . strtolower($pData['sku']),
                    'description'   => $pData['name'] . ' Primary View',
                    'display_order' => 0,
                ]
            );

            $this->command->info("  Product #{$product->id} [{$pData['name']}] Stock: {$pData['stock_quantity']} units");
        }

        $ordersCount = Order::count();
        $this->command->info("  -> Total orders in database: {$ordersCount} (Zero fake orders; customers place real orders)");

        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('  DATA SEEDER COMPLETE: PRODUCTS, CATEGORIES & 2 USERS ONLY   ');
        $this->command->info('═══════════════════════════════════════════════════════════════');
    }
}
