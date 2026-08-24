<?php
declare(strict_types=1);
namespace MiniErp\Contracts;
interface SecretStorageContract { public function put(string $scope,string $secret):string; public function get(string $reference):string; public function delete(string $reference):void; }
