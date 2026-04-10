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

        Category::create($data);

        return back()->with('success', 'Categoria cadastrada!');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('success', 'Categoria removida.');
    }
}