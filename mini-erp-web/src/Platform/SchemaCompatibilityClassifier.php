<?php
declare(strict_types=1);
namespace MiniErp\Platform;
final class SchemaCompatibilityClassifier
{
 public function classify(array $comparison):array{$classified=[];$blocking=0;$legacy=0;foreach($comparison['differences'] as $d){$kind=$d['kind'];$category=match($kind){'MISSING_TABLE','MISSING_COLUMN'=>'REQUIRED_MISSING','MISSING_INDEX','EXTRA_INDEX'=>'INDEX_DRIFT','MISSING_FK','EXTRA_FK'=>'CONSTRAINT_DRIFT','COLUMN_PROPERTY'=>'TYPE_DRIFT', 'EXTRA_TABLE','EXTRA_COLUMN'=>'LEGACY_EXTRA',default=>'MANUAL_REVIEW'};$severity=match($category){'LEGACY_EXTRA'=>'INFO','INDEX_DRIFT'=>'WARNING',default=>'ERROR'};$action=$category==='LEGACY_EXTRA'?'PRESERVE':($category==='REQUIRED_MISSING'?'APPLY_OFFICIAL_MIGRATION':'REVIEW');$classified[]=$d+['category'=>$category,'severity'=>$severity,'action'=>$action];if($category==='LEGACY_EXTRA')$legacy++;else$blocking++;}$status=$blocking>0?'DRIFTED':($legacy>0?'CURRENT_WITH_LEGACY':'CURRENT');return ['status'=>$status,'blocking_count'=>$blocking,'legacy_count'=>$legacy,'differences'=>$classified];}
}
