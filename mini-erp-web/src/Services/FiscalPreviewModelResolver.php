<?php
declare(strict_types=1);
namespace MiniErp\Services;
final class FiscalPreviewModelResolver
{
    /** Precedência: modelo explícito da operação, modelo principal do estabelecimento, erro. */
    public function resolve(?string$operationModel,?string$companyModel):string
    {
        return$this->resolveWithSource($operationModel,$companyModel)['model'];
    }
    public function resolveWithSource(?string$operationModel,?string$companyModel):array{$operationModel=trim((string)$operationModel);if($operationModel!=='')return['model'=>$this->supported($operationModel),'source'=>'EXPLICIT'];$companyModel=trim((string)$companyModel);if($companyModel!=='')return['model'=>$this->supported($companyModel),'source'=>'ESTABLISHMENT_DEFAULT'];throw new FiscalDanfeException('MODEL_NOT_SELECTED','Selecione o modelo fiscal 55 ou 65.');}
    private function supported(string$model):string{if(!in_array($model,['55','65'],true))throw new FiscalDanfeException('FISCAL_DOCUMENT_MODEL_UNSUPPORTED','Modelo fiscal não suportado para prévia.');return$model;}
}
