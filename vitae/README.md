# ResumeAI - NanoSaaS de Geração de Currículos

Uma plataforma SaaS leve para criação, gestão e exportação de currículos profissionais em PDF. Desenvolvido com foco em arquitetura limpa, segurança e performance.

## 🚀 Funcionalidades

- **Autenticação Segura:** Registo e Login com hash de senhas (Bcrypt) e proteção CSRF.
- **Dashboard do Utilizador:** Gestão centralizada de múltiplos currículos.
- **Editor Dinâmico:** Interface reativa para adição de experiências profissionais e dados pessoais.
- **Motor de PDF:** Geração de documentos PDF de alta fidelidade via DomPDF.
- **Modelo Freemium:** Sistema de bloqueio lógico para utilizadores gratuitos vs. PRO (simulação de pagamento).
- **Segurança:** Proteção contra SQL Injection (PDO), XSS (Sanitization) e IDOR (Check de Propriedade).

## 🛠️ Stack Tecnológica

- **Backend:** PHP 8.2+
- **Base de Dados:** MySQL 8.0
- **Frontend:** HTML5, Vanilla JS, TailwindCSS (via CDN).
- **Dependências:** DomPDF (via Composer).

## 📂 Estrutura de Pastas

```text
resume-saas/
├── database/init/01-schema.sql # Schema da base de dados
├── src/                        # Código Fonte Principal
│   ├── composer.json           # Dependências PHP
│   ├── config/                 # Conexão BD
│   ├── includes/               # Lógica de Backend
│   │   ├── auth_functions.php 
│   │   └── resume_functions.php
│   ├── dashboard.php           # Área Logada
│   ├── editor.php              # Criação/Edição
│   ├── index.php               # Landing Page
│   └── generate_pdf.php        # Output
└── docker-compose.yml          # Ambiente Local
```

## ⚙️ Instalação Local (Docker)

A forma mais fácil de correr o projeto é via Docker:

```bash
# 1. Iniciar os containers
docker-compose up -d --build

# 2. Aceder ao projeto
# O sistema estará disponível em http://localhost
```

## ⚙️ Instalação Manual

1. **Configurar Base de Dados:**
   - Crie uma base MySQL `resume_saas`.
   - Importe o ficheiro `database/init/01-schema.sql`.
   - Edite `src/config/database.php` com as suas credenciais.

2. **Instalar Dependências:**
   ```bash
   cd src
   composer install
   ```

3. **Executar:** 
   Pode usar o servidor embutido do PHP para testes rápidos:
   ```bash
   cd src
   php -S localhost:8000
   ```
   Aceda a `http://localhost:8000`.

## 🚢 Deploy (Produção)

Consulte a pasta `deploy/` para scripts de automação e guia completo para servidores Ubuntu + Nginx.
