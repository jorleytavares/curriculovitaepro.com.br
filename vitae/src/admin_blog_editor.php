<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Segurança
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ------------------------------------------
// MIGRATION ON-THE-FLY (EMERGÊNCIA PARA AMBIENTE SEM CLI)
// ------------------------------------------
// --- MIGRAÇÃO AUTOMÁTICA DE BANCO DE DADOS (Robustez) ---
try {
    // Verifica colunas existentes
    $stmt = $pdo->query("SHOW COLUMNS FROM blog_posts");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Adiciona apenas se não existir
    if (!in_array('subtitle', $columns)) {
        $pdo->exec("ALTER TABLE blog_posts ADD COLUMN subtitle VARCHAR(255) NULL");
    }
    if (!in_array('main_tag', $columns)) {
        $pdo->exec("ALTER TABLE blog_posts ADD COLUMN main_tag VARCHAR(50) DEFAULT 'Geral'");
    }
    if (!in_array('tags', $columns)) {
        $pdo->exec("ALTER TABLE blog_posts ADD COLUMN tags VARCHAR(255) NULL");
    }
    if (!in_array('schema_markup', $columns)) {
        $pdo->exec("ALTER TABLE blog_posts ADD COLUMN schema_markup LONGTEXT NULL");
    }
    // Legenda da imagem de destaque
    if (!in_array('cover_caption', $columns)) {
        $pdo->exec("ALTER TABLE blog_posts ADD COLUMN cover_caption VARCHAR(255) NULL AFTER cover_image");
    }
} catch (PDOException $e) {
    // Silencioso para produção, mas vital
}
// ------------------------------------------


$postId = $_GET['id'] ?? null;
$post = null;

// Carregar Categorias (Silos)
$categories = $pdo->query("SELECT * FROM blog_categories ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Carregar post se for edição
if ($postId) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    if (!$post) die("Post não encontrado");
}

