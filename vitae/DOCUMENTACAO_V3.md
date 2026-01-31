# Documentação do Projeto: Currículo Vitae Pro (Versão 3.0 - Dashboard & Webcam Integration)
Data: 10/01/2026

## 1. Visão Geral
Esta atualização (v3.0) focou na **experiência do usuário logado**, resolvendo bugs críticos de listagem no Dashboard, melhorando a acessibilidade do Editor e introduzindo uma funcionalidade avançada de **captura de foto via Webcam com IA** (guia visual).

## 2. Novas Funcionalidades

### 📸 Webcam Inteligente (Smart Capture)
Implementada uma solução robusta de captura de imagem diretamente pelo navegador, disponível tanto no **Perfil do Usuário** quanto no **Editor de Currículo**.
- **Guia Visual (Overlay):** Máscara SVG com silhueta de rosto para orientar o enquadramento perfeito do usuário.
- **Fluxo de Dados:** Captura via HTML5 Canvas -> Conversão Base64 -> Blob -> Upload via AJAX (reutilizando lógica backend existente).
- **UX:** Feedback de carregamento ("Acessando câmera...") e tratamento de erros de permissão.

### 📊 Dashboard Otimizado
O painel principal recebeu correções estruturais e melhorias visuais:
- **Correção Crítica (SQL):** Removida referência à coluna inexistente `template` que impedia a listagem de currículos salvos para novos usuários (`SQLSTATE[42S22]`).
- **Cards Vivos:** As miniaturas dos currículos agora exibem a **foto real do usuário** (extraída dinamicamente do JSON do currículo), substituindo o placeholder genérico quando disponível.
- **Refinamento de Layout:** Ajuste no espaçamento vertical (`top-margin`) para eliminar a sobreposição indesejada entre o cabeçalho de boas-vindas e a grade de projetos.

### 📝 Editor de Currículo
- **Integração de Câmera:** Botão "Câmera IA" adicionado ao lado do upload de arquivo tradicional.
- **Acessibilidade (Dark Mode):** Os placeholders e rótulos dos formulários tiveram o contraste aumentado (`slate-600` -> `slate-400`) para garantir legibilidade perfeita em fundos escuros.
- **Stability:** Mecanismos de debug temporários foram usados para validar o salvamento de dados e posteriormente removidos para limpeza do código.

## 3. Arquivos Impactados
- `src/dashboard.php`: Correção de query SQL e lógica de renderização de imagem nos cards.
- `src/editor.php`: Inclusão do modal de webcam, script de captura e ajustes de CSS.
- `src/user_profile.php`: Implementação original da lógica de webcam e upload.
- `src/includes/resume_functions.php`: Validação de funções de salvamento.

## 4. Próximos Passos (Roadmap)
- [ ] **Geração de PDF:** Refinar o motor de renderização PDF para suportar os novos templates visuais da Home.
- [ ] **Integração de Pagamento:** Finalizar o fluxo de upgrade para conta PRO.
- [ ] **Testes Automatizados:** Implementar testes unitários para funções críticas de banco de dados.

---
**Status:** ✅ Funcionalidades Implementadas e Bug Crítico Resolvido.

## 5. Atualização de Infraestrutura (11/01/2026)
### 🗄️ Correção de Conexão de Banco de Dados
Resolvido um problema crítico onde o servidor ignorava as credenciais do `.env` e tentava usar um usuário padrão do sistema operacional, causando `Access Denied`.
- **Lógica de Conexão (`src/config/database.php`):** Reescrevida para **priorizar** variáveis do arquivo `.env` sobre variáveis de ambiente do sistema.
- **Suporte a Porta Personalizada:** Adicionada leitura da variável `DB_PORT` (padrão 3306), permitindo conexões em portas não-padrão.
- **Robustez:** Adicionado suporte a chaves alternativas (ex: `DB_DATABASE` além de `DB_NAME`, `DB_USERNAME` além de `DB_USER`) para maior compatibilidade com padrões Laravel/Docker.

> **Atenção para Deploy:** O arquivo `.env` de produção deve ser configurado manualmente no servidor com as credenciais corretas (Host: `127.0.0.1` ou `localhost`, e o usuário/senha do cPanel).
