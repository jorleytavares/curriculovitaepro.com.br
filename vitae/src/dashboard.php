<?php
/**
 * Dashboard Controller
 * Patterns: MVC-Lite, Strict Typing
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/resume_functions.php';

// Segurança: Login Obrigatório
requireLogin();

$userId = (int) $_SESSION['user_id'];
$status = canCreateResume($pdo, $userId);

// Saudação Personalizada
$userName = $_SESSION['user_name'] ?? 'Usuário';
$firstName = explode(' ', trim($userName))[0];

// Busca Otimizada de Currículos
try {
    $stmt = $pdo->prepare("SELECT id, title, updated_at, content FROM resumes WHERE user_id = ? ORDER BY updated_at DESC");
    $stmt->execute([$userId]);
    $resumes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $resumes = [];
    // Não exibimos erro crítico para manter a UI limpa
}

// Busca Notificações do Usuário (não lidas)
$notifications = [];
try {
    // Verifica se tabela existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
    if ($tableExists) {
        $stmt = $pdo->prepare("
            SELECT id, type, title, message, data, created_at 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Ignora erro se tabela não existe
}

$seo_title = "Dashboard | Currículo Vitae Pro";
include __DIR__ . '/includes/components/header.php';
?>

<!-- MAIN DASHBOARD -->
<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 font-sans">
    
    <?php if (!empty($notifications)): ?>
    <!-- NOTIFICATION BANNER -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <?php foreach ($notifications as $notif): ?>
                <div class="flex items-center justify-between gap-4 notification-item" data-id="<?php echo $notif['id']; ?>">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">✨</span>
                        <div>
                            <p class="font-bold text-sm"><?php echo htmlspecialchars($notif['title']); ?></p>
                            <p class="text-xs text-green-100"><?php echo htmlspecialchars($notif['message']); ?></p>
                        </div>
                    </div>
                    
                    <?php 
                        // Link inteligente: edita o último currículo ou cria um novo
                        $latestResumeId = !empty($resumes) ? $resumes[0]['id'] : null;
                        $editorLink = $latestResumeId ? "editor.php?id={$latestResumeId}&action=edit" : "editor.php";
                    ?>
                    
                    <div class="flex items-center gap-2">
                        <a href="<?php echo $editorLink; ?>" class="bg-white text-green-600 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-50 transition-colors whitespace-nowrap">
                            Experimentar Agora
                        </a>
                        <button onclick="dismissNotification(<?php echo $notif['id']; ?>)" class="text-white/60 hover:text-white p-1 transition-colors" title="Dispensar">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- HEADER AREA -->
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700/50 pt-10 pb-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <!-- Greeting -->
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
                            Olá, <?php echo htmlspecialchars($firstName); ?> 
                            <span class="animate-wave inline-block origin-bottom-right">👋</span>
                        </h1>
                        
                        <!-- Plan Badge -->
                        <?php if ($status['plan'] === 'pro'): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-500/30">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 5.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L7.414 10l2.293-2.293z" clip-rule="evenodd"/></svg>
                                PRO
                            </span>
                        <?php else: ?>
                            <a href="/upgrade.php" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-purple-100 hover:text-purple-700 transition-colors">
                                FREE
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-slate-500 dark:text-slate-400 text-lg">
                        Você tem <strong class="text-purple-600 dark:text-purple-400"><?php echo count($resumes); ?></strong> currículos ativos.
                        <?php if ($status['plan'] === 'pro' && $status['days_remaining'] !== null): ?>
                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">
                                · Renovação em <?php echo $status['days_remaining']; ?> dias
                            </span>
                        <?php elseif ($status['plan'] === 'free'): ?>
                            <a href="/upgrade.php" class="underline decoration-purple-300 hover:text-purple-600 transition-colors">Faça Upgrade</a> para desbloquear limites.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Main CTA -->
                <div>
                    <?php if ($status['allowed']): ?>
                        <a href="#" onclick="openResumeTypeModal(event)" class="group relative inline-flex items-center justify-center px-8 py-3.5 text-base font-bold text-white transition-all duration-200 bg-purple-600 font-pj rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 hover:bg-purple-700 shadow-lg shadow-purple-500/30 hover:-translate-y-0.5">
                            <span class="mr-2 text-2xl leading-none font-light">+</span> Novo Currículo
                        </a>
                    <?php else: ?>
                        <button onclick="openPlanModal()" class="group relative inline-flex items-center justify-center px-8 py-3.5 text-base font-bold text-slate-600 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed opacity-80" title="Limite atingido">
                            <span class="mr-2">🔒</span> Limite Atingido
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT AREA -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 mt-4">
        
        <?php if (empty($resumes)): ?>
            <!-- EMPTY STATE -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-12 md:p-20 text-center border border-slate-100 dark:border-slate-700/50 animate-[fadeInUp_0.5s_ease-out]">
                <div class="w-24 h-24 bg-purple-50 dark:bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-6 relative group">
                    <div class="absolute inset-0 bg-purple-100 dark:bg-purple-900/20 rounded-full animate-ping opacity-20 group-hover:opacity-40"></div>
                    <svg class="w-10 h-10 text-purple-500 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Seu portfólio está vazio</h2>
                <p class="text-slate-500 dark:text-slate-400 mb-8 max-w-lg mx-auto leading-relaxed">
                    Comece sua jornada profissional agora. Nossa Inteligência Artificial guiará você passo a passo.
                </p>
                 <a href="editor.php" class="inline-flex items-center text-purple-600 hover:text-purple-700 font-semibold gap-2 border-b-2 border-purple-100 hover:border-purple-300 transition-all pb-0.5 cursor-pointer">
                    Criar meu primeiro currículo (Grátis) &rarr;
                </a>
            </div>
        <?php else: ?>
            <!-- RESUME GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($resumes as $resume): ?>
                    <!-- Resume Card -->
                    <div class="group relative bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm hover:shadow-2xl hover:shadow-purple-900/10 dark:hover:shadow-none dark:hover:border-purple-500/30 transition-all duration-300 flex flex-col h-full overflow-hidden transform hover:-translate-y-1">
                        
                        <?php 
                            $rData = json_decode($resume['content'] ?? '{}', true);
                        ?>
                        <!-- Card Header (Visual Preview Mockup) -->
                        <div class="h-44 bg-slate-100 dark:bg-slate-900/50 relative overflow-hidden border-b border-slate-100 dark:border-slate-700/50 p-6 flex flex-col items-center justify-center gap-2 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 transition-colors cursor-pointer" onclick="window.location='editor.php?id=<?php echo $resume['id']; ?>'">
                            
                            <!-- Score Badge -->
                            <?php 
                                $score = $rData['score'] ?? 0;
                                if ($score > 0):
                                    $scoreColor = $score >= 80 ? 'bg-green-500' : ($score >= 50 ? 'bg-yellow-500' : 'bg-red-500');
                            ?>
                            <div class="absolute top-3 right-3 <?php echo $scoreColor; ?> text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-lg z-10 flex items-center gap-1" title="Pontuação do Currículo">
                                <span class="opacity-75 text-[8px] mr-0.5">SCORE</span>
                                <span><?php echo $score; ?></span>
                            </div>
                            <?php endif; ?>

                            <!-- Efeito Folha de Papel -->
                             <div class="w-28 h-36 bg-white dark:bg-slate-700 shadow-xl transform rotate-[-3deg] group-hover:rotate-0 group-hover:scale-105 transition-all duration-500 absolute top-8 border dark:border-slate-600 p-3 flex flex-col items-start overflow-hidden">
                                 <?php 
                                    $photo = $rData['photo_url'] ?? null;
                                 ?>
                                 <?php if($photo): ?>
                                    <img src="<?php echo htmlspecialchars($photo); ?>" class="w-10 h-10 rounded-full object-cover mb-3 border border-slate-200 dark:border-slate-500 shadow-sm bg-slate-100" onerror="this.style.display='none'">
                                 <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-600 mb-3 opacity-50"></div>
                                 <?php endif; ?>
                                 <div class="h-2 w-16 bg-slate-200 dark:bg-slate-600 rounded-sm mb-2"></div>
                                 <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-500/50 rounded-sm mb-1"></div>
                                 <div class="h-1.5 w-2/3 bg-slate-100 dark:bg-slate-500/50 rounded-sm"></div>
                             </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 flex-grow flex flex-col">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white truncate pr-2 tracking-tight" title="<?php echo htmlspecialchars($resume['title']); ?>">
                                    <?php echo htmlspecialchars($resume['title']); ?>
                                </h3>
                            </div>
                            
                            <p class="text-xs text-slate-500 mb-6 flex items-center gap-2">
                                <span class="relative flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                Editado em <?php echo date('d/m/Y', strtotime($resume['updated_at'])); ?>
                            </p>

                            <!-- Quick Actions -->
                            <div class="grid grid-cols-2 gap-3 mt-auto">
                                <a href="editor.php?id=<?php echo $resume['id']; ?>" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors group/btn">
                                    <svg class="w-4 h-4 text-slate-500 group-hover/btn:text-purple-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg> 
                                    Editar
                                </a>
                                <a href="generate_pdf.php?id=<?php echo $resume['id']; ?>" target="_blank" class="flex items-center justify-center gap-2 px-4 py-2.5 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm font-bold rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Baixar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Create New Placeholder -->
                <?php if ($status['allowed']): ?>
                    <a href="editor.php" class="relative overflow-hidden border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-6 flex flex-col items-center justify-center text-center hover:border-purple-500 dark:hover:border-purple-500 hover:bg-purple-50/50 dark:hover:bg-purple-900/10 transition-all cursor-pointer group h-full min-h-[300px]">
                        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-5"></div>
                        <div class="w-16 h-16 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:shadow-lg transition-all shadow-sm border border-slate-100 dark:border-slate-700 z-10">
                            <svg class="w-8 h-8 text-slate-400 group-hover:text-purple-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        </div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-lg z-10">Criar Novo</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 z-10">Começar do zero com IA</p>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Danger Zone -->
        <div class="mt-20 pt-10 border-t border-slate-200 dark:border-slate-800">
             <details class="group">
                <summary class="flex items-center cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors text-sm font-medium select-none w-fit">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Configurações Avançadas da Conta
                </summary>
                
                <div class="mt-6 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-xl p-6 flex flex-col md:flex-row justify-between items-center gap-6 animate-[fadeIn_0.3s]">
                    <div>
                        <h3 class="text-red-600 dark:text-red-400 font-bold mb-1">Zona de Perigo</h3>
                        <p class="text-sm text-red-500/80 dark:text-red-400/70">Excluir sua conta removerá todos os currículos e dados associados permanentemente.</p>
                    </div>
                    <form action="delete_account.php" method="POST" onsubmit="return confirm('ATENÇÃO: A exclusão é DEFINITIVA e IRREVERSÍVEL. Deseja continuar?');">
                         <button type="submit" class="text-red-600 border border-red-200 bg-white hover:bg-red-50 px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                            Excluir Conta
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</div>

<!-- PLAN MODAL -->
<div id="plan-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity opacity-0" id="plan-modal-backdrop"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
             <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 shadow-2xl transition-all w-full max-w-lg opacity-0 translate-y-4" id="plan-modal-panel">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Status da Assinatura</h3>
                        <button onclick="closePlanModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-xl p-6 text-white mb-6 shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                         <div class="relative z-10">
                            <p class="text-purple-200 text-sm font-medium mb-1">Plano Atual</p>
                            <h4 class="text-3xl font-bold mb-4 capitalize"><?php echo htmlspecialchars($status['plan']); ?></h4>
                            
                            <div class="w-full bg-black/20 h-2 rounded-full overflow-hidden mb-2">
                                <?php $percent = ($status['limit'] > 0) ? ($status['current'] / $status['limit']) * 100 : 100; ?>
                                <div class="bg-white h-full rounded-full transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-purple-100">
                                <span><?php echo $status['current']; ?> utilizados</span>
                                <span>Limite: <?php echo $status['limit'] === 999999 ? 'Ilimitado' : $status['limit']; ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if($status['plan'] !== 'pro'): ?>
                        <div class="space-y-3 mb-6">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Vantagens Premium:</p>
                            <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-2">
                                <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Currículos Ilimitados</li>
                                <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Templates Executivos</li>
                                <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Análise de IA</li>
                            </ul>
                        </div>
                        <a href="upgrade.php" class="block w-full text-center py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold rounded-lg hover:opacity-90 transition-opacity">
                            Fazer Upgrade Agora
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<!-- RESUME TYPE MODAL -->
<div id="resume-type-modal" class="hidden relative z-50">
    <div id="resume-type-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div id="resume-type-panel" class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl opacity-0 translate-y-4 duration-300 scale-95 border border-slate-100 dark:border-slate-700">
                
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <span class="bg-white/20 p-1.5 rounded-lg">✨</span>
                        Criar Novo Currículo
                    </h3>
                    <p class="text-purple-100 text-xs mt-1">Escolha como deseja começar</p>
                    <button onclick="closeResumeTypeModal()" class="absolute top-4 right-4 text-white/60 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Option: With Photo -->
                        <a href="editor.php?photo=1" class="group relative flex flex-col items-center p-6 border-2 border-slate-100 dark:border-slate-700 rounded-xl hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-slate-700/50 transition-all cursor-pointer text-center h-full">
                            <div class="w-16 h-16 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h4 class="font-bold text-slate-800 dark:text-white mb-2">Com Foto</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Ideal para áreas criativas, comerciais e atendimento.</p>
                        </a>

                        <!-- Option: Without Photo -->
                        <a href="editor.php?photo=0" class="group relative flex flex-col items-center p-6 border-2 border-slate-100 dark:border-slate-700 rounded-xl hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-slate-700/50 transition-all cursor-pointer text-center h-full">
                            <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h4 class="font-bold text-slate-800 dark:text-white mb-2">Sem Foto</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Padrão executivo, focado em conteúdo e minimalismo.</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('plan-modal');
    const backdrop = document.getElementById('plan-modal-backdrop');
    const panel = document.getElementById('plan-modal-panel');
    const body = document.body;

    function openPlanModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('opacity-0', 'translate-y-4');
        }, 10);
        body.classList.add('overflow-hidden');
    }

    function closePlanModal() {
        if (!modal) return;
        backdrop.classList.add('opacity-0');
        panel.classList.add('opacity-0', 'translate-y-4');
        setTimeout(() => {
            modal.classList.add('hidden');
            body.classList.remove('overflow-hidden');
        }, 300);
    }

    if(backdrop) backdrop.onclick = closePlanModal;
    
    // Dismiss Notification
    async function dismissNotification(notifId) {
        try {
            await fetch('api/dismiss_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notifId })
            });
            
            // Remove from UI with animation
            const item = document.querySelector(`.notification-item[data-id="${notifId}"]`);
            if (item) {
                item.style.opacity = '0';
                item.style.transform = 'translateX(100%)';
                item.style.transition = 'all 0.3s ease-out';
                setTimeout(() => {
                    item.remove();
                    // If no more notifications, hide banner
                    const banner = document.querySelector('.notification-item');
                    if (!banner) {
                        const container = document.querySelector('.from-green-500');
                        if (container) container.remove();
                    }
                }, 300);
            }
        } catch (err) {
            console.error('Error dismissing notification:', err);
        }
    }

    function openResumeTypeModal(e) {
        if(e) e.preventDefault();
        const modal = document.getElementById('resume-type-modal');
        const backdrop = document.getElementById('resume-type-backdrop');
        const panel = document.getElementById('resume-type-panel');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
        }, 10);
        document.body.classList.add('overflow-hidden');
    }

    function closeResumeTypeModal() {
        const modal = document.getElementById('resume-type-modal');
        const backdrop = document.getElementById('resume-type-backdrop');
        const panel = document.getElementById('resume-type-panel');

        backdrop.classList.add('opacity-0');
        panel.classList.add('opacity-0', 'translate-y-4', 'scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }
</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
