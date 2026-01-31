<?php
// Silo Page (Category) - SEO Optimized
require_once __DIR__ . '/config/database.php';

// Se data não veio do include, tenta buscar
if(!isset($categoryData)) {
    $slug = $_GET['fallback_slug'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM blog_categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$categoryData) { header("Location: /blog"); exit; }
}

// Fetch Posts da Categoria
$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE category_id = ? AND status = 'published' ORDER BY created_at DESC");
$stmt->execute([$categoryData['id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$postCount = count($posts);

// Keywords baseadas na categoria
$categoryKeywords = [
    'curriculo' => 'criar currículo grátis, gerador de currículo ia, modelo curriculo ats, currículo gupy, editor de currículo pdf',
    'carreira' => 'dicas de carreira, crescimento profissional, desenvolvimento profissional, planejamento de carreira, sucesso profissional',
    'entrevista' => 'como se preparar para entrevista, perguntas entrevista emprego, entrevista de emprego dicas, entrevista online',
    'tecnologia' => 'tendências tecnologia RH, inteligência artificial recrutamento, ferramentas digitais carreira, tecnologia emprego'
];
$keywords = $categoryKeywords[$categoryData['slug']] ?? 'blog currículo, dicas carreira, emprego, profissional';

// SEO Meta Tags - OTIMIZADOS
$seo_title = $categoryData['title'] . " | Guia Completo " . date('Y') . " - Blog Currículo Vitae Pro";
$seo_desc = "Explore todos os artigos sobre " . $categoryData['title'] . ". " . $categoryData['description'] . " Dicas práticas, estratégias e tutoriais atualizados para " . date('Y') . ". " . $postCount . " artigos disponíveis.";

// Garante mínimo de 120 caracteres na descrição
if(strlen($seo_desc) < 120) {
    $seo_desc .= " Aprenda técnicas comprovadas de especialistas para impulsionar sua carreira e conquistar a vaga dos seus sonhos.";
}

// Garante máximo de 160 caracteres
$seo_desc = substr($seo_desc, 0, 160);

// Protocol e URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
$currentUrl = $baseUrl . "/blog/" . $categoryData['slug'];

include __DIR__ . '/includes/components/header.php';
?>

<!-- Canonical e Meta Tags Extras -->
<link rel="canonical" href="<?php echo $currentUrl; ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="keywords" content="<?php echo htmlspecialchars($keywords); ?>">
<meta name="author" content="Currículo Vitae Pro">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($seo_desc); ?>">
<meta property="og:url" content="<?php echo $currentUrl; ?>">
<meta property="og:site_name" content="Currículo Vitae Pro">
<meta name="twitter:card" content="summary_large_image">

<div class="bg-slate-50 dark:bg-slate-900 min-h-screen py-20 px-4">
    <div class="max-w-7xl mx-auto">
        
        <!-- Breadcrumbs Visuais -->
        <nav class="breadcrumbs text-sm text-slate-500 dark:text-slate-400 mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="/" itemprop="item" class="hover:text-purple-600 transition-colors" title="Ir para página inicial">
                        <span itemprop="name">Início</span>
                    </a>
                    <meta itemprop="position" content="1" />
                </li>
                <li class="text-slate-400">/</li>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="/blog" itemprop="item" class="hover:text-purple-600 transition-colors" title="Ver todos os artigos do blog">
                        <span itemprop="name">Blog</span>
                    </a>
                    <meta itemprop="position" content="2" />
                </li>
                <li class="text-slate-400">/</li>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name" class="text-purple-600 font-semibold"><?php echo htmlspecialchars($categoryData['title']); ?></span>
                    <meta itemprop="position" content="3" />
                </li>
            </ol>
        </nav>
        
        <!-- Silo Header -->
        <header class="text-center mb-16">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 mb-6 shadow-lg shadow-purple-500/20">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white mb-4 tracking-tight"><?php echo htmlspecialchars($categoryData['title']); ?></h1>
            <p class="text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto mb-6"><?php echo htmlspecialchars($categoryData['description']); ?></p>
            
            <!-- Stats Badge -->
            <div class="inline-flex items-center gap-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-4 py-2 rounded-full text-sm font-medium">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <?php echo $postCount; ?> artigo<?php echo $postCount !== 1 ? 's' : ''; ?> disponíve<?php echo $postCount !== 1 ? 'is' : 'l'; ?>
            </div>
        </header>
        
        <!-- Introdução SEO -->
        <section class="prose prose-lg dark:prose-invert max-w-3xl mx-auto mb-16 text-center">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-200">O que você vai encontrar em <?php echo htmlspecialchars($categoryData['title']); ?></h2>
            <p class="text-slate-600 dark:text-slate-400">
                Nesta seção reunimos os melhores conteúdos sobre <strong><?php echo htmlspecialchars($categoryData['title']); ?></strong>. 
                Cada artigo foi cuidadosamente elaborado para ajudar você a alcançar seus objetivos profissionais.
                Explore, aprenda e aplique as estratégias que vão transformar sua carreira.
            </p>
        </section>
        
        <!-- Grid de Artigos (main content) -->
        <main>
            <h2 class="sr-only">Artigos sobre <?php echo htmlspecialchars($categoryData['title']); ?></h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($posts as $index => $post): ?>
               <article class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group border border-slate-200 dark:border-slate-700 h-full flex flex-col" itemscope itemtype="https://schema.org/Article">
                    <a href="/blog/<?php echo $categoryData['slug']; ?>/<?php echo $post['slug']; ?>" class="block relative overflow-hidden h-48" title="<?php echo htmlspecialchars($post['title']); ?>">
                        <?php if($post['cover_image']): ?>
                            <img src="<?php echo htmlspecialchars($post['cover_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" title="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" itemprop="image">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center text-slate-400">CV Pro</div>
                        <?php endif; ?>
                        <!-- Article Number Badge -->
                        <div class="absolute top-3 left-3 bg-purple-600 text-white text-xs font-bold px-2 py-1 rounded-lg">
                            #<?php echo $index + 1; ?>
                        </div>
                    </a>
                    <div class="p-6 flex flex-col flex-grow">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 group-hover:text-purple-600 transition-colors" itemprop="headline">
                            <a href="/blog/<?php echo $categoryData['slug']; ?>/<?php echo $post['slug']; ?>" itemprop="url" title="Ler artigo: <?php echo htmlspecialchars($post['title']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                        </h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm line-clamp-3 mb-4 flex-grow" itemprop="description">
                            <?php echo htmlspecialchars($post['excerpt']); ?>
                        </p>
                        <div class="flex items-center justify-between mt-auto">
                            <time datetime="<?php echo date('Y-m-d', strtotime($post['created_at'])); ?>" class="text-xs text-slate-500" itemprop="datePublished">
                                <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                            </time>
                            <a href="/blog/<?php echo $categoryData['slug']; ?>/<?php echo $post['slug']; ?>" class="text-purple-600 font-bold text-sm uppercase tracking-wide hover:text-purple-700" title="Ler artigo completo">Ler &rarr;</a>
                        </div>
                    </div>
               </article>
                <?php endforeach; ?>
            </div>
        </main>
        
        <?php if(empty($posts)): ?>
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-6">
                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-700 dark:text-slate-300 mb-2">Nenhum artigo ainda</h3>
                <p class="text-slate-500">Estamos preparando conteúdos incríveis para esta categoria. Volte em breve!</p>
            </div>
        <?php endif; ?>

        <!-- CTA Section -->
        <section class="mt-20 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-3xl p-8 lg:p-12 text-center text-white">
            <h2 class="text-2xl lg:text-3xl font-bold mb-4">Crie seu Currículo Profissional Agora</h2>
            <p class="text-purple-100 mb-6 max-w-2xl mx-auto">
                Aplique o que você aprendeu nos nossos artigos e crie um currículo otimizado para ATS em minutos.
            </p>
            <a href="/register" class="inline-flex items-center gap-2 bg-white text-purple-700 font-bold px-8 py-4 rounded-xl hover:bg-purple-50 transition-colors shadow-lg" title="Criar seu currículo profissional grátis">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Criar Currículo Grátis
            </a>
        </section>

        <div class="mt-12 text-center">
             <a href="/blog" class="text-slate-500 hover:text-purple-600 font-bold flex items-center justify-center gap-2" title="Voltar para o Blog Geral">
                 &larr; Voltar para o Blog Geral
             </a>
        </div>
    </div>
</div>

<!-- SEO STRUCTURED DATA (JSON-LD) -->
<?php
// 1. Breadcrumb Schema
$bcSchema = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "Início", "item" => $baseUrl],
        ["@type" => "ListItem", "position" => 2, "name" => "Blog", "item" => $baseUrl . "/blog"],
        ["@type" => "ListItem", "position" => 3, "name" => $categoryData['title'], "item" => $currentUrl]
    ]
];

