# Changelog - Manutenção e Segurança

## [2026-01-14] - Ativação do Modo Manutenção e Regras de Workspace

### Segurança
- **Modo Manutenção Ativado:** 
  - Alteração no arquivo `.htaccess` para redirecionar tráfego público para `/maintenance.php`.
  - Acesso de desenvolvedor garantido via cookie `maintenance_bypass` (ativado por `?access=dev`).
  - Rotas de assets (`/public/`) mantidas abertas para renderização da página de manutenção.

### Documentação e Compliance
- **Regras de Escopo (Workspace Rules):**
  - Adicionado cabeçalho mandatório em `AI_CONTEXT.md`.
  - **Bloqueio Explícito:** Proibido o acesso ou referência a qualquer projeto fora de `curriculo-vitae-pro`.
  - Caminho permitido fixado em: `c:/Users/hosta/Desktop/IAS 2026/Antigravity/curriculo-vitae-pro/*`.

---
*Este documento serve como registro oficial das alterações de segurança e isolamento de contexto realizadas.*
