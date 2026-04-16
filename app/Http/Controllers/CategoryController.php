<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:service,product,expense'],
        ]);

        $category = Category::create($data);

        if ($request->expectsJson()) {
            return response()->json($category->fresh());
        }

        return back()->with('success', 'Categoria cadastrada!');
    }

    public function destroy(Request $request, Category $category)
    {
        $category->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Categoria removida.');
    }
}