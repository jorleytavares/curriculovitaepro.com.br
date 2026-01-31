# Documentação do Projeto: Currículo Vitae Pro (Versão 2.0 - SaaS Premium)
Data: 10/01/2026

## 1. Visão Geral
Esta atualização focou na reformulação completa da **Landing Page (`index.php`)** para alinhá-la com uma estética "SaaS Premium", moderna e de alta conversão. O objetivo foi eliminar a percepção de "modelos feios" e criar uma apresentação visualmente impactante que transmita autoridade e tecnologia.

## 2. Mudanças Visuais (Frontend)

### Identidade Visual
- **Paleta de Cores:** Foco em Alto Contraste (Preto/Branco) com acentos em Roxo (`purple-600`), Ciano (`cyan-500`) e Rosa.
- **Modo Escuro:** Suporte nativo completo via Tailwind CSS (`dark:` classes).
- **Tipografia:** Uso agressivo da fonte **Inter** com pesos `Black` e `ExtraBold` para títulos.

### Landing Page (`index.php`)
A página foi reescrita com as seguintes seções:
1.  **Hero Section:** Títulos grandes, badges animados ("Nova IA 2026"), e elementos de fundo (blobs) animados com GSAP e efeito de vidro (glassmorphism).
2.  **Social Proof:** Logos de confiança (Gupy, LinkedIn, Glassdoor) monocromáticos e avatares de usuários.
3.  **Slider 3D (Destaque):**
    *   Implementação de um carrossel rotativo 3D.
    *   **Templates Refinados:**
        *   *Executivo:* Design sóbrio, serifado, com foto discreta.
        *   *Criativo:* Sidebar colorida, foto grande redonda, foco em portfólio.
        *   *Acadêmico:* Estilo paper/Lattes, muito texto técnico, foto preto e branca.
        *   *Júnior:* Foco em educação e objetivos, foto jovem.
    *   **High Density:** Todos os templates foram preenchidos com texto real e denso para evitar a aparência de "esqueleto vazio".
    *   **High Contrast:** Texto preto absoluto sobre fundo branco puro (#FFFFFF) para legibilidade máxima.
4.  **Recursos (Bento Grid):** Layout em grid assimétrico destacando IA Nativa, Mobile First e Privacidade.
5.  **Depoimentos (Marquee):** Faixa de rolagem infinita com reviews de usuários.
6.  **FAQ:** Seção de perguntas frequentes com animação de accordion.
7.  **Footer CTA:** Bloco final de alta conversão com fundo escuro e botões de brilho neon.

### Tecnologias Utilizadas
- **Tailwind CSS:** Framework utilitário para todo o estilo.
- **GSAP (GreenSock):**
    *   `ScrollTrigger`: Para revelar elementos ao rolar a página.
    *   `Timeline`: Para a entrada sequencial do Hero.
    *   `Parallax`: Movimento do mouse controlando os blobs de fundo.
- **Vanilla JS:** Lógica do slider 3D e alternância de temas.

## 3. Estrutura de Arquivos
- `src/index.php`: Arquivo principal modificado.
- `src/includes/components/header.php`: Cabeçalho com lógica de SEO e toggle de tema.
- `src/includes/components/footer.php`: Rodapé com scripts globais.

## 4. Próximos Passos (Roadmap)
- [ ] **Dashboard:** Reformular o painel do usuário para seguir a mesma estética.
- [ ] **Editor de Currículo:** Garantir que o PDF gerado corresponda exatamente aos modelos HTML mostrados na Home.
- [ ] **Integração de Pagamento:** Implementar tela de Upgrade real.

---
**Status:** ✅ Landing Page Finalizada e Aprovada visulamente.
