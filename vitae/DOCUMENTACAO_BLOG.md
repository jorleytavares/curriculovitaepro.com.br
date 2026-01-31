# Documentação Técnica: Módulo de Blog & SEO (v1.0)
**Data:** 10/01/2026
**Responsável:** Antigravity Agent (Google Deepmind)

## 1. Visão Geral
Este módulo adiciona uma capacidade completa de **Content Marketing** ao projeto Currículo Vitae Pro. O objetivo é atrair tráfego orgânico (SEO) qualificado através de artigos sobre carreira, currículos e mercado de trabalho. O sistema foi desenhado para ser autônomo e altamente otimizado para motores de busca (Google) e respostas de IA (SGE).

## 2. Arquitetura do Sistema

### 2.1. Fluxo de Dados
1.  **Criação/Edição:** O Admin acessa `admin_blog_editor.php` para escrever conteúdo.
2.  **Armazenamento:** Dados são salvos na tabela `blog_posts` (MySQL).
3.  **Processamento:** Imagens são renomeadas e otimizadas; SEO é gerado automaticamente se omitido.
4.  **Exibição (Frontend):**
    *   `blog.php`: Listagem geral com busca e filtros.
    *   `blog_post.php`: Página do artigo individual (Slug).
    *   `index.php`: Widget de "Últimos Artigos" na Home.

### 2.2. Estrutura de Banco de Dados
Novas colunas adicionadas à tabela `blog_posts`:
*   `subtitle` (VARCHAR): Linha fina de apoio ao título.
*   `main_tag` (VARCHAR): Categoria principal (ex: "Tutorial", "Dicas").
*   `tags` (VARCHAR): Palavras-chave secundárias separadas por vírgula.
*   `schema_markup` (LONGTEXT): JSON-LD pré-gerado para Rich Snippets.

## 3. Funcionalidades Detalhadas

### 3.1. Editor Inteligente (`admin_blog_editor.php`)
*   **Interface:** WYSIWYG (Quill.js) com suporte a Markdown.
*   **Smart Image Renamer:** Renomeia uploads automaticamente usando o slug do post (ex: `como-fazer-curriculo-a1b2c3.avif`) para melhor indexação de imagens.
*   **Fallback SEO:** Se o usuário não preencher a Meta Description ou Schema, o sistema gera automaticamente baseando-se no conteúdo e título.
*   **Resiliência:** Verificação de extensão GD para evitar erros fatais em ambientes sem a biblioteca gráfica.

### 3.2. Frontend (`blog.php` e `blog_post.php`)
*   **Hierarquia Semântica:** Estrutura correta de HEADINGS (`H1` > `H2` > `H3`) auditada para facilitar leitura por bots.
*   **SEO Técnico:**
    *   **Meta Tags Dinâmicas:** Título, Descrição e Imagem (Open Graph) injetados no `<head>`.
    *   **Autoridade E-E-A-T:** Meta Author definida como "Currículo Vitae Pro" (Marca) em vez de genéricos.
    *   **JSON-LD:** Injeção automática de dados estruturados do tipo `BlogPosting` ou `Article`.
*   **UX Features:**
    *   Barra de Progresso de Leitura no topo do artigo.
    *   Animações de entrada (`fadeIn`) suaves.
    *   Busca interna funcional por título, conteúdo e subtítulo.
    *   Design consistente com o tema SaaS (Dark/Light Mode).

## 4. Setup e Instalação
Para rodar este módulo em um ambiente novo:
1.  Execute o script SQL de migração (ou acesse o Editor que roda a migração on-the-fly).
2.  Garanta permissões de escrita em `public/uploads/blog/`.
3.  (Opcional) Rode `php src/seed_blog.php` para popular o banco com 10 artigos iniciais.

## 5. Checklist de SEO (Validado)
- [x] Títulos de Página Únicos (`<title>`)
- [x] Meta Descriptions Únicas
- [x] Tags Canônicas
- [x] Open Graph (Facebook/LinkedIn/WhatsApp)
- [x] Schema Markup (JSON-LD)
- [x] Imagens com Alt Text e Nomes Otimizados
- [x] Estrutura H1-H6 Hierárquica
- [x] Responsividade Mobile Friendly

## 6. Próximos Passos Sugeridos
- Implementar **sitemap.xml** dinâmico incluindo as URLs do blog.
- Criar sistema de **Comentários** (requer moderação).
- Adicionar botões de **Compartilhamento Social** nos artigos.
