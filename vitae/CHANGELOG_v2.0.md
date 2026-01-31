# Changelog v2.0 - Produção Asaas

**Data:** 12 de Janeiro de 2026  
**Autor:** Sessão Antigravity  

---

## 🚀 Resumo das Alterações

Esta versão marca a transição do ambiente de desenvolvimento (Docker/Sandbox) para produção completa com pagamentos reais via Asaas.

---

## ✅ Alterações Realizadas

### 1. Remoção de Configurações Docker
- **Arquivos removidos:**
  - `docker-compose.yml`
  - `docker/php/Dockerfile`
- **Motivo:** Projeto agora roda diretamente em servidor web remoto (cPanel/Apache)

### 2. Migração Asaas: Sandbox → Produção
- **Arquivo:** `src/config/asaas.php`
- **Alterações:**
  - `environment`: `sandbox` → `production`
  - `api_key`: Configurada com chave de produção
  - `base_url`: `https://api.asaas.com/v3`
- **Arquivo:** `src/services/AsaasService.php`
  - SSL habilitado automaticamente em produção (`CURLOPT_SSL_VERIFYPEER`)

### 3. Atualização de Preços do Plano PRO
| Configuração | Antes | Depois |
|---|---|---|
| Preço Normal | R$ 19,90 | **R$ 13,99** |
| Preço Promocional (50% off) | R$ 9,90 | **R$ 6,99** |
| Duração Promoção | 6 meses | 6 meses (mantido) |

- **Arquivos atualizados:**
  - `src/config/asaas.php` - Backend de pagamentos
  - `src/upgrade.php` - Página de upgrade
  - `src/index.php` - Landing page (seção de preços)
  - `src/admin_dashboard.php` - Cálculo de MRR

### 4. Correção de Links de Upgrade
- **Problema:** Links usavam `/upgrade` (URL amigável não configurada)
- **Solução:** Alterado para `/upgrade.php` (caminho absoluto)
- **Arquivos corrigidos:**
  - `src/dashboard.php` - Badge FREE e link "Faça Upgrade"
  - `src/upgrade.php` - Botão cancelar no modal de CPF

---

## 📋 Configuração Pendente no Asaas

### Webhook (OBRIGATÓRIO para ativar planos automaticamente)

1. Acessar: **Painel Asaas → Configurações → Integrações → Webhooks**
2. Adicionar nova URL:
   ```
   https://curriculovitaepro.com.br/webhook/asaas.php
   ```
3. Selecionar eventos:
   - `PAYMENT_CONFIRMED`
   - `PAYMENT_RECEIVED`
   - `PAYMENT_OVERDUE`
   - `PAYMENT_DELETED`
   - `SUBSCRIPTION_CREATED`
   - `SUBSCRIPTION_DELETED`

### Token de Segurança (RECOMENDADO)
1. Gerar token no Asaas
2. Adicionar em `src/config/asaas.php`:
   ```php
   'webhook_token' => 'SEU_TOKEN_AQUI'
   ```

---

## 🔐 Arquivos Sensíveis

Os seguintes arquivos contêm credenciais e **NÃO devem ser versionados publicamente**:
- `src/config/asaas.php` (API Key de produção)
- `.env` (variáveis de ambiente)

---

## 📊 Commits Desta Sessão

1. `e162411` - Remover configurações Docker e atualizar projeto para ambiente remoto
2. `414cf58` - Migrar Asaas de sandbox para producao com API Key real
3. `e8907bc` - Atualizar precos do plano PRO: R$ 13,99 (normal) e R$ 6,99 (50% off por 6 meses)
4. `f1e26b9` - Corrigir links de upgrade para usar upgrade.php
5. `b967f6a` - Usar caminho absoluto /upgrade.php nos links

---

## 🎯 Status Final

| Item | Status |
|---|---|
| Docker removido | ✅ |
| Asaas em produção | ✅ |
| Preços atualizados | ✅ |
| Links de upgrade corrigidos | ✅ |
| Webhook configurado no Asaas | ⏳ Pendente (manual) |
| Deploy no servidor | ✅ |

---

**Próximos Passos:**
1. Configurar webhook no painel Asaas
2. Testar fluxo completo de pagamento
3. Monitorar logs de webhook em `/webhook/asaas.php`
