<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfessionalController extends Controller
{
    public function index()
    {
        $professionals = Professional::orderBy('name')->get();
        return view('app.professionals.index', compact('professionals'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:150'],
            'specialty'           => ['nullable', 'string', 'max:100'],
            'birthday'            => ['nullable', 'date'],
            'commission_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'show_on_agenda'      => ['boolean'],
            'receives_commission' => ['boolean'],
            'photo'               => ['nullable', 'image', 'max:2048'],
        ]);

        $data['show_on_agenda']      = $request->boolean('show_on_agenda', true);
        $data['receives_commission'] = $request->boolean('receives_commission', true);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')
                ->store('professionals/' . auth()->user()->tenant_id, 'public');
        }

        Professional::create($data);

        return back()->with('success', 'Profissional cadastrado!');
    }

    public function update(Request $request, Professional $professional)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:150'],
            'specialty'           => ['nullable', 'string', 'max:100'],
            'birthday'            => ['nullable', 'date'],
            'commission_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'show_on_agenda'      => ['boolean'],
            'receives_commission' => ['boolean'],
            'photo'               => ['nullable', 'image', 'max:2048'],
        ]);

        $data['show_on_agenda']      = $request->boolean('show_on_agenda');
        $data['receives_commission'] = $request->boolean('receives_commission');

        if ($request->hasFile('photo')) {
            if ($professional->photo) Storage::disk('public')->delete($professional->photo);
            $data['photo'] = $request->file('photo')
                ->store('professionals/' . auth()->user()->tenant_id, 'public');
        }

        $professional->update($data);

        return back()->with('success', 'Profissional atualizado!');
    }

    public function destroy(Professional $professional)
    {
        if ($professional->photo) Storage::disk('public')->delete($professional->photo);
        $professional->delete();
        return back()->with('success', 'Profissional removido.');
    }
}