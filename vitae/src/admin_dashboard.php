<?php
/**
 * Admin Dashboard Controller
 * Access Restricted to Super Admins
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Check: Apenas Admins podem passar daqui.
requireAdmin();

// ------------------------------------------
// DATA FETCHING (Controller Logic)
// ------------------------------------------

// 1. KPIs Rápidos (Simples e Eficiente)
try {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalResumes = $pdo->query("SELECT COUNT(*) FROM resumes")->fetchColumn();
    // Exclui Admins da conta de receita/assinantes REAIS
    $totalPro = $pdo->query("SELECT COUNT(*) FROM users WHERE plan = 'pro' AND role != 'admin'")->fetchColumn();
    $newUsersToday = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    // 2. Lista de Usuários Recentes
    $usersList = $pdo->query("
        SELECT id, name, email, role, plan, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Termos Mais Buscados (Top 10)
    $topSearches = [];
    try {
        $topSearches = $pdo->query("
            SELECT term, COUNT(*) as count 
            FROM search_logs 
            GROUP BY term 
            ORDER BY count DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {} // Tabela pode não existir ainda

} catch (PDOException $e) {
    error_log("Admin Dashboard DB Error: " . $e->getMessage());
    $totalUsers = $totalResumes = $totalPro = 0;
    $usersList = [];
    $topSearches = [];
}

// 4. AI Feedbacks (Sugestões de competências não encontradas)
$aiFeedbacks = [];
$feedbackFile = __DIR__ . '/logs/ai_feedback.json';
if (file_exists($feedbackFile)) {
    $content = file_get_contents($feedbackFile);
    $aiFeedbacks = json_decode($content, true) ?: [];
    // Ordena por data (mais recentes primeiro)
    usort($aiFeedbacks, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    // Limita aos últimos 10
    $aiFeedbacks = array_slice($aiFeedbacks, 0, 10);
}

// 5. Lista de usuários para notificação manual
$allUsersForNotify = [];
try {
    $allUsersForNotify = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$seo_title = "Centro de Comando | Admin";
include __DIR__ . '/includes/components/header.php';
?>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 animate-[fadeIn_0.5s_ease-out]">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-2.5 py-0.5 bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-[10px] font-black uppercase tracking-widest rounded-full">
                        Super Admin
                    </span>
                    <span class="text-slate-400 text-sm font-medium flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Sistema Operacional
                    </span>
                </div>
                <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Centro de Comando
                </h1>
            </div>
            
            <div class="flex gap-3">
                 <a href="admin_settings.php" class="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-bold text-xs uppercase transition-colors shadow-lg shadow-purple-500/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Configurações
                </a>
                 <a href="admin_actions.php?action=export_csv" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-600 dark:text-slate-300 font-bold text-xs uppercase hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Exportar CSV
                </a>
            </div>
        </div>

        <!-- KPIs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
            <!-- Total Users -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 text-slate-50 dark:text-slate-700/50 opacity-50 group-hover:scale-110 transition-transform">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" /></svg>
                </div>
                <div class="relative z-10">
                    <p class="text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Usuários Totais</p>
                    <h3 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo number_format($totalUsers); ?></h3>
                    <div class="mt-2 text-xs text-emerald-500 font-bold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        <?php echo $newUsersToday; ?> novos hoje
                    </div>
                </div>
            </div>

            <!-- Total Resumes -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 text-slate-50 dark:text-slate-700/50 opacity-50 group-hover:scale-110 transition-transform">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>
                </div>
                <div class="relative z-10">
                    <p class="text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Currículos Ativos</p>
                    <h3 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo number_format($totalResumes); ?></h3>
                     <div class="mt-2 text-xs text-blue-500 font-bold">Docs Gerados</div>
                </div>
            </div>

            <!-- PRO Users -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 text-slate-50 dark:text-slate-700/50 opacity-50 group-hover:scale-110 transition-transform">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 5a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0v-1H3a1 1 0 010-2h1v-1a1 1 0 011-1zm5 0v2h2a1 1 0 010 2h-2v2a1 1 0 01-2 0v-2H6a1 1 0 010-2h2v-2a1 1 0 012 0z" clip-rule="evenodd" /></svg>
                </div>
                <div class="relative z-10">
                    <p class="text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Assinantes PRO</p>
                    <h3 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo number_format($totalPro); ?></h3>
                    <div class="mt-2 text-xs text-purple-600 dark:text-purple-400 font-bold bg-purple-100 dark:bg-purple-900/30 px-2 py-0.5 rounded inline-block">
                        Premium
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 opacity-70">*Exclui Admins</p>
                </div>
            </div>

            <!-- Revenue -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden group always-dark">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 to-blue-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                <div class="relative z-10">
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Receita MRR (Est.)</p>
                    <h3 class="text-3xl font-black text-white">R$ <?php echo number_format($totalPro * 13.99, 2, ',', '.'); ?></h3>
                    <div class="mt-2 text-xs text-slate-400 opacity-80">Mensal Recorrente</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
            <!-- Blog Promo -->
             <div class="lg:col-span-2 group relative h-[240px] rounded-3xl overflow-hidden shadow-lg cursor-pointer transform transition-transform hover:-translate-y-1 always-dark" onclick="window.location='admin_blog.php'">
                 <div class="absolute inset-0 bg-[url('/public/images/feature-interview.png')] bg-cover bg-center"></div>
                 <div class="absolute inset-0 bg-gradient-to-r from-indigo-900/95 via-purple-900/90 to-transparent"></div>
                 <div class="relative z-10 p-8 flex flex-col justify-center h-full items-start">
                      <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur border border-white/20 rounded-full text-white text-[10px] font-bold uppercase tracking-widest mb-3">
                         <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span> CMS Ativo
                      </div>
                      <h2 class="text-3xl font-bold text-white mb-2 max-w-md">Gerenciar Conteúdo & Blog</h2>
                      <p class="text-indigo-200 text-sm mb-6 max-w-md leading-relaxed">Publique artigos, gerencie SEO e aumente a autoridade da plataforma.</p>
                      <button class="bg-white !text-slate-900 px-5 py-2.5 rounded-xl font-bold text-sm shadow hover:bg-slate-50 transition-colors flex items-center gap-2">
                          Acessar Editor <svg class="w-4 h-4 !text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                      </button>
                 </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-center gap-4">
                 <button onclick="openCreateModal()" class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/30 hover:bg-blue-50 dark:hover:bg-blue-900/10 border border-slate-100 dark:border-slate-700 group transition-all">
                     <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                     </div>
                     <div class="text-left">
                         <div class="font-bold text-slate-800 dark:text-white text-sm">Novo Usuário</div>
                         <div class="text-[10px] text-slate-500 uppercase font-bold">Acesso Manual</div>
                     </div>
                 </button>

                 <a href="admin_media.php" class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/30 hover:bg-purple-50 dark:hover:bg-purple-900/10 border border-slate-100 dark:border-slate-700 group transition-all">
                     <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                     </div>
                     <div class="text-left">
                         <div class="font-bold text-slate-800 dark:text-white text-sm">Biblioteca de Mídia</div>
                         <div class="text-[10px] text-slate-500 uppercase font-bold">Uploads Gerais</div>
                     </div>
                 </a>
            </div>
        </div>

        <!-- Widget: Termos Mais Buscados -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Termos Buscados
                    </h3>
                    <span class="text-[10px] font-mono text-slate-400 uppercase">Top 10</span>
                </div>
                
                <?php if(empty($topSearches)): ?>
                    <div class="text-center py-8 text-slate-400 text-sm">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Nenhuma busca registrada ainda
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach($topSearches as $i => $s): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-700 transition-colors group">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 text-xs font-bold flex items-center justify-center">
                                    <?php echo $i + 1; ?>
                                </span>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-purple-600 transition-colors">
                                    "<?php echo htmlspecialchars($s['term']); ?>"
                                </span>
                            </div>
                            <span class="text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-600 px-2 py-1 rounded">
                                <?php echo number_format($s['count']); ?>x
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- AI Feedback Widget: Sugestões de Competências -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                        Feedbacks IA - Competências
                    </h3>
                    <?php if(!empty($aiFeedbacks)): ?>
                        <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-[10px] font-bold rounded-full">
                            <?php echo count($aiFeedbacks); ?> sugestões
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if(empty($aiFeedbacks)): ?>
                    <div class="text-center py-8 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                        <p class="text-sm font-medium">Nenhum feedback recebido</p>
                        <p class="text-xs mt-1">Usuários sugerirão competências quando a IA não encontrar a área deles</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2 max-h-[280px] overflow-y-auto custom-scrollbar pr-2">
                        <?php foreach($aiFeedbacks as $fbIndex => $fb): ?>
                        <?php 
                            $status = $fb['status'] ?? 'pending';
                            $statusClass = match($status) {
                                'implemented' => 'bg-green-100 border-green-200 dark:bg-green-900/20 dark:border-green-800/30',
                                'rejected' => 'bg-red-100 border-red-200 dark:bg-red-900/20 dark:border-red-800/30 opacity-50',
                                default => 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/10 dark:border-yellow-800/30'
                            };
                            // Gera ID automático se não existir (retrocompatibilidade)
                            $feedbackId = $fb['id'] ?? 'legacy_' . $fbIndex;
                        ?>
                        <div class="p-3 rounded-lg <?php echo $statusClass; ?> hover:border-yellow-300 dark:hover:border-yellow-700 transition-colors" id="feedback-<?php echo $feedbackId; ?>">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="text-xs font-bold text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/50 px-2 py-0.5 rounded">
                                            <?php echo htmlspecialchars($fb['suggestion'] ?? 'Não informado'); ?>
                                        </span>
                                        <?php if($status === 'implemented'): ?>
                                            <span class="text-[10px] font-bold text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/50 px-1.5 py-0.5 rounded">✓ Implementado</span>
                                        <?php elseif($status === 'rejected'): ?>
                                            <span class="text-[10px] font-bold text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/50 px-1.5 py-0.5 rounded">✕ Rejeitado</span>
                                        <?php endif; ?>
                                        <?php if(!empty($fb['user_id'])): ?>
                                            <span class="text-[10px] text-blue-500" title="Usuário será notificado">👤 #<?php echo $fb['user_id']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(!empty($fb['job_title'])): ?>
                                        <p class="text-[10px] text-slate-500 mb-1">Cargo: <?php echo htmlspecialchars($fb['job_title']); ?></p>
                                    <?php endif; ?>
                                    <?php if(!empty($fb['user_text'])): ?>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 truncate">
                                            "<?php echo htmlspecialchars(substr($fb['user_text'], 0, 60)); ?><?php echo strlen($fb['user_text']) > 60 ? '...' : ''; ?>"
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap">
                                        <?php echo date('d/m H:i', strtotime($fb['date'])); ?>
                                    </span>
                                    <?php if($status === 'pending' && !empty($feedbackId)): ?>
                                        <div class="flex gap-1 mt-1">
                                            <button onclick="markFeedback('<?php echo $feedbackId; ?>', 'implement', <?php echo !empty($fb['user_id']) ? 'true' : 'false'; ?>)" 
                                                    class="text-[10px] bg-green-500 hover:bg-green-600 text-white px-2 py-0.5 rounded font-bold transition-colors" 
                                                    title="Marcar como implementado e notificar usuário">
                                                ✓ Impl.
                                            </button>
                                            <button onclick="markFeedback('<?php echo $feedbackId; ?>', 'reject', <?php echo !empty($fb['user_id']) ? 'true' : 'false'; ?>)" 
                                                    class="text-[10px] bg-slate-400 hover:bg-red-500 text-white px-2 py-0.5 rounded font-bold transition-colors" 
                                                    title="Rejeitar sugestão">
                                                ✕
                                            </button>
                                        </div>
                                    <?php elseif($status === 'implemented' && empty($fb['user_id'])): ?>
                                        <div class="mt-1">
                                            <button onclick="markFeedback('<?php echo $feedbackId; ?>', 'implement', false)" 
                                                    class="text-[10px] bg-blue-100 hover:bg-blue-200 text-blue-700 border border-blue-200 px-2 py-0.5 rounded font-bold transition-colors flex items-center gap-1" 
                                                    title="Este feedback já foi implementado, mas nenhum usuário foi notificado. Clique para vincular manually.">
                                                🔗 Notificar
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <p class="text-[10px] text-slate-400">
                            💡 <strong>Dica:</strong> Após adicionar a competência em <code class="bg-slate-100 dark:bg-slate-700 px-1 rounded">src/api/enhance_summary.php</code>, clique em "✓ Impl." para notificar o usuário.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                 <h3 class="text-lg font-bold text-slate-800 dark:text-white">Últimos Cadastros</h3>
                 <span class="text-xs font-mono text-slate-400">LIMIT 20</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Usuário</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Plano</th>
                            <th class="px-6 py-4">Data</th>
                            <th class="px-6 py-4 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
                        <?php foreach ($usersList as $u): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-slate-400">#<?php echo $u['id']; ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-300">
                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                    </div>
                                    <div class="leading-tight">
                                        <div class="font-bold text-sm text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($u['name']); ?></div>
                                        <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($u['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'; ?>">
                                    <?php echo $u['role']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                 <span class="text-xs font-bold <?php echo $u['plan'] === 'pro' ? 'text-purple-600 dark:text-purple-400' : 'text-slate-400'; ?>">
                                    <?php echo strtoupper($u['plan']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 font-mono">
                                <?php echo date('d/m H:i', strtotime($u['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick='openEditModal(<?php echo json_encode($u); ?>)' class="text-blue-500 hover:text-blue-600 font-bold text-xs mr-3">EDITAR</button>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="admin_actions.php?action=delete_user&id=<?php echo $u['id']; ?>" onclick="return confirm('Confirma exclusão?')" class="text-red-500 hover:text-red-600 font-bold text-xs !text-red-500 transition-colors ml-2">EXCLUIR</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Reutilizando Modals Antigos (Refatorados Visualmente) -->
<!-- ... (Mantendo script de modals inalterado pois a lógica JS estava boa) ... -->
<!-- Script de Modals embutido para simplicidade -->
<script>
// Mock functions to keep compat logic
function openCreateModal() { document.getElementById('createModal').classList.remove('hidden'); }
function closeCreateModal() { document.getElementById('createModal').classList.add('hidden'); }
function openEditModal(u) {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_name').value = u.name;
    document.getElementById('edit_email').value = u.email;
    document.getElementById('edit_role').value = u.role;
    document.getElementById('edit_plan').value = u.plan;
}
function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }

// AI Feedback Actions
let currentFeedbackId = null;

async function markFeedback(feedbackId, action, hasUserId) {
    // Se for 'implement' e não tiver usuário associado, abre modal de seleção
    if (action === 'implement' && !hasUserId) {
        currentFeedbackId = feedbackId;
        document.getElementById('selectUserModal').classList.remove('hidden');
        return;
    }

    await processFeedbackAction(feedbackId, action, null);
}

async function submitManualFeedback() {
    const userId = document.getElementById('manual_user_id').value;
    if (!userId) {
        alert('Selecione um usuário para notificar');
        return;
    }
    
    document.getElementById('selectUserModal').classList.add('hidden');
    await processFeedbackAction(currentFeedbackId, 'implement', userId);
}

function closeSelectUserModal() {
    document.getElementById('selectUserModal').classList.add('hidden');
    currentFeedbackId = null;
    document.getElementById('manual_user_id').value = '';
}

async function processFeedbackAction(feedbackId, action, targetUserId) {
    const confirmMsg = action === 'implement' 
        ? 'Marcar como implementado? O usuário será notificado.' 
        : 'Rejeitar esta sugestão?';
    
    if (!confirm(confirmMsg)) return;
    
    try {
        const payload = { feedback_id: feedbackId, action: action };
        if (targetUserId) payload.target_user_id = targetUserId;

        const response = await fetch('api/ai_feedback_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update UI
            const feedbackEl = document.getElementById('feedback-' + feedbackId);
            if (feedbackEl) {
                if (action === 'implement') {
                    feedbackEl.className = feedbackEl.className.replace(/bg-yellow-\d+/g, 'bg-green-100')
                        .replace(/border-yellow-\d+/g, 'border-green-200');
                    // Remove buttons and show implemented badge
                    const buttonsContainer = feedbackEl.querySelector('.flex.flex-col.items-end .flex.gap-1');
                    if (buttonsContainer) {
                        buttonsContainer.outerHTML = '<div class="mt-1"><span class="text-[10px] font-bold text-green-600 bg-green-100 px-1.5 py-0.5 rounded">✓ Implementado</span></div>';
                    }
                    // Adiciona badge de usuário notificado se tiver sido manual
                    if (targetUserId) {
                         const infoContainer = feedbackEl.querySelector('.flex.items-center.gap-2.mb-1');
                         if (infoContainer) {
                             infoContainer.insertAdjacentHTML('beforeend', `<span class="text-[10px] text-blue-500" title="Notificado Manualmente">👤 ID ${targetUserId}</span>`);
                         }
                    }
                } else {
                    feedbackEl.classList.add('opacity-50');
                }
            }
            
            alert(result.message);
        } else {
            throw new Error(result.message);
        }
    } catch (err) {
        alert('Erro: ' + err.message);
    }
}
</script>

<!-- MODAL CREATE E EDIT (Incluídos inline ocultos) -->
<div id="createModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl w-96 shadow-2xl">
        <h3 class="font-bold mb-4 dark:text-white">Novo Usuário</h3>
        <form action="admin_actions.php" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="create_user">
            <input type="text" name="new_name" placeholder="Nome" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white" required>
            <input type="email" name="new_email" placeholder="Email" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white" required>
            <input type="password" name="new_password" placeholder="Senha" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white" required>
            <select name="new_role" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white"><option value="user">User</option><option value="admin">Admin</option></select>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeCreateModal()" class="text-slate-500">Cancelar</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Criar</button>
            </div>
        </form>
    </div>
</div>
<div id="editModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl w-96 shadow-2xl">
        <h3 class="font-bold mb-4 dark:text-white">Editar Usuário</h3>
        <form action="admin_actions.php" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="edit_id" id="edit_id">
            <input type="text" name="edit_name" id="edit_name" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white" required>
            <input type="email" name="edit_email" id="edit_email" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white" required>
            <select name="edit_role" id="edit_role" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white"><option value="user">User</option><option value="admin">Admin</option></select>
            <select name="edit_plan" id="edit_plan" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white"><option value="free">Free</option><option value="pro">PRO</option></select>
            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                <label class="block text-xs font-bold text-slate-500 mb-1">Redefinir Senha (Opcional)</label>
                <input type="password" name="edit_password" placeholder="Nova senha (deixe em branco para manter)" class="w-full p-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditModal()" class="text-slate-500">Cancelar</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Select User for Manual Notification -->
<div id="selectUserModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl w-96 shadow-2xl border border-slate-200 dark:border-slate-700">
        <h3 class="font-bold mb-2 dark:text-white text-lg">📢 Notificar Usuário</h3>
        <p class="text-xs text-slate-500 mb-4">Este feedback não tem usuário vinculado. Selecione quem deve receber a notificação.</p>
        
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Usuário de Destino</label>
                <select id="manual_user_id" class="w-full p-2 text-sm border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    <option value="">Selecione um usuário...</option>
                    <?php foreach($allUsersForNotify as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo $u['email']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeSelectUserModal()" class="text-slate-500 text-sm font-medium px-3 py-2 rounded hover:bg-slate-100 dark:hover:bg-slate-700">Cancelar</button>
                <button type="button" onclick="submitManualFeedback()" class="bg-green-600 text-white text-sm font-bold px-4 py-2 rounded hover:bg-green-700 shadow-md">
                    Notificar & Implementar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
