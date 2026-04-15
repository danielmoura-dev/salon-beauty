<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\ProfessionalVoucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $professionals = Professional::orderBy('name')
            ->with(['vouchers' => fn($q) => $q->orderByDesc('issued_at')])
            ->get();

        return view('app.professionals.vouchers', compact('professionals'));
    }

    public function store(Request $request, Professional $professional)
    {
        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'issued_at'   => ['required', 'date'],
        ]);

        ProfessionalVoucher::create([
            'tenant_id'       => auth()->user()->tenant_id,
            'professional_id' => $professional->id,
            'amount'          => $data['amount'],
            'description'     => $data['description'],
            'issued_at'       => $data['issued_at'],
        ]);

        return back()->with('success', "Vale emitido para {$professional->name}!");
    }

    public function destroy(ProfessionalVoucher $voucher)
    {
        if ($voucher->commission_payment_id) {
            return back()->withErrors(['voucher' => 'Vale já descontado não pode ser removido.']);
        }

        $voucher->delete();

        return back()->with('success', 'Vale removido.');
    }
}
