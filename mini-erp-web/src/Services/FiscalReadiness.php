<?php

declare(strict_types=1);

namespace MiniErp\Services;

require_once __DIR__ . '/EstablishmentFiscalCompleteness.php';

final class FiscalReadiness
{
    public function evaluate(?array $establishment, ?array $certificate = null, array $series = [], array $companySettings = []): array
    {
        $required = ['tax_id','legal_name','state_registration','tax_regime_code','street','number','district','city_ibge_code','city_name','state','postal_code'];
        $items = [];
        foreach ($required as $field) $items[$field] = trim((string) ($establishment[$field] ?? '')) !== '';
        $items['certificate_configured'] = $certificate !== null;
        $items['certificate_valid'] = in_array($certificate['status'] ?? '', ['VALID','EXPIRING_SOON'], true);
        $items['certificate_identity_matches'] = $certificate !== null && strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)($certificate['tax_id']??'')) ?? '') === strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)($establishment['tax_id']??'')) ?? '');
        $items['certificate_not_expired'] = $certificate !== null && strtotime((string)($certificate['valid_until']??'')) > time();
        $items['certificate_ready'] = $items['certificate_configured'] && $items['certificate_valid'] && $items['certificate_identity_matches'] && $items['certificate_not_expired'];
        $items['certificate_a1'] = $items['certificate_ready'];
        $items['fiscal_environment'] = count(array_filter($series,static fn(array $s):bool=>(int)($s['environment']??0)===2&&(int)($s['active']??0)===1))>0;
        $items['document_series'] = $items['fiscal_environment'];
        $items['nfe_series_configured'] = count(array_filter($series,static fn(array $s):bool=>(string)($s['model']??'')==='55'&&(int)($s['environment']??0)===2&&(int)($s['active']??0)===1))>0;
        $items['nfce_series_configured'] = count(array_filter($series,static fn(array $s):bool=>(string)($s['model']??'')==='65'&&(int)($s['environment']??0)===2&&(int)($s['active']??0)===1))>0;
        $items['cfop_defaults_configured'] = count($companySettings['cfops'] ?? []) === 4;
        $items['icms_defaults_configured'] = count($companySettings['icms'] ?? []) > 0;
        $legacy = $companySettings['legacy'] ?? [];
        $items['pis_cofins_defaults_configured'] = !empty($legacy['pis_output_cst']) && !empty($legacy['cofins_output_cst']);
        $items['ipi_defaults_configured'] = in_array($legacy['ipi_applicability'] ?? '', ['APPLICABLE','NOT_APPLICABLE'], true);
        $items['rtc_defaults_configured'] = count($companySettings['rtc'] ?? []) > 0;
        $items['csc_homologation_configured'] = !empty($companySettings['csc']['2']['secret_reference']) || !empty($companySettings['csc'][2]['secret_reference']);
        $items['csc_production_configured'] = !empty($companySettings['csc']['1']['secret_reference']) || !empty($companySettings['csc'][1]['secret_reference']);
        $items['nfce_ready'] = $items['certificate_ready'] && $items['nfce_series_configured'] && $items['csc_homologation_configured'];
        $groups = (new EstablishmentFiscalCompleteness())->evaluate($establishment);
        $ready=$items['certificate_ready']&&$items['document_series'];
        return ['status' => $ready ? 'TECHNICALLY_READY_OFFLINE' : 'INCOMPLETE', 'certificate_ready'=>$items['certificate_ready'], 'homologation_ready'=>false, 'production_ready'=>false, 'items' => $items, 'groups' => $groups, 'complete_count' => count(array_filter($items)), 'total_count' => count($items)];
    }
}
