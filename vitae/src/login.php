<?php
/**
 * Login Controller
 * Security: Removed hardcoded backdoors, added basic brute-force mitigation (sleep).
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/csrf_helper.php';   // NOVO: CSRF Protection
require_once __DIR__ . '/includes/rate_limiter.php';  // NOVO: Rate Limiter

// AUTO-MIGRATION TRIGGER (Self-Healing)
// Se o usuário acessar ?setup=1, roda as migrações pendentes.
if (isset($_GET['setup'])) {
    try {
        // Tenta adicionar colunas de reset se não existirem (MySQL 8.0+ syntax friendly or standard try-catch fallback)
        try {
             $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL AFTER password");
             $pdo->exec("CREATE INDEX idx_reset_token ON users(reset_token)");
        } catch(PDOException $e) {} // Ignora se já existe
        
        try {
             $pdo->exec("ALTER TABLE users ADD COLUMN reset_expires_at DATETIME NULL DEFAULT NULL AFTER reset_token");
        } catch(PDOException $e) {} // Ignora se já existe

        $msg = "Setup Concluído! Banco de dados atualizado.";
    } catch (PDOException $e) {
        $msg = "Erro no Setup: " . $e->getMessage();
    }
    header("Location: /entrar?msg=" . urlencode($msg));
    exit;
}

// Redireciona se já logado
if (isLoggedIn()) {
    header("Location: /painel");
    exit;
}

// Captura mensagens de URL (ex: ?msg=Salvo)
$systemMsg = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_SPECIAL_CHARS);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verificar Rate Limit (IP) - 5 tentativas / 15 minutos
    $clientIp = getClientIp(); // Função do auth_functions.php
    if (isRateLimited('login', $clientIp, 5, 900)) {
        $error = "Muitas tentativas falhas. Por segurança, aguarde 15 minutos antes de tentar novamente.";
        // Adiciona um sleep extra para desencorajar bots persistentes sem bloquear o script PHP
        sleep(2);
    } else {
        // 2. Verificar CSRF
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $error = "Sessão expirada ou solicitação inválida. Recarregue a página.";
        } else {
            // Sanitização de Entrada
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? ''; 

            if ($email && !empty($password)) {
                $loginResult = loginUser($pdo, $email, $password);
                
                if ($loginResult['success']) {
                    // SUCESSO: Limpa rate limit para este IP
                    clearRateLimit('login', $clientIp);
                    
                    // Redirecionamento Seguro
                    $redirect = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL);
                    if ($redirect && strpos($redirect, '/') === 0) { 
                        header("Location: " . $redirect);
                    } else {
                        header("Location: /painel");
                    }
                    exit;
                } else {
                    // FALHA: Incrementa Rate Limit e aplica penalidade de tempo
                    incrementAttempts('login', $clientIp);
                    sleep(1); // Brute-force mitigation
                    $error = $loginResult['message'];
                }
            } else {
                $error = "Por favor, preencha todos os campos corretamente.";
            }
        }
    }
}

// Configurações de SEO e Layout
$is_auth_page = true;
$hide_global_nav = true; 
$seo_title = "Login Seguro - Currículo Vitae Pro";
include __DIR__ . '/includes/components/header.php';
?>

<!-- SPLIT SCREEN LAYOUT -->
<div class="min-h-screen flex w-full bg-white dark:bg-[#131c31] transition-colors duration-300">
    
    <!-- LADO ESQUERDO: Arte/Contexto (Hidden no Mobile) -->
    <div class="hidden lg:flex w-1/2 relative overflow-hidden bg-slate-900">
        <!-- Background Dinâmico -->
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-950 via-slate-900 to-black z-0"></div>
        <img src="/public/images/login-hero.avif" width="1920" height="1080" 
             class="absolute inset-0 w-full h-full object-cover opacity-20 mix-blend-overlay" alt="Ambiente de escritório moderno">
        
        <!-- Conteúdo Sobreposto -->
        <div class="relative z-10 flex flex-col justify-between w-full h-full p-16 text-white">
            <!-- Topo -->
            <div>
                <a href="index.php" class="inline-flex items-center gap-3 text-white/90 hover:text-white transition-colors group py-2 px-4 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 backdrop-blur-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    <span class="font-medium tracking-wide text-xs uppercase">Voltar ao Site</span>
                </a>
            </div>
            
            <!-- Centro: Depoimento ou Frase de Efeito -->
            <div class="space-y-6 max-w-lg">
                <h1 class="text-5xl font-bold leading-tight tracking-tight">
                    Construa sua <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-cyan-400">carrreira dos sonhos.</span>
                </h1>
                <p class="text-lg text-slate-300 leading-relaxed font-light">
                    "O Currículo Vitae Pro transformou a maneira como me apresento ao mercado. Simples, rápido e incrivelmente profissional."
                </p>
                
                 <!-- Mini Profile -->
                <div class="flex items-center gap-4 pt-4">
                     <img src="/public/images/avatar-testimonial.avif" width="48" height="48" class="w-12 h-12 rounded-full border-2 border-purple-500/50" alt="Foto de Juliana S. - Profissional contratada">
                     <div>
                         <p class="font-bold text-white text-sm">Juliana S.</p>
                         <p class="text-slate-400 text-xs text-xs">Contratada no Nubank</p>
                     </div>
                </div>
            </div>
            
            <!-- Rodapé da Arte -->
            <div class="flex justify-between items-center text-xs text-slate-500 font-medium tracking-wider">
                <p>© <?php echo date('Y'); ?> V.Pro Enterprise</p>
                <div class="flex gap-4">
                    <a href="/privacidade" class="hover:text-white cursor-pointer transition-colors text-inherit no-underline">Privacidade</a>
                    <a href="/termos-de-uso" class="hover:text-white cursor-pointer transition-colors text-inherit no-underline">Termos</a>
                </div>
            </div>
        </div>
        
        <!-- Detalhe Decorativo Animado -->
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-purple-600/30 rounded-full blur-[100px] pointer-events-none animate-pulse"></div>
    </div>

    <!-- LADO DIREITO: Formulário de Login -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 lg:p-16 relative">
        <!-- Toggle Theme (Absoluto no topo direito) -->
        <div class="absolute top-6 right-6 z-20">
            <button onclick="toggleTheme()" class="p-2 rounded-full text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
            </button>
        </div>

        <div class="w-full max-w-md space-y-8 relative z-10">
            <!-- Cabeçalho Mobile (Só aparece se a arte estiver escondida) -->
            <div class="lg:hidden text-center mb-10">
                 <a href="index.php" class="inline-block mb-4">
                    <img src="/public/images/Curriculo Vitae Pro - logomarca.avif" alt="Logo" height="40" width="168" class="h-10 w-auto mx-auto">
                </a>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Bem-vindo de volta</h2>
            </div>
            
            <!-- Cabeçalho Desktop -->
            <div class="hidden lg:block mb-10">
                 <img src="/public/images/Curriculo Vitae Pro - logomarca.avif" alt="Logo" height="32" width="134" class="h-8 w-auto mb-8">
                 <h2 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Acesse sua conta</h2>
                 <p class="text-slate-500 dark:text-slate-400 mt-2">Continuar editando seu currículo profissional.</p>
            </div>

            <!-- Feedback de Sistema (Mensagem Verde) -->
            <?php if (!empty($systemMsg)): ?>
                <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 flex items-center gap-3 animate-[fadeIn_0.5s]">
                    <div class="shrink-0 text-green-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <p class="text-sm font-medium text-green-700 dark:text-green-300"><?php echo htmlspecialchars($systemMsg); ?></p>
                </div>
            <?php endif; ?>

            <!-- Feedback de Erro -->
            <?php if ($error): ?>
                <div class="animate-[shake_0.5s_ease-in-out] rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 flex items-center gap-3">
                    <div class="shrink-0 text-red-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-red-600 dark:text-red-400"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <!-- Formulário -->
            <form class="space-y-6 group" method="POST" action="">
                <?php echo csrfField(); ?>
                
                <!-- Email Input -->
                <div class="space-y-2">
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Endereço de Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                            class="block w-full pl-10 pr-3 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-sm hover:border-purple-300 dark:hover:border-slate-500" 
                            placeholder="seu@email.com">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Senha</label>
                        <a href="/recuperar-senha" class="text-sm font-medium text-purple-600 hover:text-purple-500 dark:text-purple-400">Esqueceu?</a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <input id="password" name="password" type="password" required 
                            class="block w-full pl-10 pr-10 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-sm hover:border-purple-300 dark:hover:border-slate-500" 
                            placeholder="••••••••">
                         <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 focus:outline-none transition-colors">
                            <svg class="h-5 w-5 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg class="h-5 w-5 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Botão Submit -->
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed">
                     <span class="flex items-center gap-2">
                        Entrar na Conta
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                     </span>
                </button>
                
                <!-- Separator -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-slate-900 text-slate-500">ou continue com</span>
                    </div>
                </div>
                
                 <!-- Social Login -->
                 <div class="grid grid-cols-2 gap-3">
                    <a href="auth_google.php" class="w-full inline-flex justify-center py-2.5 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-purple-300 transition-colors">
                        <svg class="h-5 w-5 text-current mr-2" viewBox="0 0 24 24"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z" fill="#4285F4"/></svg>
                        Google
                    </a>
                    <button type="button" disabled class="w-full inline-flex justify-center py-2.5 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-medium text-slate-400 cursor-not-allowed opacity-60">
                        <svg class="h-5 w-5 text-[#0077b5] mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        LinkedIn
                    </button>
                </div>
                </div>
            </form>
            
            <!-- Área de Ações Secundárias (Encapsulada para evitar desalinhamento) -->
            <div class="flex flex-col gap-6 mt-8 w-full">
                <!-- Magic Link Toggle -->
                <div id="magic-link-trigger" class="flex justify-center pt-6 border-t border-slate-100 dark:border-slate-800 w-full">
                    <button type="button" onclick="toggleMagicLink()" class="group inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-purple-600 dark:text-slate-400 dark:hover:text-purple-400 transition-colors">
                        <span class="p-1 rounded-md bg-purple-50 dark:bg-purple-900/20 group-hover:bg-purple-100 dark:group-hover:bg-purple-900/40 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </span>
                        Prefiro entrar sem senha (Magic Link)
                    </button>
                </div>

                <!-- Magic Link Form (Hidden) -->
                <form id="magic-form" class="space-y-6 hidden w-full" onsubmit="sendMagicLink(event)">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Seu E-mail</label>
                        <input type="email" id="magic-email" required 
                            class="block w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" 
                            placeholder="seu@email.com">
                    </div>
                    <button type="submit" id="magic-btn" class="w-full py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 transition-all">
                        Enviar Link de Acesso
                    </button>
                    <div class="text-center">
                        <button type="button" onclick="toggleMagicLink()" class="text-sm text-slate-500 hover:text-slate-700">Voltar para Login normal</button>
                    </div>
                </form>
                
                <p class="text-center text-sm text-slate-600 dark:text-slate-400 w-full">
                    Não tem uma conta ainda? 
                    <a href="/criar-conta" class="font-bold text-purple-600 hover:text-purple-500 underline decoration-purple-500/30 underline-offset-4 transition-colors">Criar conta grátis</a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Script Utilitário -->
<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const iconEye = btn.querySelector('.icon-eye');
    const iconEyeOff = btn.querySelector('.icon-eye-off');

    if (input.type === 'password') {
        input.type = 'text';
        iconEye.classList.add('hidden');
        iconEyeOff.classList.remove('hidden');
    } else {
        input.type = 'password';
        iconEye.classList.remove('hidden');
        iconEyeOff.classList.add('hidden');
    }
    }
}

// Magic Link Lógica
function toggleMagicLink() {
    const loginForm = document.querySelector('form[action=""]'); // Form principal
    const magicForm = document.getElementById('magic-form');
    const trigger = document.getElementById('magic-link-trigger');
    const social = document.querySelector('.grid.grid-cols-2'); // Botões sociais
    const divider = document.querySelector('.relative.flex.justify-center.text-sm').parentElement.parentElement; // Separador

    if (magicForm.classList.contains('hidden')) {
        // Mostrar Magic
        loginForm.classList.add('hidden');
        social.classList.add('hidden');
        trigger.classList.add('hidden');
        if(divider) divider.classList.add('hidden');
        
        magicForm.classList.remove('hidden');
        magicForm.classList.add('animate-[fadeIn_0.3s]');
    } else {
        // Voltar
        loginForm.classList.remove('hidden');
        social.classList.remove('hidden');
        trigger.classList.remove('hidden');
        if(divider) divider.classList.remove('hidden');
        
        magicForm.classList.add('hidden');
    }
}

async function sendMagicLink(e) {
    e.preventDefault();
    const btn = document.getElementById('magic-btn');
    const email = document.getElementById('magic-email').value;
    
    // Loading State
    const originalText = btn.innerText;
    btn.innerText = "Enviando...";
    btn.disabled = true;
    btn.classList.add('opacity-75');

    try {
        const res = await fetch('/api/send_magic_link.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ email })
        });
        const data = await res.json();
        
        if (data.success) {
            btn.innerText = "✨ Link Enviado!";
            btn.classList.remove('from-purple-600', 'to-indigo-600');
            btn.classList.add('bg-green-600', 'hover:bg-green-500');
            alert(data.message);
        } else {
            alert(data.message || 'Erro ao enviar.');
            btn.innerText = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-75');
        }
    } catch (err) {
        alert('Erro de conexão.');
        btn.innerText = originalText;
        btn.disabled = false;
        btn.classList.remove('opacity-75');
    }
}
</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
