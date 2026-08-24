<?php
declare(strict_types=1);namespace MiniErp\Services;final class FiscalDanfeException extends \RuntimeException{public function __construct(public readonly string$errorCode,string$message,?\Throwable$previous=null){parent::__construct($message,0,$previous);}}
