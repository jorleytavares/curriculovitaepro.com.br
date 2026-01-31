# Changelog - Landing Page Redesign (v2.0)
**Data:** 10 de Janeiro de 2026
**Responsável:** Antigravity Agent

## Resumo
Este documento detalha a refatoração completa da Landing Page (`index.php`) para atingir um visual "SaaS Premium", focado em humanização, conversão e estética moderna.

## 1. Hero Section (Topo)
- **Visual:** Adicionado background com "Blobs" animados (roxo e ciano) e textura de ruído (`noise.svg`) para profundidade.
- **Animações:** Implementação de GSAP para entrada sequencial dos elementos (Título, Subtítulo, CTA).
- **Social Proof:** Adicionada barra de "Profissionais contratados" com avatares sobrepostos.

## 2. Slider de Currículos (3D)
- **Upgrade:** Substituição de imagens estáticas por *Templates HTML/CSS Vivos*.
- **Modelos:**
    - *Executivo:* Foco em texto denso e sobriedade.
    - *Moderno:* Foto de destaque e layout em colunas.
    - *Acadêmico:* Fonte serifada e estrutura formal.
    - *Júnior:* Visual limpo e direto.
- **Interatividade:** O slider gira em 3D, pausa no hover e permite navegação por pontos.

## 3. Seção de Recursos (Humanizada)
- **Layout:** Mudança de "Grid Técnico" para **Zig-Zag Humanizado**.
- **Conteúdo:** 
    - Uso de fotos grandes e emotivas (Lifestyle/Unsplash).
    - Copywriting focado em *sentimento* (ex: "Alívio", "Confiança") ao invés de *features* técnicas.
    - Mockups visuais integrados (ex: celular flutuante).

## 4. Depoimentos (Marquee)
- **Correção:** Implementação de *Infinite Scroll* verdadeiro.
- **Técnica:** Duplicação do grupo de cards para eliminar o "buraco" visual ao fim da animação.
- **Estilo:** Cards clean com sombra suave.

## 5. FAQ (Perguntas Frequentes)
- **Layout Final:** Grid de 2 Colunas.
- **Conteúdo:** Expandido para 6 perguntas essenciais (Preço, ATS, Segurança, IA, Formatos, Suporte).
- **Estilo:** Cards brancos, ícones roxos de destaque e tipografia hierárquica clara.

## 6. Footer CTA
- **Design:** Fundo escuro (`slate-900`) de alto contraste.
- **Efeitos:** Glow roxo central e botões com hover state evidente.

## 7. Ajustes Gerais
- **Espaçamento:** Redução global de paddings verticais (`py-32` para `py-20`/`py-16`) para compactar a página.
- **Responsividade:** Todas as seções adaptadas para Mobile e Desktop.
