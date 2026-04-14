<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query()
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
            )
            ->when($request->filter === 'debtors',  fn($q) => $q->where('balance', '<', 0))
            ->when($request->filter === 'credits',  fn($q) => $q->where('balance', '>', 0))
            ->orderBy('name');

        $clients = $query->paginate(20)->withQueryString();

        return view('app.clients.index', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'email'    => ['nullable', 'email', 'max:150'],
            'birthday' => ['nullable', 'date'],
            'notes'    => ['nullable', 'string', 'max:1000'],
            'photo'    => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')
                ->store('clients/' . auth()->user()->tenant_id, 'public');
        }

        $client = Client::create($data);

        if ($request->expectsJson()) {
            return response()->json($client->fresh());
        }

        return back()->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'email'    => ['nullable', 'email', 'max:150'],
            'birthday' => ['nullable', 'date'],
            'notes'    => ['nullable', 'string', 'max:1000'],
            'photo'    => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            if ($client->photo) Storage::disk('public')->delete($client->photo);
            $data['photo'] = $request->file('photo')
                ->store('clients/' . auth()->user()->tenant_id, 'public');
        }

        $client->update($data);

        return back()->with('success', 'Cliente atualizado!');
    }

    public function destroy(Client $client)
    {
        if ($client->photo) Storage::disk('public')->delete($client->photo);
        $client->delete();
        return back()->with('success', 'Cliente removido.');
    }
}