// Salvar (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'] ?? ''; 
    $main_tag = $_POST['main_tag'] ?? 'Geral'; 
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null; // SILO
    $tags = $_POST['tags'] ?? ''; // Novo: Tags secundárias (texto livre)
    $slug = $_POST['slug'] ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $excerpt = $_POST['excerpt'];
    $content = $_POST['content'];
    $status = $_POST['status'];
    $schema_markup = $_POST['schema_markup'] ?? ''; 
    $cover_image = $post['cover_image'] ?? '';
    $cover_caption = $_POST['cover_caption'] ?? ''; // Legenda da imagem

    // --- VALIDAÇÃO DE DUPLICIDADE (SLUG ÚNICO) ---
    // Verifica se já existe outro post com o mesmo slug (excluindo o post atual em caso de edição)
    $checkSql = "SELECT id FROM blog_posts WHERE slug = ?";
    $checkParams = [$slug];
    if ($postId) {
        $checkSql .= " AND id != ?";
        $checkParams[] = $postId;
    }
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($checkParams);
    
    if ($checkStmt->fetch()) {
        // Slug duplicado encontrado - Gerar um sufixo único
        $originalSlug = $slug;
        $counter = 2;
        do {
            $slug = $originalSlug . '-' . $counter;
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$slug, $postId ?? 0]);
            $counter++;
        } while ($checkStmt->fetch() && $counter < 100); // Limite de segurança
    }

    // --- AUTO-GERAÇÃO DE SEO (Fallback Inteligente) ---
    // 1. Meta Description Automática (se vazio)
    if (empty($excerpt)) {
        // Remove tags HTML e quebras de linha excessivas
        $plainText = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
        // Corta em 160 caracteres respeitando palavras
        if (mb_strlen($plainText) > 160) {
            $excerpt = mb_substr($plainText, 0, 157) . '...';
        } else {
            $excerpt = $plainText;
        }
    }

    // 2. Schema Markup Automático (se vazio)
    if (empty($schema_markup)) {
        // Determina URL base (aproximada para backend)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $currentUrl = "$protocol://$host";

        $schemaData = [
            "@context" => "https://schema.org",
            "@type" => "BlogPosting",
            "headline" => $title,
            "alternativeHeadline" => $subtitle,
            "description" => $excerpt,
            "image" => $currentUrl . ($cover_image ?: '/public/images/default-blog.jpg'),
            "author" => [
                "@type" => "Organization",
                "name" => "Currículo Vitae Pro"
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => "Currículo Vitae Pro",
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => "$currentUrl/public/images/logo.png"
                ]
            ],
            "datePublished" => date('c'),
            "dateModified" => date('c'),
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => "$currentUrl/blog/$slug"
            ]
        ];
        $schema_markup = json_encode($schemaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // Prioridade 1: Imagem Existente (Selecionada da Galeria ou Mantida)
    if (!empty($_POST['existing_cover_image'])) {
        $cover_image = $_POST['existing_cover_image'];
    }

    // Upload e Processamento de Imagem (AVIF/WebP) -- Se houver novo upload, sobrescreve
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/public/uploads/blog/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $tmpName = $_FILES['cover_image']['tmp_name'];
        
        // RENOMEADOR INTELIGENTE (SEO)
        // Usa o slug do post como nome do arquivo para melhor indexação
        // Ex: de 'IMG_2024.jpg' para 'titulo-do-meu-post-a12b3c.avif'
        $baseName = !empty($slug) ? $slug : 'imagem-blog';
        // Limita tamanho para evitar erros de sistema de arquivos
        $baseName = substr($baseName, 0, 80); 
        $fileHash = substr(uniqid(), -6); // Sufixo curto único

        // Tenta processar com GD (Apenas se a extensão estiver disponível)
        $info = getimagesize($tmpName);
        if (extension_loaded('gd') && $info !== false) {
            $mime = $info['mime'];
            
            // Carregar imagem
            switch ($mime) {
                case 'image/jpeg': $image = imagecreatefromjpeg($tmpName); break;
                case 'image/png':  $image = imagecreatefrompng($tmpName); break;
                case 'image/webp': $image = imagecreatefromwebp($tmpName); break;
                default: $image = null; 
            }

            if ($image) {
                // Redimensionar se muito grande (Max Width 1920px)
                $width = imagesx($image);
                $height = imagesy($image);
                $maxWidth = 1920;
                
                if ($width > $maxWidth) {
                    $newHeight = floor($height * ($maxWidth / $width));
                    $newImage = imagecreatetruecolor($maxWidth, $newHeight);
                    
                    // Manter transparência para PNG/WebP
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    
                    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $newImage;
                }

                // Converter e Salvar
                $targetFile = '';
                $webPath = '';

                // Tenta AVIF (Prioridade)
                if (function_exists('imageavif')) {
                    $filename = $baseName . '-' . $fileHash . '.avif';
                    $targetFile = $uploadDir . $filename;
                    
                    if (imageavif($image, $targetFile, 60)) { // Qualidade 60 (bom balanço)
                        $cover_image = '/public/uploads/blog/' . $filename;
                    }
                } 
                
                // Se AVIF falhou ou não existe, tenta WebP (Fallback Moderno)
                if (empty($cover_image) && function_exists('imagewebp')) {
                    $filename = $baseName . '-' . $fileHash . '.webp';
                    $targetFile = $uploadDir . $filename;
                    
                    // Habilita alpha blending para salvar transparência corretamente
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);

                    if (imagewebp($image, $targetFile, 80)) { // Qualidade 80
                        $cover_image = '/public/uploads/blog/' . $filename;
                    }
                }

                imagedestroy($image);
            }
        }

        // Fallback final: Mover arquivo original mantendo extensão mas renomeando
        if (empty($cover_image)) {
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $filename = $baseName . '-' . $fileHash . '.' . $ext;
            if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                $cover_image = '/public/uploads/blog/' . $filename;
            }
        }
    }

    if ($postId) {
        // Update
        $stmt = $pdo->prepare("UPDATE blog_posts SET title=?, subtitle=?, main_tag=?, category_id=?, tags=?, slug=?, excerpt=?, content=?, cover_image=?, cover_caption=?, status=?, schema_markup=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $subtitle, $main_tag, $category_id, $tags, $slug, $excerpt, $content, $cover_image, $cover_caption, $status, $schema_markup, $postId]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO blog_posts (title, subtitle, main_tag, category_id, tags, slug, excerpt, content, cover_image, cover_caption, status, schema_markup, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $subtitle, $main_tag, $category_id, $tags, $slug, $excerpt, $content, $cover_image, $cover_caption, $status, $schema_markup, $_SESSION['user_id']]);
        $postId = $pdo->lastInsertId();
    }
    
    header("Location: admin_blog.php?msg=saved");
    exit;
}

