<?php
/**
 * Register Controller
 * Security: Added input sanitization, strict Password checks.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/csrf_helper.php';   // CSRF Protection
require_once __DIR__ . '/includes/rate_limiter.php';  // Rate Limiting

// Redireciona se já logado
if (isLoggedIn()) {
    header("Location: /painel");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Rate Limit (Registro é sensível a spam)
    // 3 tentativas a cada 30 minutos por IP
    $clientIp = getClientIp();
    if (isRateLimited('register', $clientIp, 3, 1800)) {
        $error = "Muitas tentativas de registro. Aguarde 30 minutos.";
        sleep(2);
    } else {
        // 2. CSRF Check
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $error = "Sessão inválida. Por favor, recarregue a página.";
        } else {
            // Sanitização rigorosa
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS); // Previne XSS básico
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            // Validação
            if (!$name || strlen($name) < 2) {
                $error = "Por favor, informe um nome válido.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "E-mail inválido.";
            } elseif (strlen($password) < 6) {
                $error = "A senha deve ter pelo menos 6 caracteres para sua segurança.";
            } else {
                // Tentativa de Registro
                $register = registerUser($pdo, $name, $email, $password);
                
                if ($register['success']) {
                    // SUCESSO: Limpa rate limit p/ evitar falso positivo se ele tentar logar logo em seguida
                    clearRateLimit('register', $clientIp);
                    
                    $success = "Conta criada com sucesso! Redirecionando para login...";
                    // Meta Refresh seguro
                    header("refresh:2;url=login.php");
                } else {
                    // FALHA
                    incrementAttempts('register', $clientIp);
                    sleep(1); // Simples delay anti-spam
                    $error = $register['message'];
                }
            }
        }
    }
}

$is_auth_page = true;
$hide_global_nav = true;
$seo_title = "Criar Conta - Currículo Vitae Pro";
include __DIR__ . '/includes/components/header.php';
?>

<!-- SPLIT SCREEN REGISTRO -->
<div class="min-h-screen flex w-full bg-white dark:bg-[#131c31] transition-colors duration-300">
    
    <!-- LADO ESQUERDO: Arte Inspiracional -->
    <div class="hidden lg:flex w-1/2 relative overflow-hidden bg-slate-900">
        <!-- Background com gradiente forte -->
        <div class="absolute inset-0 bg-gradient-to-br from-cyan-800 via-slate-800 to-slate-900 z-0"></div>
        <img src="/public/images/team_collaboration.avif" width="1920" height="1080" 
             class="absolute inset-0 w-full h-full object-cover opacity-30 mix-blend-soft-light" alt="Equipe diversa colaborando em um escritório moderno - Sucesso na carreira">
             
        <!-- Conteúdo -->
        <div class="relative z-10 flex flex-col justify-between w-full h-full p-16 max-w-2xl mx-auto">
             <!-- Topo -->
             <div>
                <a href="index.php" class="inline-flex items-center gap-2 text-white hover:text-cyan-300 transition-colors group">
                    <div class="p-2 bg-white/20 rounded-lg group-hover:bg-white/30 transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </div>
                </a>
            </div>

            <div class="space-y-6">
                <div class="inline-block px-4 py-2 rounded-full bg-cyan-500 text-white text-xs font-bold shadow-lg">
                    ✨ Junte-se a +2.500 PROs
                </div>
                <h1 class="text-4xl lg:text-5xl font-black leading-tight text-white drop-shadow-lg">
                    Comece sua jornada <br>
                    <span class="text-cyan-300">profissional hoje.</span>
                </h1>
                
                <div class="grid grid-cols-2 gap-4 pt-4">
                    <div class="bg-slate-800/80 backdrop-blur p-5 rounded-xl border border-cyan-500/30 shadow-lg">
                        <div class="text-3xl font-black text-cyan-400 mb-1">100%</div>
                        <div class="text-sm text-white uppercase tracking-wide font-medium">Gratuito para começar</div>
                    </div>
                     <div class="bg-slate-800/80 backdrop-blur p-5 rounded-xl border border-teal-500/30 shadow-lg">
                        <div class="text-3xl font-black text-teal-400 mb-1">ATS</div>
                        <div class="text-sm text-white uppercase tracking-wide font-medium">Compatível com Robôs</div>
                    </div>
                </div>
            </div>
            
            <!-- Rodapé -->
            <div class="text-white/60 text-sm">
                © 2026 Currículo Vitae Pro
            </div>
        </div>
    </div>

    <!-- LADO DIREITO: Form Register -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 lg:p-16 relative">
         <!-- Toggle Theme (Absoluto no topo direito) -->
         <div class="absolute top-6 right-6 z-20">
            <button onclick="toggleTheme()" class="p-2 rounded-full text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
            </button>
        </div>

        <div class="w-full max-w-md space-y-8 relative z-10">
             <!-- Mobile Header -->
             <div class="lg:hidden text-center mb-10">
                 <a href="index.php" class="inline-block mb-4">
                    <img src="/public/images/Curriculo Vitae Pro - logomarca.avif" alt="Logo" height="40" width="168" class="h-10 w-auto mx-auto">
                </a>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Criar nova conta</h2>
            </div>
            
            <!-- Desktop Header -->
             <div class="hidden lg:block mb-8">
                 <img src="/public/images/Curriculo Vitae Pro - logomarca.avif" alt="Logo" height="32" width="134" class="h-8 w-auto mb-8">
                 <h2 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Crie sua conta grátis</h2>
                 <p class="text-slate-500 dark:text-slate-400 mt-2">Não exigimos cartão de crédito.</p>
            </div>

            <!-- Feedback Messages -->
            <?php if($error): ?>
                <div class="animate-[shake_0.5s_ease-in-out] rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 flex items-center gap-3">
                    <div class="shrink-0 text-red-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-red-600 dark:text-red-400"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="animate-[bounce_1s] rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 flex items-center gap-3">
                    <div class="shrink-0 text-green-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-green-600 dark:text-green-400"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form class="space-y-5" method="POST" action="">
                <?php echo csrfField(); ?>
                <!-- Name -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nome Completo</label>
                    <input type="text" name="name" required 
                        class="block w-full px-3 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all shadow-sm"
                        placeholder="Ex: Maria Silva">
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">E-mail</label>
                    <input type="email" name="email" required 
                        class="block w-full px-3 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all shadow-sm"
                        placeholder="seu@melhoremail.com">
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Senha</label>
                    <div class="relative">
                        <input id="reg-password" name="password" type="password" required 
                            class="block w-full pl-3 pr-10 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all shadow-sm"
                            placeholder="Mínimo 6 caracteres">
                        <button type="button" onclick="togglePasswordVisibility('reg-password', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-cyan-600 dark:hover:text-cyan-400 focus:outline-none transition-colors">
                            <svg class="h-5 w-5 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg class="h-5 w-5 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        </button>
                    </div>
                    
                    <!-- Medidor de Força da Senha -->
                    <div class="mt-2 h-1.5 w-full bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div id="password-strength-bar" class="h-full bg-red-500 w-0 transition-all duration-300"></div>
                    </div>
                    <p id="password-feedback" class="text-xs text-slate-500 mt-1">Mínimo 6 caracteres</p>
                    <p class="text-xs text-slate-500 mt-1">Ao registrar-se, você concorda com nossos <a href="/termos-de-uso" class="underline hover:text-cyan-500">Termos de Uso</a>.</p>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-all transform hover:-translate-y-0.5 mt-4">
                    Criar Conta Gratuita
                </button>

                <!-- Divisor -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-slate-900 text-slate-500">ou cadastre-se com</span>
                    </div>
                </div>
                
                <!-- Google Sign Up -->
                <a href="auth_google.php" class="w-full inline-flex justify-center items-center py-2.5 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-cyan-300 transition-colors">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z" fill="#4285F4"/></svg>
                    Continuar com Google
                </a>
            </form>

            <p class="text-center text-sm text-slate-600 dark:text-slate-400">
                Já possui cadastro? 
                <a href="/entrar" class="font-bold text-cyan-600 hover:text-cyan-500 underline decoration-cyan-500/30 underline-offset-4 transition-colors">Fazer Login</a>
            </p>
        </div>
    </div>
</div>

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

// Medidor de Força da Senha
document.addEventListener('DOMContentLoaded', function() {
    const passInput = document.getElementById('reg-password');
    if(passInput) {
        passInput.addEventListener('input', function() {
            const password = this.value;
            const bar = document.getElementById('password-strength-bar');
            const feedback = document.getElementById('password-feedback');
            
            let strength = 0;
            if (password.length >= 6) strength += 20;
            if (password.length >= 10) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            // Limitador
            if (password.length > 0 && strength < 20) strength = 10;
            
            // Atualiza Barra
            bar.style.width = strength + '%';
            
            // Cores e Feedback
            if (strength <= 20) {
                bar.className = 'h-full w-0 transition-all duration-300 bg-red-500';
                feedback.innerText = 'Muito fraca';
                feedback.className = 'text-xs text-red-500 mt-1';
            } else if (strength <= 40) {
                bar.className = 'h-full transition-all duration-300 bg-orange-500';
                feedback.innerText = 'Fraca';
                feedback.className = 'text-xs text-orange-500 mt-1';
            } else if (strength <= 60) {
                bar.className = 'h-full transition-all duration-300 bg-yellow-500';
                feedback.innerText = 'Média';
                feedback.className = 'text-xs text-yellow-600 mt-1';
            } else if (strength <= 80) {
                bar.className = 'h-full transition-all duration-300 bg-blue-500';
                feedback.innerText = 'Forte';
                feedback.className = 'text-xs text-blue-500 mt-1';
            } else {
                bar.className = 'h-full transition-all duration-300 bg-green-500';
                feedback.innerText = 'Excelente!';
                feedback.className = 'text-xs text-green-600 mt-1 font-bold';
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