// 2. CollectionPage Schema (Lista de Posts)
$collectionSchema = [
    "@context" => "https://schema.org",
    "@type" => "CollectionPage",
    "name" => $categoryData['title'],
    "headline" => $categoryData['title'] . " - Artigos e Guias Completos",
    "description" => $seo_desc,
    "url" => $currentUrl,
    "inLanguage" => "pt-BR",
    "isPartOf" => [
        "@type" => "Blog",
        "name" => "Blog Currículo Vitae Pro",
        "url" => $baseUrl . "/blog"
    ],
    "publisher" => [
        "@type" => "Organization",
        "name" => "Currículo Vitae Pro",
        "url" => $baseUrl,
        "logo" => [
            "@type" => "ImageObject",
            "url" => $baseUrl . "/public/images/logo.png"
        ]
    ],
    "mainEntity" => [
        "@type" => "ItemList",
        "numberOfItems" => $postCount,
        "itemListElement" => []
    ]
];

foreach($posts as $i => $p) {
    $collectionSchema['mainEntity']['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => $i + 1,
        "item" => [
            "@type" => "Article",
            "headline" => $p['title'],
            "description" => $p['excerpt'],
            "url" => $baseUrl . "/blog/" . $categoryData['slug'] . "/" . $p['slug'],
            "datePublished" => date('c', strtotime($p['created_at']))
        ]
    ];
}

// 3. WebPage Schema
$webPageSchema = [
    "@context" => "https://schema.org",
    "@type" => "WebPage",
    "name" => $seo_title,
    "description" => $seo_desc,
    "url" => $currentUrl,
    "inLanguage" => "pt-BR",
    "isPartOf" => [
        "@type" => "WebSite",
        "name" => "Currículo Vitae Pro",
        "url" => $baseUrl
    ],
    "about" => [
        "@type" => "Thing",
        "name" => $categoryData['title']
    ],
    "breadcrumb" => $bcSchema
];
?>
<script type="application/ld+json">
    <?php echo json_encode([$bcSchema, $collectionSchema, $webPageSchema], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
