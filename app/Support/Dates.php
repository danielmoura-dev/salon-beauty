<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Datas vindas da query string (?date=, ?month=) são texto livre do usuário:
 * Carbon::parse() lança exceção com lixo e a página vira um erro 500.
 */
class Dates
{
    public static function parse(?string $value, ?Carbon $default = null): Carbon
    {
        $default ??= Carbon::today();

        if (! $value) {
            return $default;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $default;
        }
    }

    /** Aceita "YYYY-MM"; qualquer outra coisa cai no mês atual. */
    public static function month(?string $value): Carbon
    {
        if ($value && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value . '-01')->startOfMonth();
        }

        return Carbon::now()->startOfMonth();
    }
}
