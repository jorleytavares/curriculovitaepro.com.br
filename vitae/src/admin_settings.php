<?php
/**
 * Admin Site Settings
 * Configurações globais: AdSense, Analytics, etc.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/settings_helper.php';

requireAdmin();

// Inicializa tabela
initSettingsTable($pdo);

$message = '';

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adsense_client = trim($_POST['adsense_client'] ?? '');
    $adsense_slot_article_top = trim($_POST['adsense_slot_article_top'] ?? '');
    $adsense_slot_article_middle = trim($_POST['adsense_slot_article_middle'] ?? '');
    $adsense_slot_article_bottom = trim($_POST['adsense_slot_article_bottom'] ?? '');
    $adsense_enabled = isset($_POST['adsense_enabled']) ? '1' : '0';
    $google_analytics = trim($_POST['google_analytics'] ?? '');
    
    setSetting($pdo, 'adsense_client', $adsense_client);
    setSetting($pdo, 'adsense_slot_article_top', $adsense_slot_article_top);
    setSetting($pdo, 'adsense_slot_article_middle', $adsense_slot_article_middle);
    setSetting($pdo, 'adsense_slot_article_bottom', $adsense_slot_article_bottom);
    setSetting($pdo, 'adsense_enabled', $adsense_enabled);
    setSetting($pdo, 'google_analytics', $google_analytics);
    
    $message = 'Configurações salvas com sucesso!';
}

// Carregar valores atuais
$adsense_client = getSetting($pdo, 'adsense_client');
$adsense_slot_article_top = getSetting($pdo, 'adsense_slot_article_top');
$adsense_slot_article_middle = getSetting($pdo, 'adsense_slot_article_middle');
$adsense_slot_article_bottom = getSetting($pdo, 'adsense_slot_article_bottom');
$adsense_enabled = getSetting($pdo, 'adsense_enabled', '0');
$google_analytics = getSetting($pdo, 'google_analytics');

$seo_title = "Configurações do Site | Admin";
include __DIR__ . '/includes/components/header.php';
?>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Header -->
        <div class="mb-10">
            <a href="admin_dashboard.php" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-purple-600 transition-colors mb-2 font-medium">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Voltar ao Dashboard
            </a>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Configurações do Site
            </h1>
            <p class="text-slate-500 mt-2">Gerencie integrações externas como Google AdSense e Analytics.</p>
        </div>

        <?php if($message): ?>
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 rounded-xl text-sm font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            
            <!-- Google AdSense Section -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Google AdSense</h2>
                        <p class="text-xs text-slate-500">Monetize seu blog com anúncios</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Toggle Ativo -->
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer group">
                        <input type="checkbox" name="adsense_enabled" value="1" <?php echo $adsense_enabled === '1' ? 'checked' : ''; ?> class="w-5 h-5 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                        <div>
                            <span class="font-bold text-slate-800 dark:text-white text-sm">Ativar Anúncios</span>
                            <p class="text-xs text-slate-500">Exibe os blocos de anúncio nos posts do blog</p>
                        </div>
                    </label>

                    <!-- Publisher ID -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Publisher ID (data-ad-client)</label>
                        <input type="text" name="adsense_client" value="<?php echo htmlspecialchars($adsense_client); ?>" 
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm text-slate-700 dark:text-slate-200 focus:border-purple-500 outline-none transition-all font-mono" 
                               placeholder="ca-pub-XXXXXXXXXXXXXXXX">
                        <p class="text-xs text-slate-400 mt-1">Encontre no painel do AdSense → Conta → Informações da conta</p>
                    </div>

                    <!-- Ad Slots -->
                    <div class="grid md:grid-cols-3 gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Slot: Topo do Artigo</label>
                            <input type="text" name="adsense_slot_article_top" value="<?php echo htmlspecialchars($adsense_slot_article_top); ?>" 
                                   class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-xs text-slate-600 dark:text-slate-300 focus:border-purple-500 outline-none font-mono" 
                                   placeholder="1234567890">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Slot: Meio do Artigo</label>
                            <input type="text" name="adsense_slot_article_middle" value="<?php echo htmlspecialchars($adsense_slot_article_middle); ?>" 
                                   class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-xs text-slate-600 dark:text-slate-300 focus:border-purple-500 outline-none font-mono" 
                                   placeholder="1234567891">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Slot: Final do Artigo</label>
                            <input type="text" name="adsense_slot_article_bottom" value="<?php echo htmlspecialchars($adsense_slot_article_bottom); ?>" 
                                   class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-xs text-slate-600 dark:text-slate-300 focus:border-purple-500 outline-none font-mono" 
                                   placeholder="1234567892">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Google Analytics Info -->
            <div class="bg-slate-100 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Google Analytics</h2>
                        <p class="text-sm text-green-600 dark:text-green-400 font-medium">✓ Configurado no código (G-DXEW8RRB78)</p>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-3">O rastreamento GA4 já está ativo em todas as páginas.</p>
            </div>


            <!-- Danger Zone -->
            <div class="mt-12 bg-red-50 dark:bg-red-900/10 rounded-2xl border border-red-200 dark:border-red-900/50 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                     <div>
                        <h2 class="text-lg font-bold text-red-700 dark:text-red-400">Zona de Perigo</h2>
                        <p class="text-xs text-red-600/70 dark:text-red-400/70">Ações irreversíveis do sistema</p>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800 dark:text-slate-200">Resetar Sistema</h3>
                        <p class="text-sm text-slate-500">Apaga TODOS os usuários (menos você), currículos, posts e logs.</p>
                    </div>
                    <a href="admin_actions.php?action=wipe_database" 
                       onclick="return confirm('⚠️ TEM CERTEZA ABSOLUTA? Isso apagará TODO o banco de dados (posts, currículos, usuários) mantendo apenas sua conta atual. Esta ação não pode ser desfeita.')" 
                       class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-sm shadow-sm transition-colors border border-red-800">
                        LIMPAR BANCO DE DADOS
                    </a>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-700 my-8">

            <!-- Submit -->
            <div class="flex justify-end">
                <button type="submit" class="px-8 py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl shadow-lg shadow-purple-500/20 transition-all transform hover:-translate-y-0.5">
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
