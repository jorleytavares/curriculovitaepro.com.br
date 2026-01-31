<?php
/**
 * Admin Blog Controller
 * Gerenciamento completo de posts do blog (CMS).
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Segurança: Apenas Admins
requireAdmin();

// AUTO-MIGRATION TRIGGER (Self-Healing)
if (isset($_GET['setup_blog'])) {
    try {
        // Coluna de Views
        try {
            $pdo->exec("ALTER TABLE blog_posts ADD COLUMN views INT DEFAULT 0 AFTER slug");
        } catch(PDOException $e) {} 
        
        // Índice UNIQUE para evitar slugs duplicados
        try {
            $pdo->exec("CREATE UNIQUE INDEX idx_unique_slug ON blog_posts(slug)");
        } catch(PDOException $e) {} // Ignora se já existe
        
        $msg = "Setup Completo! Colunas e índices atualizados.";
    } catch (PDOException $e) { $msg = "Erro: " . $e->getMessage(); }
    header("Location: admin_blog.php?msg=" . urlencode($msg));
    exit;
}

// Deletar Post
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: admin_blog.php?msg=deleted");
    exit;
}

// --- AUTO-MIGRATION SILO (Categories) ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_categories (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(50) NOT NULL, slug VARCHAR(50) NOT NULL UNIQUE, description TEXT, icon VARCHAR(50) DEFAULT 'folder')");
    // Seeds iniciais se vazio
    if ($pdo->query("SELECT COUNT(*) FROM blog_categories")->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO blog_categories (title, slug, description, icon) VALUES ('Carreira', 'carreira', 'Dicas de crescimento profissional', 'briefcase'), ('Currículo', 'curriculo', 'Modelos e estratégias para currículos', 'document-text'), ('Entrevista', 'entrevista', 'Como vencer as perguntas difíceis', 'chat'), ('Linkedin', 'linkedin', 'Networking e Marca Pessoal', 'share')");
    }
    // Coluna FK em Posts
    $cols = $pdo->query("SHOW COLUMNS FROM blog_posts")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('category_id', $cols)) {
        $pdo->exec("ALTER TABLE blog_posts ADD COLUMN category_id INT NULL");
        try { $pdo->exec("ALTER TABLE blog_posts ADD CONSTRAINT fk_cat FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL"); } catch(Exception $ex){}
        // Migrar órfãos para a primeira categoria encontrada
        $fid = $pdo->query("SELECT id FROM blog_categories LIMIT 1")->fetchColumn();
        if($fid) $pdo->exec("UPDATE blog_posts SET category_id = $fid WHERE category_id IS NULL");
    }
} catch(PDOException $e) {}

// --- ESTATÍSTICAS DO BLOG ---
$totalPosts = $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
$totalPublished = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
$totalDrafts = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'")->fetchColumn();

// --- FILTROS & BUSCA ---
$filterStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['q'] ?? '';

$sql = "SELECT * FROM blog_posts WHERE 1=1";
$params = [];

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}

if (!empty($searchQuery)) {
    $sql .= " AND title LIKE ?";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/components/header.php';
?>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 animate-[fadeIn_0.6s_ease-out]">
        
        <!-- Header & Breadcrumb -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6">
            <div>
                <a href="admin_dashboard.php" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-purple-600 transition-colors mb-2 font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Voltar ao Centro de Comando
                </a>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                    Gerenciador de Conteúdo
                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs rounded-full uppercase tracking-wider font-bold border border-purple-200 dark:border-purple-800">
                        CMS v1.0
                    </span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2 text-lg">
                    Publique artigos, gerencie notícias e melhore seu SEO.
                </p>
            </div>
            
            <div>
                 <a href="admin_blog_editor.php" class="relative inline-flex items-center justify-center px-8 py-3 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-bold text-sm shadow-xl shadow-purple-500/20 transition-all transform hover:-translate-y-1 group">
                    <span class="mr-2 text-xl leading-none font-light">+</span> Novo Artigo
                    <div class="absolute inset-0 rounded-xl ring-2 ring-white/20 group-hover:ring-white/40 transition-all"></div>
                </a>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Total Posts</div>
                    <div class="text-2xl font-black text-slate-800 dark:text-white"><?php echo $totalPosts; ?></div>
                </div>
                <div class="p-2 bg-slate-100 dark:bg-slate-700 rounded-lg text-slate-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                <div>
                     <div class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Publicados</div>
                     <div class="text-2xl font-black text-green-600 dark:text-green-400"><?php echo $totalPublished; ?></div>
                </div>
                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg text-green-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                <div>
                     <div class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Rascunhos</div>
                     <div class="text-2xl font-black text-amber-600 dark:text-amber-400"><?php echo $totalDrafts; ?></div>
                </div>
                <div class="p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-amber-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
            </div>
        </div>

        <!-- FILTROS E BUSCA UI -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm mb-8">
            <form method="GET" class="flex flex-col md:flex-row gap-4 items-center">
                
                <!-- Busca -->
                <div class="relative flex-grow w-full md:w-auto">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                           class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl leading-5 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 sm:text-sm transition-all" 
                           placeholder="Buscar por título...">
                </div>

                <!-- Filtro Status -->
                <div class="w-full md:w-48">
                    <select name="status" onchange="this.form.submit()" class="block w-full pl-3 pr-10 py-2.5 text-base border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white">
                        <option value="">Todos os Status</option>
                        <option value="published" <?php echo $filterStatus === 'published' ? 'selected' : ''; ?>>✅ Publicados</option>
                        <option value="draft" <?php echo $filterStatus === 'draft' ? 'selected' : ''; ?>>📝 Rascunhos</option>
                    </select>
                </div>

                <!-- Botões -->
                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="w-full md:w-auto px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none transition-colors">
                        Filtrar
                    </button>
                    
                    <?php if(!empty($searchQuery) || !empty($filterStatus)): ?>
                        <a href="admin_blog.php" class="w-full md:w-auto px-4 py-2.5 border border-slate-200 dark:border-slate-700 text-sm font-bold rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none transition-colors flex items-center justify-center" title="Limpar Filtros">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabela de Posts -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-lg">
            
            <?php if(empty($posts)): ?>
                <!-- Empty State -->
                <div class="p-16 text-center flex flex-col items-center justify-center">
                    <div class="w-24 h-24 bg-purple-50 dark:bg-slate-700/50 rounded-full flex items-center justify-center mb-6 relative group">
                        <div class="absolute inset-0 bg-purple-100 dark:bg-purple-900/20 rounded-full animate-ping opacity-20 pointer-events-none"></div>
                        <svg class="w-10 h-10 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Sem artigos por enquanto</h3>
                    <p class="text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-8">
                        Artigos de blog são ótimos para SEO e autoridade. Escreva seu primeiro post hoje mesmo!
                    </p>
                    <a href="admin_blog_editor.php" class="px-6 py-2 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-white rounded-lg font-bold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        Escrever Primeiro Artigo
                    </a>
                </div>
            <?php else: ?>
                <!-- Lista de Posts -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 dark:bg-slate-900/50 text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-6 py-5">Artigo</th>
                                <th class="px-6 py-5">Status</th>
                                <th class="px-6 py-5">Visibilidade</th>
                                <th class="px-6 py-5">Views</th>
                                <th class="px-6 py-5">Atualizado em</th>
                                <th class="px-6 py-5 text-right">Controles</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <?php foreach ($posts as $p): ?>
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <!-- Capa Miniatura -->
                                        <div class="w-16 h-12 bg-slate-200 dark:bg-slate-700 rounded-lg overflow-hidden flex-shrink-0 shadow-sm border border-slate-200 dark:border-slate-600">
                                            <?php if($p['cover_image']): ?>
                                                <img src="<?php echo htmlspecialchars($p['cover_image']); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-slate-400 bg-slate-100 dark:bg-slate-800">
                                                    <svg class="w-5 h-5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 dark:text-white text-base group-hover:text-purple-600 transition-colors">
                                                <a href="admin_blog_editor.php?id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></a>
                                            </div>
                                            <div class="text-xs text-slate-400 font-mono mt-0.5 truncate max-w-[200px]" title="<?php echo $p['slug']; ?>">
                                                /<?php echo $p['slug']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if($p['status'] === 'published'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Publicado
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            Rascunho
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> 
                                        Público
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-500 font-mono text-xs" title="Visualizações">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <?php echo number_format($p['views'] ?? 0, 0, ',', '.'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-400 font-mono">
                                    <?php echo date('d/m/y H:i', strtotime($p['updated_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-50 group-hover:opacity-100 transition-opacity">
                                        <a href="admin_blog_editor.php?id=<?php echo $p['id']; ?>" class="p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors" title="Editar">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </a>
                                        <a href="admin_blog.php?delete_id=<?php echo $p['id']; ?>" onclick="return confirm('Tem certeza que deseja DELETAR este post permanentemente?')" class="p-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors" title="Excluir">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
