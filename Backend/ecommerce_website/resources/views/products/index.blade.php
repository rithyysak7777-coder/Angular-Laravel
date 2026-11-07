<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products List - T-Shirt Atelier</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background: #0b0f19; color: #f1f5f9; padding: 40px 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        h1 { font-size: 24px; font-weight: 700; color: #38bdf8; }
        .btn-add { background: #0284c7; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; }
        .btn-add:hover { background: #0369a1; }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #86efac; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
        .card { background: #131b2e; border: 1px solid #1e293b; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; }
        .card-img-wrap { width: 100%; height: 220px; background: #0f172a; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 16px; }
        .card-img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .card-body { padding: 16px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .sku { font-size: 11px; text-transform: uppercase; color: #38bdf8; font-weight: 700; letter-spacing: 0.05em; }
        .title { font-size: 15px; font-weight: 600; margin: 6px 0; color: #f8fafc; }
        .price { font-size: 18px; font-weight: 700; color: #38bdf8; margin-top: 8px; }
        .stock { font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Streetwear T-Shirt Inventory</h1>
                <p style="color: #64748b; font-size: 13px; margin-top: 4px;">Showing {{ count($products) }} items</p>
            </div>
            <a href="{{ route('products.create') }}" class="btn-add">+ Add Product with Photo</a>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <div class="grid">
            @forelse ($products as $product)
                <div class="card">
                    <div class="card-img-wrap">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="card-img">
                        @else
                            <div style="color: #64748b; font-size: 13px;">No Photo</div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div>
                            <div class="sku">{{ $product->sku }}</div>
                            <h3 class="title">{{ $product->name }}</h3>
                            <div class="stock">In Stock: {{ $product->stock_quantity }} pcs</div>
                        </div>
                        <div class="price">${{ number_format($product->price, 2) }}</div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b;">
                    No products added yet. Click <a href="{{ route('products.create') }}" style="color: #38bdf8;">Add Product with Photo</a> to create one.
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>

