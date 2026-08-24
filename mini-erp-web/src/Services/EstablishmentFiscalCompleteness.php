<?php
declare(strict_types=1);
namespace MiniErp\Services;
final class EstablishmentFiscalCompleteness
{
    public function evaluate(?array $row): array
    {
        $has=static fn(string $field):bool=>trim((string)($row[$field]??''))!=='';
        $registration=$has('tax_id')&&$has('legal_name');
        $taxIdentity=$has('state_registration')&&in_array((string)($row['tax_regime_code']??''),EstablishmentData::CRT,true);
        $address=$has('street')&&$has('number')&&$has('district')&&$has('city_ibge_code')&&$has('city_name')&&$has('state')&&$has('postal_code')&&$has('country_code')&&$has('country_name');
        $contact=$has('phone')||$has('email');
        return ['registration_complete'=>$registration,'tax_identity_complete'=>$taxIdentity,'address_complete'=>$address,'contact_complete'=>$contact,'emit_ready'=>$registration&&$taxIdentity&&$address,'certificate_ready'=>false,'nfe_configuration_ready'=>false,'nfce_configuration_ready'=>false,'nfe_ready'=>false,'nfce_ready'=>false,'homologation_ready'=>false,'production_ready'=>false];
    }
}
