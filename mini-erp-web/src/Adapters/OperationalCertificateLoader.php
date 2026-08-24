<?php
declare(strict_types=1);
namespace MiniErp\Adapters;
use MiniErp\Contracts\OperationalCertificateLoaderContract;use MiniErp\Fiscal\OperationalCertificateProvider;
final readonly class OperationalCertificateLoader implements OperationalCertificateLoaderContract{public function __construct(private OperationalCertificateProvider$provider){}public function load(int$establishmentId,string$expectedTaxId):array{return$this->provider->resolveOperationalCertificate($establishmentId,$expectedTaxId);}}
