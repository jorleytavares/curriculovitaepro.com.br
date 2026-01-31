<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/settings_helper.php';

// Inicializa tabela de configurações (silencioso)
initSettingsTable($pdo);

// Carrega configurações de AdSense
$adsense_enabled = getSetting($pdo, 'adsense_enabled', '0') === '1';
$adsense_client = getSetting($pdo, 'adsense_client', '');
$adsense_slot_top = getSetting($pdo, 'adsense_slot_article_top', '');
$adsense_slot_middle = getSetting($pdo, 'adsense_slot_article_middle', '');
$adsense_slot_bottom = getSetting($pdo, 'adsense_slot_article_bottom', '');

// Helper: Renderiza bloco de anúncio
function renderAdBlock($client, $slot, $format = 'auto') {
    if (empty($client) || empty($slot)) return '';
    return '
    <div class="adsense-container my-8 text-center">
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="' . htmlspecialchars($client) . '"
             data-ad-slot="' . htmlspecialchars($slot) . '"
             data-ad-format="' . $format . '"
             data-full-width-responsive="true"></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    </div>';
}

// Obter Slug
// Routing & SILO Logic
$slugParam = $_GET['slug'] ?? '';
$catParam = $_GET['category'] ?? '';
$fallbackParam = $_GET['fallback_slug'] ?? '';
$post = null;