include __DIR__ . '/includes/components/header.php';
?>

<!-- Quill JS Theme -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
    /* Customizando Quill para Tema Escuro/Claro */
    .ql-toolbar.ql-snow {
        border-color: #e2e8f0;
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        background: #f8fafc;
    }
    .dark .ql-toolbar.ql-snow {
        border-color: #334155;
        background: #1e293b;
    }
    .dark .ql-stroke {
        stroke: #94a3b8 !important;
    }
    .dark .ql-fill {
        fill: #94a3b8 !important;
    }
    .dark .ql-picker {
        color: #94a3b8 !important;
    }
    
    .ql-container.ql-snow {
        border-color: #e2e8f0;
        border-bottom-left-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
        font-family: 'Merriweather', serif; /* Fonte boa para leitura */
    }
    .dark .ql-container.ql-snow {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }
    .ql-editor {
        min-height: 400px;
        font-size: 1.125rem;
        line-height: 1.8;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 pb-24">
    <form method="POST" enctype="multipart/form-data">
        
        <!-- Top Toolbar Sticky -->
        <div class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg border-b border-slate-200 dark:border-slate-800 transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="admin_blog.php" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 transition-colors" title="Voltar">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800 dark:text-white">
                            <?php echo $post ? 'Editando Artigo' : 'Criar Novo Artigo'; ?>
                        </h1>
                        <p class="text-xs text-slate-400 font-mono">
                            <?php echo $post ? 'ID: #'.$post['id'] : 'Rascunho não salvo'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <span class="hidden md:block text-xs text-slate-400 mr-2">
                        <?php echo $post ? 'Última edição: '.date('d/m/y H:i', strtotime($post['updated_at'])) : ''; ?>
                    </span>
                    <button type="button" onclick="window.history.back()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white font-bold text-sm transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg font-bold text-sm shadow-lg shadow-green-500/20 transform hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Publicar Mudanças
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-[fadeIn_0.5s_ease-out]">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Main Editor Column -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Title Input -->
                    <!-- Cabecçalho do Post (Título e Subtítulo) -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-6">
                        
                        <!-- Título -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wide">
                                Título do Artigo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" 
                                   class="w-full text-2xl md:text-3xl font-bold bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-slate-900 dark:text-white placeholder-slate-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all" 
                                   placeholder="Digite um título impactante..." required>
                        </div>

                        <!-- Subtítulo -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wide">
                                Subtítulo / Linha Fina
                            </label>
                            <input type="text" name="subtitle" value="<?php echo htmlspecialchars($post['subtitle'] ?? ''); ?>" 
                                   class="w-full text-lg font-medium bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-slate-700 dark:text-slate-300 placeholder-slate-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all" 
                                   placeholder="Uma breve introdução que aparece abaixo do título...">
                        </div>

                    </div>

                    <!-- WYSIWYG Editor -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div id="editor-container">
                            <?php echo $post['content'] ?? ''; ?>
                        </div>
                        <input type="hidden" name="content" id="hiddenContent">
                    </div>

                    <!-- SEO / Excerpt -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 p-1.5 rounded-lg">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </span>
                            <h3 class="font-bold text-slate-800 dark:text-white text-sm uppercase tracking-wide">SEO & Meta Description</h3>
                        </div>
                        <textarea name="excerpt" rows="3" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-sm text-slate-600 dark:text-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all resize-none" placeholder="Este texto aparecerá nos resultados de busca do Google e nos cards do blog..."><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
                        <p class="text-xs text-slate-400 mt-2 text-right">Recomendado: 140-160 caracteres</p>
                    </div>
                    
                    <!-- NOVO: Schema Markup (JSON-LD) -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 p-1.5 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                </span>
                                <h3 class="font-bold text-slate-800 dark:text-white text-sm uppercase tracking-wide">Schema Markup (JSON-LD)</h3>
                            </div>
                            <button type="button" onclick="insertSeoTemplate()" class="text-xs font-bold text-purple-500 hover:text-purple-600 dark:hover:text-purple-400 transition-colors mr-3">
                                + Template Conteúdo SEO
                            </button>
                            <button type="button" onclick="insertDefaultSchema()" class="text-xs font-bold text-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                Inserir Schema JSON-LD
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Cole aqui o JSON estruturado para Rich Snippets (Google). Não inclua as tags &lt;script&gt;.</p>
                        <textarea id="schemaMarkup" name="schema_markup" rows="8" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-4 text-xs font-mono text-green-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition-all resize-none" placeholder="{ ... }"><?php echo htmlspecialchars($post['schema_markup'] ?? ''); ?></textarea>
                    </div>

                </div>

                <!-- Sidebar Settings -->
                <div class="space-y-6">
                    
                    <!-- Publish Status -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm sticky top-24">
                        <h3 class="font-bold text-slate-800 dark:text-white mb-4 text-sm uppercase tracking-wide flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg> Configurações
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Categoria</label>
                                <div class="relative">
                                    <select name="category_id" class="w-full appearance-none bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-200 focus:border-purple-500 focus:outline-none">
                                        <option value="">Geral (Sem Categoria)</option>
                                        <?php foreach($categories as $c): ?>
                                            <option value="<?php echo $c['id']; ?>" <?php echo ($post['category_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($c['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campo Opcional para Destaque -->
                            <div class="opacity-70 hover:opacity-100 transition-opacity">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">🏷️ Tag de Destaque (Opcional)</label>
                                <input type="text" name="main_tag" value="<?php echo htmlspecialchars($post['main_tag'] ?? ''); ?>" 
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-2 px-4 text-xs font-medium text-slate-700 dark:text-slate-200 focus:border-purple-500 focus:outline-none"
                                       placeholder="Ex: EXCLUSIVO, PROMOÇÃO, URGENTE">
                                <p class="text-[10px] text-slate-400 mt-1">Aparece como badge de destaque no artigo. Deixe vazio se não quiser.</p>
                            </div>

                            <!-- Tags Secundárias -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Tags (separadas por vírgula)</label>
                                <input type="text" name="tags" value="<?php echo htmlspecialchars($post['tags'] ?? ''); ?>" 
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-200 focus:border-purple-500 focus:outline-none" 
                                       placeholder="seo, performance, tutorial">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Visibilidade</label>
                                <div class="relative">
                                    <select name="status" class="w-full appearance-none bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-200 focus:border-purple-500 focus:outline-none">
                                        <option value="draft" <?php echo ($post['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>🔒 Rascunho (Privado)</option>
                                        <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>🌍 Publicado (Visível)</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-100 dark:border-slate-700 my-4">

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Imagem de Capa</label>
                                
                                <input type="hidden" name="existing_cover_image" id="existingCoverInput" value="<?php echo htmlspecialchars($post['cover_image'] ?? ''); ?>">

                                <div class="relative group cursor-pointer" id="dropZone">
                                    <div class="aspect-video bg-slate-100 dark:bg-slate-900 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 flex flex-col items-center justify-center overflow-hidden hover:border-purple-500 transition-colors relative">
                                        
                                        <!-- Preview Image -->
                                        <img id="coverPreview" src="<?php echo htmlspecialchars($post['cover_image'] ?? ''); ?>" class="absolute inset-0 w-full h-full object-cover <?php echo empty($post['cover_image']) ? 'hidden' : ''; ?>">
                                        
                                        <!-- Placeholder -->
                                        <div id="coverPlaceholder" class="<?php echo !empty($post['cover_image']) ? 'hidden' : 'flex flex-col items-center'; ?>">
                                            <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-xs text-slate-500">Enviar ou Selecionar</span>
                                        </div>

                                        <!-- Hover Overlay -->
                                        <div class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity gap-2">
                                            <span class="text-white text-xs font-bold pointer-events-none">Alterar Capa</span>
                                            <div class="flex gap-2">
                                                <button type="button" onclick="document.getElementById('fileInput').click()" class="bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded text-xs backdrop-blur-sm transition-colors">
                                                    Upload
                                                </button>
                                                <button type="button" onclick="openMediaModal()" class="bg-purple-600 hover:bg-purple-500 text-white px-3 py-1 rounded text-xs shadow-lg transition-colors">
                                                    Galeria
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- File Input Invisible -->
                                    <input type="file" id="fileInput" name="cover_image" class="hidden" onchange="handleFileSelect(this)">
                                </div>
                            </div>

                            <!-- Legenda da Imagem -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Legenda / Crédito da Imagem</label>
                                <input type="text" name="cover_caption" value="<?php echo htmlspecialchars($post['cover_caption'] ?? ''); ?>" 
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-2 px-3 text-xs text-slate-600 dark:text-slate-400 focus:border-purple-500 outline-none" 
                                       placeholder="Ex: Foto por João Silva / Unsplash">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Slug (URL)</label>
                                <input type="text" name="slug" value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-2 px-3 text-xs font-mono text-slate-600 dark:text-slate-400 focus:border-purple-500 outline-none" placeholder="minha-noticia-incrivel">
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </form>
</div>

<!-- Media Picker Modal -->
<div id="mediaModal" class="fixed inset-0 z-50 hidden" style="background-color: rgba(0,0,0,0.8);">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-4xl max-h-[80vh] flex flex-col shadow-2xl overflow-hidden animate-[fadeIn_0.3s_ease-out]">
            
            <!-- Header -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900">
                <h3 class="font-bold text-lg text-slate-800 dark:text-white">Selecionar da Galeria</h3>
                <button type="button" onclick="document.getElementById('mediaModal').classList.add('hidden')" class="text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <!-- Search -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <input type="text" id="mediaSearch" placeholder="Buscar imagem..." class="w-full bg-slate-100 dark:bg-slate-700 border-0 rounded-lg px-4 py-2 text-slate-800 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none">
            </div>

            <!-- Grid -->
            <div class="flex-grow overflow-y-auto p-4 bg-slate-100 dark:bg-black/20">
                <div id="mediaGrid" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Images injected via JS -->
                    <div class="col-span-full text-center py-10 text-slate-400">Carregando...</div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Quill Initialization -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
  var quill = new Quill('#editor-container', {
    theme: 'snow',
    placeholder: 'Comece a escrever sua história...',
    modules: {
      toolbar: [
        [{ 'header': [2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike', 'blockquote'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'image', 'video'],
        ['clean']
      ]
    }
  });

  // Sync Quill content
  document.querySelector('form').onsubmit = function() {
      var html = document.querySelector('.ql-editor').innerHTML;
      document.querySelector('#hiddenContent').value = html;
  };

  // --- GERADOR DE TEMPLATE SEO (CONTÉUDO) ---
  function insertSeoTemplate() {
      if(quill.getLength() > 1) {
          if(!confirm("Isso adicionará o template ao final do conteúdo atual. Continuar?")) return;
      }
      
      const templateHTML = `
        <h1>Título Principal do Artigo (H1)</h1>
        <blockquote><strong>Resumo otimizado:</strong> Parágrafo introdutório objetivo, com contexto, intenção de busca clara e palavra-chave principal inserida naturalmente.</blockquote>
        <hr>
        <p>📍 <em>Blog &rarr; Categoria Pilar &rarr; Artigo Atual</em></p>
        <hr>
        <h2>🎯 Introdução</h2>
        <p>Texto introdutório aprofundado, contextualizando o problema, a oportunidade ou o tema central do artigo.</p>
        <hr>
        <h2>🧱 Subtópico Pilar (H2)</h2>
        <p>Conteúdo estruturante relacionado diretamente à página pilar.</p>
        <h3>🔹 Subtópico de Apoio (H3)</h3>
        <p>Aprofundamento técnico, exemplos práticos, listas.</p>
        <hr>
        <h2>🧩 Subtópico Cluster (H2)</h2>
        <ul>
            <li>Lista escaneável</li>
            <li>Destaques semânticos</li>
        </ul>
        <hr>
        <h2>🔗 Links Internos Estratégicos</h2>
        <ul>
            <li><a href="/blog/categoria/artigo-1">Artigo Cluster Relacionado 1</a></li>
            <li><a href="/blog/categoria">Página Pilar Principal</a></li>
        </ul>
        <hr>
        <h2>❓ Perguntas Frequentes (FAQ)</h2>
        <h3>O que é [termo principal]?</h3>
        <p>Resposta objetiva, clara e direta.</p>
        <h3>Por que [termo] é importante?</h3>
        <p>Resposta aprofundada, com foco em decisão.</p>
        <hr>
        <h2>👤 Sobre o Autor</h2>
        <p><strong>Nome do Autor</strong><br>Especialista em Carreira</p>
        <hr>
        <h2>📣 CTA Institucional</h2>
        <blockquote>Quer aplicar essa estratégia? <a href="/register.php">Crie seu currículo agora</a>.</blockquote>
      `;
      
      const range = quill.getSelection(true);
      quill.clipboard.dangerouslyPasteHTML(range.index, templateHTML);
  }

  // --- GERADOR DE SCHEMA JSON-LD (ESTRUTURADO) ---
  function insertDefaultSchema() {
      const title = document.querySelector('input[name="title"]').value || "Título do Artigo";
      const excerpt = document.querySelector('textarea[name="excerpt"]').value || "Descrição SEO...";
      const date = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
      const slug = document.querySelector('input[name="slug"]').value || "slug";
      
      const catSelect = document.querySelector('select[name="category_id"]');
      const categoryName = catSelect.options[catSelect.selectedIndex].text.trim();
      const tags = document.querySelector('input[name="tags"]').value.split(',').map(t => t.trim()).filter(t => t);
      
      const baseUrl = "https://" + window.location.hostname;
      const fullUrl = `${baseUrl}/blog/${slug}`;
      const imgUrl = document.getElementById('coverPreview').src || `${baseUrl}/assets/img/artigo.jpg`;

      // 1. BlogPosting
      const blogPosting = {
          "@context": "https://schema.org",
          "@type": "BlogPosting",
          "headline": title.substring(0, 110),
          "description": excerpt,
          "image": {
              "@type": "ImageObject",
              "url": imgUrl
          },
          "author": {
              "@type": "Person",
              "name": "<?php echo $_SESSION['user_name'] ?? 'Equipe CV Pro'; ?>", // Dinâmico
              "url": `${baseUrl}/sobre`
          },
          "publisher": {
              "@type": "Organization",
              "name": "Currículo Vitae Pro",
              "logo": {
                  "@type": "ImageObject",
                  "url": `${baseUrl}/public/images/logo.png`
              }
          },
          "datePublished": date,
          "dateModified": new Date().toISOString().split('T')[0],
          "mainEntityOfPage": {
              "@type": "WebPage",
              "@id": fullUrl
          },
          "articleSection": categoryName !== "Geral (Sem Categoria)" ? categoryName : "Blog",
          "keywords": tags.length > 0 ? tags : ["curriculo", "carreira"]
      };

      // 2. FAQPage (Tentativa de Extração do Conteúdo)
      // Procura headings com "?" ou texto "FAQ" e pega o próximo parágrafo
      const faqSchema = {
          "@context": "https://schema.org",
          "@type": "FAQPage",
          "mainEntity": []
      };
      
      // Lógica simples de extração via DOM do editor (se possível) ou Placeholder
      // Aqui inserimos um placeholder para o usuário preencher manualmente se quiser rigor
      faqSchema.mainEntity.push({
          "@type": "Question",
          "name": "O que é [Exemplo]?",
          "acceptedAnswer": { "@type": "Answer", "text": "Resposta exemplo." }
      });

      // Combina os Schemas em um Array (Graph)
      const graph = [blogPosting, faqSchema];

      const textarea = document.getElementById('schemaMarkup');
      if(textarea.value.trim() !== "") {
          if(!confirm("Substituir o Schema atual?")) return;
      }
      textarea.value = JSON.stringify(graph, null, 2);
  }

  // --- MEDIA PICKER LOGIC ---
  
  function handleFileSelect(input) {
      if (input.files && input.files[0]) {
          var reader = new FileReader();
          reader.onload = function(e) {
              document.getElementById('coverPreview').src = e.target.result;
              document.getElementById('coverPreview').classList.remove('hidden');
              document.getElementById('coverPlaceholder').classList.add('hidden');
              
              // Limpa o input hidden se o user escolheu upload manual
              document.getElementById('existingCoverInput').value = '';
          }
          reader.readAsDataURL(input.files[0]);
      }
  }

  function openMediaModal() {
      const modal = document.getElementById('mediaModal');
      const grid = document.getElementById('mediaGrid');
      
      modal.classList.remove('hidden');
      grid.innerHTML = '<div class="col-span-full text-center py-10 text-slate-400"><svg class="animate-spin h-8 w-8 text-purple-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>';
      
      fetch('admin_media.php?ajax=1')
        .then(res => res.json())
        .then(data => {
            if(data.length === 0) {
                grid.innerHTML = '<div class="col-span-full text-center py-10 text-slate-500">Nenhuma imagem encontrada na galeria.</div>';
                return;
            }
            
            grid.innerHTML = '';
            window.mediaImages = data; // Cache para busca
            renderImages(data);
        })
        .catch(err => {
            console.error(err);
            grid.innerHTML = '<div class="col-span-full text-center text-red-500">Erro ao carregar imagens.</div>';
        });
  }

  function renderImages(images) {
      const grid = document.getElementById('mediaGrid');
      grid.innerHTML = images.map(img => `
          <div onclick="selectMedia('${img.url}')" class="group aspect-square bg-slate-200 dark:bg-slate-700 rounded-lg overflow-hidden cursor-pointer relative border-2 border-transparent hover:border-purple-500 transition-all">
              <img src="${img.url}" class="w-full h-full object-cover">
              <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold">
                  Selecionar
              </div>
          </div>
      `).join('');
  }

  function selectMedia(url) {
      // Atualiza preview
      document.getElementById('coverPreview').src = url;
      document.getElementById('coverPreview').classList.remove('hidden');
      document.getElementById('coverPlaceholder').classList.add('hidden');
      
      // Atualiza input hidden
      document.getElementById('existingCoverInput').value = url;
      
      // Limpa input file (pois agora é galeria)
      document.getElementById('fileInput').value = '';

      // Fecha modal
      document.getElementById('mediaModal').classList.add('hidden');
  }

  // Busca do Modal
  document.getElementById('mediaSearch').addEventListener('input', (e) => {
      const term = e.target.value.toLowerCase();
      const filtered = window.mediaImages.filter(img => img.filename.toLowerCase().includes(term));
      renderImages(filtered);
  });

</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
