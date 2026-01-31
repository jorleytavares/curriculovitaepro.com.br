<?php
/**
 * User Profile Settings
 * Edit Name, Email, Password
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

requireLogin();

$userId = $_SESSION['user_id'];
$message = '';
$messageType = ''; // 'success' or 'error'

// Process Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // === 3. Upload Avatar Logic (File OR Webcam) ===
    if ( (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) || !empty($_POST['avatar_base64']) ) {
        try {
            $uploadDir = __DIR__ . '/public/uploads/avatars/';
            
            // Diretório
            if (!is_dir($uploadDir)) {
                 if (!@mkdir($uploadDir, 0777, true)) {
                     throw new Exception("Erro de Permissão: Não foi possível criar pasta de uploads.");
                 }
            }

            $extension = 'jpg'; // Default
            $sourceData = null; // Para Base64
            $sourcePath = null; // Para Upload de Arquivo

            // A. Processamento via Câmera (Base64)
            if (!empty($_POST['avatar_base64'])) {
                $data = $_POST['avatar_base64'];
                if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                    $data = substr($data, strpos($data, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, webp
                    
                    if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) throw new Exception("Formato de imagem inválido.");
                    
                    $sourceData = base64_decode($data);
                    if ($sourceData === false) throw new Exception("Falha na decodificação da imagem.");
                    
                    $extension = $type;
                } else {
                    throw new Exception("Dados de imagem inválidos.");
                }
            } 
            // B. Processamento via Upload Tradicional
            else {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                $fileType = $_FILES['avatar']['type'];
                
                if (!in_array($fileType, $allowed)) throw new Exception("Formato inválido. Use JPG, PNG ou WebP.");
                if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) throw new Exception("Máximo 5MB.");
                
                $sourcePath = $_FILES['avatar']['tmp_name'];
                $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            }

            // Definição do Nome Final
            $filename = 'user_' . $userId . '.' . $extension;
            $destination = $uploadDir . $filename;

            // Limpeza de arquivos antigos deste usuário
            $oldFiles = glob($uploadDir . "user_{$userId}.*");
            if ($oldFiles) foreach($oldFiles as $f) @unlink($f);

            // Salvamento Final
            $success = false;
            if ($sourceData !== null) {
                // Salva Base64
                $success = file_put_contents($destination, $sourceData);
            } else {
                // Move Upload
                $success = move_uploaded_file($sourcePath, $destination);
            }
            
            if ($success) {
                $_SESSION['user_avatar'] = 'public/uploads/avatars/' . $filename . '?t=' . time();
                $message = 'Foto de perfil atualizada!';
                $messageType = 'success';
            } else {
                throw new Exception("Falha ao gravar o arquivo no disco.");
            }

        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }

    // === 1. Update Profile Logic ===
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $newName = trim($_POST['name']);
        $newEmail = trim($_POST['email']);
        
        if (empty($newName) || empty($newEmail)) {
            $message = 'Nome e Email são obrigatórios.';
            $messageType = 'error';
        } else {
            // Check email duplication (if changed)
            try {
                // Get current email to compare
                $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $currentEmail = $stmt->fetchColumn();

                if ($newEmail !== $currentEmail) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                    $stmt->execute([$newEmail]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Este email já está em uso por outro usuário.");
                    }
                }

                // Update
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$newName, $newEmail, $userId]);
                
                // Update Session
                $_SESSION['user_name'] = $newName;
                $_SESSION['user_email'] = $newEmail; // if stored
                
                $message = 'Dados atualizados com sucesso!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Erro: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // === 2. Update Password Logic ===
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $message = 'Todos os campos de senha são obrigatórios.';
            $messageType = 'error';
        } elseif ($newPass !== $confirmPass) {
            $message = 'A nova senha e a confirmação não coincidem.';
            $messageType = 'error';
        } elseif (strlen($newPass) < 6) {
             $message = 'A nova senha deve ter pelo menos 6 caracteres.';
             $messageType = 'error';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $hash = $stmt->fetchColumn();

                if (password_verify($currentPass, $hash)) {
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$newHash, $userId]);
                    
                    $message = 'Senha alterada com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = 'Senha atual incorreta.';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Erro ao alterar senha.';
                $messageType = 'error';
            }
        }
    }
}

// Fetch Current Data
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$seo_title = "Minha Conta | Settings";
include __DIR__ . '/includes/components/header.php';
?>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 py-10 font-sans">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <div class="mb-8">
             <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-purple-600 dark:text-slate-400 dark:hover:text-purple-400 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Voltar ao Dashboard
            </a>
        </div>

        <div class="flex flex-col md:flex-row items-center gap-8 mb-10">
            <!-- Avatar Upload Section -->
            <div class="relative group">
                <div class="h-24 w-24 md:h-32 md:w-32 rounded-full ring-4 ring-white dark:ring-slate-800 shadow-xl overflow-hidden bg-white dark:bg-slate-800 flex items-center justify-center relative">
                    <?php if (isset($_SESSION['user_avatar'])): ?>
                        <img src="<?php echo $_SESSION['user_avatar']; ?>" alt="Avatar" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="text-3xl md:text-5xl font-bold text-slate-400 dark:text-slate-600 select-none">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Overlay Options (Upload & Camera) -->
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] flex items-center justify-center gap-4 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <!-- Upload Button -->
                        <label for="avatarInput" class="p-2.5 bg-white/10 hover:bg-purple-600 border border-white/20 hover:border-purple-500 rounded-full cursor-pointer text-white transition-all transform hover:scale-110" title="Enviar foto do dispositivo">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                        </label>
                        
                        <!-- Camera Button -->
                        <button type="button" onclick="openCameraModal()" class="p-2.5 bg-white/10 hover:bg-purple-600 border border-white/20 hover:border-purple-500 rounded-full cursor-pointer text-white transition-all transform hover:scale-110" title="Tirar foto com a câmera">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </button>
                    </div>
                </div>
                
                <!-- Hidden Forms -->
                <form id="avatarForm" method="POST" enctype="multipart/form-data" class="hidden">
                    <input type="file" name="avatar" id="avatarInput" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                </form>
                <form id="webcamForm" method="POST" class="hidden">
                    <input type="hidden" name="avatar_base64" id="avatarBase64">
                </form>
            </div>

            <div class="text-center md:text-left">
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight"><?php echo htmlspecialchars($user['name']); ?></h1>
                <p class="text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 mt-2">
                    Conta Pessoal
                </span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="rounded-xl p-4 mb-6 <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' : 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800'; ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <?php if ($messageType === 'success'): ?>
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <?php else: ?>
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 1. Profile Data -->
        <div class="bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/20 rounded-2xl border border-slate-100 dark:border-slate-700/50 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-800/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Informações Pessoais
                </h3>
            </div>
            <div class="p-6 md:p-8">
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nome Completo</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-shadow outline-none">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-shadow outline-none">
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="inline-flex justify-center items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-xl text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-lg shadow-purple-500/30 transition-all hover:-translate-y-0.5">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Security -->
        <div class="bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/20 rounded-2xl border border-slate-100 dark:border-slate-700/50 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-800/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    Segurança
                </h3>
            </div>
            <div class="p-6 md:p-8">
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Senha Atual</label>
                        <input type="password" name="current_password" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-shadow outline-none">
                        <p class="text-xs text-slate-400 mt-1">Necessário para confirmar sua identidade.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nova Senha</label>
                            <input type="password" name="new_password" required minlength="6" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-shadow outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Confirmar Nova Senha</label>
                            <input type="password" name="confirm_password" required minlength="6" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-shadow outline-none">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="inline-flex justify-center items-center px-6 py-2.5 border border-slate-300 dark:border-slate-600 text-sm font-medium rounded-xl text-slate-700 dark:text-white bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all">
                            Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>


<!-- WEBCAM MODAL -->
<div id="camera-modal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="closeCameraModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 text-left shadow-2xl transition-all w-full max-w-lg border border-slate-200 dark:border-slate-700">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Tirar Foto
                    </h3>
                    
                    <div class="relative bg-black rounded-xl overflow-hidden aspect-square mb-6 flex items-center justify-center group">
                        <video id="webcam-video" autoplay playsinline class="w-full h-full object-cover transform -scale-x-100"></video>
                        <canvas id="webcam-canvas" class="hidden"></canvas>
                        
                        <!-- Face Guide Overlay -->
                        <div class="absolute inset-0 pointer-events-none z-10">
                            <svg class="w-full h-full text-black/60" viewBox="0 0 100 100" preserveAspectRatio="none">
                                <defs>
                                    <mask id="face-hole">
                                        <rect width="100%" height="100%" fill="white"/>
                                        <!-- Ellipse for face -->
                                        <ellipse cx="50" cy="45" rx="28" ry="38" fill="black" />
                                    </mask>
                                </defs>
                                <!-- Darkened Background -->
                                <rect width="100%" height="100%" fill="currentColor" mask="url(#face-hole)" />
                                
                                <!-- Guide Outline -->
                                <ellipse cx="50" cy="45" rx="28" ry="38" fill="none" stroke="rgba(255,255,255,0.8)" stroke-width="0.5" stroke-dasharray="2 1" />
                            </svg>
                            <div class="absolute bottom-6 left-0 right-0 text-center">
                                <span class="bg-black/50 text-white text-xs px-3 py-1 rounded-full backdrop-blur-sm">Posicione seu rosto no centro</span>
                            </div>
                        </div>

                        <div id="camera-loading" class="absolute inset-0 flex items-center justify-center text-white z-20">
                            <span class="animate-pulse flex flex-col items-center gap-2">
                                <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Acessando câmera...
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between gap-4">
                        <button type="button" onclick="closeCameraModal()" class="flex-1 px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 font-medium transition-colors">
                            Cancelar
                        </button>
                        <button type="button" onclick="capturePhoto()" class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold shadow-lg shadow-purple-500/30 transition-all flex items-center justify-center gap-2">
                            <div class="w-3 h-3 bg-white rounded-full animate-ping absolute opacity-0"></div>
                            Capturar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let stream = null;
    const video = document.getElementById('webcam-video');
    const canvas = document.getElementById('webcam-canvas');
    const modal = document.getElementById('camera-modal');
    const loading = document.getElementById('camera-loading');

    async function openCameraModal() {
        modal.classList.remove('hidden');
        loading.classList.remove('hidden');
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: "user",
                    width: { ideal: 640 },
                    height: { ideal: 640 }
                }, 
                audio: false 
            });
            video.srcObject = stream;
            video.onloadedmetadata = () => {
                loading.classList.add('hidden');
            };
        } catch (err) {
            console.error("Erro Câmera:", err);
            alert("Não foi possível acessar a câmera. Verifique as permissões do navegador.");
            closeCameraModal();
        }
    }

    function closeCameraModal() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        video.srcObject = null;
        modal.classList.add('hidden');
    }

    function capturePhoto() {
        if (!stream) return;
        
        // Configura canvas para tamanho quadrado (crop center se necessário)
        // Por simplicidade, capturamos o frame inteiro.
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const ctx = canvas.getContext('2d');
        // Flip horizontal para combinar com preview
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Converte para Base64
        const dataURL = canvas.toDataURL('image/jpeg', 0.9);
        
        // Popula form oculto e envia
        document.getElementById('avatarBase64').value = dataURL;
        closeCameraModal();
        
        // Feedback visual ou loading antes do submit
        const submitBtn = event.target;
        submitBtn.innerHTML = 'Salvando...';
        
        document.getElementById('webcamForm').submit();
    }
</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
