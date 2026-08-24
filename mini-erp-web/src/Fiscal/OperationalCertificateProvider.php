<?php
declare(strict_types=1);

namespace MiniErp\Fiscal;

use MiniErp\Repositories\FiscalConfigurationRepository;
use RuntimeException;

final class OperationalCertificateProvider
{
    public function __construct(
        private A1CertificateInspector $inspector,
        private PrivateCertificateStorage $storage,
        private LocalEncryptedSecretStorage $secrets,
        private FiscalConfigurationRepository $repo
    ) {}

    public function verifyCurrent(int $establishmentId, string $expectedTaxId): array
    {
        $row = $this->repo->certificate($establishmentId);
        if (!$row) {
            throw new RuntimeException('Certificado ativo nao configurado.');
        }

        $content = $this->storage->read((string) $row['storage_reference']);
        $password = $this->secrets->get((string) $row['secret_reference']);

        $result = $this->inspector->inspect($content, $password, (string) $row['file_name'], $expectedTaxId);
        unset($result['certificate']);

        return $result;
    }

    public function certificateReady(int $establishmentId, string $expectedTaxId): bool
    {
        try {
            $this->resolveOperationalCertificate($establishmentId, $expectedTaxId);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function verifyStored(int $establishmentId, int $certificateId, string $expectedTaxId): array
    {
        $row = $this->repo->certificateById($establishmentId, $certificateId);
        if (!$row) throw new RuntimeException('Certificado armazenado nao encontrado.');
        $content = $this->storage->read((string) $row['storage_reference']);
        $password = $this->secrets->get((string) $row['secret_reference']);
        $result = $this->inspector->inspect($content, $password, (string) $row['file_name'], $expectedTaxId);
        unset($result['certificate']);
        return $result + ['file_found'=>true,'secret_recovered'=>true,'pkcs12_valid'=>true];
    }

    public function resolveOperationalCertificate(int $establishmentId, string $expectedTaxId): array
    {
        $row = $this->repo->certificate($establishmentId);
        if (!$row) {
            throw new RuntimeException('CERTIFICATE_NOT_READY');
        }

        $content = $this->storage->read((string) $row['storage_reference']);
        $password = $this->secrets->get((string) $row['secret_reference']);
        $result = $this->inspector->inspect($content, $password, (string) $row['file_name'], $expectedTaxId);
        if (($result['operational'] ?? false) !== true) {
            throw new RuntimeException('CERTIFICATE_NOT_READY');
        }

        $certificate = \NFePHP\Common\Certificate::readPfx($content, $password);

        return [
            'content' => $content,
            'password' => $password,
            'certificate' => $certificate,
            'file_name' => $result['file_name'],
            'sha256' => $result['sha256'],
            'identity' => $result['subject'],
            'valid_until' => $result['valid_until'],
            'ready' => true,
            'storage_reference' => (string) $row['storage_reference'],
            'secret_reference' => (string) $row['secret_reference'],
            'certificate_id' => (int) $row['id'],
        ];
    }
}
