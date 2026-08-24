<?php
declare(strict_types=1);
namespace MiniErp\Services;
final readonly class SefazStatusResult{
 public function __construct(public bool$success,public int$environment,public string$uf,public string$service,public ?string$cStat,public ?string$xMotivo,public ?string$dhRecbto,public string$rawReceivedAt,public int$latencyMs,public string$endpointIdentifier,public ?string$errorCode=null){}
 public function toArray():array{return['success'=>$this->success,'environment'=>$this->environment,'uf'=>$this->uf,'service'=>$this->service,'cStat'=>$this->cStat,'xMotivo'=>$this->xMotivo,'dhRecbto'=>$this->dhRecbto,'raw_received_at'=>$this->rawReceivedAt,'latency_ms'=>$this->latencyMs,'endpoint_identifier'=>$this->endpointIdentifier,'error_code'=>$this->errorCode];}
}
