<?php

declare(strict_types=1);

namespace MiniErp\Fiscal;

final class FiscalNumericCodeGenerator
{
    public function generate(): string
    {
        return str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
    }
}
