<?php
// Ajustando paths
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/services/AsaasService.php';

requireLogin();

$error = '';
$paymentUrl = '';
$showCpfForm = false;

// LÓGICA DE CRIAÇÃO DE ASSINATURA
if (isset($_POST['action']) && $_POST['action'] === 'subscribe') {
    // Verifica se o CPF foi enviado
    $cpf = isset($_POST['cpf']) ? preg_replace('/[^0-9]/', '', $_POST['cpf']) : '';
    
    if (empty($cpf) || strlen($cpf) < 11) {
        $showCpfForm = true;
    } else {
        try {
            $asaas = new AsaasService();
            
            // 1. Cria ou recupera o cliente no Asaas (agora com CPF)
            $customerId = $asaas->createOrGetCustomer(
                $_SESSION['user_id'],
                $_SESSION['user_name'],
                $_SESSION['user_email'],
                $cpf // Passa o CPF
            );
            
            // 2. Cria a assinatura e pega o link de pagamento
            $paymentUrl = $asaas->createSubscription($customerId);
            
            // 3. Redireciona para o checkout do Asaas
            header("Location: " . $paymentUrl);
            exit;
            
        } catch (Exception $e) {
            $error = "Erro ao processar pagamento: " . $e->getMessage();
            error_log("[Upgrade Error] " . $e->getMessage());
        }
    }
}

include __DIR__ . '/includes/components/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center py-10 animate-[fadeIn_0.5s_ease-out]">
    <h1 class="text-4xl font-bold text-white mb-4">Desbloqueie todo o potencial</h1>
    <p class="text-slate-400 mb-12 text-lg">Escolha o plano ideal para acelerar sua carreira.</p>

    <?php if ($error): ?>
        <div class="mb-8 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-300">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($showCpfForm): ?>
    <!-- MODAL DE CPF -->
    <div class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-8 max-w-md w-full animate-[fadeIn_0.3s_ease-out]">
            <h2 class="text-2xl font-bold text-white mb-4">📋 Confirme seu CPF</h2>
            <p class="text-slate-400 mb-6 text-sm">Para emitir a cobrança, precisamos do seu CPF. Seus dados estão seguros.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="subscribe">
                <div class="mb-6">
                    <input 
                        type="text" 
                        name="cpf" 
                        placeholder="000.000.000-00"
                        maxlength="14"
                        required
                        class="w-full px-4 py-3 bg-slate-800 border border-slate-600 rounded-xl text-white text-center text-xl tracking-widest focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                        oninput="this.value = this.value.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')"
                    >
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-400 hover:to-emerald-400 text-white font-bold py-4 rounded-xl shadow-lg shadow-green-500/30 transition-all">
                    Continuar para Pagamento
                </button>
                <a href="upgrade.php" class="block mt-4 text-slate-500 hover:text-slate-300 text-sm">Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-8 items-center px-4">
        
        <!-- Plano Free -->
        <div class="bg-slate-800/80 p-8 rounded-2xl border border-slate-700 opacity-90 hover:opacity-100 transition-opacity">
            <h3 class="text-xl font-bold text-white">Iniciante</h3>
            <div class="text-4xl font-bold text-white my-4">Grátis</div>
            <ul class="text-slate-400 space-y-3 mb-8 text-left mx-auto max-w-xs text-sm">
                <li class="flex items-center gap-2"><span class="text-green-400">✓</span> 1 Currículo</li>
                <li class="flex items-center gap-2"><span class="text-green-400">✓</span> Exportação em PDF</li>
                <li class="flex items-center gap-2"><span class="text-green-400">✓</span> Modelos básicos</li>
            </ul>
            <button disabled class="w-full bg-slate-700/50 text-slate-500 py-3 rounded-lg font-bold cursor-not-allowed border border-slate-600 border-dashed">
                Seu Plano Atual
            </button>
        </div>

        <!-- Plano Pro -->
        <div class="bg-slate-900/90 backdrop-blur-xl p-8 rounded-2xl border-2 border-purple-500 shadow-neon transform md:scale-105 relative z-10 overflow-hidden">
            <!-- Badge Promoção -->
            <div class="absolute top-0 left-0 right-0 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 text-white text-xs font-black py-2 text-center tracking-wider animate-pulse">
                🔥 50% OFF NOS 6 PRIMEIROS MESES 🔥
            </div>
            
            <div class="mt-8">
                <h3 class="text-xl font-bold text-white">Profissional</h3>
                
                <!-- Preço Promocional -->
                <div class="my-4">
                    <div class="flex items-center justify-center gap-3">
                        <span class="text-lg text-slate-500 line-through">R$ 13,99</span>
                        <span class="bg-green-500 text-white text-xs font-bold px-2 py-1 rounded">-50%</span>
                    </div>
                    <div class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400 mt-2">
                        R$ 6,99 <span class="text-sm text-slate-500 font-normal">/mês</span>
                    </div>
                    <p class="text-xs text-amber-400 mt-2 font-medium">⏰ Nos 6 primeiros meses • Depois R$ 13,99/mês</p>
                </div>
                
                <ul class="text-slate-300 space-y-3 mb-8 text-left mx-auto max-w-xs text-sm">
                    <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> <strong>Currículos Ilimitados</strong></li>
                    <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> Editor Avançado com IA (Beta)</li>
                    <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> Sem marca d'água no PDF</li>
                    <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> Prioridade no suporte</li>
                    <li class="flex items-center gap-2"><span class="text-cyan-400">✓</span> Modelos Premium exclusivos</li>
                </ul>
                
                <form method="POST">
                    <input type="hidden" name="action" value="subscribe">
                    <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-400 hover:to-emerald-400 text-white font-bold py-4 rounded-xl shadow-lg shadow-green-500/30 transition-all transform hover:scale-105 active:scale-100 text-lg">
                        Começar Agora por R$ 6,99
                    </button>
                </form>
                
                <p class="text-xs text-slate-500/60 mt-4 flex justify-center items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Garantia de 7 dias ou seu dinheiro de volta
                </p>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
