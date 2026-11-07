<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - T-Shirt Atelier</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background: #0b0f19; color: #f1f5f9; padding: 40px 20px; }
        .container { max-width: 680px; margin: 0 auto; background: #131b2e; border: 1px solid #1e293b; border-radius: 12px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
        h1 { font-size: 24px; font-weight: 700; margin-bottom: 24px; color: #38bdf8; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f8fafc; font-size: 14px; transition: border-color 0.2s;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; }
        .file-upload-box {
            border: 2px dashed #334155; border-radius: 10px; padding: 24px; text-align: center; background: #0f172a; cursor: pointer; transition: border-color 0.2s;
        }
        .file-upload-box:hover { border-color: #38bdf8; }
        .file-upload-box input[type="file"] { display: block; margin: 10px auto; color: #94a3b8; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        button[type="submit"] {
            width: 100%; padding: 14px; background: #0284c7; color: #ffffff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 10px;
        }
        button[type="submit"]:hover { background: #0369a1; }
        .hint { font-size: 12px; color: #64748b; margin-top: 4px; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Streetwear T-Shirt</h1>

        @if ($errors->any())
            <div class="alert-danger">
                <ul style="padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Cyber Skull Core Obsidian Tee" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="sku">SKU Code *</label>
                    <input type="text" id="sku" name="sku" value="{{ old('sku') }}" placeholder="SOUL-TEE-008" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="brand_id">Brand</label>
                    <select id="brand_id" name="brand_id">
                        <option value="">Select Brand (Optional)</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', 50) }}" min="0" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" step="0.01" id="price" name="price" value="{{ old('price') }}" placeholder="48.00" min="0" required>
                </div>
                <div class="form-group">
                    <label for="compare_at_price">Compare At Price ($)</label>
                    <input type="number" step="0.01" id="compare_at_price" name="compare_at_price" value="{{ old('compare_at_price') }}" placeholder="60.00" min="0">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="320GSM custom combed ring-spun cotton streetwear drops...">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label>Primary Photo Upload (Stored to public/images/products)</label>
                <div class="file-upload-box">
                    <p style="font-size: 14px; color: #cbd5e1; margin-bottom: 6px;">Select image from your computer</p>
                    <input type="file" name="photo" id="photo" accept="image/*">
                    <p class="hint">JPG, PNG, WEBP, or SVG up to 5MB. Will be saved to: <code>public/images/products/</code></p>
                </div>
            </div>

            <div class="form-group">
                <label for="image_url">Or Image URL (Optional)</label>
                <input type="text" id="image_url" name="image_url" value="{{ old('image_url') }}" placeholder="https://... or /assets/showcase/tee-1.svg">
                <p class="hint">If no file is chosen, this external or local image URL will be used.</p>
            </div>

            <button type="submit">Save Product with Photo</button>
        </form>
    </div>
</body>
</html>

