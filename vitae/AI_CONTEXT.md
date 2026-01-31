# ResumeAI (Currículo Vitae Pro) - Documentação para Agentes de IA
**⚠️ WORKSPACE RULE: STRICT SCOPE ENFORCEMENT**
**Allowed Path:** `c:/Users/hosta/Desktop/IAS 2026/Antigravity/curriculo-vitae-pro/*`
**Forbidden:** Any other project folder. Do NOT access files outside the allowed path.

## O que é
ResumeAI é um NanoSaaS focado na criação, edição e exportação de currículos profissionais em formato PDF. Ele resolve o problema de formatação de documentos, permitindo que o usuário foque apenas no conteúdo.

## Funcionalidades Principais
- **Editor Estruturado:** Separação clara entre dados pessoais, experiência e formação (JSON Data).
- **Exportação PDF:** Gera arquivos PDF leves e compatíveis com ATS (Applicant Tracking Systems) via DomPDF.
- **Segurança:** Autenticação via Sessão PHP, Senhas com Bcrypt e proteção CSRF/XSS.
- **Custo:** Modelo Freemium (1 currículo grátis, ilimitados no plano PRO).

## Como Utilizar (Fluxo do Usuário)
1. **Onboarding:** O usuário acessa `/index.php` (Landing Page) e vai para `/register.php`.
2. **Dashboard:** Após login, cai em `/dashboard.php`. Vê lista de currículos e status do plano.
3. **Criação:** Clica em "Novo Currículo".
   - *Validação:* O backend verifica `canCreateResume()`. Se limite excedido > `/upgrade.php`.
4. **Edição:** Acessa `/editor.php`. Preenche formulário dinâmico (Repeater Fields).
5. **Output:** No dashboard, clica em "Baixar PDF" (`/generate_pdf.php`) para obter o arquivo.

## Estrutura de Dados (Técnico)

### Database Schema
- **users:** `id`, `name`, `email`, `password_hash`, `plan` ('free'|'pro').
- **resumes:** `id`, `user_id`, `title`, `content` (JSON), `created_at`.

### JSON Resume Structure (Tabela `resumes.content`)
Esta é a estrutura que IAs geradoras de conteúdo devem seguir ao popular o banco:

```json
{
  "full_name": "João Silva",
  "job_title": "Senior Software Engineer",
  "contact_email": "joao@example.com",
  "phone": "+55 11 99999-9999",
  "links": "linkedin.com/in/joao, github.com/joao",
  "summary": "Desenvolvedor experiente com foco em arquitetura...",
  "experiences": [
    {
      "company": "Tech Corp",
      "role": "Tech Lead",
      "desc": "Liderança técnica de equipe de 5 devs..."
    },
    {
      "company": "StartUp X",
      "role": "Full Stack Dev",
      "desc": "Desenvolvimento de MVP utilizando PHP e React..."
    }
  ]
}
```

## Links Úteis (Ambiente Produção)
- Login: `https://seu-dominio.com/login.php`
- Registro: `https://seu-dominio.com/register.php`
- Home: `https://seu-dominio.com/index.php`
