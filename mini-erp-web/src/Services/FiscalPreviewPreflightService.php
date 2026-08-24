<?php
declare(strict_types=1);
namespace MiniErp\Services;

final class FiscalPreviewPreflightService
{
    public function assertCommercial(array$order,array$issuer,array$recipient):void
    {
        if(!$issuer)throw new FiscalDanfeException('PREVIEW_ISSUER_MISSING','Cadastre o estabelecimento emitente para gerar a prévia.');
        if(!$recipient)throw new FiscalDanfeException('PREVIEW_RECIPIENT_MISSING','Cadastre o destinatário para gerar a prévia.');
        if(empty($order['items']))throw new FiscalDanfeException('PREVIEW_ITEMS_MISSING','Adicione produtos ao pedido antes de gerar a prévia fiscal.');
        foreach($order['items']as$position=>$item){if(empty($item['id'])||bccomp((string)($item['quantity']??'0'),'0',4)<=0||bccomp((string)($item['unit_price']??'-1'),'0',4)<0)throw new FiscalDanfeException('PREVIEW_ITEM_INVALID','O item '.($position+1).' não possui quantidade e valor comercial válidos.');}
    }

    public function warning(string$code,string$message,array$context=[]):array{return['severity'=>'WARNING','code'=>$code,'message'=>$message,'context'=>$context];}

    /** Estrutura exclusivamente técnica para o XML efêmero do preview. */
    public function technicalTax(string$cfop,string$origin):array
    {
        return['preview_only'=>true,'resolved'=>false,'cfop'=>$cfop,'icms'=>['cst'=>'90','orig'=>$origin?:'0','modBC'=>'3','base'=>'0.00','rate'=>'0.00','amount'=>'0.00','preview_only'=>true],'ipi'=>[],'pis'=>['cst'=>'49','base'=>'0.00','rate'=>'0.00','amount'=>'0.00','preview_only'=>true],'cofins'=>['cst'=>'49','base'=>'0.00','rate'=>'0.00','amount'=>'0.00','preview_only'=>true]];
    }

    public function warningText(array$warnings):string
    {
        if(!$warnings)return'';$codes=implode(', ',array_values(array_unique(array_column($warnings,'code'))));
        return' TRIBUTAÇÃO PENDENTE: Este documento não representa cálculo fiscal definitivo. A emissão da NF-e permanece bloqueada até a conclusão da configuração tributária. Pendências: '.$codes.'. Valores tributários zerados significam NÃO CALCULADO PARA PREVIEW.';
    }
}
