<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

// --- AUTO-MIGRATION: Tabela de Buscas ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS search_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        term VARCHAR(255) NOT NULL,
        searched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_term (term)
    )");
} catch(PDOException $e) {}

// Search Logic
$search = trim($_GET['q'] ?? '');
$where = "status = 'published'";
$params = [];

if (!empty($search)) {
    $where .= " AND (title LIKE ? OR content LIKE ? OR subtitle LIKE ? OR tags LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    
    // Salva o termo de busca no log (apenas se tiver >= 2 caracteres)
    if (strlen($search) >= 2) {
        try {
            $logStmt = $pdo->prepare("INSERT INTO search_logs (term) VALUES (?)");
            $logStmt->execute([mb_strtolower($search)]);
        } catch(PDOException $e) {}
    }
}

// Fetch Categories (Silos) for Navigation & Schema
$categories = $pdo->query("SELECT * FROM blog_categories ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Posts
// Fetch Posts (Safe Mode)
try {
    // Tentativa padrão com Silos (JOIN)
    $stmt = $pdo->prepare("SELECT p.*, c.title as category_title, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE $where ORDER BY p.created_at DESC");
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback: Se der erro no JOIN (ex: migração não rodou), busca simples
    try {
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE status='published' ORDER BY created_at DESC");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e2) {
        $posts = []; // Zera se tudo falhar
    }
}


// SEO Overrides
$seo_title = "Blog de Carreira - Dicas e Estratégias | Currículo Vitae Pro";
$seo_desc = "Explore nosso blog para dicas de carreira, como fazer um currículo perfeito, preparação para entrevistas e novidades do mercado de trabalho.";

include __DIR__ . '/includes/components/header.php';
?>

<!-- Hero Section -->
<section class="relative pt-24 pb-12 lg:pt-32 lg:pb-20 bg-slate-50 dark:bg-slate-900 overflow-hidden border-b border-slate-200 dark:border-slate-800 transition-colors duration-500">
    <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 dark:opacity-10 mix-blend-soft-light pointer-events-none"></div>
    <div class="absolute top-[-10%] left-[-5%] w-[500px] h-[500px] bg-purple-600/20 rounded-full blur-[100px] opacity-60 dark:opacity-30 pointer-events-none"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <h1 class="text-4xl md:text-6xl font-black text-slate-900 dark:text-white tracking-tight mb-6">
            Blog & <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-500">Insights</span>
        </h1>
        <p class="text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed font-light">
            Estratégias comprovadas para acelerar sua carreira, conquistar recrutadores e dominar entrevistas.
        </p>

        <!-- Search Bar -->
        <div class="max-w-xl mx-auto">
            <form action="blog.php" method="GET" class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-purple-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" 
                       class="block w-full pl-11 pr-4 py-4 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-full text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all shadow-lg" 
                       placeholder="Buscar por temas, dicas ou palavras-chave...">
            </form>
        </div>
    </div>
</section>

<!-- Silo Navigation (Semantic Categories) -->
<nav class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 sticky top-0 z-30 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 overflow-x-auto">
        <div class="flex items-center gap-2 py-4">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mr-2 whitespace-nowrap">Explore:</span>
            <a href="/blog.php" class="px-4 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-bold hover:bg-purple-100 dark:hover:bg-purple-900/30 hover:text-purple-600 transition-colors whitespace-nowrap">
                Todas
            </a>
            <?php foreach($categories as $cat): ?>
                <a href="/blog/<?php echo $cat['slug']; ?>" class="px-4 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-sm font-medium hover:border-purple-300 dark:hover:border-purple-700 hover:text-purple-600 dark:hover:text-purple-400 transition-colors whitespace-nowrap flex items-center gap-2">
                    <?php if(!empty($cat['icon'])): ?>
                        <!-- Icon Placeholder based on name matches if needed, simple for now -->
                    <?php endif; ?>
                    <?php echo htmlspecialchars($cat['title']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<!-- Blog Grid -->
<section class="py-16 bg-white dark:bg-slate-900 min-h-screen transition-colors duration-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if (!empty($search)): ?>
            <div class="mb-8 items-center flex gap-2">
                <p class="text-slate-500 dark:text-slate-400">Resultados para: <strong class="text-slate-900 dark:text-white">"<?php echo htmlspecialchars($search); ?>"</strong></p>
                <a href="blog.php" class="text-sm font-bold text-purple-600 hover:text-purple-500 underline">Limpar busca</a>
            </div>
        <?php endif; ?>

        <?php if (count($posts) > 0): ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($posts as $post): ?>
                <article class="bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group border border-slate-100 dark:border-slate-700 h-full flex flex-col animate-[fadeIn_0.5s_ease-out]">
                    <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>" class="block relative overflow-hidden h-52">
                        <?php if($post['cover_image']): ?>
                            <img src="<?php echo htmlspecialchars($post['cover_image']); ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center text-white font-bold opacity-80">
                                CV Pro
                            </div>
                        <?php endif; ?>
                        
                        <!-- Badge -->
                        <span class="absolute top-4 left-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm px-3 py-1 rounded-lg text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider shadow-sm">
                            <?php echo htmlspecialchars($post['category_title'] ?? $post['main_tag'] ?? 'Geral'); ?>
                        </span>
                    </a>
                    
                    <div class="p-6 flex flex-col flex-grow">
                        <!-- Meta -->
                        <div class="text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider flex items-center gap-2">
                            <span><?php echo date('d M, Y', strtotime($post['created_at'])); ?></span>
                        </div>

                        <!-- Title -->
                        <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>" class="block mb-2">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-purple-600 transition-colors leading-tight">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </h2>
                        </a>

                        <!-- Subtitle (if any) -->
                         <?php if(!empty($post['subtitle'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-3 line-clamp-1">
                                <?php echo htmlspecialchars($post['subtitle']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Excerpt -->
                        <p class="text-slate-600 dark:text-slate-400 text-sm line-clamp-3 mb-6 flex-grow">
                            <?php echo htmlspecialchars($post['excerpt'] ?? strip_tags($post['content'])); ?>
                        </p>
                        
                        <!-- Link -->
                        <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>" class="text-purple-600 dark:text-purple-400 font-bold text-sm hover:underline inline-flex items-center gap-1 mt-auto group/link">
                            Ler artigo completo 
                            <svg class="w-4 h-4 transform group-hover/link:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State - Redesigned -->
            <div class="max-w-4xl mx-auto">
                <!-- Elegant Header -->
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-full mb-6">
                        <svg class="w-10 h-10 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-3">
                        Hmm, não encontramos "<span class="text-purple-600"><?php echo htmlspecialchars($search); ?></span>"
                    </h2>
                    <p class="text-lg text-slate-500 dark:text-slate-400 max-w-lg mx-auto leading-relaxed">
                        Que tal explorar nossos conteúdos mais populares? Temos dicas incríveis esperando por você.
                    </p>
                </div>

                <!-- Sugestões de Busca -->
                <div class="flex flex-wrap justify-center gap-2 mb-12">
                    <span class="text-sm text-slate-400 mr-2">Tente buscar:</span>
                    <?php 
                    $sugestoes = ['Currículo', 'Entrevista', 'LinkedIn', 'Soft Skills', 'Carreira'];
                    foreach($sugestoes as $sug): 
                    ?>
                    <a href="blog.php?q=<?php echo urlencode($sug); ?>" class="px-4 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm rounded-full hover:bg-purple-100 dark:hover:bg-purple-900/30 hover:text-purple-600 transition-all">
                        <?php echo $sug; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if(!empty($popularPosts)): ?>
                <!-- Posts Populares -->
                <div class="border-t border-slate-200 dark:border-slate-800 pt-12">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Artigos em Alta</h3>
                            <p class="text-sm text-slate-500">Os mais lidos pelos nossos visitantes</p>
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($popularPosts as $pop): ?>
                        <a href="/blog/<?php echo htmlspecialchars($pop['slug']); ?>" class="group block bg-slate-50 dark:bg-slate-800 rounded-xl p-5 border border-slate-100 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-700 hover:shadow-lg transition-all">
                            <div class="flex items-start gap-4">
                                <?php if(!empty($pop['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($pop['cover_image']); ?>" class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                <?php else: ?>
                                <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex-shrink-0"></div>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider"><?php echo htmlspecialchars($pop['main_tag'] ?? 'Dicas'); ?></span>
                                    <h4 class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-purple-600 transition-colors line-clamp-2 mt-1">
                                        <?php echo htmlspecialchars($pop['title']); ?>
                                    </h4>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- CTA Final -->
                <div class="text-center mt-12 pt-8 border-t border-slate-200 dark:border-slate-800">
                    <a href="blog.php" class="inline-flex items-center gap-2 px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-full font-bold hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Ver Todos os Artigos
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// SEO JSON-LD: Blog Hub & Navigation
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];

$blogSchema = [
    "@context" => "https://schema.org",
    "@type" => "Blog",
    "name" => "Blog Currículo Vitae Pro",
    "description" => $seo_desc,
    "url" => $baseUrl . "/blog.php",
    "publisher" => [
        "@type" => "Organization",
        "name" => "Currículo Vitae Pro",
        "logo" => ["@type" => "ImageObject", "url" => $baseUrl . "/public/images/logo.png"]
    ],
    "blogPost" => [] // Povoado abaixo
];

// Adiciona os posts recentes ao schema
foreach(array_slice($posts, 0, 10) as $p) {
    if (!empty($p['slug'])) {
        $blogSchema['blogPost'][] = [
            "@type" => "BlogPosting",
            "headline" => $p['title'],
            "url" => $baseUrl . "/blog/" . ($p['category_slug'] ?? 'geral') . "/" . $p['slug'],
            "datePublished" => date('c', strtotime($p['created_at']))
        ];
    }
}

// SiteNavigationElement para Silos
$navSchema = [
    "@context" => "https://schema.org",
    "@type" => "ItemList",
    "itemListElement" => []
];

foreach ($categories as $idx => $cat) {
    $navSchema['itemListElement'][] = [
        "@type" => "SiteNavigationElement",
        "position" => $idx + 1,
        "name" => $cat['title'],
        "description" => $cat['description'],
        "url" => $baseUrl . "/blog/" . $cat['slug']
    ];
}
?>
<script type="application/ld+json">
    <?php echo json_encode([$blogSchema, $navSchema], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
