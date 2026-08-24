<?php

declare(strict_types=1);

namespace MiniErp\Services;

use InvalidArgumentException;
use RuntimeException;

final readonly class TenantSchemaTemplate
{
    public const CURRENT = 'v1';

    public function __construct(private string $templatesRoot)
    {
    }

    public function currentVersion(): string
    {
        return self::CURRENT;
    }

    public function currentSchemaPath(): string
    {
        return $this->schemaPathFor(self::CURRENT);
    }

    public function schemaPathFor(string $version): string
    {
        if ($version !== self::CURRENT) {
            throw new InvalidArgumentException('Versão de template não reconhecida.');
        }
        $path = rtrim($this->templatesRoot, '/\\') . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR . 'schema.sql';
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Template de tenant inexistente ou ilegível.');
        }
        $contents = file_get_contents($path);
        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException('Template de tenant vazio.');
        }
        return $path;
    }
}
