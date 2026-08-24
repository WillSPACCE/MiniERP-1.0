<?php
declare(strict_types=1);

namespace MiniErp\Contracts;
use MiniErp\Services\CnpjLookupResult;

interface CnpjLookupProviderContract
{
    public function lookup(string $cnpj): ?CnpjLookupResult;
}
