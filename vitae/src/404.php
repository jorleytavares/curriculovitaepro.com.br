<?php
/**
 * 404 Error Page
 * Design Enterprise: Clean, Helpful, Dark Mode compatible.
 */
$hide_global_nav = true; // Opcional, mantemos limpo
$seo_title = "Página Não Encontrada | Vitae Pro";
require_once __DIR__ . '/includes/components/header.php';
?>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex items-center justify-center px-4 py-16 transition-colors duration-500">
    <div class="text-center max-w-lg mx-auto animate-[fadeIn_0.8s_ease-out]">
        
        <!-- Illustration -->
        <div class="relative w-64 h-64 mx-auto mb-8">
            <div class="absolute inset-0 bg-purple-500/20 blur-[60px] rounded-full animate-pulse"></div>
            <img src="/public/images/404-illustration.svg" onerror="this.src='https://illustrations.popsy.co/amber/crashed-error.svg'" alt="404 Error" class="relative z-10 w-full h-full drop-shadow-2xl opacity-90">
        </div>

        <!-- Content -->
        <h1 class="text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-500 to-indigo-600 mb-2 tracking-tight">404</h1>
        <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mb-4">Ops! Página perdida no espaço.</h2>
        <p class="text-slate-600 dark:text-slate-400 text-lg mb-10 leading-relaxed">
            O link que você tentou acessar pode ter sido removido ou o endereço digitado está incorreto.
        </p>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="index.php" class="inline-flex items-center justify-center px-8 py-3 text-base font-bold text-white bg-slate-900 dark:bg-white dark:text-slate-900 rounded-xl hover:bg-slate-800 dark:hover:bg-slate-100 transition-all shadow-lg hover:shadow-xl hover:-translate-y-1">
                Ir para o Início
            </a>
            <a href="blog.php" class="inline-flex items-center justify-center px-8 py-3 text-base font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm hover:border-purple-300">
                Ler o Blog
            </a>
        </div>
        
        <!-- Search Suggestion (Optional functional integration later) -->
        <div class="mt-12 opacity-60">
            <p class="text-xs text-slate-400 uppercase tracking-widest font-bold mb-2">Perdido? Tente pesquisar:</p>
            <form action="blog.php" method="GET" class="max-w-xs mx-auto relative">
                <input type="text" name="q" placeholder="Buscar conteúdo..." class="w-full bg-transparent border-b border-slate-300 dark:border-slate-700 text-center py-2 text-sm focus:outline-none focus:border-purple-500 text-slate-600 dark:text-slate-300">
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
