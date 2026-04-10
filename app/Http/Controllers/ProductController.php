<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products   = Product::with('category')->orderBy('name')->get();
        $categories = Category::where('type', 'product')->orderBy('name')->get();
        return view('app.products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'brand'          => ['nullable', 'string', 'max:100'],
            'category_id'    => ['nullable', 'uuid', 'exists:categories,id'],
            'for_sale'       => ['boolean'],
            'price'          => ['nullable', 'numeric', 'min:0'],
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'photo'          => ['nullable', 'image', 'max:2048'],
        ]);

        $data['for_sale'] = $request->boolean('for_sale');

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')
                ->store('products/' . auth()->user()->tenant_id, 'public');
        }

        Product::create($data);

        return back()->with('success', 'Produto cadastrado!');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'brand'          => ['nullable', 'string', 'max:100'],
            'category_id'    => ['nullable', 'uuid', 'exists:categories,id'],
            'for_sale'       => ['boolean'],
            'price'          => ['nullable', 'numeric', 'min:0'],
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'photo'          => ['nullable', 'image', 'max:2048'],
            'active'         => ['boolean'],
        ]);

        $data['for_sale'] = $request->boolean('for_sale');
        $data['active']   = $request->boolean('active', true);

        if ($request->hasFile('photo')) {
            if ($product->photo) Storage::disk('public')->delete($product->photo);
            $data['photo'] = $request->file('photo')
                ->store('products/' . auth()->user()->tenant_id, 'public');
        }

        $product->update($data);

        return back()->with('success', 'Produto atualizado!');
    }

    public function destroy(Product $product)
    {
        if ($product->photo) Storage::disk('public')->delete($product->photo);
        $product->delete();
        return back()->with('success', 'Produto removido.');
    }
}