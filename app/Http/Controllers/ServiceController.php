<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services   = Service::with('category')->orderBy('name')->get();
        $categories = Category::where('type', 'service')->orderBy('name')->get();
        return view('app.services.index', compact('services', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'category_id'    => ['nullable', 'uuid', 'exists:categories,id'],
            'price'          => ['required', 'numeric', 'min:0'],
            'duration_min'   => ['required', 'integer', 'min:5', 'max:480'],
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $service = Service::create($data);

        if ($request->expectsJson()) {
            return response()->json($service->fresh());
        }

        return back()->with('success', 'Serviço cadastrado!');
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'category_id'    => ['nullable', 'uuid', 'exists:categories,id'],
            'price'          => ['required', 'numeric', 'min:0'],
            'duration_min'   => ['required', 'integer', 'min:5', 'max:480'],
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'active'         => ['boolean'],
        ]);

        $data['active'] = $request->boolean('active', true);
        $service->update($data);

        return back()->with('success', 'Serviço atualizado!');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return back()->with('success', 'Serviço removido.');
    }
}