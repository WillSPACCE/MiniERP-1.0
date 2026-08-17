Instruções de migração (adicionar tenant_id)

1) Backup do banco (obrigatório):

mysqldump -u <user> -p <database> > backup_pre_tenant_20260814.sql

2) Validar estado atual (opcional):

mysql -u <user> -p -e "SELECT COUNT(*) FROM produtos WHERE tenant_id IS NULL;"
mysql -u <user> -p -e "SELECT COUNT(*) FROM usuarios WHERE tenant_id IS NULL;"

3) Executar migration (na pasta migrations):

# em ambiente UNIX/WSL/PowerShell com mysql no PATH
mysql -u <user> -p <database> < migrations/20260814_add_tenant_id.sql

4) Popular tenant_id a partir de company_id (compatibilidade):

mysql -u <user> -p -e "UPDATE produtos SET tenant_id = company_id WHERE tenant_id IS NULL;" <database>
mysql -u <user> -p -e "UPDATE usuarios SET tenant_id = company_id WHERE tenant_id IS NULL;" <database>

5) Validações pós-migração:

-- contar registros sem tenant
SELECT COUNT(*) FROM produtos WHERE tenant_id IS NULL;
SELECT COUNT(*) FROM usuarios WHERE tenant_id IS NULL;

-- verificar discrepâncias company_id vs tenant_id
SELECT COUNT(*) FROM produtos WHERE tenant_id IS NOT NULL AND company_id IS NOT NULL AND tenant_id <> company_id;
SELECT COUNT(*) FROM usuarios WHERE tenant_id IS NOT NULL AND company_id IS NOT NULL AND tenant_id <> company_id;

6) Rollback simples (recomenda-se restaurar o dump):

mysql -u <user> -p <database> < backup_pre_tenant_20260814.sql

OBS: Não remover `company_id` durante a fase de compatibilidade.
