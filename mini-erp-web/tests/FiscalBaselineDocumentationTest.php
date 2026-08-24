<?php
declare(strict_types=1);
function fiscalAssert(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"ASSERTION FAILED: {$message}\n");exit(1);}}
$root=__DIR__.'/..';
$files=['docs/fiscal/NFE-XML-DATA-MAP.md','docs/fiscal/NFEPHP-INTEGRATION.md','docs/fiscal/FISCAL-ARCHITECTURE.md','docs/manual-tests/FISCAL-00.md','.specify/specs/FISCAL-00-fiscal-first.md'];
foreach($files as $file)fiscalAssert(is_file($root.'/'.$file)&&filesize($root.'/'.$file)>200,"required fiscal document must exist: {$file}");
$map=file_get_contents($root.'/docs/fiscal/NFE-XML-DATA-MAP.md');$architecture=file_get_contents($root.'/docs/fiscal/FISCAL-ARCHITECTURE.md');$integration=file_get_contents($root.'/docs/fiscal/NFEPHP-INTEGRATION.md');$roadmap=file_get_contents($root.'/docs/roadmap-projeto.md');
foreach(['infNFe','ide','emit','enderEmit','dest','enderDest','det/prod','imposto','ICMS','IPI','PIS','COFINS','IBSCBS','total','transp','cobr','pag','infAdic'] as $group)fiscalAssert(str_contains($map,'| '.$group.' |'),"XML map must include {$group}");
preg_match_all('/^\| (?:infNFe|ide|emit|enderEmit|dest|enderDest|det\/prod|imposto|ICMS|IPI|PIS|COFINS|IBSCBS|IS|total|transp|cobr|pag|infAdic) \|/m',$map,$mapped);fiscalAssert(count($mapped[0])>=90,'XML map must contain a useful field-level baseline');
foreach(['REQUIRED_ALWAYS','REQUIRED_WHEN','OPTIONAL','DERIVED','NOT_APPLICABLE'] as $classification)fiscalAssert(str_contains($architecture,$classification)||str_contains($map,$classification),"obligation model must include {$classification}");
foreach(['NT 2025.002 RTC','NT 2026.004','CNPJ alfanumérico','MOC NF-e/NFC-e','Schemas XML oficiais'] as $source)fiscalAssert(str_contains($architecture,$source),"official baseline must include {$source}");
fiscalAssert(str_contains($integration,'ADOPT_WITH_CONDITIONS')&&str_contains($integration,'PHP CLI: 8.2.12')&&str_contains($integration,'Composer'),'NFePHP decision and local environment must be recorded');
foreach(range(0,16) as $number)fiscalAssert(str_contains($roadmap,sprintf('FISCAL-%02d',$number)),"roadmap must include FISCAL task {$number}");
foreach(['BEGIN PRIVATE KEY','PRIVATE KEY-----','senha real','CSC real'] as $secretPattern)fiscalAssert(!str_contains($architecture,$secretPattern)&&!str_contains($map,$secretPattern),'documents must contain no real secret material');
echo 'FiscalBaselineDocumentation OK mapped_tags='.count($mapped[0])."\n";
