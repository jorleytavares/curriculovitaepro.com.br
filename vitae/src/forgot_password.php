<?php
/**
 * Forgot Password Controller
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/EmailService.php';

// Se já logado, sai
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Verifica se usuário existe
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Gera Token Seguro (32 bytes hex)
            $token = bin2hex(random_bytes(32));
            // Validade de 1 hora
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Salva no Banco (Aqui é crucial que a migração tenha rodado)
            try {
                $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
                $update->execute([$token, $expires, $user['id']]);
                
                // Envia Email (Simulado ou Real)
                EmailService::sendPasswordReset($email, $token);
                
                $message = "Enviamos um link de recuperação para seu e-mail. Verifique sua caixa de entrada (e spam).";
                $messageType = 'success';
            } catch (PDOException $e) {
                // Se der erro de coluna não encontrada, avisa sobre migração
                if (strpos($e->getMessage(), 'Unknown column') !== false) {
                    $message = "Erro de Configuração: O banco de dados precisa ser atualizado. Rode a migração.";
                } else {
                    $message = "Erro ao processar solicitação. Tente novamente.";
                }
                $messageType = 'error';
            }
        } else {
            // Por segurança, mostramos a mesma mensagem para não revelar se o email existe ou não
            $message = "Se este e-mail estiver cadastrado, você receberá um link de recuperação.";
            $messageType = 'success';
        }
    } else {
        $message = "Por favor, informe um e-mail válido.";
        $messageType = 'error';
    }
}

// UI Setup
$hide_global_nav = true;
$seo_title = "Recuperar Senha";
include __DIR__ . '/includes/components/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 px-4 py-12">
    <div class="max-w-md w-full space-y-8 bg-white dark:bg-slate-800 p-10 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-slate-900 dark:text-white">Recuperar Acesso</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Informe seu e-mail para receber as instruções.
            </p>
        </div>
        
        <?php if ($message): ?>
            <div class="p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?> text-sm font-medium">
                <?php echo htmlspecialchars($message); ?>
                <?php if($messageType === 'success'): ?>
                    <!-- Dica Pro para Localhost -->
                    <p class="mt-2 text-xs text-slate-500 italic block border-t border-green-200 pt-2">
                        (Em localhost: Verifique o arquivo email_log.txt na raiz do projeto)
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="" method="POST">
            <div>
                <label for="email" class="sr-only">Endereço de Email</label>
                <input id="email" name="email" type="email" required class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-slate-300 dark:border-slate-600 placeholder-slate-500 text-slate-900 dark:text-white dark:bg-slate-900 focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm transition-colors" placeholder="Seu e-mail cadastrado">
            </div>

            <div class="flex flex-col gap-3">
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all shadow-lg hover:shadow-purple-500/30">
                    Enviar Link de Recuperação
                </button>
                <a href="login.php" class="w-full text-center py-3 text-sm font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white transition-colors">
                    Voltar para Login
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
