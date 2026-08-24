# FISCAL-06B-A — Validação da toolchain

Data: 2026-08-21. Resultado final: **BLOCKED para FISCAL-06B-B** exclusivamente pela falta de equivalência comprovada com os XSD oficiais vigentes.

## Ambiente

- PHP: `C:\xampp\php\php.exe`, 8.2.12, x64, ZTS.
- INI: `C:\xampp\php\php.ini`.
- Habilitadas a partir das DLLs oficiais existentes no XAMPP: `soap`, `zip`, `gd`. Backup: `C:\xampp\php\php.ini.fiscal06ba-20260821.bak`.
- Composer 2.10.2 instalado em `C:\xampp\php\composer.phar` pelo instalador oficial, conferido com SHA-384 publicado em `composer.github.io/installer.sig`.
- NFePHP `nfephp-org/sped-nfe` solicitado com `^5.2.8` e resolvido/travado em `v5.2.8` pelo `composer.lock`.

## Evidências

`composer validate --strict`, `check-platform-reqs` e `audit` passaram. Autoload carrega `Make`, `Tools` e `Keys`. A API contém `tagIBSCBS`, `tagIBSCBSTot`, `tagIS`, `tagISTot`, `cClassTrib`, grupos IBS e CBS. `Make::tagEmit` preservou `12ABC34501DE35`; `NFePHP\Common\Keys` aceitou a chave alfanumérica produzida por `NfeAccessKeyGenerator` e calculou o mesmo DV.

## Schemas e blocker

O pacote contém `PL_009_V4`, `PL_010_V1`, `PL_010_V1.21` e `PL_010_V1.30`. Este último contém regex alfanumérica e grupos RTC, mas o Portal NF-e lista como vigentes pacotes posteriores em agosto de 2026: `010e v1.02` para RTC e `010d v1.03` para CNPJ alfanumérico. Portanto a classificação é **DESATUALIZADO/NÃO CONFIRMADO**, não `IGUAL`.

Checksums locais de referência:

- `PL_010_V1.30/DFeTiposBasicos_v1.00.xsd`: `7FE1DBD89A1DD80826C5134C2406B7EB5DF4FA7A9177C5AA6E72319CABA7C6D2`.
- `PL_010_V1.30/leiauteNFe_v4.00.xsd`: `A3ACF8470DEC58A6FAB45C68818B436131322A38D5E8554BA055E22E7E15A1C3`.

Nenhum XML foi gerado. Nenhuma migration foi aplicada e nenhum runtime foi ligado à biblioteca.
