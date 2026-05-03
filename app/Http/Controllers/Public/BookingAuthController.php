<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Http\Request;

class BookingAuthController extends Controller
{
    // Verifica se o WhatsApp já é cliente do salão
    public function check(string $slug, Request $request)
    {
        $tenant = $this->resolveTenant($slug);

        $request->validate(['phone' => ['required', 'string', 'max:20']]);

        $phone  = $this->normalizePhone($request->phone);
        $client = $this->findClient($tenant, $phone);

        if ($client) {
            session()->put("booking_client.{$tenant->id}", $client->id);
            return response()->json([
                'exists' => true,
                'name'   => $client->name,
            ]);
        }

        return response()->json(['exists' => false]);
    }

    // Cria cliente novo a partir do nome + WhatsApp
    public function register(string $slug, Request $request)
    {
        $tenant = $this->resolveTenant($slug);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $this->normalizePhone($data['phone']);

        // Se já existe (corrida), apenas vincula
        $existing = $this->findClient($tenant, $phone);
        if ($existing) {
            session()->put("booking_client.{$tenant->id}", $existing->id);
            return response()->json(['ok' => true, 'name' => $existing->name]);
        }

        $client = new Client([
            'name'  => $data['name'],
            'phone' => $phone,
        ]);
        $client->tenant_id = $tenant->id;
        $client->save();

        session()->put("booking_client.{$tenant->id}", $client->id);

        return response()->json(['ok' => true, 'name' => $client->name]);
    }

    public function logout(string $slug)
    {
        $tenant = $this->resolveTenant($slug);
        session()->forget("booking_client.{$tenant->id}");
        return redirect()->route('public.booking.show', $slug);
    }

    protected function resolveTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('booking_slug', $slug)
            ->where('booking_active', true)
            ->first();

        abort_if(! $tenant, 404, 'Página não encontrada.');

        return $tenant;
    }

    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }

    protected function findClient(Tenant $tenant, string $phone): ?Client
    {
        if ($phone === '') return null;

        return Client::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') = ?", [$phone])
            ->first();
    }
}
