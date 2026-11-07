<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display a listing of the resource (user's cart items).
     */
    public function index(Request $request)
    {
        $userId = Auth::id() ?? (int) ($request->header('X-User-Id') ?? $request->get('user_id', 2));

        $items = CartItem::with(['product.primaryImage', 'product.category'])
            ->where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();

        $totalAmount = $items->sum('subtotal');
        $totalItems = $items->sum('qty');

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success'      => true,
                'count'        => $items->count(),
                'total_items'  => $totalItems,
                'grand_total'  => round($totalAmount, 2),
                'data'         => $items,
            ]);
        }

        return view('cart.index', compact('items', 'totalAmount', 'totalItems'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::where('is_active', true)->get();

        return view('cart.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage (Add to cart).
     */
    public function store(Request $request)
    {
        if ($request->has('quantity') && !$request->has('qty')) {
            $request->merge(['qty' => $request->input('quantity')]);
        }

        $validated = $request->validate([
            'user_id'       => 'nullable',
            'product_id'    => 'required|exists:products,id',
            'selected_size' => 'nullable|string|max:20',
            'qty'           => 'nullable|integer|min:1',
        ]);

        $defaultUser = \App\Models\User::first();
        $userId = Auth::id() ?? (int) ($validated['user_id'] ?? $request->header('X-User-Id') ?? $defaultUser?->id ?? 1);
        $size = strtoupper(trim($validated['selected_size'] ?? 'L'));
        $qty = (int) ($validated['qty'] ?? 1);

        // If product already in cart with same size, increment quantity
        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $validated['product_id'])
            ->where('selected_size', $size)
            ->first();

        if ($cartItem) {
            $cartItem->increment('qty', $qty);
        } else {
            $cartItem = CartItem::create([
                'user_id'       => $userId,
                'product_id'    => $validated['product_id'],
                'selected_size' => $size,
                'qty'           => $qty,
            ]);
        }

        $cartItem->load(['product.primaryImage']);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data'    => $cartItem,
            ], 201);
        }

        return redirect()->route('cart.index')->with('success', 'Item added to cart');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $cartItem = CartItem::with(['product.primaryImage'])->findOrFail($id);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $cartItem,
            ]);
        }

        return view('cart.show', compact('cartItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $cartItem = CartItem::with('product')->findOrFail($id);

        return view('cart.edit', compact('cartItem'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cartItem = CartItem::findOrFail($id);

        $validated = $request->validate([
            'qty'           => 'required|integer|min:0',
            'selected_size' => 'nullable|string|max:20',
        ]);

        if ($validated['qty'] <= 0) {
            $cartItem->delete();

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item removed from cart (quantity 0)',
                ]);
            }

            return redirect()->route('cart.index')->with('success', 'Item removed from cart');
        }

        $cartItem->update($validated);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully',
                'data'    => $cartItem->load(['product.primaryImage']),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $cartItem = CartItem::findOrFail($id);
        $cartItem->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart',
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Item removed from cart');
    }

    /**
     * Clear all cart items for the user.
     */
    public function clear(Request $request)
    {
        $userId = Auth::id() ?? (int) ($request->header('X-User-Id') ?? $request->get('user_id', 2));

        CartItem::where('user_id', $userId)->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully',
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart cleared successfully');
    }
}

