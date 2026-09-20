<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfessionalController extends Controller
{
    public function index(Request $request)
    {
        $professionals = Professional::orderBy('name')
            ->when($request->search, fn($q, $s) => $q->whereLike('name', "%{$s}%"))
            ->with(['services' => fn($q) => $q->withPivot('commission_pct')])
            ->get();

        $services = Service::where('active', true)
            ->with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'commission_pct', 'category_id']);

        $tenant = auth()->user()->tenant;

        return view('app.professionals.index', compact('professionals', 'services', 'tenant'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:150'],
            'specialty'           => ['nullable', 'string', 'max:100'],
            'birthday'            => ['nullable', 'date'],
            'show_on_agenda'      => ['boolean'],
            'receives_commission' => ['boolean'],
            'photo'               => ['nullable', 'mimes:jpeg,jpg,png,gif,webp,heic,heif,avif', 'max:5120'],
            'work_schedule'       => ['nullable', 'string'],
            'custom_commissions'   => ['nullable', 'array'],
            'custom_commissions.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['show_on_agenda']      = $request->boolean('show_on_agenda', true);
        $data['receives_commission'] = $request->boolean('receives_commission', true);

        if ($request->filled('work_schedule')) {
            $data['work_schedule'] = json_decode($request->work_schedule, true);
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')
                ->store('professionals/' . auth()->user()->tenant_id, 'public');
        }

        DB::transaction(function () use ($data, $request) {
            $professional = Professional::create($data);
            $this->syncServiceCommissions($request, $professional);
        });

        return back()->with('success', 'Profissional cadastrado!');
    }

    public function update(Request $request, Professional $professional)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:150'],
            'specialty'           => ['nullable', 'string', 'max:100'],
            'birthday'            => ['nullable', 'date'],
            'show_on_agenda'      => ['boolean'],
            'receives_commission' => ['boolean'],
            'photo'               => ['nullable', 'mimes:jpeg,jpg,png,gif,webp,heic,heif,avif', 'max:5120'],
            'work_schedule'       => ['nullable', 'string'],
            'custom_commissions'   => ['nullable', 'array'],
            'custom_commissions.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['show_on_agenda']      = $request->boolean('show_on_agenda');
        $data['receives_commission'] = $request->boolean('receives_commission');

        if ($request->filled('work_schedule')) {
            $data['work_schedule'] = json_decode($request->work_schedule, true);
        }

        if ($request->hasFile('photo')) {
            if ($professional->photo) Storage::disk('public')->delete($professional->photo);
            $data['photo'] = $request->file('photo')
                ->store('professionals/' . auth()->user()->tenant_id, 'public');
        }

        DB::transaction(function () use ($data, $request, $professional) {
            $professional->update($data);
            $this->syncServiceCommissions($request, $professional);
        });

        return back()->with('success', 'Profissional atualizado!');
    }

    public function destroy(Professional $professional)
    {
        if ($professional->photo) Storage::disk('public')->delete($professional->photo);
        $professional->delete();
        return back()->with('success', 'Profissional removido.');
    }

    private function syncServiceCommissions(Request $request, Professional $professional): void
    {
        if (! $request->has('custom_commissions')) return;

        $custom = $request->input('custom_commissions', []);

        // Só serviços deste salão (a chave do array vem do cliente e não é validada pelo `.*`)
        $validKeys     = array_values(array_filter(array_keys($custom), fn ($k) => is_string($k) && Str::isUuid($k)));
        $ownServiceIds = Service::whereIn('id', $validKeys)->pluck('id')->all();
        $syncData = [];

        foreach ($custom as $serviceId => $pct) {
            if (in_array($serviceId, $ownServiceIds, true) && $pct !== null && $pct !== '') {
                $syncData[$serviceId] = ['commission_pct' => (float) $pct];
            }
        }

        $professional->services()->sync($syncData);
    }
}
