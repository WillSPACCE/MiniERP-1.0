<?php
declare(strict_types=1);

namespace MiniErp\Platform;

use RuntimeException;
use XMLReader;
use ZipArchive;

final class TabularImportReader
{
    public function __construct(private int $maxRows=20000,private int $maxBytes=10485760){}

    public function read(string $path,string $originalName):array
    {
        if(!is_file($path)||filesize($path)>$this->maxBytes)throw new RuntimeException('Arquivo ausente ou maior que 10 MB.');
        $extension=strtolower(pathinfo($originalName,PATHINFO_EXTENSION));
        if($extension==='xls')throw new RuntimeException('O formato .xls antigo não é aceito. Salve a planilha como .xlsx ou CSV.');
        $matrix=$extension==='xlsx'?$this->xlsx($path):($extension==='csv'||$extension==='txt'?$this->csv($path):throw new RuntimeException('Envie um arquivo CSV, TXT ou XLSX.'));
        while($matrix&&$this->blank(end($matrix)))array_pop($matrix);
        if(count($matrix)<2)throw new RuntimeException('A planilha precisa ter cabeçalho e ao menos uma linha de dados.');
        $rawHeaders=array_shift($matrix);$headers=[];$seen=[];
        foreach($rawHeaders as$index=>$header){$name=$this->header((string)$header);if($name==='')$name='coluna_'.($index+1);if(isset($seen[$name]))throw new RuntimeException("Cabeçalho repetido: {$name}.");$seen[$name]=true;$headers[]=$name;}
        $rows=[];
        foreach($matrix as$i=>$values){if($this->blank($values))continue;if(count($rows)>=$this->maxRows)throw new RuntimeException("A planilha excede o limite de {$this->maxRows} linhas.");$row=[];foreach($headers as$j=>$header)$row[$header]=trim((string)($values[$j]??''));$rows[]=['line'=>$i+2,'data'=>$row];}
        if(!$rows)throw new RuntimeException('Nenhuma linha preenchida foi encontrada.');
        return['headers'=>$headers,'rows'=>$rows];
    }

    private function csv(string$path):array
    {
        $handle=fopen($path,'rb');if(!$handle)throw new RuntimeException('Não foi possível ler o CSV.');
        $first=(string)fgets($handle);$first=preg_replace('/^\xEF\xBB\xBF/','',$first)??$first;
        if(preg_match('/^sep=(.)\s*$/i',trim($first),$match)){$delimiter=$match[1];}else{$delimiter=$this->delimiter($first);rewind($handle);}
        $rows=[];while(($row=fgetcsv($handle,0,$delimiter,'"','\\'))!==false){$rows[]=array_map(fn($v)=>$this->utf8((string)$v),$row);if(count($rows)>$this->maxRows+1)break;}fclose($handle);return$rows;
    }

    private function xlsx(string$path):array
    {
        if(!class_exists(ZipArchive::class)||!class_exists(XMLReader::class))throw new RuntimeException('Este servidor não possui suporte para XLSX. Use CSV.');
        $zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('Arquivo XLSX inválido ou corrompido.');
        try{$shared=$this->sharedStrings($zip);$sheet=$this->firstSheetPath($zip);$xml=$zip->getFromName($sheet);if($xml===false)throw new RuntimeException('A primeira guia do XLSX não pôde ser lida.');return$this->sheetRows($xml,$shared);}finally{$zip->close();}
    }

    private function sharedStrings(ZipArchive$zip):array
    {
        $xml=$zip->getFromName('xl/sharedStrings.xml');if($xml===false)return[];$doc=simplexml_load_string($xml);if($doc===false)return[];$out=[];
        foreach($doc->si as$si){$text=(string)$si->t;if($text==='')foreach($si->r as$run)$text.=(string)$run->t;$out[]=$text;}return$out;
    }

    private function firstSheetPath(ZipArchive$zip):string
    {
        $workbook=$zip->getFromName('xl/workbook.xml');$rels=$zip->getFromName('xl/_rels/workbook.xml.rels');if($workbook===false||$rels===false)return'xl/worksheets/sheet1.xml';
        $book=simplexml_load_string($workbook);$relationships=simplexml_load_string($rels);if($book===false||$relationships===false)return'xl/worksheets/sheet1.xml';
        $book->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');$book->registerXPathNamespace('r','http://schemas.openxmlformats.org/officeDocument/2006/relationships');$sheets=$book->xpath('//m:sheets/m:sheet');if(!$sheets)return'xl/worksheets/sheet1.xml';$attrs=$sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');$id=(string)$attrs['id'];
        foreach($relationships->Relationship as$rel)if((string)$rel['Id']===$id){$target=ltrim((string)$rel['Target'],'/');return str_starts_with($target,'xl/')?$target:'xl/'.$target;}return'xl/worksheets/sheet1.xml';
    }

    private function sheetRows(string$xml,array$shared):array
    {
        $reader=new XMLReader();$reader->XML($xml,null,LIBXML_NONET|LIBXML_COMPACT);$rows=[];
        while($reader->read()){if($reader->nodeType!==XMLReader::ELEMENT||$reader->localName!=='row')continue;$node=simplexml_load_string($reader->readOuterXML());if($node===false)continue;$values=[];foreach($node->c as$cell){$ref=(string)$cell['r'];preg_match('/^[A-Z]+/i',$ref,$m);$column=$this->columnIndex($m[0]??'A');$type=(string)$cell['t'];$value=$type==='inlineStr'?(string)$cell->is->t:(string)$cell->v;if($type==='s')$value=(string)($shared[(int)$value]??'');$values[$column]=$value;}if($values){$max=max(array_keys($values));$dense=[];for($i=0;$i<=$max;$i++)$dense[]=$values[$i]??'';$rows[]=$dense;}if(count($rows)>$this->maxRows+1)break;}$reader->close();return$rows;
    }

    private function columnIndex(string$letters):int{$number=0;foreach(str_split(strtoupper($letters))as$letter)$number=$number*26+(ord($letter)-64);return max(0,$number-1);}
    private function delimiter(string$line):string{$scores=[];foreach([';',',',"\t"]as$d)$scores[$d]=substr_count($line,$d);arsort($scores);return(string)array_key_first($scores);}
    private function header(string$value):string{$value=$this->utf8($value);$value=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value;$value=strtolower(trim($value));return trim((string)preg_replace('/[^a-z0-9]+/','_',$value),'_');}
    private function utf8(string$value):string{return mb_check_encoding($value,'UTF-8')?$value:(mb_convert_encoding($value,'UTF-8','Windows-1252,ISO-8859-1'));}
    private function blank(array$row):bool{foreach($row as$value)if(trim((string)$value)!=='')return false;return true;}
}
