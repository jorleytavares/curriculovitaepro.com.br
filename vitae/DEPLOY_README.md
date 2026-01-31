# 🚀 Guia de Deploy - Currículo Vitae Pro

Este guia detalha os passos necessários para colocar o **Currículo Vitae Pro** em produção (Linux/Apache/MySQL).

## 1. Requisitos do servidor
*   **PHP**: 8.0 ou superior (Recomendado 8.2)
*   **MySQL**: 5.7 ou superior (ou MariaDB 10.4+)
*   **Web Server**: Apache 2.4+ (com `mod_rewrite` e `mod_headers` habilitados)
*   **Extensões PHP Necessárias**: `pdo`, `pdo_mysql`, `mbstring`, `gd`, `curl`, `json`, `fileinfo`.

## 2. Estrutura de Arquivos
Envie todo o conteúdo da pasta `src/` para a raiz pública do seu servidor (geralmente `public_html` ou `/var/www/html`).

> **Nota de Segurança**: O diretório `includes/`, `config/`, e `logs/` possuem proteção `.htaccess`, mas idealmente, se você tiver acesso root, mova-os para **fora** do diretório público e ajuste os `require` no `index.php` e outros arquivos. Se estiver em hospedagem compartilhada (cPanel), as proteções `.htaccess` atuais são suficientes.

## 3. Configuração do Banco de Dados
1.  Crie um banco de dados MySQL e um usuário.
2.  Importe o esquema inicial (se houver um `schema.sql`).
    *   *Nota*: O sistema possui **Auto-Migração**. Ao acessar a página de login pela primeira vez com `?setup=1`, ele tentará criar as tabelas necessárias.

## 4. Variáveis de Ambiente (.env)
Renomeie o arquivo `.env.example` para `.env` (se existir) ou crie um novo na pasta `config/` (ou na raiz, dependendo de onde o `database.php` procura).

O conteúdo deve ser:
```ini
DB_HOST=localhost
DB_NAME=nome_do_banco
DB_USER=usuario_do_banco
DB_PASS=senha_forte
db_port=3306
```

**Bloqueie o acesso web a este arquivo!** (O `.htaccess` em `config/` já faz isso, mas certifique-se que o `.env` esteja dentro de `config/` ou protegido).

## 5. Permissões de Pasta (CHMOD)
O servidor web precisa de permissão de **ESCRITA** nestas pastas:
*   `logs/` (Para logs de erro e rate limiting)
*   `public/uploads/` (Para fotos de perfil)
*   `email_log.txt` (Se existir na raiz, para logs de email)

Comandos recomendados (Linux):
```bash
chmod -R 755 src/
chmod -R 775 src/logs/
chmod -R 775 src/public/uploads/
chown -R www-data:www-data src/
```

## 6. Configurações Finais
1.  **HTTPS**: É obrigatório para recursos como PWA e Segurança de Cookies. Instale um certificado SSL (Let's Encrypt é grátis).
2.  **Cron Jobs** (Opcional mas Recomendado):
    *   Limpeza de arquivos temporários de upload.
    *   Limpeza de `logs/rate_limits` antigos.

## 7. Checklist de Verificação
- [ ] Arquivo `.env` configurado com credenciais de produção.
- [ ] Permissões de escrita em `logs` e `uploads`.
- [ ] HTTPS ativo e forçando redirecionamento (o `.htaccess` na raiz já tenta fazer isso).
- [ ] Teste de envio de e-mail (Recuperação de Senha).
- [ ] Login e Registro funcionando.
- [ ] Upload de foto funcionando.

---
**Suporte**: Em caso de "Erro 500", verifique o `error_log` do Apache/PHP na raiz ou na pasta `logs/`.