// 1. Rota Silo (/blog/categoria/post)
if ($catParam && $slugParam) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.title as category_title, c.slug as category_slug 
        FROM blog_posts p 
        LEFT JOIN blog_categories c ON p.category_id = c.id 
        WHERE p.slug = ? AND p.status = 'published'
    ");
    $stmt->execute([$slugParam]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
} 
// 2. Rota Fallback (/blog/algo)
elseif ($fallbackParam) {
    // A) É Categoria?
    try {
        $stmt = $pdo->prepare("SELECT * FROM blog_categories WHERE slug = ?");
        $stmt->execute([$fallbackParam]);
        $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($categoryData) {
            require __DIR__ . '/blog_category.php';
            exit;
        }
    } catch (PDOException $e) {
        // Tabela de categorias pode não existir ainda
    }
    
    // B) É Post?
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.title as category_title, c.slug as category_slug 
            FROM blog_posts p 
            LEFT JOIN blog_categories c ON p.category_id = c.id 
            WHERE p.slug = ? AND p.status = 'published'
        ");
        $stmt->execute([$fallbackParam]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback sem JOIN se der erro
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
        $stmt->execute([$fallbackParam]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$post && empty($slug)) {
    header("Location: blog.php");
    exit;
}

if (!$post) {
    http_response_code(404);
    if(file_exists('404.php')) { include '404.php'; } else { echo "Página 404"; }
    exit;
}

// 3. Incrementa Views (Sessão Debounce)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['viewed_posts'])) { $_SESSION['viewed_posts'] = []; }

if (!in_array($post['id'], $_SESSION['viewed_posts'])) {
    try {
        $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);
        $_SESSION['viewed_posts'][] = $post['id'];
    } catch(PDOException $e) {}
}

// SEO
$seo_title = $post['title'] . " | Currículo Vitae Pro";
$seo_desc = $post['excerpt'] ?? strip_tags(mb_substr($post['content'], 0, 160));
$seo_image = $post['cover_image'] ?? '';
$seo_type = 'article';

// Helper: Formata data em Português BR
function formatDatePtBr($dateString) {
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $timestamp = strtotime($dateString);
    $dia = date('d', $timestamp);
    $mes = $meses[(int)date('n', $timestamp)];
    $ano = date('Y', $timestamp);
    return "{$dia} de {$mes} de {$ano}";
}
?>
<!-- Corporate B2B Typography System -->
<style>
    /* ============================================
       CORPORATE B2B TYPOGRAPHY - "Gold Standard"
       Font: Inter | Colors: Slate Palette
       Optimized for SaaS/Tech content
    ============================================ */
    
    :root {
        --font-corporate: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        --color-slate-900: #0f172a; /* Títulos */
        --color-slate-800: #1e293b; /* Títulos secundários */
        --color-slate-700: #334155; /* Texto corpo */
        --color-slate-600: #475569; /* Subtítulos */
        --color-slate-500: #64748b; /* Metadados */
        --color-slate-400: #94a3b8; /* Texto secundário */
        --color-slate-200: #e2e8f0; /* Bordas */
        --color-slate-100: #f1f5f9; /* Backgrounds */
        --color-slate-50: #f8fafc;  /* Background sutil */
        --color-primary: #7c3aed;   /* Roxo marca */
        --color-primary-light: #ede9fe; /* Roxo claro para badges */
        --color-primary-dark: #5b21b6;  /* Roxo escuro */
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* ============================================
       ARTICLE CONTAINER
    ============================================ */
    .article-corporate {
        font-family: var(--font-corporate);
        max-width: 720px;
        margin: 0 auto;
        text-align: left; /* Sempre alinhado à esquerda - padrão corporativo */
    }
    
    /* ============================================
       BADGE / TAG PRINCIPAL
       Estilo "etiqueta de arquivo" - funcional e sóbrio
    ============================================ */
    .badge-corporate {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 99px; /* Pill shape */
        background-color: var(--color-primary-light);
        color: var(--color-primary-dark);
        font-size: 0.75rem; /* 12px */
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 16px;
    }
    
    .dark .badge-corporate {
        background-color: rgba(124, 58, 237, 0.15);
        color: #a78bfa;
    }
    
    /* ============================================
       TÍTULO H1 - Direto e Assertivo
    ============================================ */
    .title-corporate {
        font-size: 2.5rem !important; /* 40px */
        line-height: 1.2 !important;
        font-weight: 800 !important; /* Extra bold para autoridade */
        color: var(--color-slate-900) !important;
        letter-spacing: -0.025em !important;
        margin-bottom: 16px !important;
    }
    
    .dark .title-corporate {
        color: #f8fafc !important;
    }
    
    @media (max-width: 640px) {
        .title-corporate {
            font-size: 1.875rem !important; /* 30px mobile */
        }
    }
    
    /* ============================================
       SUBTÍTULO / LEAD - Resumo Executivo
    ============================================ */
    .subtitle-corporate {
        font-size: 1.25rem !important; /* 20px */
        line-height: 1.6 !important;
        font-weight: 400 !important;
        color: var(--color-slate-600) !important;
        margin-bottom: 32px !important;
        padding-bottom: 32px !important;
        border-bottom: 1px solid var(--color-slate-200) !important;
    }
    
    .dark .subtitle-corporate {
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    
    /* ============================================
       METADADOS DO AUTOR
    ============================================ */
    .author-meta {
        font-size: 0.875rem; /* 14px */
        color: var(--color-slate-500);
    }
    
    /* ============================================
       CORPO DO ARTIGO
    ============================================ */
    .article-corporate p {
        font-size: 1.0625rem !important; /* 17px - Meio termo perfeito */
        line-height: 1.65 !important;    /* ~28px de altura */
        color: var(--color-slate-700) !important;
        margin-bottom: 1.5em !important;
        text-align: left !important;
    }
    
    .dark .article-corporate p {
        color: #cbd5e1 !important; /* Slate 300 */
    }
    
    /* ============================================
       HEADINGS NO CORPO
    ============================================ */
    .article-corporate h2 {
        font-size: 1.5rem !important; /* 24px */
        line-height: 1.3 !important;
        font-weight: 700 !important;
        color: var(--color-slate-900) !important;
        margin-top: 48px !important;
        margin-bottom: 24px !important;
        padding-bottom: 16px !important;
        border-bottom: 2px solid var(--color-slate-200) !important;
        letter-spacing: -0.015em !important;
    }
    
    .dark .article-corporate h2 {
        color: #f1f5f9 !important;
        border-bottom-color: #334155 !important;
    }
    
    .article-corporate h3 {
        font-size: 1.25rem !important; /* 20px */
        line-height: 1.4 !important;
        font-weight: 600 !important;
        color: var(--color-slate-800) !important;
        margin-top: 32px !important;
        margin-bottom: 16px !important;
        padding-left: 16px !important;
        border-left: 3px solid var(--color-primary) !important;
    }
    
    .dark .article-corporate h3 {
        color: #e2e8f0 !important;
    }
    
    /* ============================================
       LINKS - Roxo com underline no hover
    ============================================ */
    .article-corporate a {
        color: var(--color-primary) !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        border-bottom: 1px solid transparent !important;
        transition: border-color 0.2s ease !important;
    }
    
    .article-corporate a:hover {
        border-bottom-color: var(--color-primary) !important;
    }
    
    /* ============================================
       STRONG / BOLD - Destaque sutil
    ============================================ */
    .article-corporate strong {
        font-weight: 600 !important;
        color: var(--color-slate-900) !important;
    }
    
    .dark .article-corporate strong {
        color: #f8fafc !important;
    }
    
    /* ============================================
       ITÁLICO - Termos técnicos
    ============================================ */
    .article-corporate em {
        font-style: italic !important;
        color: var(--color-slate-600) !important;
    }
    
    .dark .article-corporate em {
        color: #a78bfa !important;
    }
    
    /* ============================================
       LISTAS - Profissional e Clean
    ============================================ */
    .article-corporate ul,
    .article-corporate ol {
        margin: 24px 0 !important;
        padding-left: 24px !important;
    }
    
    .article-corporate li {
        font-size: 1.0625rem !important;
        line-height: 1.65 !important;
        color: var(--color-slate-700) !important;
        margin-bottom: 12px !important;
    }
    
    .dark .article-corporate li {
        color: #cbd5e1 !important;
    }
    
    .article-corporate ul li::marker {
        color: var(--color-primary) !important;
    }
    
    .article-corporate ol li::marker {
        color: var(--color-primary) !important;
        font-weight: 600 !important;
    }
    
    /* ============================================
       BLOCKQUOTE - Dados/Frases de Especialistas
    ============================================ */
    .article-corporate blockquote {
        margin: 32px 0 !important;
        padding: 24px 24px 24px 28px !important;
        border-left: 4px solid var(--color-primary) !important;
        background-color: var(--color-slate-50) !important;
        border-radius: 0 8px 8px 0 !important;
        font-size: 1.125rem !important; /* 18px */
        font-style: normal !important;
        color: var(--color-slate-800) !important;
    }
    
    .dark .article-corporate blockquote {
        background-color: rgba(124, 58, 237, 0.08) !important;
        color: #e2e8f0 !important;
    }
    
    /* ============================================
       IMAGENS
    ============================================ */
    .article-corporate img {
        border-radius: 12px !important;
        margin: 32px 0 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    }
    
    /* ============================================
       CODE / CÓDIGO
    ============================================ */
    .article-corporate code {
        font-family: 'JetBrains Mono', 'Fira Code', monospace !important;
        font-size: 0.875rem !important;
        background-color: var(--color-slate-100) !important;
        color: var(--color-primary-dark) !important;
        padding: 2px 8px !important;
        border-radius: 4px !important;
    }
    
    .dark .article-corporate code {
        background-color: #1e293b !important;
        color: #a78bfa !important;
    }
    
    .article-corporate pre {
        background-color: var(--color-slate-900) !important;
        border-radius: 8px !important;
        padding: 24px !important;
        overflow-x: auto !important;
        margin: 24px 0 !important;
    }
    
    .article-corporate pre code {
        background: transparent !important;
        color: #e2e8f0 !important;
        padding: 0 !important;
    }
    
    /* ============================================
       TABELAS
    ============================================ */
    .article-corporate table {
        width: 100% !important;
        margin: 32px 0 !important;
        border-collapse: collapse !important;
        font-size: 0.9375rem !important;
    }
    
    .article-corporate th {
        background-color: var(--color-slate-100) !important;
        color: var(--color-slate-900) !important;
        font-weight: 600 !important;
        text-align: left !important;
        padding: 12px 16px !important;
        border-bottom: 2px solid var(--color-slate-200) !important;
    }
    
    .article-corporate td {
        padding: 12px 16px !important;
        border-bottom: 1px solid var(--color-slate-200) !important;
        color: var(--color-slate-700) !important;
    }
    
    .dark .article-corporate th {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
        border-bottom-color: #334155 !important;
    }
    
    .dark .article-corporate td {
        border-bottom-color: #334155 !important;
        color: #cbd5e1 !important;
    }
    
    /* ============================================
       HR / SEPARADORES
    ============================================ */
    .article-corporate hr {
        border: none !important;
        height: 1px !important;
        background-color: var(--color-slate-200) !important;
        margin: 48px 0 !important;
    }
    
    .dark .article-corporate hr {
        background-color: #334155 !important;
    }
    
    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 640px) {
        .article-corporate {
            padding: 0 4px;
        }
        
        .article-corporate p,
        .article-corporate li {
            font-size: 1rem !important; /* 16px mobile */
        }
        
        .article-corporate h2 {
            font-size: 1.25rem !important;
            margin-top: 32px !important;
        }
        
        .article-corporate h3 {
            font-size: 1.125rem !important;
        }
        
        .article-corporate blockquote {
            padding: 16px 16px 16px 20px !important;
            font-size: 1rem !important;
        }
    }
</style>
<?php include __DIR__ . '/includes/components/header.php'; ?>

<!-- Progress Bar de Leitura -->
<div id="progress-bar" class="fixed top-0 left-0 h-1 bg-purple-600 z-50 w-0 transition-all duration-100"></div>

<!-- Article Header -->
<header class="pt-24 pb-12 lg:pt-32 lg:pb-20 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 transition-colors duration-500 relative overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-purple-600/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        
        <!-- Breadcrumb Visual -->
        <nav class="flex justify-center text-xs md:text-sm text-slate-500 mb-8 font-medium animate-[fadeIn_0.4s_ease-out]" aria-label="Breadcrumb">
          <ol class="inline-flex items-center space-x-1 md:space-x-3 bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm px-4 py-2 rounded-full border border-slate-100 dark:border-slate-700/50">
            <li>
              <a href="/" class="hover:text-purple-600 transition-colors flex items-center gap-1">
                 <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
              </a>
            </li>
            <li><span class="text-slate-300">/</span></li>
            <li><a href="/blog.php" class="hover:text-purple-600 transition-colors">Blog</a></li>
            <?php if(!empty($post['category_slug'])): ?>
                <li><span class="text-slate-300">/</span></li>
                <li>
                    <a href="/blog/<?php echo $post['category_slug']; ?>" class="hover:text-purple-600 transition-colors text-purple-600 font-bold">
                        <?php echo htmlspecialchars($post['category_title']); ?>
                    </a>
                </li>
            <?php endif; ?>
            <li><span class="text-slate-300">/</span></li>
            <li class="text-slate-400 truncate max-w-[150px] md:max-w-[300px]" aria-current="page">
                <?php echo htmlspecialchars($post['title']); ?>
            </li>
          </ol>
        </nav>

        <!-- Date & Highlight Badge -->
        <div class="flex items-center justify-center gap-4 mb-6 opacity-0 animate-[fadeIn_0.6s_ease-out_forwards]">
            <?php if(!empty($post['main_tag'])): ?>
            <span class="badge-corporate animate-pulse">
                <?php echo htmlspecialchars($post['main_tag']); ?>
            </span>
            <?php endif; ?>
            <span class="text-slate-400 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <?php echo formatDatePtBr($post['created_at']); ?>
            </span>
            <?php if(!empty($post['reading_time'])): ?>
            <span class="text-slate-400 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?php echo $post['reading_time']; ?> min de leitura
            </span>
            <?php endif; ?>
        </div>

        <!-- Title -->
        <h1 class="title-corporate opacity-0 animate-[fadeIn_0.6s_0.2s_ease-out_forwards]">
            <?php echo htmlspecialchars($post['title']); ?>
        </h1>

        <!-- Subtitle -->
        <?php if(!empty($post['subtitle'])): ?>
        <p class="subtitle-corporate opacity-0 animate-[fadeIn_0.6s_0.4s_ease-out_forwards]">
            <?php echo htmlspecialchars($post['subtitle']); ?>
        </p>
        <?php endif; ?>

        <!-- Author & Share -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6 opacity-0 animate-[fadeIn_0.6s_0.6s_ease-out_forwards]">
            
            <!-- Author Card -->
            <div class="flex items-center gap-4 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm px-5 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-lg">
                <div class="w-14 h-14 rounded-full overflow-hidden border-3 border-purple-500 shadow-lg shadow-purple-500/20 ring-2 ring-white dark:ring-slate-800">
                    <img src="/public/images/author-specialist.png" 
                         alt="Especialista em Carreira - Equipe CV Pro" 
                         title="Especialista em Carreira - Equipe CV Pro"
                         class="w-full h-full object-cover"
                         onerror="this.src='https://ui-avatars.com/api/?name=CV+Pro&background=7c3aed&color=fff&size=128&bold=true'">
                </div>
                <div class="text-left">
                    <p class="text-base font-bold text-slate-900 dark:text-white">Equipe CV Pro</p>
                    <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Especialistas em Carreira</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">📖 <?php echo ceil(str_word_count(strip_tags($post['content'])) / 200); ?> min de leitura</p>
                </div>
            </div>
            
            <!-- Share Buttons -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 font-medium mr-1 hidden sm:block">Compartilhar:</span>
                
                <!-- WhatsApp -->
                <a href="https://wa.me/?text=<?php echo urlencode($post['title'] . ' - ' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/blog/' . $post['slug']); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="group w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/30 hover:shadow-green-500/50 hover:scale-110 transition-all"
                   title="Compartilhar no WhatsApp">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </a>
                
                <!-- LinkedIn -->
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/blog/' . $post['slug']); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="group w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-110 transition-all"
                   title="Compartilhar no LinkedIn">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </a>
                
                <!-- Twitter/X -->
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($post['title']); ?>&url=<?php echo urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/blog/' . $post['slug']); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="group w-10 h-10 rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center shadow-lg shadow-slate-500/30 hover:shadow-slate-500/50 hover:scale-110 transition-all"
                   title="Compartilhar no X (Twitter)">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </a>
                
                <!-- Facebook -->
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/blog/' . $post['slug']); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="group w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-110 transition-all"
                   title="Compartilhar no Facebook">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                
                <!-- Copy Link -->
                <button onclick="copyArticleLink()" 
                        class="group w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 hover:scale-110 transition-all"
                        title="Copiar link">
                    <svg id="copy-icon" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                    </svg>
                    <svg id="check-icon" class="w-5 h-5 text-white hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            </div>
        </div>

    </div>
</header>

<!-- Cover Image -->
<?php if(!empty($post['cover_image'])): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-12 relative z-20 opacity-0 animate-[fadeIn_0.8s_0.8s_ease-out_forwards]">
    <figure>
        <div class="rounded-2xl overflow-hidden shadow-2xl border-4 border-white dark:border-slate-800 aspect-video">
            <img src="<?php echo htmlspecialchars($post['cover_image']); ?>" 
                 alt="<?php echo htmlspecialchars($post['cover_caption'] ?? $post['title']); ?>" 
                 title="<?php echo htmlspecialchars($post['title']); ?>"
                 class="w-full h-full object-cover">
        </div>
        <?php if(!empty($post['cover_caption'])): ?>
        <figcaption class="text-center text-sm text-slate-500 dark:text-slate-400 mt-4 italic">
            <?php echo htmlspecialchars($post['cover_caption']); ?>
        </figcaption>
        <?php endif; ?>
    </figure>
</div>
<?php endif; ?>

<!-- Main Content -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    
    <!-- AdSense: Topo do Artigo -->
    <?php if($adsense_enabled): echo renderAdBlock($adsense_client, $adsense_slot_top); endif; ?>
    
    <!-- Article Content Container - Corporate B2B -->
    <div class="article-corporate">
        <!-- Article Body -->
        <article>
            <?php 
            // --- AUTO SEO IMAGES ENGINE ---
            // Processa o conteúdo para garantir que todas as imagens tenham ALT e TITLE
            $content = $post['content'];
            
            if (!empty($content) && class_exists('DOMDocument')) {
                // Suprime warnings de HTML malformado
                libxml_use_internal_errors(true);
                
                $dom = new DOMDocument();
                // Hack para UTF-8: Substituindo mb_convert_encoding depreciado
                $dom->loadHTML(mb_encode_numericentity($content, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                
                $images = $dom->getElementsByTagName('img');
                $postTitleClean = $post['title'];
                
                foreach ($images as $img) {
                    // 1. Título: Se vazio, usa o título do post
                    $currentTitle = $img->getAttribute('title');
                    if (empty($currentTitle) || $currentTitle === '/') {
                        $img->setAttribute('title', $postTitleClean);
                    }
                    
                    // 2. Alt: Se vazio, usa o título do post
                    $currentAlt = $img->getAttribute('alt');
                    if (empty($currentAlt)) {
                        $img->setAttribute('alt', $postTitleClean);
                    }
                    
                    // 3. Performance: Lazy Load automático
                    if (!$img->hasAttribute('loading')) {
                        $img->setAttribute('loading', 'lazy');
                    }
                    
                    // 4. Acessibilidade/SEO: Decodifica entidades HTML nos atributos para evitar &quot;
                    // (O setAttribute faz escape automático, então precisamos passar limpo)
                }
                
                // Salva HTML corrigido
                echo $dom->saveHTML();
                libxml_clear_errors();
            } else {
                // Fallback caso DOMDocument não exista
                echo $content;
            }
            ?>
        </article>
    </div>
    
    <!-- AdSense: Final do Artigo -->
    <?php if($adsense_enabled): echo renderAdBlock($adsense_client, $adsense_slot_bottom); endif; ?>

    <!-- Tags Secundárias (Tópicos Relacionados) -->
    <?php if(!empty($post['tags'])): ?>
    <div class="max-w-4xl mx-auto mt-12 pt-8 border-t border-slate-200 dark:border-slate-800">
        <p class="text-sm font-bold text-slate-400 mb-4 uppercase tracking-wider">Tópicos Relacionados</p>
        <div class="flex flex-wrap gap-2">
            <?php 
            $tags = array_map('trim', explode(',', $post['tags']));
            foreach($tags as $tag): 
                if(empty($tag)) continue;
            ?>
                <a href="/blog.php?q=<?php echo urlencode($tag); ?>" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm hover:bg-purple-100 dark:hover:bg-purple-900/30 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                    #<?php echo htmlspecialchars($tag); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA Box -->
    <div class="mt-16 bg-gradient-to-br from-slate-900 to-purple-900 rounded-3xl p-8 md:p-12 text-center text-white shadow-2xl relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -mr-16 -mt-16 group-hover:bg-white/10 transition-all duration-700"></div>
        
        <h3 class="text-2xl md:text-3xl font-black mb-4 relative z-10">Gostou das dicas?</h3>
        <p class="text-slate-300 mb-8 max-w-lg mx-auto relative z-10">Aplique essas estratégias agora mesmo criando um currículo profissional em minutos com nossa IA.</p>
        
        <a href="/register.php" class="inline-flex items-center gap-2 bg-white text-slate-900 px-8 py-4 rounded-full font-bold hover:bg-cyan-50 transition-all transform hover:scale-105 shadow-lg relative z-10">
            Criar Currículo Grátis
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>

</main>

<!-- Auto-Generated Breadcrumb Schema (SEO Structure) -->
<?php
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$bcItems = [
    ["@type" => "ListItem", "position" => 1, "name" => "Início", "item" => $baseUrl . "/"],
    ["@type" => "ListItem", "position" => 2, "name" => "Blog", "item" => $baseUrl . "/blog.php"]
];

if (!empty($post['category_slug'])) {
    $bcItems[] = ["@type" => "ListItem", "position" => 3, "name" => $post['category_title'], "item" => $baseUrl . "/blog/" . $post['category_slug']];
    $pos = 4;
} else {
    $pos = 3;
}
// Último item (Post atual)
$bcItems[] = ["@type" => "ListItem", "position" => $pos, "name" => $post['title']];

$bcSchema = ["@context" => "https://schema.org", "@type" => "BreadcrumbList", "itemListElement" => $bcItems];
?>
<script type="application/ld+json">
    <?php echo json_encode($bcSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<!-- Custom Schema Markup (Manual Override) -->
<?php if(!empty($post['schema_markup'])): ?>
<script type="application/ld+json">
    <?php echo $post['schema_markup']; ?>
</script>
<?php endif; ?>

<!-- Script Barra de Leitura e Share -->
<script>
// Barra de progresso de leitura
window.onscroll = function() {
  let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
  let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
  let scrolled = (winScroll / height) * 100;
  document.getElementById("progress-bar").style.width = scrolled + "%";
};

// Copiar link do artigo
function copyArticleLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        // Feedback visual
        const copyIcon = document.getElementById('copy-icon');
        const checkIcon = document.getElementById('check-icon');
        
        copyIcon.classList.add('hidden');
        checkIcon.classList.remove('hidden');
        
        setTimeout(() => {
            copyIcon.classList.remove('hidden');
            checkIcon.classList.add('hidden');
        }, 2000);
    }).catch(err => {
        // Fallback para navegadores antigos
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Link copiado!');
    });
}
</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
