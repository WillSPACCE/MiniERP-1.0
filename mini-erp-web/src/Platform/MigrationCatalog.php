<?php
declare(strict_types=1);
namespace MiniErp\Platform;
use RuntimeException;

final class MigrationCatalog
{
    public function __construct(private string $directory) {}
    public function all(): array
    {
        $main=['20260814_add_company_fields.sql','20260814_add_company_municipio_regime.sql','20260814_add_tenant_id.sql','20260820_add_tenant_schema_version.sql','20260822_create_multitenant_operations.sql','20260822_create_platform_admin_auth.sql','20260829_create_platform_server_settings.sql','20260829_create_tenant_registration_flow.sql','20260902_create_platform_fiscal_catalog.sql'];
        $dependencies=[
            '20260821_close_fiscal_certificate_series_runtime.sql'=>['20260821_create_fiscal_credentials_and_series_audit.sql'],
            '20260821_create_fiscal_credentials_and_series_audit.sql'=>['20260821_create_tenant_establishments.sql'],
            '20260821_create_fiscal_xml_pipeline.sql'=>['20260821_create_fiscal_operations.sql'],
            '20260821_create_fiscal_operations.sql'=>['20260821_extend_produtos_as_fiscal_products.sql'],
            '20260822_reconcile_legacy_v1_types.sql'=>['20260821_extend_clientes_as_fiscal_people.sql'],
        ];
        $manual=['20260821_close_fiscal_certificate_series_runtime.sql','20260822_reconcile_legacy_v1_types.sql'];$out=[];
        foreach(glob(rtrim($this->directory,'/\\').'/*.sql')?:[] as $path){
            $id=basename($path);$sql=(string)file_get_contents($path);preg_match('/^\s*--\s*(.+)$/m',$sql,$desc);preg_match_all('/\b(?:TABLE(?:\s+IF\s+(?:NOT\s+)?EXISTS)?|INTO|FROM)\s+`?([a-z_][a-z0-9_]*)`?/i',$sql,$tables);
            $hasAlter=(bool)preg_match('/\bALTER\s+TABLE\b/i',$sql);$safeAdditiveAlter=$hasAlter&&(bool)preg_match('/\bADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\b/i',$sql)&&!preg_match('/\b(DROP|MODIFY|CHANGE|RENAME)\b/i',$sql);$risk=in_array($id,$manual,true)?'MANUAL_REVIEW':(preg_match('/\b(DROP\s+(?:TABLE|COLUMN|DATABASE)|DELETE\s+FROM|TRUNCATE)\b/i',$sql)?'DESTRUCTIVE':($hasAlter&&!$safeAdditiveAlter?'STRUCTURAL':'SAFE_ADDITIVE'));
            $out[$id]=['migration_id'=>$id,'filename'=>$id,'path'=>realpath($path),'checksum'=>hash_file('sha256',$path),'target'=>in_array($id,$main,true)?'MAIN':'TENANT','risk'=>$risk,'description'=>$desc[1]??'Migration oficial','dependencies'=>$dependencies[$id]??[],'tables_affected'=>array_values(array_unique($tables[1]??[])),'requires_backup'=>true,'transaction_mode'=>preg_match('/\b(?:CREATE|ALTER|DROP)\b/i',$sql)?'NON_TRANSACTIONAL':'TRANSACTIONAL','schema_version_from'=>null,'schema_version_to'=>null,'approved'=>$risk==='SAFE_ADDITIVE'||$id==='99999999_test_only_multitenant_probe.sql','test_only'=>$id==='99999999_test_only_multitenant_probe.sql','expected_table'=>$id==='99999999_test_only_multitenant_probe.sql'?'platform02b_test_probe':null];
        }
        ksort($out);return$out;
    }
    public function get(string $id):array{$all=$this->all();if(!isset($all[$id]))throw new RuntimeException('Migration fora do catálogo.');return$all[$id];}
}
