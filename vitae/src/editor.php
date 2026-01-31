<?php
/**
 * Resume Editor Controller
 * Security: Strict XSS Prevention (Backend Input Sanitization + Frontend Encoding).
 * Performance: Optimized queries, lean assets.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/resume_functions.php';
require_once __DIR__ . '/services/ResumeAnalyzerService.php';

use Services\ResumeAnalyzerService;

requireLogin();

$userId = (int) $_SESSION['user_id'];
$resumeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$resumeData = [];
$title = '';

// LOAD DATA
if ($resumeId) {
    $resume = getResumeById($pdo, $resumeId, $userId);
    if (!$resume) {
        header("Location: dashboard.php");
        exit;
    }
    $title = $resume['title'];
    $resumeData = $resume['data'];
} else {
    // Check Limits for New
    $status = canCreateResume($pdo, $userId);
    if (!$status['allowed']) {
        header("Location: upgrade.php");
        exit;
    }
}

// Inicializa Defaults (Null Coalescing para evitar Warnings)
$experiences = $resumeData['experiences'] ?? [];
if (empty($experiences)) {
    $experiences[] = ['company' => '', 'role' => '', 'desc' => ''];
}

// Initialize Defaults for New Resume
if (!$resumeId && isset($_GET['photo'])) {
   $resumeData['design']['show_photo'] = (int)$_GET['photo'];
}

// SAVE LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize Experiences (Array)
    $cleanExperiences = [];
    if (isset($_POST['exp_company']) && is_array($_POST['exp_company'])) {
        foreach ($_POST['exp_company'] as $index => $company) {
            $company = trim($company);
            if ($company === '') continue;
            
            $cleanExperiences[] = [
                'company' => filter_var($company, FILTER_SANITIZE_SPECIAL_CHARS),
                'role'    => filter_var($_POST['exp_role'][$index] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
                'date'    => filter_var($_POST['exp_date'][$index] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
                'desc'    => filter_var($_POST['exp_desc'][$index] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)
            ];
        }
    }

    // Sanitize Main Data
    $saveData = [
        'template'      => in_array($_POST['template'], ['modern', 'classic', 'sidebar']) ? $_POST['template'] : 'modern',
        'photo_url'     => filter_input(INPUT_POST, 'photo_url', FILTER_SANITIZE_URL), // Agora esperamos uma URL limpa
        'full_name'     => filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS),
        'social_name'   => filter_input(INPUT_POST, 'social_name', FILTER_SANITIZE_SPECIAL_CHARS),
        'gender'        => filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_SPECIAL_CHARS),
        'is_pcd'        => isset($_POST['is_pcd']) ? 1 : 0,
        'pcd_details'   => filter_input(INPUT_POST, 'pcd_details', FILTER_SANITIZE_SPECIAL_CHARS),
        'job_title'     => filter_input(INPUT_POST, 'job_title', FILTER_SANITIZE_SPECIAL_CHARS),
        'contact_email' => filter_input(INPUT_POST, 'contact_email', FILTER_SANITIZE_EMAIL),
        'phone'         => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS),
        'phone'         => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS),
        'location'      => filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS),
        'links'         => filter_input(INPUT_POST, 'links', FILTER_SANITIZE_SPECIAL_CHARS),
        'skills'        => filter_input(INPUT_POST, 'skills', FILTER_SANITIZE_SPECIAL_CHARS),
        'languages'     => filter_input(INPUT_POST, 'languages', FILTER_SANITIZE_SPECIAL_CHARS),
        'education'     => filter_input(INPUT_POST, 'education', FILTER_SANITIZE_SPECIAL_CHARS),
        'summary'       => filter_input(INPUT_POST, 'summary', FILTER_SANITIZE_SPECIAL_CHARS),
        'experiences'   => $cleanExperiences,
        'design'        => [
            'color'      => filter_input(INPUT_POST, 'design_color', FILTER_SANITIZE_SPECIAL_CHARS) ?: '#1e293b',
            'font'       => filter_input(INPUT_POST, 'design_font', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'font-sans',
            'size'       => filter_input(INPUT_POST, 'design_size', FILTER_SANITIZE_SPECIAL_CHARS) ?: '1',
            'show_photo' => isset($_POST['design_show_photo']) ? 1 : 0,
            'photo_x'    => filter_input(INPUT_POST, 'design_photo_x', FILTER_VALIDATE_INT) ?: 0,
            'photo_y'    => filter_input(INPUT_POST, 'design_photo_y', FILTER_VALIDATE_INT) ?: 0,
            'text_align' => filter_input(INPUT_POST, 'design_text_align', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'left',
            'text_x'     => filter_input(INPUT_POST, 'design_text_x', FILTER_VALIDATE_INT) ?: 0,
            'text_y'     => filter_input(INPUT_POST, 'design_text_y', FILTER_VALIDATE_INT) ?: 0,
            'summary_x'  => filter_input(INPUT_POST, 'design_summary_x', FILTER_VALIDATE_INT) ?: 0,
            'summary_y'  => filter_input(INPUT_POST, 'design_summary_y', FILTER_VALIDATE_INT) ?: 0,
            'exp_x'      => filter_input(INPUT_POST, 'design_exp_x', FILTER_VALIDATE_INT) ?: 0,
            'exp_y'      => filter_input(INPUT_POST, 'design_exp_y', FILTER_VALIDATE_INT) ?: 0,
            'header_type'=> filter_input(INPUT_POST, 'design_header_type', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'simple'
        ]
    ];
    
    // Title separado
    $newTitle = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $saveData['title'] = $newTitle ?: 'Meu Currículo'; // Fallback só no JSON, título real passa na função

    // Calculate Score (Server-side verification)
    try {
        $analyzer = new ResumeAnalyzerService();
        $analysisResult = $analyzer->analyze($saveData);
        $saveData['score'] = $analysisResult['score'];
    } catch (Exception $e) {
        $saveData['score'] = 0; // Fallback
    }

    $result = saveResume($pdo, $userId, $saveData, $resumeId);
    
    if ($result['success']) {
        if (!$resumeId) {
             header("Location: editor.php?id=" . $result['id'] . "&msg=saved");
        } else {
             header("Location: editor.php?id=" . $resumeId . "&msg=saved");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Editor | Currículo Vitae Pro</title>
    
    <!-- CSS Dependencies -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Configs -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { 
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif']
                    },
                    colors: {
                        slate: { 850: '#151f32', 950: '#020617' } 
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
        
        /* A4 Paper Logic */
        .paper-a4 {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 0 auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            color: #000;
            position: relative;
            transform-origin: top center;
            overflow: hidden;
        }
        
        @media screen and (max-width: 1024px) {
            .paper-a4 { width: 100%; min-height: auto; aspect-ratio: 210/297; box-shadow: none; }
        }

        /* Helper Utilities */
        [contenteditable]:empty:before { content: attr(data-placeholder); color: #9ca3af; pointer-events: none; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 overflow-hidden h-screen flex flex-col selection:bg-purple-500/30">

    <!-- TOP BAR -->
    <header class="h-14 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-4 shrink-0 z-30 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="dashboard.php" class="text-slate-400 hover:text-white transition-colors p-2 hover:bg-slate-800 rounded-lg" title="Voltar ao Dashboard">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div class="h-5 w-px bg-slate-700"></div>
            <input type="text" form="resumeForm" name="title" value="<?php echo htmlspecialchars($title ?: 'Meu Currículo Sem Título'); ?>" 
                   class="bg-transparent text-sm font-semibold focus:outline-none focus:bg-slate-800 focus:ring-1 focus:ring-purple-500/50 px-2 py-1.5 rounded text-white w-48 sm:w-64 transition-all placeholder-slate-500"
                   placeholder="Nome do Arquivo...">
        </div>
        
        <div class="flex items-center gap-4">
            <!-- SCORE INDICATOR -->
            <div id="score-container" class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-slate-800 rounded-full cursor-help relative group border border-slate-700 hover:border-purple-500/50 transition-all">
                <div class="w-2 h-2 rounded-full bg-slate-400 animate-pulse" id="score-dot"></div>
                <span class="text-sm font-bold text-slate-300" id="score-value">--</span>
                <span class="text-xs text-slate-500 font-mono">/ 100</span>
                
                <!-- Tooltip Score -->
                <div class="absolute top-full right-0 mt-3 w-80 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 p-0 hidden group-hover:block z-50 overflow-hidden ring-1 ring-white/10">
                    <div class="bg-slate-900/50 p-3 border-b border-slate-700 flex justify-between items-center">
                        <h4 class="font-bold text-white text-xs uppercase tracking-wider">Análise de IA</h4>
                        <span class="text-[10px] text-purple-400 font-mono">BETA</span>
                    </div>
                    <div id="analysis-suggestions" class="p-3 space-y-2 text-sm text-slate-400 max-h-64 overflow-y-auto custom-scrollbar">
                        <div class="flex items-center gap-2 text-slate-500 italic text-xs">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Analisando conteúdo...
                        </div>
                    </div>
                </div>
            </div>

             <span class="text-xs text-slate-500 hidden sm:flex items-center gap-1.5 transition-opacity duration-300" id="save-status">
                 <span class="w-1.5 h-1.5 bg-slate-600 rounded-full"></span>
                 Todas alterações salvas
             </span>
             <button type="submit" form="resumeForm" class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-2 rounded-lg text-xs md:text-sm font-bold shadow-lg shadow-purple-900/20 transition-all flex items-center gap-2 hover:translate-y-px active:translate-y-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                <span class="hidden md:inline">Salvar</span>
            </button>
        </div>
    </header>

    <!-- CONTENT -->
    <div class="flex-1 flex overflow-hidden relative">
        
        <!-- LEFT PANEL: DATA ENTRY -->
        <div class="w-full lg:w-[420px] xl:w-[460px] bg-slate-900/95 border-r border-slate-800 flex flex-col z-20 shadow-[4px_0_24px_rgba(0,0,0,0.3)] backdrop-blur-sm">
            <!-- Tabs -->
            <div class="flex border-b border-slate-800">
                <button type="button" class="flex-1 py-3 text-sm font-semibold text-purple-400 border-b-2 border-purple-500 bg-slate-800/50 cursor-default">Dados do Conteúdo</button>
            </div>

            <!-- Form Container -->
            <div class="flex-1 overflow-y-auto p-5 space-y-8 custom-scrollbar relative">
                <form id="resumeForm" method="POST" class="space-y-8 pb-10">
                    <!-- HIDDEN FIELDS FOR DESIGN PERSISTENCE -->
                    <input type="hidden" name="design_color" id="input_design_color" value="<?php echo htmlspecialchars($resumeData['design']['color'] ?? '#1e293b'); ?>">
                    <input type="hidden" name="design_font" id="input_design_font" value="<?php echo htmlspecialchars($resumeData['design']['font'] ?? 'font-sans'); ?>">
                    <input type="hidden" name="design_size" id="input_design_size" value="<?php echo htmlspecialchars($resumeData['design']['size'] ?? '1'); ?>">
                    <input type="checkbox" name="design_show_photo" id="input_design_show_photo" class="hidden" <?php echo ($resumeData['design']['show_photo'] ?? 1) == 1 ? 'checked' : ''; ?>>
                    <input type="hidden" name="design_photo_x" id="input_design_photo_x" value="<?php echo htmlspecialchars($resumeData['design']['photo_x'] ?? '0'); ?>">
                    <input type="hidden" name="design_photo_y" id="input_design_photo_y" value="<?php echo htmlspecialchars($resumeData['design']['photo_y'] ?? '0'); ?>">
                    <input type="hidden" name="design_text_align" id="input_design_text_align" value="<?php echo htmlspecialchars($resumeData['design']['text_align'] ?? 'left'); ?>">
                    <input type="hidden" name="design_text_x" id="input_design_text_x" value="<?php echo htmlspecialchars($resumeData['design']['text_x'] ?? '0'); ?>">
                    <input type="hidden" name="design_text_y" id="input_design_text_y" value="<?php echo htmlspecialchars($resumeData['design']['text_y'] ?? '0'); ?>">
                    <input type="hidden" name="design_summary_x" id="input_design_summary_x" value="<?php echo htmlspecialchars($resumeData['design']['summary_x'] ?? '0'); ?>">
                    <input type="hidden" name="design_summary_y" id="input_design_summary_y" value="<?php echo htmlspecialchars($resumeData['design']['summary_y'] ?? '0'); ?>">
                    <input type="hidden" name="design_exp_x" id="input_design_exp_x" value="<?php echo htmlspecialchars($resumeData['design']['exp_x'] ?? '0'); ?>">
                    <input type="hidden" name="design_exp_y" id="input_design_exp_y" value="<?php echo htmlspecialchars($resumeData['design']['exp_y'] ?? '0'); ?>">
                    <input type="hidden" name="design_header_type" id="input_design_header_type" value="<?php echo htmlspecialchars($resumeData['design']['header_type'] ?? 'simple'); ?>">
                    
                    <!-- 1. TEMPLATE SELECTOR -->
                    <!-- 1. PRESETS SELECTOR -->
                    <div class="bg-slate-800/40 p-4 rounded-xl border border-slate-700/50">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 block">Modelos Prontos</label>
                        <div class="grid grid-cols-2 gap-2">
                             <button type="button" onclick="applyPreset('classic')" class="px-3 py-3 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-left group transition-all">
                                <span class="block text-slate-200 font-bold text-xs mb-0.5 group-hover:text-purple-400">Padrão Clássico</span>
                                <span class="block text-[10px] text-slate-500">Simples e Elegante</span>
                            </button>
                            <button type="button" onclick="applyPreset('modern_clean')" class="px-3 py-3 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-left group transition-all">
                                <span class="block text-slate-200 font-bold text-xs mb-0.5 group-hover:text-purple-400">Moderno Clean</span>
                                <span class="block text-[10px] text-slate-500">Minimalista</span>
                            </button>
                            <button type="button" onclick="applyPreset('modern_bold')" class="px-3 py-3 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-left group transition-all">
                                <span class="block text-slate-200 font-bold text-xs mb-0.5 group-hover:text-purple-400">Executivo</span>
                                <span class="block text-[10px] text-slate-500">Faixa Sólida</span>
                            </button>
                            <button type="button" onclick="applyPreset('creative')" class="px-3 py-3 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-left group transition-all">
                                <span class="block text-slate-200 font-bold text-xs mb-0.5 group-hover:text-purple-400">Criativo</span>
                                <span class="block text-[10px] text-slate-500">Alinhamento Direita</span>
                            </button>
                        </div>
                        <div class="hidden">
                             <input type="radio" name="template" id="tmpl_modern" value="modern" <?php echo ($resumeData['template'] !== 'classic') ? 'checked' : ''; ?>>
                             <input type="radio" name="template" id="tmpl_classic" value="classic" <?php echo ($resumeData['template'] === 'classic') ? 'checked' : ''; ?>>
                        </div>
                    </div>

                    <!-- DESIGN CONTROLS -->
                    <div class="bg-slate-800/40 p-4 rounded-xl border border-slate-700/50 mb-6">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 block">Personalização</label>
                        
                        <div class="space-y-4">
                            <!-- Color & Font Row -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <span class="text-[10px] text-slate-400 block mb-1">Cor</span>
                                    <div class="flex items-center gap-2">
                                        <input type="color" id="builder-color" value="#1e293b" class="w-full h-9 rounded cursor-pointer border-0 p-0 bg-transparent" oninput="updatePreview()">
                                    </div>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 block mb-1">Fonte</span>
                                    <select id="builder-font" class="w-full h-9 bg-slate-900 border border-slate-700 text-xs text-white rounded px-2 focus:ring-purple-500 outline-none" onchange="updatePreview()">
                                         <option value="font-serif">Clássica</option>
                                         <option value="font-sans">Moderna</option>
                                         <option value="font-mono">Tech</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Size & Photo Row -->
                            <div class="grid grid-cols-2 gap-3 items-end">
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-[10px] text-slate-400">Tamanho</span>
                                        <span id="font-size-val" class="text-[10px] text-slate-500">1.0x</span>
                                    </div>
                                    <input type="range" id="builder-size" min="0.8" max="1.3" step="0.1" value="1.0" class="w-full h-1.5 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-purple-600" oninput="updatePreview(); document.getElementById('font-size-val').innerText = this.value + 'x'">
                                </div>
                                
                                <label class="flex items-center gap-2 cursor-pointer bg-slate-900 border border-slate-700 rounded px-2 h-9 items-center justify-center hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" id="builder-photo" <?php echo ($resumeData['design']['show_photo'] ?? 1) ? 'checked' : ''; ?> class="rounded border-slate-500 text-purple-600 focus:ring-purple-500 w-4 h-4" onchange="togglePhotoSection(); updatePreview()">
                                    <span class="text-xs font-bold text-slate-300">Foto</span>
                                </label>
                            </div>

                            <!-- Alignment -->
                            <div>
                                 <span class="text-[10px] text-slate-400 block mb-1">Alinhamento</span>
                                 <div class="flex bg-slate-900 rounded-lg p-1 border border-slate-700">
                                    <button type="button" onclick="setAlign('left')" id="btn-align-left" class="flex-1 py-1.5 rounded transition-colors text-slate-400 hover:text-white hover:bg-slate-800"><svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7" /></svg></button>
                                    <button type="button" onclick="setAlign('center')" id="btn-align-center" class="flex-1 py-1.5 rounded transition-colors text-slate-400 hover:text-white hover:bg-slate-800"><svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg></button>
                                    <button type="button" onclick="setAlign('right')" id="btn-align-right" class="flex-1 py-1.5 rounded transition-colors text-slate-400 hover:text-white hover:bg-slate-800"><svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6H4m16 6H10m10 6H13" /></svg></button>
                                 </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-5 transition-all duration-300" id="photo-upload-section">
                        <div class="relative group cursor-pointer w-16 h-16 shrink-0" onclick="document.getElementById('photo-upload').click()">
                            <div class="w-16 h-16 rounded-full bg-slate-800 overflow-hidden border-2 border-slate-700 group-hover:border-purple-500 transition-colors">
                                <img id="photo-preview-thumb" src="<?php echo !empty($resumeData['photo_url']) ? htmlspecialchars($resumeData['photo_url']) : '/public/images/default-avatar.png'; ?>" 
                                     onerror="this.src='https://ui-avatars.com/api/?name=User&background=1e293b&color=fff'"
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">Foto de Perfil</label>
                            <p class="text-[10px] text-slate-500 mb-2">Recomendado: 400x400px (JPG/PNG)</p>
                            
                            <input type="hidden" name="photo_url" id="photo-url-input" value="<?php echo htmlspecialchars($resumeData['photo_url'] ?? ''); ?>">
                            <input type="file" id="photo-upload" class="hidden" accept="image/png, image/jpeg, image/webp">
                            
                            <div class="flex gap-2">
                                <button type="button" onclick="document.getElementById('photo-upload').click()" class="text-xs bg-slate-800 hover:bg-slate-700 text-white px-3 py-1.5 rounded border border-slate-700 transition-colors flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                    Upload
                                </button>
                                <button type="button" onclick="openCameraModal()" class="text-xs bg-slate-800 hover:bg-slate-700 text-white px-3 py-1.5 rounded border border-slate-700 transition-colors flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    Câmera IA
                                </button>
                                <?php if(!empty($resumeData['photo_url'])): ?>
                                    <button type="button" onclick="removePhoto()" class="text-xs text-red-500 hover:text-red-400 px-2 py-1.5 transition-colors font-medium ml-auto">Remover</button>
                                <?php endif; ?>
                            </div>
                            <p id="upload-feedback" class="text-[10px] mt-1 hidden"></p>
                        </div>
                    </div>

                    <!-- 3. PERSONAL DATA -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-5 h-5 rounded flex items-center justify-center bg-purple-500/10 text-purple-400 text-xs font-bold">1</span>
                            <h3 class="text-sm font-bold text-white">Informações Pessoais</h3>
                        </div>
                        
                        <div class="grid gap-3">
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($resumeData['full_name'] ?? ''); ?>" placeholder="Nome Completo" 
                                   class="w-full bg-slate-800 border border-slate-700 rounded-lg text-sm px-3 py-2.5 focus:border-purple-500 focus:ring-1 focus:ring-purple-500/50 outline-none transition-all placeholder-slate-400" oninput="updatePreview()">
                            
                            <input type="text" name="job_title" value="<?php echo htmlspecialchars($resumeData['job_title'] ?? ''); ?>" placeholder="Cargo Desejado (Ex: Designer)" 
                                   class="w-full bg-slate-800 border border-slate-700 rounded-lg text-sm px-3 py-2.5 focus:border-purple-500 outline-none transition-all placeholder-slate-400" oninput="updatePreview()">
                            
                             <div class="grid grid-cols-2 gap-3">
                                <input type="email" name="contact_email" value="<?php echo htmlspecialchars($resumeData['contact_email'] ?? ''); ?>" placeholder="Email Profissional" 
                                       class="w-full bg-slate-800 border border-slate-700 rounded-lg text-sm px-3 py-2.5 focus:border-purple-500 outline-none transition-all placeholder-slate-400" oninput="updatePreview()">
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($resumeData['phone'] ?? ''); ?>" placeholder="Telefone / WhatsApp" 
                                       class="w-full bg-slate-800 border border-slate-700 rounded-lg text-sm px-3 py-2.5 focus:border-purple-500 outline-none transition-all placeholder-slate-400" oninput="updatePreview()">
                            </div>

                            <!-- Collapsible Extra Fields -->
                            <details class="group bg-slate-800/30 rounded-lg border border-slate-800 open:border-slate-700 transition-colors">
                                <summary class="text-xs font-medium text-slate-500 hover:text-slate-300 cursor-pointer list-none flex items-center justify-between p-3 select-none">
                                    <span>Campos Opcionais (Links, PcD, Pronomes)</span>
                                    <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </summary>
                                <div class="p-3 pt-0 space-y-3 animate-slide-down">
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="text" name="location" value="<?php echo htmlspecialchars($resumeData['location'] ?? ''); ?>" placeholder="Cidade, UF" class="w-full bg-slate-900 border border-slate-700 rounded-md text-xs px-3 py-2 focus:border-purple-500 outline-none" oninput="updatePreview()">
                                        <input type="text" name="links" value="<?php echo htmlspecialchars($resumeData['links'] ?? ''); ?>" placeholder="Links (LinkedIn...)" class="w-full bg-slate-900 border border-slate-700 rounded-md text-xs px-3 py-2 focus:border-purple-500 outline-none" oninput="updatePreview()">
                                    </div>
                                    <div class="pt-2 grid grid-cols-2 gap-3">
                                        <input type="text" name="social_name" value="<?php echo htmlspecialchars($resumeData['social_name'] ?? ''); ?>" placeholder="Nome Social (Op.)" class="w-full bg-slate-900 border border-slate-700 rounded-md text-xs px-3 py-2 focus:border-purple-500 outline-none" oninput="updatePreview()">
                                        <input type="text" name="gender" value="<?php echo htmlspecialchars($resumeData['gender'] ?? ''); ?>" placeholder="Pronomes (Op.)" class="w-full bg-slate-900 border border-slate-700 rounded-md text-xs px-3 py-2 focus:border-purple-500 outline-none" oninput="updatePreview()">
                                    </div>
                                    <div class="space-y-3 pt-2 border-t border-slate-800/50 mt-2">
                                         <div>
                                            <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1 block">Formação Acadêmica (Curso | Inst. | Ano)</label>
                                            <textarea name="education" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-xs placeholder-slate-500 focus:border-purple-500 outline-none" placeholder="Ex: Graduação em Design | USP | 2019" oninput="updatePreview()"><?php echo htmlspecialchars($resumeData['education'] ?? ''); ?></textarea>
                                         </div>
                                         <div>
                                            <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1 block">Habilidades (Uma por linha)</label>
                                            <textarea name="skills" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-xs placeholder-slate-500 focus:border-purple-500 outline-none" placeholder="Ex: Liderança&#10;Gestão de Projetos" oninput="updatePreview()"><?php echo htmlspecialchars($resumeData['skills'] ?? ''); ?></textarea>
                                         </div>
                                         <div>
                                            <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1 block">Idiomas (Idioma | Nível)</label>
                                            <textarea name="languages" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-xs placeholder-slate-500 focus:border-purple-500 outline-none" placeholder="Ex: Inglês | Avançado&#10;Espanhol | Básico" oninput="updatePreview()"><?php echo htmlspecialchars($resumeData['languages'] ?? ''); ?></textarea>
                                         </div>
                                    </div>
                                    <div class="pt-2 border-t border-slate-800/50">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="is_pcd" id="is_pcd_toggle" class="form-checkbox w-4 h-4 text-purple-600 rounded bg-slate-700 border-slate-600 focus:ring-purple-500 focus:ring-offset-slate-900" onchange="togglePcdField(); updatePreview();" <?php echo !empty($resumeData['is_pcd']) ? 'checked' : ''; ?>>
                                            <span class="text-xs text-slate-300 font-medium">Sou Pessoa com Deficiência (PcD)</span>
                                        </label>
                                        <input type="text" id="pcd_details_input" name="pcd_details" value="<?php echo htmlspecialchars($resumeData['pcd_details'] ?? ''); ?>" placeholder="Detalhes (CID, Adaptações necessárias...)" class="<?php echo !empty($resumeData['is_pcd']) ? '' : 'hidden'; ?> w-full mt-2 bg-slate-900 border border-slate-700 rounded-md text-xs px-3 py-2 focus:border-purple-500 outline-none transition-all" oninput="updatePreview()">
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- 4. SUMMARY -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                 <span class="w-5 h-5 rounded flex items-center justify-center bg-purple-500/10 text-purple-400 text-xs font-bold">2</span>
                                 <h3 class="text-sm font-bold text-white">Resumo Profissional</h3>
                            </div>
                            <button type="button" onclick="enhanceSummaryWithAI()" id="ai-enhance-btn" class="text-xs bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white px-3 py-1.5 rounded-lg transition-all font-medium flex items-center gap-1.5 shadow-lg shadow-purple-500/20 hover:shadow-purple-500/40 hover:scale-105 active:scale-100" title="Melhorar com IA">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                <span>✨ IA</span>
                            </button>
                        </div>
                        <div class="relative">
                            <textarea name="summary" id="summary-textarea" rows="4" class="w-full bg-slate-800 border border-slate-700 rounded-lg text-sm px-3 py-2.5 focus:border-purple-500 outline-none transition-all placeholder-slate-400 leading-relaxed custom-scrollbar resize-y" placeholder="Escreva uma ideia simples do que você faz. Ex: 'Desejo trabalhar com programação java' — A IA vai transformar isso em um texto profissional!" oninput="updatePreview()"><?php echo htmlspecialchars($resumeData['summary'] ?? ''); ?></textarea>
                            <div id="ai-loading" class="absolute inset-0 bg-slate-800/90 rounded-lg hidden items-center justify-center">
                                <div class="flex flex-col items-center gap-2 text-purple-400">
                                    <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span class="text-xs font-medium">Gerando resumo profissional...</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-500">💡 Dica: Escreva suas ideias e clique em "✨ IA" para transformar em texto profissional.</p>
                    </div>

                    <!-- 5. EXPERIENCE -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded flex items-center justify-center bg-purple-500/10 text-purple-400 text-xs font-bold">3</span>
                                <h3 class="text-sm font-bold text-white">Experiências</h3>
                            </div>
                            <button type="button" id="addExpBtn" class="text-xs bg-purple-600/10 hover:bg-purple-600/20 text-purple-400 border border-purple-600/20 hover:border-purple-600/40 px-3 py-1.5 rounded-md transition-all font-medium">+ Adicionar</button>
                        </div>
                        
                        <div id="experiences-container" class="space-y-3">
                             <?php foreach ($experiences as $index => $exp): ?>
                                <div class="experience-item bg-slate-800/40 p-3 rounded-lg border border-slate-700/50 group hover:border-slate-600 transition-colors">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="text-[10px] font-bold text-slate-500 uppercase">Empresa #<?php echo $index + 1; ?></p>
                                        <button type="button" onclick="removeParent(this); updatePreview();" class="text-slate-600 hover:text-red-400 p-1 opacity-0 group-hover:opacity-100 transition-opacity" title="Remover"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></button>
                                    </div>
                                    <div class="grid gap-2">
                                        <div class="grid grid-cols-3 gap-2">
                                            <input type="text" name="exp_company[]" value="<?php echo htmlspecialchars($exp['company'] ?? ''); ?>" class="col-span-2 w-full bg-slate-900 border border-slate-700/50 rounded px-2 py-1.5 text-sm font-medium focus:border-purple-500 outline-none placeholder-slate-400" placeholder="Nome da Empresa" oninput="updatePreview()">
                                            <input type="text" name="exp_date[]" value="<?php echo htmlspecialchars($exp['date'] ?? ''); ?>" class="col-span-1 w-full bg-slate-900 border border-slate-700/50 rounded px-2 py-1.5 text-xs focus:border-purple-500 outline-none placeholder-slate-400" placeholder="Ex: 2020-2023" oninput="updatePreview()">
                                        </div>
                                        <input type="text" name="exp_role[]" value="<?php echo htmlspecialchars($exp['role'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700/50 rounded px-2 py-1.5 text-xs focus:border-purple-500 outline-none placeholder-slate-400" placeholder="Seu Cargo" oninput="updatePreview()">
                                        <textarea name="exp_desc[]" rows="2" class="w-full bg-slate-900 border border-slate-700/50 rounded px-2 py-1.5 text-xs focus:border-purple-500 outline-none resize-none placeholder-slate-400" placeholder="Descrição das atividades..." oninput="updatePreview()"><?php echo htmlspecialchars($exp['desc'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                             <?php endforeach; ?>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- RIGHT PANEL: PREVIEW -->
        <div class="flex-1 bg-slate-950 flex justify-center overflow-hidden p-4 sm:p-8 lg:p-12 relative cursor-grab active:cursor-grabbing selection:bg-purple-200/50 selection:text-purple-900" id="preview-viewport">
            
            <!-- Toolbar Removed -->

            <!-- Zoom Controls -->
            <div class="fixed bottom-6 right-6 flex items-center gap-1 z-40 bg-slate-900/90 backdrop-blur border border-slate-700 rounded-xl shadow-2xl p-1.5">
                 <button onclick="changeZoom(-0.1)" class="text-slate-400 hover:text-white hover:bg-slate-700 p-2 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg></button>
                 <span class="text-slate-300 text-xs font-mono font-bold w-12 text-center select-none" id="zoom-level">85%</span>
                 <button onclick="changeZoom(0.1)" class="text-slate-400 hover:text-white hover:bg-slate-700 p-2 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></button>
            </div>

            <!-- Paper Surface -->
            <div id="resume-preview" class="paper-a4 p-[8%] transition-transform duration-100 ease-out origin-top border border-gray-100/10">
                <!-- Content Injected via JS -->
            </div>

        </div>
    </div>
    
    <!-- Experience Template -->
    <template id="exp-template">
         <div class="experience-item bg-slate-800/40 p-3 rounded-lg border border-slate-700/50 group hover:border-slate-600 transition-colors animate-fade-in">
             <div class="flex justify-between items-start mb-2">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Nova Experiência</p>
                <button type="button" onclick="removeParent(this); updatePreview();" class="text-slate-600 hover:text-red-400 p-1 opacity-100"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></button>
            </div>
            <div class="grid gap-2">
                <div class="grid grid-cols-3 gap-2">
                    <input type="text" name="exp_company[]" class="col-span-2 w-full bg-slate-900 border border-slate-700/50 rounded px-2 py-1.5 text-sm font-medium focus:border-purple-500 outline-none placeholder-slate-400" placeholder="Nome da Empresa" oninput="updatePreview()">
                    <input type="text" name="exp_date[]" class="col-span-1 w-full bg-slate-900 border border-slate-700/50 rounded px-2 py-1.5 text-xs focus:border-purple-500 outline-none placeholder-slate-400" placeholder="Ex: 2020-2023" oninput="updatePreview()">
                </div>
                <input type="text" name="exp_role[]" class="w-full bg-slate-900 border border-slate-700/50 rounded px-2 py-1.5 text-xs focus:border-purple-500 outline-none placeholder-slate-400" placeholder="Seu Cargo" oninput="updatePreview()">
                <textarea name="exp_desc[]" rows="2" class="w-full bg-slate-900 border border-slate-700/50 rounded px-2 py-1.5 text-xs focus:border-purple-500 outline-none resize-none placeholder-slate-400" placeholder="Descrição das atividades..." oninput="updatePreview()"></textarea>
            </div>
        </div>
    </template>

    <script>
        // --- XSS PROTECTION ---
        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        function formatText(text) {
             if(!text) return '';
             // Permite apenas quebra de linha. Negrito/Itálico pode ser adicionado futuramente via Markdown parser simples.
             return escapeHtml(text).replace(/\n/g, '<br>');
        }

    // --- UI HELPERS ---
    let currentAlign = 'left';
    function setAlign(align) {
        currentAlign = align;
        const input = document.getElementById('input_design_text_align');
        if(input) input.value = align;
        updatePreview();
    }

    // --- PRESETS ---
    const PRESETS = {
        'classic': { template: 'classic', align: 'left', header: 'simple' },
        'modern_clean': { template: 'modern', align: 'left', header: 'simple' },
        'modern_bold': { template: 'modern', align: 'left', header: 'solid' },
        'creative': { template: 'modern', align: 'right', header: 'simple' },
        'sidebar': { template: 'sidebar', align: 'left', header: 'simple' }
    };

    function applyPreset(key) {
        const p = PRESETS[key];
        if(!p) return;
        const rad = document.querySelector(`input[name="template"][value="${p.template}"]`);
        if(rad) rad.checked = true;
        
        document.getElementById('input_design_text_align').value = p.align;
        document.getElementById('input_design_header_type').value = p.header;
        
        ['text_x','text_y','summary_x','summary_y','exp_x','exp_y','photo_x','photo_y'].forEach(k => {
             const el = document.getElementById('input_design_' + k); if(el) el.value = 0;
        });
        updatePreview();
    }

    // --- PREVIEW LOGIC ---
    function togglePhotoSection() {
        const checkbox = document.getElementById('builder-photo');
        const section = document.getElementById('photo-upload-section');
        if(!checkbox || !section) return;
        
        if(checkbox.checked) {
            section.classList.remove('opacity-40', 'pointer-events-none', 'grayscale');
        } else {
            section.classList.add('opacity-40', 'pointer-events-none', 'grayscale');
        }
    }

    function updatePreview() {
        const form = document.querySelector('#resumeForm');
        if(!form) return;
        
        // Sync Align UI
        const alignInput = document.getElementById('input_design_text_align');
        if(alignInput) currentAlign = alignInput.value || 'left';
        
        document.querySelectorAll('[id^="btn-align-"]').forEach(btn => btn.className = "p-1 rounded hover:bg-white dark:hover:bg-slate-600 text-slate-500 dark:text-slate-300 transition-all");
        document.getElementById(`btn-align-${currentAlign}`)?.classList.add('bg-white', 'shadow-sm', 'text-purple-600', 'scale-110');

        // Coletar dados
        const data = {
            template: form.querySelector('input[name="template"]:checked')?.value || 'modern',
            fullName: form.querySelector('input[name="full_name"]')?.value || 'Seu Nome',
            jobTitle: form.querySelector('input[name="job_title"]')?.value || 'Cargo',
            email: form.querySelector('input[name="contact_email"]')?.value || '',
            phone: form.querySelector('input[name="phone"]')?.value || '',
            location: form.querySelector('input[name="location"]')?.value || '',
            links: form.querySelector('input[name="links"]')?.value || '',
            socialName: form.querySelector('input[name="social_name"]')?.value || '',
            gender: form.querySelector('input[name="gender"]')?.value || '',
            summary: form.querySelector('textarea[name="summary"]')?.value || '',
            skills: form.querySelector('textarea[name="skills"]')?.value || '',
            languages: form.querySelector('textarea[name="languages"]')?.value || '',
            education: form.querySelector('textarea[name="education"]')?.value || '',
            isPcd: form.querySelector('input[name="is_pcd"]')?.checked || false,
            pcdDetails: form.querySelector('[name="pcd_details"]')?.value || '',
            photoUrl: form.querySelector('#photo-url-input')?.value || '',
            experiences: [],
            // BUILDER SETTINGS
            settings: {
                color: document.getElementById('builder-color')?.value || '#1e293b',
                font: document.getElementById('builder-font')?.value || 'font-sans',
                size: document.getElementById('builder-size')?.value || '1',
                showPhoto: document.getElementById('builder-photo')?.checked ?? true,
                photoX: parseInt(document.getElementById('input_design_photo_x')?.value || 0),
                photoY: parseInt(document.getElementById('input_design_photo_y')?.value || 0),
                textX: parseInt(document.getElementById('input_design_text_x')?.value || 0),
                textY: parseInt(document.getElementById('input_design_text_y')?.value || 0),
                summaryX: parseInt(document.getElementById('input_design_summary_x')?.value || 0),
                summaryY: parseInt(document.getElementById('input_design_summary_y')?.value || 0),
                expX: parseInt(document.getElementById('input_design_exp_x')?.value || 0),
                expY: parseInt(document.getElementById('input_design_exp_y')?.value || 0),
                headerType: document.getElementById('input_design_header_type')?.value || 'simple',
                textAlign: currentAlign
            }
        };

        // Coletar Arrays
        const expCompanies = form.querySelectorAll('input[name="exp_company[]"]');
        expCompanies.forEach((input, i) => {
            const role = form.querySelectorAll('input[name="exp_role[]"]')[i]?.value || '';
            const date = form.querySelectorAll('input[name="exp_date[]"]')[i]?.value || '';
            const desc = form.querySelectorAll('textarea[name="exp_desc[]"]')[i]?.value || '';
            if(input.value || role || desc) { 
                data.experiences.push({ company: input.value, role, date, desc });
            }
        });

        // UI Feedback
        const statusEl = document.getElementById('save-status');
        if(statusEl) statusEl.innerHTML = '<span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse"></span> Editando...';

        // SYNC: Update Hidden Inputs
        const inputColor = document.getElementById('input_design_color');
        const inputFont = document.getElementById('input_design_font');
        const inputSize = document.getElementById('input_design_size');
        const inputPhoto = document.getElementById('input_design_show_photo');
        
        if(inputColor) inputColor.value = data.settings.color;
        if(inputFont) inputFont.value = data.settings.font;
        if(inputSize) inputSize.value = data.settings.size;
        if(inputPhoto) inputPhoto.checked = data.settings.showPhoto;

        // Render Strategy
        const container = document.getElementById('resume-preview');
        const showPhoto = data.photoUrl && data.settings.showPhoto;
        
        const photoEl = showPhoto ? `<img src="${escapeHtml(data.photoUrl)}" class="profile-photo" style="object-fit:cover; pointer-events: none;">` : '';

        if (data.template === 'modern') {
            renderModern(container, data, photoEl);
        } else if (data.template === 'sidebar') {
            renderSidebar(container, data, photoEl);
        } else {
            renderClassic(container, data, photoEl);
        }
        
        if(showPhoto) initDraggableElement('photo-draggable', 'input_design_photo_x', 'input_design_photo_y');
        initDraggableElement('text-draggable', 'input_design_text_x', 'input_design_text_y');
        initDraggableElement('summary-draggable', 'input_design_summary_x', 'input_design_summary_y');
        initDraggableElement('exp-draggable', 'input_design_exp_x', 'input_design_exp_y');
        
        // Update UI State
        togglePhotoSection();
    }

    function renderModern(container, data, photoEl) {
         const primaryColor = data.settings.color;
         const fontSize = data.settings.size;
         const align = data.settings.textAlign;
         const isSolid = data.settings.headerType === 'solid';

         let expHtml = data.experiences.map(exp => `
            <div class="mb-5">
                <div class="flex flex-col mb-1.5">
                    <h4 class="font-bold text-slate-800 text-sm leading-tight" style="font-size: ${0.9 * fontSize}rem">${escapeHtml(exp.role || 'Cargo')}</h4>
                    <span class="text-xs font-semibold tracking-wide uppercase mt-0.5 flex items-center gap-2" style="color: ${primaryColor}; font-size: ${0.75 * fontSize}rem">
                        ${escapeHtml(exp.company || 'Empresa')} 
                        ${exp.date ? `<span class="opacity-40 text-slate-400 font-normal normal-case">• ${escapeHtml(exp.date)}</span>` : ''}
                    </span>
                 </div>
                <div class="text-xs text-slate-600 leading-relaxed text-justify" style="font-size: ${0.75 * fontSize}rem">${formatText(exp.desc)}</div>
            </div>
        `).join('');

        const nameDisplay = escapeHtml(data.socialName ? data.socialName : data.fullName);
        const photoStyle = `transform: translate(${data.settings.photoX}px, ${data.settings.photoY}px); cursor: move; touch-action: none;`;
        const photoBlock = photoEl ? `<div id="photo-draggable" class="w-28 h-28 rounded-full border-4 border-white shadow-sm absolute top-0 right-0 -mt-2 z-10 box-content overflow-hidden bg-white" style="${photoStyle}" title="Arraste a moldura">${photoEl}</div>` : '';
        
        const textStyle = `transform: translate(${data.settings.textX || 0}px, ${data.settings.textY || 0}px); cursor: move; touch-action: none; position: relative; z-index: 20;`;
        
        const summaryStyle = `transform: translate(${data.settings.summaryX||0}px, ${data.settings.summaryY||0}px); cursor: move; touch-action: none; position: relative; z-index: 15;`;
        const expStyle = `transform: translate(${data.settings.expX||0}px, ${data.settings.expY||0}px); cursor: move; touch-action: none; position: relative; z-index: 15;`;

        // Dynamic Flex ALign
        let justifyClass = 'justify-start';
        if(align === 'center') justifyClass = 'justify-center';
        if(align === 'right') justifyClass = 'justify-end';

        // Header Logic
        let headerClass = "flex justify-between items-start border-b-2 pb-8 mb-8 relative min-h-[120px]";
        let headerStyle = `border-color: ${primaryColor}`;
        let titleColor = primaryColor;
        let subtitleColor = primaryColor; 
        let contactColorClass = "text-slate-500";
        let pcdStyle = `color: ${primaryColor}`;

        if(isSolid) {
            headerClass = "flex justify-between items-start relative min-h-[120px] p-6 rounded-xl mb-8 shadow-sm";
            headerStyle = `background-color: ${primaryColor};`;
            titleColor = '#ffffff';
            subtitleColor = '#ffffff'; 
            contactColorClass = "text-white/80";
            pcdStyle = "color: white; background-color: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.3)";
        }

        container.innerHTML = `
            <div class="${data.settings.font} text-slate-800 relative pt-4" style="--primary: ${primaryColor}">
                <div class="${headerClass}" style="${headerStyle}">
                    <!-- Full width container for text, alignment controlled by user -->
                    <div id="text-draggable" class="w-full relative z-0" style="text-align: ${align}; ${textStyle}" title="Arraste o texto">
                        <h1 class="font-extrabold uppercase tracking-tight leading-none mb-2" style="color: ${titleColor}; font-size: ${2.25 * fontSize}rem">${nameDisplay}</h1>
                        <h2 class="font-bold uppercase tracking-wide opacity-80" style="color: ${subtitleColor}; font-size: ${1.125 * fontSize}rem">${escapeHtml(data.jobTitle)}</h2>
                        
                        <div class="text-xs ${contactColorClass} mt-3 font-medium flex flex-wrap gap-x-3 ${justifyClass}" style="font-size: ${0.75 * fontSize}rem">
                            <span>${escapeHtml(data.email)}</span>
                            ${data.phone ? '<span>• ' + escapeHtml(data.phone) + '</span>' : ''}
                            ${data.links ? '<span>• ' + escapeHtml(data.links) + '</span>' : ''}
                        </div>
                         ${data.isPcd ? `<div class="text-[10px] font-bold mt-2 bg-purple-50 inline-block px-2 py-1 rounded border border-purple-100" style="${pcdStyle}">♿ P. Confidencial (PcD) ${data.pcdDetails ? '- '+escapeHtml(data.pcdDetails) : ''}</div>` : ''}
                    </div>
                    ${photoBlock}
                </div>

                ${data.summary ? `
                <div id="summary-draggable" class="mb-8" style="${summaryStyle}" title="Arraste o Resumo">
                    <h3 class="text-xs font-bold text-slate-900 mb-3 uppercase tracking-widest border-b border-slate-100 pb-1" style="font-size: ${0.75 * fontSize}rem">Resumo</h3>
                    <p class="text-sm text-slate-700 leading-relaxed text-justify" style="font-size: ${0.875 * fontSize}rem">${formatText(data.summary)}</p>
                </div>` : ''}

                ${expHtml.length > 0 ? `
                <div id="exp-draggable" style="${expStyle}" title="Arraste a Experiência">
                     <h3 class="text-xs font-bold text-slate-900 mb-4 uppercase tracking-widest border-b border-slate-100 pb-1" style="font-size: ${0.75 * fontSize}rem">Experiência Profissional</h3>
                    ${expHtml}
                </div>` : ''}
            </div>
            <style>.profile-photo { width:100%; height:100%; }</style>
        `;
    }

    function renderSidebar(container, data, photoEl) {
        
        // --- HELPER: Parse Lists ---
        const parseList = (text) => text ? text.split('\n').filter(i => i.trim()) : [];
        const parseKeyVal = (text) => text ? text.split('\n').filter(i => i.trim()).map(i => {
             const parts = i.split('|');
             return { key: parts[0], val: parts[1] || '' };
        }) : [];

        // --- LEFT COLUMN CONTENT ---
        let leftContent = `
            <div class="flex flex-col text-slate-100 h-full">
                <!-- PHOTO -->
                <div class="mb-8 photo-container relative group mx-auto">
                    ${photoEl || '<div class="w-32 h-32 rounded-full bg-white/20 flex items-center justify-center border-4 border-white/30"><span class="text-4xl">👤</span></div>'}
                </div>
                
                <div class="w-full text-left space-y-8 px-6">
                     <!-- CONTACT -->
                     <div>
                        <h4 class="font-bold text-xs uppercase tracking-widest border-b border-white/30 pb-1 mb-3 opacity-80">Contato</h4>
                        <div class="space-y-2.5 text-[11px] leading-tight font-medium opacity-90">
                             ${data.location ? `<div class="flex gap-2 items-start"><span class="opacity-70 mt-0.5">📍</span> <span>${data.location}</span></div>` : ''}
                             ${data.phone ? `<div class="flex gap-2 items-center"><span class="opacity-70">📞</span> <span>${data.phone}</span></div>` : ''}
                             ${data.email ? `<div class="flex gap-2 items-center"><span class="opacity-70">✉️</span> <span class="break-all">${data.email}</span></div>` : ''}
                             ${data.links ? `<div class="flex gap-2 items-start"><span class="opacity-70 mt-0.5">🔗</span> <span class="break-all">${data.links}</span></div>` : ''}
                        </div>
                     </div>

                     <!-- SKILLS -->
                     ${data.skills ? `
                     <div>
                        <h4 class="font-bold text-xs uppercase tracking-widest border-b border-white/30 pb-1 mb-3 opacity-80">Habilidades</h4>
                        <ul class="text-[11px] space-y-1.5 list-disc list-inside opacity-90 font-medium leading-snug">
                            ${parseList(data.skills).map(s => `<li>${s}</li>`).join('')}
                        </ul>
                     </div>` : ''}

                     <!-- LANGUAGES -->
                     ${data.languages ? `
                     <div>
                        <h4 class="font-bold text-xs uppercase tracking-widest border-b border-white/30 pb-1 mb-3 opacity-80">Idiomas</h4>
                        <div class="space-y-3 opacity-90">
                            ${parseKeyVal(data.languages).map(l => `
                                <div>
                                    <div class="flex justify-between text-[11px] font-bold mb-1"><span>${l.key}</span> <span class="text-[10px] opacity-70 font-normal uppercase">${l.val}</span></div>
                                    <div class="h-1.5 bg-white/20 rounded-full overflow-hidden"><div class="h-full bg-white opacity-90" style="width: ${l.val.toLowerCase().includes('avançado') || l.val.toLowerCase().includes('c2') || l.val.toLowerCase().includes('nativo') ? '100%' : l.val.toLowerCase().includes('proficiente') || l.val.toLowerCase().includes('c1') ? '85%' : l.val.toLowerCase().includes('intermediário') || l.val.toLowerCase().includes('b2') ? '60%' : '35%'}"></div></div>
                                </div>
                            `).join('')}
                        </div>
                     </div>` : ''}
                </div>
            </div>
        `;

        // --- RIGHT COLUMN CONTENT ---
        // Education HTML
        let educationHtml = '';
        if(data.education) {
            educationHtml = `
            <div class="mt-8">
                <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-800 border-b border-slate-200 pb-2 mb-4">
                    <span class="w-6 h-6 bg-slate-800 text-white rounded flex items-center justify-center text-xs">🎓</span> Formação Acadêmica
                </h3>
                <div class="space-y-4">
                    ${parseKeyVal(data.education).map(e => `
                        <div class="text-xs border-l-2 border-slate-300 pl-3 ml-1">
                             <div class="font-bold text-slate-800 text-sm">${e.key}</div>
                             <div class="text-slate-500 font-medium">${e.val.replace(/\|/g, ' • ')}</div>
                        </div>
                    `).join('')}
                </div>
            </div>`;
        }
        
        container.innerHTML = `
            <div class="w-full h-full flex bg-white text-slate-800 shadow-xl overflow-hidden sidebar-layout font-sans">
                <!-- SIDEBAR -->
                <div class="w-[32%] shrink-0 text-white flex flex-col pt-10" style="background-color: ${data.settings.color}">
                    ${leftContent}
                </div>
                
                <!-- MAIN -->
                <div class="flex-1 p-8 pt-12 flex flex-col">
                    <!-- HEADER -->
                    <div class="mb-10">
                         <h1 class="text-4xl font-extrabold text-[${data.settings.color}] leading-tight mb-2" style="color: ${data.settings.color}">${data.fullName}</h1>
                         <h2 class="text-xl text-slate-500 font-medium uppercase tracking-wide">${data.jobTitle}</h2>
                    </div>

                    <!-- SUMMARY -->
                    ${data.summary ? `
                    <div class="mb-8">
                        <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-800 border-b border-slate-200 pb-2 mb-3">
                             <span class="w-6 h-6 bg-slate-800 text-white rounded flex items-center justify-center text-xs">📝</span> Perfil Profissional
                        </h3>
                        <p class="text-xs leading-relaxed text-slate-600 text-justify font-medium">${formatText(data.summary)}</p>
                    </div>` : ''}

                    <!-- EXPERIENCE -->
                    <div class="flex-1">
                        <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-800 border-b border-slate-200 pb-2 mb-4">
                            <span class="w-6 h-6 bg-slate-800 text-white rounded flex items-center justify-center text-xs">💼</span> Experiência Profissional
                        </h3>
                        <div class="space-y-6 relative border-l-2 border-slate-100 ml-3 pl-5 py-1">
                            ${data.experiences.map(exp => `
                                <div class="relative group">
                                    <div class="absolute -left-[27px] top-1.5 w-3 h-3 bg-slate-200 rounded-full border-2 border-white group-hover:bg-[${data.settings.color}] transition-colors" style="background-color: ${data.settings.color}"></div>
                                    <h4 class="font-bold text-sm text-slate-800">${exp.role || 'Cargo'}</h4>
                                    <div class="text-xs text-[${data.settings.color}] font-bold mb-1 opacity-80" style="color: ${data.settings.color}">${exp.company} ${exp.date ? `• ${exp.date}` : ''}</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed text-justify">${formatText(exp.desc)}</p>
                                </div>
                            `).join('')}
                        </div>
                        
                        ${educationHtml}
                    </div>
                </div>
            </div>
        `;
    }

    function renderClassic(container, data, photoEl) {
         const primaryColor = data.settings.color;
         const fontSize = data.settings.size;
         const align = data.settings.textAlign;

         let expHtml = data.experiences.map(exp => `
            <div class="mb-5">
                <div class="border-b border-gray-300 pb-1 mb-2" style="font-size: ${0.875 * fontSize}rem; border-color: ${primaryColor}40">
                     <div class="flex justify-between items-baseline">
                        <span class="font-bold text-black uppercase tracking-wide">${escapeHtml(exp.company || 'Empresa')}</span>
                        <span class="text-xs italic text-gray-500">${escapeHtml(exp.date || '')}</span>
                     </div>
                     <div class="font-medium italic text-xs mt-0.5" style="color: ${primaryColor}">${escapeHtml(exp.role || 'Cargo')}</div>
                </div>
                <p class="text-xs text-gray-800 leading-relaxed text-justify" style="font-size: ${0.75 * fontSize}rem">${formatText(exp.desc)}</p>
            </div>
        `).join('');

        const nameDisplay = escapeHtml(data.socialName ? data.socialName : data.fullName);
        const photoStyle = `transform: translate(${data.settings.photoX}px, ${data.settings.photoY}px); cursor: move; touch-action: none;`;
        const photoBlock = photoEl ? `<div id="photo-draggable" class="w-32 h-32 mx-auto mb-4 border p-1 bg-white relative z-10" style="border-color: ${primaryColor}40; overflow: hidden; ${photoStyle}" title="Arraste a moldura">${photoEl}</div>` : '';

        const textStyle = `transform: translate(${data.settings.textX || 0}px, ${data.settings.textY || 0}px); cursor: move; touch-action: none; position: relative; z-index: 20;`;

        // NEW: Summary/Exp Styles
        const summaryStyle = `transform: translate(${data.settings.summaryX||0}px, ${data.settings.summaryY||0}px); cursor: move; touch-action: none; position: relative; z-index: 15;`;
        const expStyle = `transform: translate(${data.settings.expX||0}px, ${data.settings.expY||0}px); cursor: move; touch-action: none; position: relative; z-index: 15;`;

        container.innerHTML = `
            <div class="${data.settings.font} text-black leading-relaxed">
                <div id="text-draggable" class="pb-6 mb-8 border-b-2 max-w-lg mx-auto relative" style="border-color: ${primaryColor}; text-align: ${align}; ${textStyle}" title="Arraste o texto">
                    ${photoBlock}
                    <h1 class="font-bold uppercase tracking-widest mb-2" style="color: ${primaryColor}; font-size: ${1.875 * fontSize}rem">${nameDisplay}</h1>
                    <div class="text-sm uppercase tracking-wider mb-2 font-bold" style="font-size: ${0.875 * fontSize}rem">${escapeHtml(data.jobTitle)}</div>
                    <div class="text-xs italic text-gray-600" style="font-size: ${0.75 * fontSize}rem">
                        ${escapeHtml(data.email)} ${data.phone ? ' | ' + escapeHtml(data.phone) : ''}
                    </div>
                    ${data.links ? `<div class="text-xs mt-1 text-gray-500" style="font-size: ${0.75 * fontSize}rem">${escapeHtml(data.links)}</div>` : ''}
                </div>

                ${data.summary ? `
                <div id="summary-draggable" class="mb-8" style="${summaryStyle}" title="Arraste o Resumo">
                    <h3 class="font-bold border-b text-sm uppercase mb-3 pb-1" style="color: ${primaryColor}; border-color: ${primaryColor}; font-size: ${0.875 * fontSize}rem">Perfil</h3>
                    <p class="text-sm leading-relaxed text-justify" style="font-size: ${0.875 * fontSize}rem">${formatText(data.summary)}</p>
                </div>` : ''}

                ${expHtml.length > 0 ? `
                <div id="exp-draggable" style="${expStyle}" title="Arraste a Experiência">
                    <h3 class="font-bold border-b text-sm uppercase mb-3 pb-1" style="color: ${primaryColor}; border-color: ${primaryColor}; font-size: ${0.875 * fontSize}rem">Experiência</h3>
                    ${expHtml}
                </div>` : ''}
            </div>
            <style>.profile-photo { width:100%; height:100%; object-fit: cover; }</style>
        `;
    }
        
        // --- DRAG HANDLER ---
        function initDraggablePhoto() {
            const el = document.getElementById('photo-draggable');
            if(!el) return;

            let isDragging = false;
            let startX, startY;
            
            // Touch/Mouse Start
            const dragStart = (e) => {
                isDragging = true;
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                
                startX = clientX;
                startY = clientY;
                
                // Pega valor atual do input (source of truth)
                const currentX = parseInt(document.getElementById('input_design_photo_x').value || 0);
                const currentY = parseInt(document.getElementById('input_design_photo_y').value || 0);
                
                el.dataset.startX = startX;
                el.dataset.startY = startY;
                el.dataset.initialX = currentX;
                el.dataset.initialY = currentY;
                
                el.style.transition = 'none';
                el.style.zIndex = '100';
                
                document.addEventListener('mousemove', onDrag);
                document.addEventListener('mouseup', endDrag);
                document.addEventListener('touchmove', onDrag, { passive: false });
                document.addEventListener('touchend', endDrag);
                e.preventDefault();
            };

            el.onmousedown = dragStart;
            el.ontouchstart = dragStart;

            function onDrag(e) {
                if(!isDragging) return;
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                
                const deltaX = clientX - parseFloat(el.dataset.startX);
                const deltaY = clientY - parseFloat(el.dataset.startY);
                
                const newX = parseFloat(el.dataset.initialX) + deltaX;
                const newY = parseFloat(el.dataset.initialY) + deltaY;
                
                el.style.transform = `translate(${newX}px, ${newY}px)`;
                
                if(e.cancelable) e.preventDefault();
            }
            
            function endDrag(e) {
                if(!isDragging) return;
                isDragging = false;
                el.style.transition = '';
                
                // Recalcular final
                const clientX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
                const clientY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
                const deltaX = clientX - parseFloat(el.dataset.startX);
                const deltaY = clientY - parseFloat(el.dataset.startY);
                const finalX = parseFloat(el.dataset.initialX) + deltaX;
                const finalY = parseFloat(el.dataset.initialY) + deltaY;
                
                // Salvar inputs
                document.getElementById('input_design_photo_x').value = Math.round(finalX);
                document.getElementById('input_design_photo_y').value = Math.round(finalY);
                
                document.removeEventListener('mousemove', onDrag);
                document.removeEventListener('mouseup', endDrag);
                document.removeEventListener('touchmove', onDrag);
                document.removeEventListener('touchend', endDrag);
            }
        }

        // --- HELPERS & HANDLERS ---
        let currentZoom = 0.85;
        function changeZoom(delta) {
            currentZoom = Math.min(Math.max(currentZoom + delta, 0.5), 1.2);
            document.getElementById('resume-preview').style.transform = `scale(${currentZoom})`;
            document.getElementById('zoom-level').innerText = Math.round(currentZoom * 100) + '%';
        }

        function removeParent(btn) { btn.closest('.experience-item').remove(); updatePreview(); }
        function togglePcdField() { document.getElementById('pcd_details_input').classList.toggle('hidden'); }

        // --- GENERIC DRAG HANDLER ---
        function initDraggableElement(elementId, inputXId, inputYId) {
            const el = document.getElementById(elementId);
            if (!el) return;

            let isDragging = false;
            let startX, startY, initialX, initialY;

            const getInitialPos = () => {
                const xInput = document.getElementById(inputXId);
                const yInput = document.getElementById(inputYId);
                return { 
                    x: parseInt(xInput ? xInput.value : 0) || 0, 
                    y: parseInt(yInput ? yInput.value : 0) || 0 
                };
            };

            const onMove = (e) => {
                if (!isDragging) return;
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                
                const dx = clientX - startX;
                const dy = clientY - startY;
                
                // Visual update
                el.style.transform = `translate(${initialX + dx}px, ${initialY + dy}px)`;
            };

            const onEnd = (e) => {
                if (!isDragging) return;
                isDragging = false;
                el.style.cursor = 'grab';
                el.style.zIndex = ''; 

                const clientX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
                const clientY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
                
                const dx = clientX - startX;
                const dy = clientY - startY;
                
                const finalX = initialX + dx;
                const finalY = initialY + dy;

                const inX = document.getElementById(inputXId);
                const inY = document.getElementById(inputYId);
                if(inX) inX.value = Math.round(finalX);
                if(inY) inY.value = Math.round(finalY);
                
                // Remove Global Listeners
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onEnd);
                window.removeEventListener('touchmove', onMove);
                window.removeEventListener('touchend', onEnd);
            };

            const onStart = (e) => {
                if(e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON') return;
                e.stopPropagation(); // Stop bubbling
                e.preventDefault();  // Stop selection
                
                isDragging = true;
                const pos = getInitialPos();
                initialX = pos.x;
                initialY = pos.y;
                
                startX = e.touches ? e.touches[0].clientX : e.clientX;
                startY = e.touches ? e.touches[0].clientY : e.clientY;
                
                el.style.cursor = 'grabbing';
                el.style.zIndex = '100';

                // Add Global Listeners
                window.addEventListener('mousemove', onMove);
                window.addEventListener('mouseup', onEnd);
                window.addEventListener('touchmove', onMove, {passive: false});
                window.addEventListener('touchend', onEnd);
            };

            el.addEventListener('mousedown', onStart);
            el.addEventListener('touchstart', onStart, {passive: false});
            el.style.cursor = 'grab';
        }

        // --- GRID HANDLER ---
        function toggleGrid() {
            const preview = document.getElementById('resume-preview');
            preview.classList.toggle('show-grid');
            const btn = document.getElementById('btn-grid');
            if(btn) {
                btn.classList.toggle('bg-purple-100');
                btn.classList.toggle('text-purple-600');
            }
        }

        // --- UPLOAD HANDLER ---
        const photoInput = document.getElementById('photo-upload');
        const feedback = document.getElementById('upload-feedback');
        
        if(photoInput) {
            photoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) uploadFile(this.files[0]);
            });
        }
        
        async function uploadFile(file) {
            if(file.size > 5 * 1024 * 1024) { 
                feedback.innerText = "Erro: Arquivo maior que 5MB.";
                feedback.classList.remove('hidden'); return;
            }
            const formData = new FormData(); formData.append('photo', file);
            feedback.innerText = "Enviando..."; feedback.classList.remove('hidden');

            try {
                const response = await fetch('upload_photo.php', { method: 'POST', body: formData });
                const result = await response.json();
                if(result.success) {
                    document.getElementById('photo-url-input').value = result.url;
                    document.getElementById('photo-preview-thumb').src = result.url;
                    feedback.innerText = "Sucesso!";
                    updatePreview();
                } else throw new Error(result.message);
            } catch (err) { feedback.innerText = "Erro: " + err.message; }
        }

        function removePhoto() {
            document.getElementById('photo-url-input').value = '';
            document.getElementById('photo-preview-thumb').src = '/public/images/default-avatar.png';
            updatePreview();
        }

        document.getElementById('addExpBtn')?.addEventListener('click', () => {
             const container = document.getElementById('experiences-container');
             const template = document.getElementById('exp-template');
             container.appendChild(template.content.cloneNode(true));
        });
        window.onload = function() {
            // INIT TOOLBAR FROM SAVED DATA
            const savedColor = document.getElementById('input_design_color')?.value;
            const savedFont = document.getElementById('input_design_font')?.value;
            const savedSize = document.getElementById('input_design_size')?.value;
            const savedPhoto = document.getElementById('input_design_show_photo')?.checked;

            if(savedColor && document.getElementById('builder-color')) document.getElementById('builder-color').value = savedColor;
            if(savedFont && document.getElementById('builder-font')) document.getElementById('builder-font').value = savedFont;
            if(savedSize && document.getElementById('builder-size')) document.getElementById('builder-size').value = savedSize;
            if(document.getElementById('builder-photo')) document.getElementById('builder-photo').checked = savedPhoto;

            if(window.innerWidth < 1024) changeZoom(-0.25); // Zoom out no mobile
            updatePreview(); 
            // Reset status after first render
            setTimeout(() => { 
                const status = document.getElementById('save-status');
                if(status) status.innerHTML = '<span class="w-1.5 h-1.5 bg-slate-600 rounded-full"></span> Pronto';
            }, 1000);

            // Setup Real-time Analysis
            const formInputs = document.querySelectorAll('#resumeForm input, #resumeForm textarea');
            formInputs.forEach(input => input.addEventListener('input', triggerAnalysis));
            // Initial analysis
            triggerAnalysis();
        };

        // REAL-TIME ANALYSIS LOGIC (DEBOUNCED)
        let analysisTimeout;
        
        function triggerAnalysis() {
            clearTimeout(analysisTimeout);
            analysisTimeout = setTimeout(async () => {
                const form = document.getElementById('resumeForm');
                if(!form) return;
                
                const formData = new FormData(form);
                const data = {};
                
                // Convert FormData to JSON object
                formData.forEach((value, key) => {
                    if (key.endsWith('[]')) {
                        const cleanKey = key.slice(0, -2);
                        if (!data[cleanKey]) data[cleanKey] = [];
                        data[cleanKey].push(value);
                    } else {
                        data[key] = value;
                    }
                });

                // Structure experiences
                if (data.exp_company && Array.isArray(data.exp_company)) {
                    data.experiences = data.exp_company.map((_, i) => ({
                        company: data.exp_company[i],
                        role: data.exp_role ? data.exp_role[i] : '',
                        date: data.exp_date ? data.exp_date[i] : '',
                        desc: data.exp_desc ? data.exp_desc[i] : ''
                    }));
                }

                const scoreContainer = document.getElementById('score-container');
                if (!scoreContainer) return;

                try {
                    const response = await fetch('api/analyze_resume.php', {
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    
                    if (!response.ok) return;
                    
                    const result = await response.json();
                    
                    // Update UI
                    const scoreValue = document.getElementById('score-value');
                    const scoreDot = document.getElementById('score-dot');
                    
                    if(scoreValue) scoreValue.innerText = result.score;
                    
                    // Reset classes
                    scoreContainer.classList.remove('bg-red-900/20', 'bg-yellow-900/20', 'bg-green-900/20', 'border-red-500/30', 'border-yellow-500/30', 'border-green-500/30');
                    if(scoreDot) scoreDot.classList.remove('bg-red-500', 'bg-yellow-500', 'bg-green-500');
                    
                    let colorClass = 'green';
                    if (result.score < 50) colorClass = 'red';
                    else if (result.score < 80) colorClass = 'yellow';
                    
                    scoreContainer.classList.add(`bg-${colorClass}-900/20`, `border-${colorClass}-500/30`);
                    if(scoreDot) scoreDot.classList.add(`bg-${colorClass}-500`);
                    
                    // Render suggestions
                    const suggestionsBox = document.getElementById('analysis-suggestions');
                    if(suggestionsBox) {
                        suggestionsBox.innerHTML = '';
                        
                        if (result.suggestions.length === 0 && result.score >= 90) {
                            suggestionsBox.innerHTML = `<div class="text-green-400 flex items-center gap-2 text-xs"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Excelente trabalho!</div>`;
                        } else {
                            result.suggestions.forEach(s => {
                                let icon = '💡';
                                let textColor = 'text-slate-300';
                                if (s.type === 'critical') { icon = '⚠️'; textColor = 'text-red-400'; }
                                if (s.type === 'warning') { icon = '⚠️'; textColor = 'text-yellow-400'; }
                                
                                const item = document.createElement('div');
                                item.className = `text-[11px] py-1.5 border-b border-slate-700/50 last:border-0 ${textColor} flex gap-2 items-start`;
                                item.innerHTML = `<span class="mt-0.5">${icon}</span> <span>${s.message}</span>`;
                                suggestionsBox.appendChild(item);
                            });
                        }
                    }
                } catch (e) {
                    console.error('Analysis Error:', e);
                }
            }, 1000); // 1s debounce
        }

        // --- AI ENHANCEMENT ---
        async function enhanceSummaryWithAI() {
            const textarea = document.getElementById('summary-textarea');
            const loading = document.getElementById('ai-loading');
            const btn = document.getElementById('ai-enhance-btn');
            const originalText = textarea.value.trim();
            
            if (!originalText) {
                alert('Por favor, escreva algo no campo de resumo antes de usar a IA.');
                return;
            }
            
            // Get additional context from form
            const jobTitle = document.querySelector('input[name="job_title"]')?.value || '';
            const fullName = document.querySelector('input[name="full_name"]')?.value || '';
            
            // Show loading
            loading.classList.remove('hidden');
            loading.classList.add('flex');
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            
            try {
                const response = await fetch('api/enhance_summary.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        text: originalText,
                        job_title: jobTitle,
                        name: fullName
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.enhanced_text) {
                    textarea.value = result.enhanced_text;
                    updatePreview();
                    
                    // Check if generic template was used
                    if (result.used_generic) {
                        // Show feedback option
                        showAiFeedbackOption(jobTitle, originalText);
                        
                        // Yellow border for generic
                        textarea.classList.add('ring-2', 'ring-yellow-500', 'border-yellow-500');
                        setTimeout(() => {
                            textarea.classList.remove('ring-2', 'ring-yellow-500', 'border-yellow-500');
                        }, 3000);
                    } else {
                        // Green border for matched
                        textarea.classList.add('ring-2', 'ring-green-500', 'border-green-500');
                        setTimeout(() => {
                            textarea.classList.remove('ring-2', 'ring-green-500', 'border-green-500');
                        }, 2000);
                    }
                } else {
                    throw new Error(result.message || 'Erro ao processar');
                }
            } catch (err) {
                console.error('AI Enhancement Error:', err);
                alert('Erro ao gerar resumo: ' + err.message);
            } finally {
                loading.classList.add('hidden');
                loading.classList.remove('flex');
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
        
        // Show feedback option when AI doesn't find profession
        function showAiFeedbackOption(jobTitle, userText) {
            const feedbackDiv = document.getElementById('ai-feedback-prompt');
            if (feedbackDiv) feedbackDiv.remove();
            
            const container = document.getElementById('summary-textarea').parentElement;
            const html = `
                <div id="ai-feedback-prompt" class="mt-2 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg animate-fadeIn">
                    <p class="text-xs text-yellow-400 mb-2">
                        <strong>⚠️ Não encontramos competências específicas para "${jobTitle || 'sua área'}".</strong><br>
                        Ajude-nos a melhorar! Descreva sua profissão:
                    </p>
                    <div class="flex gap-2">
                        <input type="text" id="feedback-input" placeholder="Ex: Piloto de drone, Tatuador, etc." 
                               class="flex-1 bg-slate-800 border border-slate-600 rounded px-2 py-1.5 text-xs text-white placeholder-slate-500 focus:border-yellow-500 outline-none">
                        <button type="button" onclick="sendAiFeedback('${jobTitle}', '${userText.replace(/'/g, "\\'")}')" 
                                class="bg-yellow-600 hover:bg-yellow-500 text-white text-xs px-3 py-1.5 rounded font-medium transition-colors">
                            Enviar
                        </button>
                        <button type="button" onclick="document.getElementById('ai-feedback-prompt').remove()" 
                                class="text-slate-500 hover:text-slate-300 text-xs px-2 transition-colors">
                            ✕
                        </button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('afterend', html);
        }
        
        // Send feedback to API
        async function sendAiFeedback(jobTitle, userText) {
            const input = document.getElementById('feedback-input');
            const suggestion = input?.value?.trim();
            
            if (!suggestion) {
                alert('Por favor, descreva sua profissão ou área de atuação.');
                return;
            }
            
            try {
                const response = await fetch('api/ai_feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        job_title: jobTitle,
                        user_text: userText,
                        suggestion: suggestion
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const prompt = document.getElementById('ai-feedback-prompt');
                    if (prompt) {
                        prompt.innerHTML = `
                            <p class="text-xs text-green-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                ${result.message}
                            </p>
                        `;
                        setTimeout(() => prompt.remove(), 4000);
                    }
                } else {
                    throw new Error(result.message);
                }
            } catch (err) {
                alert('Erro ao enviar feedback: ' + err.message);
            }
        }
    </script>

<!-- WEBCAM MODAL (Intelligent) -->
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
                                        <ellipse cx="50" cy="45" rx="28" ry="38" fill="black" />
                                    </mask>
                                </defs>
                                <rect width="100%" height="100%" fill="currentColor" mask="url(#face-hole)" />
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
    // --- WEBCAM LOGIC ---
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
                video: { facingMode: "user", width: { ideal: 640 }, height: { ideal: 640 } }, 
                audio: false 
            });
            video.srcObject = stream;
            video.onloadedmetadata = () => { loading.classList.add('hidden'); };
        } catch (err) {
            console.error("Erro Câmera:", err);
            modal.classList.add('hidden');
            alert("Não foi possível acessar a câmera. Verifique permissões.");
        }
    }

    function closeCameraModal() {
        if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; }
        video.srcObject = null;
        modal.classList.add('hidden');
    }

    function capturePhoto() {
        if (!stream) return;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Converter Canvas -> Blob -> Upload
        canvas.toBlob(blob => {
            closeCameraModal();
            // Simular arquivo para reuso da função de upload
            const file = new File([blob], "camera_capture.jpg", { type: "image/jpeg" });
            uploadFile(file);
        }, 'image/jpeg', 0.9);
    }
</script>
<style>
/* CSS GRID OVERLAY */
#resume-preview.show-grid::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    pointer-events: none;
    z-index: 50;
    background-image: 
        linear-gradient(to right, rgba(0,0,0,0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(0,0,0,0.1) 1px, transparent 1px);
    background-size: 20px 20px;
    box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
}
</style>
</body>
</html>
