<?php

declare(strict_types=1);

namespace MiniErp\Fiscal;

use RuntimeException;

final readonly class FiscalArtifactStorage
{
    public function __construct(private string $root)
    {
        if (str_contains(str_replace('\\', '/', realpath($root) ?: $root), '/public/')) throw new RuntimeException('Storage fiscal não pode ficar em public/.');
    }

    public function storeUnsignedXml(int $tenantId, int $establishmentId, int $documentId, string $xml): array
    {
        if (!str_starts_with(ltrim($xml), '<?xml') || str_contains($xml, '<protNFe')) throw new RuntimeException('XML ausente ou contém protocolo não permitido.');
        $relative = "tenant-{$tenantId}/establishment-{$establishmentId}/document-{$documentId}/generated/nfe-unsigned.xml";
        $target = rtrim($this->root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Não foi possível criar storage fiscal.');
        if (file_put_contents($target, $xml, LOCK_EX) === false) throw new RuntimeException('Não foi possível armazenar XML.');
        return ['storage_reference' => $relative, 'sha256' => hash_file('sha256', $target), 'size' => filesize($target), 'status' => 'GENERATED_UNSIGNED'];
    }

    public function storeSignedXml(int $tenantId, int $establishmentId, int $documentId, string $xml, string $artifactType = 'NFE'): array
    {
        if (!str_starts_with(ltrim($xml), '<?xml')) throw new RuntimeException('XML assinado ausente.');
        $relative = "tenant-{$tenantId}/establishment-{$establishmentId}/document-{$documentId}/generated/" . strtolower($artifactType) . '-signed.xml';
        $target = rtrim($this->root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Não foi possível criar storage fiscal.');
        if (file_put_contents($target, $xml, LOCK_EX) === false) throw new RuntimeException('Não foi possível armazenar XML assinado.');
        return ['storage_reference' => $relative, 'sha256' => hash_file('sha256', $target), 'size' => filesize($target), 'status' => 'XSD_VALID_OFFLINE'];
    }

    public function storeAuthorizedXml(int $tenantId, int $establishmentId, int $documentId, string $xml): array
    {
        if (!str_contains($xml, '<nfeProc') || !str_contains($xml, '<protNFe')) throw new RuntimeException('SEFAZ_AUTHORIZED_XML_INVALID');
        $relative = "tenant-{$tenantId}/establishment-{$establishmentId}/document-{$documentId}/authorized/nfe-proc.xml";
        $target = rtrim($this->root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Não foi possível criar storage fiscal autorizado.');
        if (file_put_contents($target, $xml, LOCK_EX) === false) throw new RuntimeException('Não foi possível armazenar XML autorizado.');
        return ['storage_reference'=>$relative,'sha256'=>hash_file('sha256',$target),'size'=>filesize($target),'status'=>'AUTHORIZED'];
    }

    public function read(string $reference): string
    {
        $path = $this->resolve($reference);
        $content = @file_get_contents($path);
        if ($content === false) throw new RuntimeException('ARTIFACT_STORAGE_FAILED');
        return $content;
    }

    public function assertIntegrity(string $reference, string $expectedSha256): void
    {
        $path = $this->resolve($reference);
        $actual = hash_file('sha256', $path);
        if ($actual !== $expectedSha256) {
            throw new RuntimeException('ARTIFACT_INTEGRITY_FAILED');
        }
    }

    public function resolve(string $reference): string
    {
        if ($reference === '' || str_contains($reference, '..') || str_starts_with($reference, '/') || preg_match('/^[A-Za-z]:/', $reference)) throw new RuntimeException('Referência de storage inválida.');
        $root = realpath($this->root);
        $path = realpath(rtrim($this->root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $reference));
        if ($root === false || $path === false || !str_starts_with(strtolower($path), strtolower($root . DIRECTORY_SEPARATOR))) throw new RuntimeException('Artefato fora do storage fiscal.');
        return $path;
    }
}
