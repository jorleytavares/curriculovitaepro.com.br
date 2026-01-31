<?php
/**
 * Reset Password Controller
 * Valida token e atualiza senha.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = '';
$success = false;

// Validação Inicial do Token
if (!$token || !$email) {
    header("Location: login.php");
    exit;
}

// Verifica token no banco e validade
$stmt = $pdo->prepare("SELECT id, reset_expires_at FROM users WHERE email = ? AND reset_token = ?");
$stmt->execute([$email, $token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "Link inválido ou expirado. Solicite uma nova redefinição.";
} else {
    // Verifica Expiração (1 hora)
    if (strtotime($user['reset_expires_at']) < time()) {
        $error = "Link expirado. Por favor, solicite um novo.";
    }
}

// Processa o POST (Troca de senha)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $newPass = $_POST['password'] ?? '';
    
    if (strlen($newPass) < 6) {
        $error = "A senha deve ter no mínimo 6 caracteres.";
    } else {
        // Atualiza Senha e Limpa Token (Single Use)
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        
        if ($update->execute([$hash, $user['id']])) {
            $success = true;
        } else {
            $error = "Erro ao atualizar senha. Tente novamente.";
        }
    }
}

// UI Setup
$hide_global_nav = true;
$seo_title = "Definir Nova Senha";
include __DIR__ . '/includes/components/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 px-4 py-12">
    <div class="max-w-md w-full space-y-8 bg-white dark:bg-slate-800 p-10 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700">
        
        <?php if ($success): ?>
            <!-- Tela de Sucesso -->
            <div class="text-center animate-[fadeIn_0.5s]">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">Senha Atualizada!</h2>
                <p class="text-slate-500 mb-8">Sua senha foi alterada com sucesso. Você já pode fazer login.</p>
                <a href="login.php" class="w-full block text-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                    Ir para Login
                </a>
            </div>
            
        <?php elseif ($error): ?>
            <!-- Tela de Erro (Token Inválido) -->
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                    <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Link Inválido</h2>
                <p class="text-red-500 mb-8"><?php echo htmlspecialchars($error); ?></p>
                <a href="forgot_password.php" class="font-bold text-purple-600 hover:text-purple-500">Solicitar novo link</a>
            </div>
            
        <?php else: ?>
            <!-- Formulário de Nova Senha -->
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-slate-900 dark:text-white">Nova Senha</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Crie uma senha forte para proteger sua conta.
                </p>
            </div>

            <form class="mt-8 space-y-6" action="" method="POST">
                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nova Senha Segura</label>
                        <input id="password" name="password" type="password" required class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-slate-300 dark:border-slate-600 placeholder-slate-500 text-slate-900 dark:text-white dark:bg-slate-900 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm transition-colors" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>

                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all shadow-lg hover:shadow-purple-500/30">
                    Definir Nova Senha
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
