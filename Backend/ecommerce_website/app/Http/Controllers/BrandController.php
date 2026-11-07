<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Brand::withCount('products');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $brands = $query->orderBy('name', 'asc')->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'count'   => $brands->count(),
                'data'    => $brands,
            ]);
        }

        return view('brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('brands.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:brands,name',
            'slug'        => 'nullable|string|max:120|unique:brands,slug',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'logo_url'    => 'nullable|string',
            'description' => 'nullable|string',
            'website'     => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $brandData = collect($validated)->except(['logo'])->toArray();
        $brand = Brand::create($brandData);

        // Handle logo file upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $uploadDir = public_path('images/brands');
            if (!File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true, true);
            }
            $file = $request->file('logo');
            $filename = time() . '_' . Str::slug($brand->name) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $brand->update(['logo_url' => '/images/brands/' . $filename]);
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Brand created successfully',
                'data'    => $brand,
            ], 201);
        }

        return redirect()->route('brands.index')->with('success', 'Brand created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $brand = Brand::with(['products.primaryImage', 'products.category'])
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id);
                } else {
                    $q->where('slug', $id)->orWhere('name', 'ILIKE', $id);
                }
            })
            ->firstOrFail();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $brand,
            ]);
        }

        return view('brands.show', compact('brand'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $brand = Brand::findOrFail($id);

        return view('brands.edit', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:100|unique:brands,name,' . $brand->id,
            'slug'        => 'nullable|string|max:120|unique:brands,slug,' . $brand->id,
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'logo_url'    => 'nullable|string',
            'description' => 'nullable|string',
            'website'     => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $updateData = collect($validated)->except(['logo'])->toArray();
        $brand->update($updateData);

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $uploadDir = public_path('images/brands');
            if (!File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true, true);
            }
            $file = $request->file('logo');
            $filename = time() . '_' . Str::slug($brand->name) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $brand->update(['logo_url' => '/images/brands/' . $filename]);
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully',
                'data'    => $brand,
            ]);
        }

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully',
            ]);
        }

        return redirect()->route('brands.index')->with('success', 'Brand deleted successfully');
    }

    /**
     * Search brands by name or description.
     */
    public function search(Request $request)
    {
        $query = trim((string) $request->get('query', $request->get('q', '')));

        $brands = Brand::withCount('products')
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'ILIKE', "%{$query}%")
                        ->orWhere('description', 'ILIKE', "%{$query}%");
                });
            })
            ->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'query'   => $query,
                'count'   => $brands->count(),
                'data'    => $brands,
            ]);
        }

        return view('brands.search', compact('brands', 'query'));
    }
}

