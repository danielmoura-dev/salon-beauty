<?php

namespace App\Http\Controllers;

use App\Rules\TenantExists;
use App\Models\Category;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month
            ? Carbon::parse($request->month . '-01')
            : Carbon::now()->startOfMonth();

        $expenses = Expense::with('category')
            ->whereYear('due_date', $month->year)
            ->whereMonth('due_date', $month->month)
            ->orderBy('due_date')
            ->get();

        $categories = Category::where('type', 'expense')->orderBy('name')->get();

        $summary = [
            'total'    => $expenses->sum('amount'),
            'paid'     => $expenses->where('is_paid', true)->sum('amount'),
            'pending'  => $expenses->where('is_paid', false)->sum('amount'),
        ];

        return view('app.expenses.index', compact('expenses', 'categories', 'month', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description'   => ['required', 'string', 'max:200'],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'category_id'   => ['nullable', 'uuid', new TenantExists('categories')],
            'payment_type'  => ['required', 'in:one_time,installment,recurring'],
            'installments'  => ['nullable', 'integer', 'min:2', 'max:60'],
            'months'        => ['nullable', 'integer', 'min:2', 'max:120'],
            'due_date'      => ['required', 'date'],
            'is_paid'       => ['boolean'],
            'notes'         => ['nullable', 'string'],
        ]);

        $data['is_paid'] = $request->boolean('is_paid');
        $data['paid_at'] = $data['is_paid'] ? now() : null;

        if ($data['payment_type'] === 'installment' && ($data['installments'] ?? 0) > 1) {
            $this->createInstallments($data);
        } elseif ($data['payment_type'] === 'recurring') {
            $this->createRecurring($data, (int) ($request->input('months', 12)));
        } else {
            Expense::create($data);
        }

        return back()->with('success', 'Despesa lançada!');
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'description'  => ['required', 'string', 'max:200'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'category_id'  => ['nullable', 'uuid', new TenantExists('categories')],
            'due_date'     => ['required', 'date'],
            'is_paid'      => ['boolean'],
            'notes'        => ['nullable', 'string'],
        ]);

        $data['is_paid'] = $request->boolean('is_paid');
        if ($data['is_paid'] && ! $expense->is_paid) {
            $data['paid_at'] = now();
        }

        $expense->update($data);

        return back()->with('success', 'Despesa atualizada!');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Despesa removida.');
    }

    public function cancelRecurrence(Expense $expense)
    {
        if ($expense->recurrence_group_id) {
            Expense::where('recurrence_group_id', $expense->recurrence_group_id)
                ->where('is_paid', false)
                ->where('due_date', '>=', $expense->due_date)
                ->delete();
        }

        return response()->json(['ok' => true]);
    }

    public function togglePaid(Expense $expense)
    {
        $expense->update([
            'is_paid' => ! $expense->is_paid,
            'paid_at' => ! $expense->is_paid ? now() : null,
        ]);

        return back()->with('success', $expense->is_paid ? 'Marcada como paga.' : 'Marcada como pendente.');
    }

    private function createRecurring(array $data, int $months = 12): void
    {
        $groupId  = Str::uuid();
        $baseDate = Carbon::parse($data['due_date']);

        for ($i = 0; $i < $months; $i++) {
            Expense::create([
                ...$data,
                'recurrence_group_id' => $groupId,
                'due_date'            => $baseDate->copy()->addMonths($i)->toDateString(),
                'is_paid'             => $i === 0 ? $data['is_paid'] : false,
                'paid_at'             => $i === 0 ? $data['paid_at'] : null,
            ]);
        }
    }

    private function createInstallments(array $data): void
    {
        $groupId = Str::uuid();
        $baseDate = Carbon::parse($data['due_date']);
        $installmentAmount = round($data['amount'] / $data['installments'], 2);

        for ($i = 1; $i <= $data['installments']; $i++) {
            Expense::create([
                ...$data,
                'amount'               => $installmentAmount,
                'current_installment'  => $i,
                'recurrence_group_id'  => $groupId,
                'due_date'             => $baseDate->copy()->addMonths($i - 1)->toDateString(),
                'is_paid'              => false,
                'paid_at'              => null,
                'description'          => $data['description'] . " ({$i}/{$data['installments']})",
            ]);
        }
    }
}