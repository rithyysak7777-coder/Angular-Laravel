<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with(['items.product.images', 'items.product.primaryImage', 'payments', 'user'])->orderBy('id', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        $orders = $query->limit(100)->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'count' => $orders->count(),
                'data' => $orders,
            ]);
        }

        return view('orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::all();
        $products = Product::where('is_active', true)->get();

        return view('orders.create', compact('users', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'total_amount' => 'required|numeric|min:0',
            'shipping_address' => 'required|array',
            'billing_address' => 'nullable|array',
            'payment_method' => 'nullable|string',
            'payment_reference' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Validate Stock Availability for all items before placing the order
        foreach ($validated['items'] as $itemData) {
            $productId = $itemData['product_id'];
            $product = Product::find($productId);
            if (!$product && !empty($itemData['product_name'])) {
                $product = Product::where('name', $itemData['product_name'])->first();
            }
            if ($product) {
                if ($product->stock_quantity <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product '{$product->name}' is out of stock.",
                    ], 422);
                }
                if ($itemData['quantity'] > $product->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot order {$itemData['quantity']} units of '{$product->name}'. Available stock is only {$product->stock_quantity}.",
                    ], 422);
                }
            }
        }

        return DB::transaction(function () use ($validated, $request) {
            $userId = $validated['user_id'] ?? null;
            if (!$userId || !User::where('id', $userId)->exists()) {
                $shippingEmail = $validated['shipping_address']['email'] ?? null;
                if ($shippingEmail) {
                    $foundUser = User::where('email', strtolower(trim($shippingEmail)))->first();
                    $userId = $foundUser?->id;
                }
            }
            if (!$userId || !User::where('id', $userId)->exists()) {
                $user = User::whereHas('role', fn ($q) => $q->where('name', 'customer'))->first();
                $userId = $user?->id ?? User::first()?->id ?? 1;
            }

            $orderNumber = $validated['order_number'] ?? ('ORD-' . date('Ym') . '-' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT));

            $order = Order::create([
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'status' => 'pending',
                'total_amount' => $validated['total_amount'],
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['billing_address'] ?? $validated['shipping_address'],
                'payment_reference' => $validated['payment_reference'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $productId = (int) $itemData['product_id'];
                $product = Product::find($productId);

                // If not found by ID, attempt lookup by name
                if (!$product && !empty($itemData['product_name'])) {
                    $product = Product::where('name', $itemData['product_name'])->first();
                }

                if (!$product) {
                    $product = Product::first();
                }

                $resolvedProductId = $product?->id ?? $productId;
                $quantity = (int) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $subtotal = $unitPrice * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $resolvedProductId,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);

                // Cut stock out in database
                if ($product) {
                    $currentStock = (int) $product->stock_quantity;
                    $newStock = max(0, $currentStock - $quantity);
                    $product->stock_quantity = $newStock;
                    $product->save();
                }
            }

            if ($request->filled('payment_method')) {
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $request->input('payment_method'),
                    'amount' => $validated['total_amount'],
                    'status' => 'COMPLETED',
                ]);
            }

            $order->load(['items.product.images', 'items.product.primaryImage', 'payments']);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'data' => $order,
                ], 201);
            }

            return redirect()->route('orders.index')->with('success', 'Order created successfully');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $order = Order::with(['items.product.images', 'payments', 'user'])
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id)->orWhere('order_number', $id);
                } else {
                    $q->where('order_number', $id);
                }
            })
            ->firstOrFail();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        }

        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $order = Order::with(['items.product', 'payments'])->findOrFail($id);

        return view('orders.edit', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|string|max:32',
            'shipping_address' => 'sometimes|array',
            'billing_address' => 'sometimes|array',
            'payment_reference' => 'nullable|string',
        ]);

        $order->update($validated);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order->load(['items.product', 'payments']),
            ]);
        }

        return redirect()->route('orders.index')->with('success', 'Order updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully',
            ]);
        }

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully');
    }

    /**
     * Cancel an order within 1-4 hours window if pending.
     */
    public function cancel(Request $request, string $id)
    {
        $order = Order::with('items')->where(function ($q) use ($id) {
            if (is_numeric($id)) {
                $q->where('id', (int) $id)->orWhere('order_number', $id);
            } else {
                $q->where('order_number', $id);
            }
        })->firstOrFail();

        // 1. Must be pending
        if (strtolower((string) $order->status) !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be cancelled. Current status is: ' . strtoupper($order->status),
            ], 422);
        }

        // 2. Check 4-hour cancellation window
        $createdAt = $order->created_at;
        if ($createdAt) {
            $minutesElapsed = abs(now()->diffInMinutes($createdAt));
            if ($minutesElapsed > 240) { // 4 hours = 240 minutes
                $hoursElapsed = round($minutesElapsed / 60, 1);
                return response()->json([
                    'success' => false,
                    'message' => "Order cannot be cancelled. The 4-hour cancellation window has expired ({$hoursElapsed} hours passed).",
                    'hours_elapsed' => $hoursElapsed
                ], 422);
            }
        }

        // 3. Mark as cancelled
        $order->update(['status' => 'cancelled']);

        // 4. Restore product stock quantities
        foreach ($order->items as $item) {
            if ($item->product_id) {
                Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
            }
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Order ' . $order->order_number . ' has been cancelled successfully within the 4-hour window.',
                'data'    => $order->fresh(['items.product', 'payments']),
            ]);
        }

        return redirect()->back()->with('success', 'Order cancelled successfully.');
    }
}
