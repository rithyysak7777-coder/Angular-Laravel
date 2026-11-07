<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * Supports both API JSON responses and Web Blade views.
     */
    public function index(Request $request)
    {
        $like = config('database.default') === 'pgsql' ? 'ILIKE' : 'LIKE';

        $query = Product::with(['category', 'images', 'primaryImage'])
            ->where('is_active', true);

        // Filter by category if provided
        if ($request->filled('category') && $request->get('category') !== 'all') {
            $cat = $request->get('category');
            $query->whereHas('category', function ($q) use ($cat, $like) {
                $q->where('name', $like, "%{$cat}%")
                  ->orWhere('id', is_numeric($cat) ? (int) $cat : 0);
            });
        }

        // Search query filter
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm, $like) {
                $q->where('name', $like, "%{$searchTerm}%")
                  ->orWhere('sku', $like, "%{$searchTerm}%")
                  ->orWhere('description', $like, "%{$searchTerm}%");
            });
        }

        // Sorting
        $sort = $request->query('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->orderBy('id', 'asc');
        }

        $products = $query->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'count' => $products->count(),
                'data' => $products,
            ]);
        }

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();

        return view('products.create', compact('categories', 'brands'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Resolve Category ID (accept category_id or category name)
        $categoryId = $request->input('category_id');
        if (!$categoryId && $request->filled('category')) {
            $catInput = $request->input('category');
            if (is_numeric($catInput)) {
                $categoryId = (int) $catInput;
            } elseif (is_string($catInput)) {
                $cat = Category::firstOrCreate(
                    ['name' => trim($catInput)],
                    ['description' => 'Collection of ' . trim($catInput) . ' pieces.', 'is_active' => true]
                );
                $categoryId = $cat->id;
            }
        }
        if (!$categoryId) {
            $categoryId = Category::first()?->id ?? 1;
        }

        // 2. Resolve SKU (auto-generate if missing)
        $sku = $request->input('sku');
        if (!$sku) {
            $sku = 'SOUL-' . strtoupper(Str::random(6));
        }

        // 3. Resolve Stock & Pricing
        $stock = (int) ($request->input('stock_quantity') ?? $request->input('stock', 25));
        $price = (float) $request->input('price', 0);
        $oldPrice = $request->filled('compare_at_price')
            ? (float) $request->input('compare_at_price')
            : ($request->filled('old_price') ? (float) $request->input('old_price') : null);

        $name = $request->input('name', 'Untitled Atelier Piece');
        $description = $request->input('description') ?? $request->input('short_description', '');

        $product = Product::create([
            'category_id'      => $categoryId,
            'brand_id'         => $request->input('brand_id'),
            'name'             => $name,
            'sku'              => $sku,
            'price'            => $price,
            'compare_at_price' => $oldPrice,
            'stock_quantity'   => $stock,
            'description'      => $description,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        $this->handleImages($request, $product);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data'    => $product->load(['category', 'brand', 'images', 'primaryImage']),
            ], 201);
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $product = Product::with(['category', 'brand', 'images', 'primaryImage'])
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('sku', $id);
                } else {
                    $q->where('sku', $id);
                }
            })
            ->firstOrFail();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $product,
            ]);
        }

        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::with(['category', 'brand', 'images'])->findOrFail($id);
        $categories = Category::all();
        $brands = Brand::all();

        return view('products.edit', compact('product', 'categories', 'brands'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $updateData = [];

        if ($request->filled('name')) {
            $updateData['name'] = $request->input('name');
        }

        if ($request->filled('sku')) {
            $updateData['sku'] = $request->input('sku');
        }

        if ($request->has('price')) {
            $updateData['price'] = (float) $request->input('price');
        }

        if ($request->has('compare_at_price')) {
            $updateData['compare_at_price'] = $request->input('compare_at_price') !== null ? (float) $request->input('compare_at_price') : null;
        } elseif ($request->has('old_price')) {
            $updateData['compare_at_price'] = $request->input('old_price') !== null ? (float) $request->input('old_price') : null;
        }

        if ($request->has('stock_quantity')) {
            $updateData['stock_quantity'] = (int) $request->input('stock_quantity');
        } elseif ($request->has('stock')) {
            $updateData['stock_quantity'] = (int) $request->input('stock');
        }

        if ($request->has('description')) {
            $updateData['description'] = $request->input('description');
        } elseif ($request->has('short_description')) {
            $updateData['description'] = $request->input('short_description');
        }

        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('brand_id')) {
            $updateData['brand_id'] = $request->input('brand_id');
        }

        // Category resolution
        if ($request->has('category_id') && $request->filled('category_id')) {
            $updateData['category_id'] = (int) $request->input('category_id');
        } elseif ($request->has('category') && $request->filled('category')) {
            $catInput = $request->input('category');
            if (is_numeric($catInput)) {
                $updateData['category_id'] = (int) $catInput;
            } elseif (is_string($catInput)) {
                $cat = Category::firstOrCreate(
                    ['name' => trim($catInput)],
                    ['description' => 'Collection of ' . trim($catInput) . ' pieces.', 'is_active' => true]
                );
                $updateData['category_id'] = $cat->id;
            }
        }

        if (!empty($updateData)) {
            $product->update($updateData);
        }

        $this->handleImages($request, $product);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data'    => $product->fresh(['category', 'brand', 'images', 'primaryImage']),
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully');
    }

    /**
     * Process uploaded files or image URLs.
     */
    private function handleImages(Request $request, Product $product): void
    {
        $uploadDir = public_path('images/products');
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true, true);
        }

        // Helper to save base64 data URI to file on disk
        $saveBase64ToFile = function ($val, $prefix = 'photo') use ($uploadDir, $product) {
            if (is_string($val) && str_starts_with($val, 'data:image')) {
                if (preg_match('/^data:image\/(\w+);base64,/', $val, $type)) {
                    $imageData = substr($val, strpos($val, ',') + 1);
                    $extension = strtolower($type[1]);
                    if ($extension === 'jpeg') $extension = 'jpg';
                    $decoded = base64_decode($imageData);
                    if ($decoded !== false) {
                        $filename = time() . '_' . $prefix . '_' . Str::slug($product->name) . '_' . Str::random(5) . '.' . $extension;
                        File::put($uploadDir . '/' . $filename, $decoded);
                        return '/images/products/' . $filename;
                    }
                }
            }
            return $val;
        };

        $finalImageUrls = [];

        // 1. Primary photo uploaded via multipart/form-data
        $uploadedFile = $request->file('photo') ?? $request->file('image') ?? $request->file('file');
        if ($uploadedFile && $uploadedFile->isValid()) {
            $filename = time() . '_primary_' . Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $uploadedFile->getClientOriginalExtension();
            $uploadedFile->move($uploadDir, $filename);
            $finalImageUrls[] = '/images/products/' . $filename;
        } elseif ($request->filled('image_url')) {
            $url = $saveBase64ToFile($request->input('image_url'), 'primary');
            if (!empty($url)) {
                $finalImageUrls[] = $url;
            }
        }

        // 2. Multiple photos uploaded via multipart/form-data
        $galleryFiles = $request->file('images') ?? $request->file('photos');
        if (is_array($galleryFiles)) {
            foreach ($galleryFiles as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . ($index + 1) . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadDir, $filename);
                    $finalImageUrls[] = '/images/products/' . $filename;
                }
            }
        }

        // 3. Gallery array of URLs / Base64 data URIs
        if ($request->has('gallery')) {
            $gallery = $request->input('gallery');
            if (is_string($gallery)) {
                $gallery = array_filter(array_map('trim', explode(',', $gallery)));
            }
            if (is_array($gallery)) {
                foreach ($gallery as $idx => $item) {
                    if (!empty($item)) {
                        $savedUrl = $saveBase64ToFile($item, 'gallery_' . ($idx + 1));
                        if (!empty($savedUrl) && !in_array($savedUrl, $finalImageUrls, true)) {
                            $finalImageUrls[] = $savedUrl;
                        }
                    }
                }
            }
        }

        // 4. If we gathered any images, sync them to product_images table
        if (!empty($finalImageUrls)) {
            $product->images()->delete();
            foreach ($finalImageUrls as $order => $url) {
                $product->images()->create([
                    'image_url'     => $url,
                    'public_id'     => 'products/' . ($product->sku ?? time()) . '-' . ($order + 1),
                    'description'   => $product->name . ' Photo ' . ($order + 1),
                    'is_primary'    => $order === 0,
                    'display_order' => $order + 1,
                ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product deleted successfully');
    }

    /**
     * Search products by name, sku, or category from PostgreSQL database.
     */
    public function search(Request $request)
    {
        $query = trim((string) $request->get('query', $request->get('q', '')));

        $products = Product::with(['category', 'images', 'primaryImage'])
            ->where('is_active', true)
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'ILIKE', "%{$query}%")
                        ->orWhere('sku', 'ILIKE', "%{$query}%")
                        ->orWhere('description', 'ILIKE', "%{$query}%")
                        ->orWhereHas('category', function ($catQ) use ($query) {
                            $catQ->where('name', 'ILIKE', "%{$query}%");
                        });
                });
            })
            ->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'query' => $query,
                'count' => $products->count(),
                'data' => $products,
            ]);
        }

        return view('products.search', compact('products', 'query'));
    }

    /**
     * Featured products for hero 3D carousel showcase.
     */
    public function featured(Request $request)
    {
        $products = Product::with(['category', 'brand', 'images', 'primaryImage'])
            ->where('is_active', true)
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $products->count(),
            'data' => $products,
        ]);
    }
}
