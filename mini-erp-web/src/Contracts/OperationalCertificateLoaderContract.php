<?php
declare(strict_types=1);
namespace MiniErp\Contracts;
interface OperationalCertificateLoaderContract{public function load(int$establishmentId,string$expectedTaxId):array;}
