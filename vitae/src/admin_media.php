<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Segurança
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$uploadDir = __DIR__ . '/public/uploads/blog/';
$publicUrl = '/public/uploads/blog/';

// Criar pasta se não existir
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// --- API AJAX (Para Modal do Editor) ---
if (isset($_GET['ajax'])) {
    $images = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp,avif}', GLOB_BRACE);
    usort($images, function($a, $b) { return filemtime($b) - filemtime($a); });
    
    $result = [];
    foreach($images as $img) {
        $result[] = [
            'url' => $publicUrl . basename($img),
            'filename' => basename($img)
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// --- AÇÕES (Upload / Delete) ---
$msg = '';

// Upload
if (isset($_FILES['media_file'])) {
    $files = $_FILES['media_file'];
    $count = count($files['name']);
    $uploaded = 0;

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $files['tmp_name'][$i];
            $name = basename($files['name'][$i]);
            // Sanitize
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            $target = $uploadDir . uniqid() . '_' . $name;
            
            // Verificar Duplicidade de Conteúdo (Hash)
            $newFileHash = md5_file($tmpName);
            $isDuplicate = false;
            
            // Otimização: Verificar primeiro o tamanho, depois o hash
            $existingFiles = glob($uploadDir . '*');
            foreach ($existingFiles as $existingFile) {
                if (is_file($existingFile) && filesize($existingFile) == filesize($tmpName)) {
                    if (md5_file($existingFile) === $newFileHash) {
                        $isDuplicate = true;
                        break;
                    }
                }
            }

            if ($isDuplicate) {
                $msg = 'duplicate';
            } else {
                if (move_uploaded_file($tmpName, $target)) {
                    $uploaded++;
                }
            }
        }
    }
    // Se pelo menos um falhou por duplicata, define msg. Se outros passaram, uploaded > 0 pode sobrescrever.
    // Prioridade msg: duplicate erro > uploaded success. 
    if ($uploaded > 0) {
        $msg = 'uploaded';
    } elseif ($msg === 'duplicate') {
        // Mantém msg duplicate se nada foi upado e houve dulpicata
    }
}

// Delete
if (isset($_POST['delete_file'])) {
    $fileToDelete = basename($_POST['delete_file']); // Segurança básica anti traversal
    $path = $uploadDir . $fileToDelete;
    if (file_exists($path)) {
        unlink($path);
        $msg = 'deleted';
    }
}

// Listar Arquivos
$images = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp,avif}', GLOB_BRACE);

// Filtrar por busca (PHP Array Filter)
$searchQuery = $_GET['q'] ?? '';
if (!empty($searchQuery)) {
    $images = array_filter($images, function($img) use ($searchQuery) {
        return stripos(basename($img), $searchQuery) !== false;
    });
}

// Ordenar por data (mais recente primeiro)
usort($images, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

include __DIR__ . '/includes/components/header.php';
?>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 animate-[fadeIn_0.5s_ease-out]">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
            <div>
                 <a href="admin_dashboard.php" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-purple-600 transition-colors mb-2 font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Voltar ao Painel
                </a>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                    Galeria de Mídia
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs rounded-full uppercase tracking-wider font-bold border border-blue-200 dark:border-blue-800">
                        Asset Manager
                    </span>
                </h1>
            </div>

            <div class="flex gap-3 items-center w-full md:w-auto">
                <!-- Busca Real-time (Form) -->
                <form method="GET" class="relative flex-grow md:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                           class="block w-full pl-9 pr-3 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition-all" 
                           placeholder="Buscar arquivo...">
                </form>

                <!-- Upload Zone Trigger -->
                <button onclick="document.getElementById('uploadInput').click()" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/20 transform hover:-translate-y-1 transition-all flex items-center gap-2 text-sm flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Upload
                </button>
            </div>
            
            <!-- Hidden Form -->
            <form method="POST" enctype="multipart/form-data" id="uploadForm" class="hidden">
                <input type="file" name="media_file[]" id="uploadInput" multiple accept="image/*" onchange="document.getElementById('uploadForm').submit()">
            </form>
        </div>

        <?php if($msg === 'uploaded'): ?>
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-xl border border-green-200 dark:border-green-800 flex items-center gap-2 animate-bounce-short">
                ✅ Imagens enviadas com sucesso!
            </div>
        <?php elseif($msg === 'deleted'): ?>
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-xl border border-red-200 dark:border-red-800 flex items-center gap-2">
                🗑️ Imagem removida.
            </div>
        <?php elseif($msg === 'deleted'): ?>
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-xl border border-red-200 dark:border-red-800 flex items-center gap-2">
                🗑️ Imagem removida.
            </div>
        <?php elseif($msg === 'duplicate'): ?>
            <div class="mb-6 p-4 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-xl border border-amber-200 dark:border-amber-800 flex items-center gap-2 animate-pulse">
                ⚠️ Imagem duplicada! Este arquivo já existe na biblioteca.
            </div>
        <?php endif; ?>

        <!-- Grid de Imagens -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
            
            <!-- Card de Upload (Visual) -->
            <div onclick="document.getElementById('uploadInput').click()" class="aspect-square bg-slate-100 dark:bg-slate-800 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 cursor-pointer flex flex-col items-center justify-center group transition-colors">
                <div class="w-12 h-12 bg-white dark:bg-slate-700 rounded-full flex items-center justify-center mb-3 shadow-sm group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-slate-400 group-hover:text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <span class="text-sm font-bold text-slate-500 dark:text-slate-400 group-hover:text-blue-500">Adicionar Nova</span>
            </div>

            <?php foreach($images as $img): 
                $filename = basename($img);
                $url = $publicUrl . $filename;
                $size = round(filesize($img) / 1024, 1) . ' KB';
            ?>
            <div class="group relative bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden hover:shadow-md transition-all">
                <!-- Imagem -->
                <div class="aspect-square bg-slate-100 dark:bg-slate-900 overflow-hidden relative">
                    <img src="<?php echo $url; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    
                    <!-- Overlay Actions -->
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 backdrop-blur-[2px]">
                        <button onclick="copyToClipboard('<?php echo $url; ?>')" class="p-2 bg-white text-slate-800 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" title="Copiar URL">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                        <a href="<?php echo $url; ?>" target="_blank" class="p-2 bg-white text-slate-800 rounded-lg hover:bg-slate-50 transition-colors" title="Ver Original">
                             <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="p-3">
                    <p class="text-xs font-bold text-slate-700 dark:text-white truncate" title="<?php echo $filename; ?>">
                        <?php echo $filename; ?>
                    </p>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-[10px] text-slate-400 font-mono bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">
                            <?php echo $size; ?>
                        </span>
                        
                        <form method="POST" onsubmit="return confirm('Tem certeza? Isso quebrará links em posts que usam esta imagem.')">
                            <input type="hidden" name="delete_file" value="<?php echo $filename; ?>">
                            <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors p-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    // Adiciona o domínio atual se for caminho relativo
    const fullUrl = window.location.origin + text;
    navigator.clipboard.writeText(fullUrl).then(() => {
        alert('URL copiada: ' + fullUrl);
    }).catch(err => {
        console.error('Erro ao copiar', err);
    });
}
</script>

<?php include __DIR__ . '/includes/components/footer.php'; ?>
