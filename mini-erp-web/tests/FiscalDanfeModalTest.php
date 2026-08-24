<?php
declare(strict_types=1);$p=file_get_contents(__DIR__.'/../public/fiscal_notes.php').file_get_contents(__DIR__.'/../public/fiscal_note_detail.php').file_get_contents(__DIR__.'/../public/assets/fiscal-notes.js');foreach(['<dialog','<iframe','AppModal.open','mode=inline','target="_blank"','mode=download']as$n)if(!str_contains($p,$n))throw new RuntimeException($n);echo"FiscalDanfeModal OK\n";
