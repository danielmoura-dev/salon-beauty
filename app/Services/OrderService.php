<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Regras de comanda: itens, estoque, pagamentos e saldo do cliente.
 * Toda operação roda em transação: ou tudo é gravado, ou nada.
 */
class OrderService
{
    public function addItem(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order = $this->lock($order);
            $this->assertNotCancelled($order);

            $order->items()->create($data);
            $this->adjustStock($data['product_id'] ?? null, -(int) $data['qty']);
            $order->recalcTotal();

            return $this->fresh($order);
        });
    }

    public function updateItem(Order $order, OrderItem $item, array $data): Order
    {
        return DB::transaction(function () use ($order, $item, $data) {
            $order = $this->lock($order);
            $this->assertNotCancelled($order);

            // Item sem product_id no payload deixa de ser um item de produto (estoque volta e não é baixado de novo)
            $data['product_id'] = $data['product_id'] ?? null;

            $this->adjustStock($item->product_id, (int) $item->qty);   // devolve a quantidade antiga
            $item->update($data);
            $this->adjustStock($data['product_id'], -(int) $data['qty']); // baixa a quantidade nova

            $order->recalcTotal();

            return $this->fresh($order);
        });
    }

    public function removeItem(Order $order, OrderItem $item): Order
    {
        return DB::transaction(function () use ($order, $item) {
            $order = $this->lock($order);

            $this->adjustStock($item->product_id, (int) $item->qty);
            $item->delete();
            $order->recalcTotal();

            return $this->fresh($order);
        });
    }

    public function addPayment(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            // Trava a comanda: dois pagamentos simultâneos não podem calcular o excesso sobre o mesmo estado
            $order = $this->lock($order);
            $this->assertNotCancelled($order);

            $order->load('payments');
            $excessBefore = max(0.0, $order->balance());

            $order->payments()->create($data);
            $order->load('payments');

            // Fiado e crédito consomem o saldo do cliente pelo valor pago
            if (in_array($data['method'], ['debt', 'credit'], true)) {
                $order->client()->first()?->decrement('balance', (float) $data['amount']);
            }

            // Troco/excesso (já descontada a taxa da maquininha) vira crédito — só a parte nova
            $excessAfter = max(0.0, $order->balance());
            if ($excessAfter > $excessBefore) {
                $order->client()->first()?->increment('balance', round($excessAfter - $excessBefore, 2));
            }

            if ($order->isPaid()) {
                $order->update(['status' => 'closed']);
            }

            return $this->fresh($order);
        });
    }

    public function close(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->lock($order);
            $order->update(['status' => 'closed']);

            $order->appointment?->update(['status' => 'completed']);

            return $this->fresh($order);
        });
    }

    public function reopen(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->lock($order);
            $order->update(['status' => 'open']);

            return $this->fresh($order);
        });
    }

    public function clearPayments(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->lock($order);

            $this->reverseClientBalance($order);
            $order->payments()->delete();
            $order->update(['status' => 'open']);

            return $this->fresh($order);
        });
    }

    /** Exclui a comanda devolvendo estoque e revertendo o saldo do cliente. */
    public function delete(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = $this->lock($order);
            $order->load('items');

            foreach ($order->items as $item) {
                $this->adjustStock($item->product_id, (int) $item->qty);
            }

            $this->reverseClientBalance($order);
            $order->delete();
        });
    }

    // ------------------------------------------------------------------

    private function lock(Order $order): Order
    {
        return Order::query()->lockForUpdate()->findOrFail($order->getKey());
    }

    private function fresh(Order $order): Order
    {
        return $order->fresh(['client', 'items.professional', 'payments', 'appointment']);
    }

    private function assertNotCancelled(Order $order): void
    {
        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'order' => 'Comanda cancelada não aceita itens nem pagamentos.',
            ]);
        }
    }

    private function adjustStock(?string $productId, int $delta): void
    {
        if (! $productId || $delta === 0) return;

        $product = Product::query()->lockForUpdate()->find($productId);

        if ($product && $product->track_stock && $product->stock_qty !== null) {
            $product->increment('stock_qty', $delta);
        }
    }

    /**
     * Desfaz o efeito líquido dos pagamentos no saldo do cliente.
     * fiado/crédito reduziram o saldo; o excesso pago a mais foi somado como crédito.
     */
    private function reverseClientBalance(Order $order): void
    {
        $payments  = $order->payments()->get();
        $sumDebt   = (float) $payments->where('method', 'debt')->sum(fn ($p) => $p->effectiveAmount());
        $sumCredit = (float) $payments->where('method', 'credit')->sum(fn ($p) => $p->effectiveAmount());
        $totalPaid = (float) $payments->sum(fn ($p) => $p->effectiveAmount());

        $excess   = max(0.0, round($totalPaid - (float) $order->total, 2));
        $reversal = round($sumDebt + $sumCredit - $excess, 2);

        if (abs($reversal) > 0.001) {
            $order->client()->first()?->increment('balance', $reversal);
        }
    }
}
