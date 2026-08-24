<?php
declare(strict_types=1);
namespace MiniErp\Contracts;
interface SefazStatusClientContract{public function status(string$configJson,object$certificate,string$uf,int$timeoutSeconds):string;}
