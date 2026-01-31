# ✅ Checklist de Verificação - Produção (v1.0 Gold)

Antes de divulgar o link, verifique os itens críticos abaixo.

### 1. URLs Amigáveis (NOVO ⚠️)
- [ ] O link de login deve ser `yoursite.com/entrar` (não /login.php).
- [ ] O link de registro deve ser `yoursite.com/criar-conta`.
- [ ] O painel principal deve ser `yoursite.com/painel`.
- [ ] Ao clicar em "Termos de Uso" no rodapé, deve abrir `/termos-de-uso`.
*Se der erro 404, verifique se o `mod_rewrite` está ativo no Apache.*

### 2. Segurança
- [ ] O arquivo `.env` contém a senha do banco de produção.
- [ ] O arquivo `.htaccess` na raiz está ativo.
- [ ] A conexão é **HTTPS**.

### 3. Funcionalidade
- [ ] **Login & Magic Link**: Teste o login com senha e com o link mágico.
- [ ] **Cadastro**: Crie uma conta nova para testar o fluxo completo.
- [ ] **PWA**: Verifique se o ícone de instalação aparece no navegador.

### 4. Arquivos Críticos
Certifique-se de enviar para o servidor:
- [x] `src/.htaccess` (Rotas)
- [x] `src/config/database.php`
- [x] `src/manifest.json` (PWA)
- [x] `src/service-worker.js` (PWA)

---
*Release v1.0.0 - Currículo Vitae Pro*
