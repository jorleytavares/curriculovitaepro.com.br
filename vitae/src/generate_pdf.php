<?php
/**
 * PDF Generator
 * Engine: DomPDF
 * Tuning: Enterprise Layouts, Security Hardening (Remote Images Restricted)
 */
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/resume_functions.php';

// Segurança: Apenas logados
requireLogin();

// 1. Validação
$resumeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user_id'];

if (!$resumeId) die("ID Inválido.");

$resume = getResumeById($pdo, $resumeId, $userId);
// Modo Admin: Se não for dono, verifica se é admin para auditoria
if (!$resume && isAdmin()) {
    // Busca sem filtro de usuario (admin audit)
    $stmt = $pdo->prepare("SELECT * FROM resumes WHERE id = ?");
    $stmt->execute([$resumeId]);
    $resumeDataRaw = $stmt->fetch(PDO::FETCH_ASSOC);
    if($resumeDataRaw) {
        $resume = ['id' => $resumeDataRaw['id'], 'title' => $resumeDataRaw['title'], 'data' => json_decode($resumeDataRaw['data'], true)];
    }
}

if (!$resume) {
    http_response_code(404);
    die("Currículo não encontrado ou acesso negado.");
}

$data = $resume['data'];
$template = $data['template'] ?? 'modern';
$design = $data['design'] ?? [];

// Configurações Visuais
$primaryColor = $design['color'] ?? ($template === 'modern' ? '#7e22ce' : '#000000');
$scale = floatval($design['size'] ?? 1);
$showPhoto = isset($design['show_photo']) ? (bool)$design['show_photo'] : true;
$photoX = intval($design['photo_x'] ?? 0);
$photoY = intval($design['photo_y'] ?? 0);
$textAlign = $design['text_align'] ?? 'left';
$textX = intval($design['text_x'] ?? 0);
$textY = intval($design['text_y'] ?? 0);
$summaryX = intval($design['summary_x'] ?? 0);
$summaryY = intval($design['summary_y'] ?? 0);
$expX = intval($design['exp_x'] ?? 0);
$expY = intval($design['exp_y'] ?? 0);
$headerType = $design['header_type'] ?? 'simple';

// Mapeamento de Fontes DomPDF
$fontMap = [
    'font-sans' => 'Helvetica',
    'font-serif' => 'Times-Roman', // DomPDF standard name
    'font-mono' => 'Courier'
];
$fontFamily = $fontMap[$design['font'] ?? ''] ?? ($template === 'modern' ? 'Helvetica' : 'Times-Roman');

// Helper de Texto
function safeText($text) {
    return nl2br(htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'));
}

// 2. Processamento Seguro de Imagem
$photoHtml = "";
if ($showPhoto && !empty($data["photo_url"])) {
    $src = "";
    $photoUrl = $data["photo_url"];

    // Caso A: Arquivo Local (Upload Seguro)
    if (strpos($photoUrl, '/public/uploads/photos/') === 0) {
        $relativePath = str_replace('/public/uploads/photos/', '', $photoUrl);
        $fileName = basename($relativePath);
        $systemPath = __DIR__ . '/public/uploads/photos/' . $fileName;
        if (file_exists($systemPath)) $src = $systemPath;
    }
    // Caso B: Base64 Legacy
    elseif (strpos($photoUrl, 'data:image') === 0) {
        $src = $photoUrl;
    }

    if($src) {
        // Opção: Borda colorida se Modern + Pos Custom
        $customPos = "margin-left: {$photoX}px; margin-top: {$photoY}px;";
        $borderStyle = ($template === 'modern') ? "border: 4px solid {$primaryColor}; opacity: 0.9;" : "border: 1px solid #000000;";
        $photoHtml = "<img src=\"{$src}\" class=\"profile-pic\" style=\"{$borderStyle} {$customPos}\">";
    }
}

// 3. Renderização de Experiências (HTML puro, estilos via CSS)
$experiencesHtml = '';
if (isset($data['experiences']) && is_array($data['experiences'])) {
    foreach ($data['experiences'] as $exp) {
        if(empty($exp['company']) && empty($exp['role'])) continue;
        $date = isset($exp['date']) ? $exp['date'] : '';

        if ($template === 'modern') {
            $dateHtml = !empty($date) ? '<span class="date"> • '.safeText($date).'</span>' : '';
            $experiencesHtml .= '
            <div class="item">
                <div class="item-header">
                    <div class="role">' . safeText($exp['role']) . '</div>
                    <div class="company-row">
                        <span class="company">' . safeText($exp['company']) . '</span>' . $dateHtml . '
                    </div>
                </div>
                <div class="item-content">' . safeText($exp['desc']) . '</div>
            </div>';
        } elseif ($template === 'sidebar') {
             $experiencesHtml .= '
            <div class="exp-item">
                 <div class="exp-role">'.safeText($exp['role']).'</div>
                 <div class="exp-meta">'.safeText($exp['company']) . ($date?' | '.safeText($date):'') .'</div>
                 <div class="exp-desc">'.safeText($exp['desc']).'</div>
            </div>';
        } else {
             $experiencesHtml .= '
            <div class="item">
                <div class="item-header-classic" style="border-color: '.$primaryColor.'40">
                    <div style="overflow: hidden; margin-bottom: 2px;">
                        <span class="company" style="float: left;">' . safeText($exp['company']) . '</span>
                        <span class="date" style="float: right; font-style: italic; color: #64748b; font-size: 11px;">' . safeText($date) . '</span>
                    </div>
                    <div class="role-classic" style="color: '.$primaryColor.'; clear: both;">' . safeText($exp['role']) . '</div>
                </div>
                <div class="item-content">' . safeText($exp['desc']) . '</div>
            </div>';
        }
    }
}

// Contato Formatting
$contactParts = [];
if (!empty($data['contact_email'])) $contactParts[] = htmlspecialchars($data['contact_email']);
if (!empty($data['phone'])) $contactParts[] = htmlspecialchars($data['phone']);
if (!empty($data['links'])) $contactParts[] = htmlspecialchars($data['links']);
$contactLine = implode(' | ', $contactParts);

$displayName = !empty($data['social_name']) ? $data['social_name'] : $data['full_name'];
$displayName = mb_strtoupper($displayName, 'UTF-8'); 

// 4. CSS Engine (Dynamic Injection)
// Base Sizes scaled by $scale
$sBody = 13 * $scale;
$sH1 = ($template==='modern'?26:24) * $scale;
$sH2 = ($template==='modern'?14:13) * $scale;
$sComp = ($template==='modern'?14:13) * $scale;
$sRole = ($template==='modern'?12:13) * $scale;

$css = "
    @page { margin: 40px 50px; }
    * { box-sizing: border-box; }
    body { font-size: {$sBody}px; line-height: 1.5; color: #1e293b; font-family: '{$fontFamily}'; }
    h1, h2, h3, h4, p { margin: 0; padding: 0; }
    a { color: inherit; text-decoration: none; }
    .item-content { text-align: justify; margin-top: 4px; color: #334155; white-space: pre-line; }
    
    .pcd-badge { 
        display: inline-block; background-color: #f0f9ff; color: {$primaryColor}; border: 1px solid {$primaryColor};
        font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 4px; 
        margin-top: 6px; text-transform: uppercase; opacity: 0.8;
    }
";

if ($template === 'modern') {
    $isSolid = ($headerType === 'solid');
    $h1Color = $isSolid ? '#ffffff' : $primaryColor;
    $h2Color = $isSolid ? '#ffffff' : $primaryColor;
    $contactColor = $isSolid ? 'rgba(255,255,255,0.9)' : '#64748b';
    
    // Header Wrapper Style
    $headerStyle = $isSolid 
        ? "background-color: {$primaryColor}; padding: 30px; border-radius: 8px; margin-bottom: 30px; color: white;" 
        : "border-bottom: 4px solid {$primaryColor}; padding-bottom: 20px; margin-bottom: 30px;";

    $css .= "
        .header { {$headerStyle} }
        .header-table { width: 100%; border-collapse: collapse; }
        td.left { vertical-align: top; }
        td.right { text-align: right; width: 120px; vertical-align: top; padding-left: 20px; }
        
        h1 { font-size: {$sH1}px; text-transform: uppercase; color: {$h1Color}; letter-spacing: -0.5px; line-height: 1.1; font-weight: 800; }
        h2 { font-size: {$sH2}px; text-transform: uppercase; color: {$h2Color}; margin-top: 4px; font-weight: 700; letter-spacing: 0.5px; opacity: 0.9; }
        
        .contact-info { font-size: ".(11*$scale)."px; color: {$contactColor}; margin-top: 8px; font-weight: 500; }
        .profile-pic { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; display: block; border: 4px solid #fff; }
        
        h3.section-title {
            font-size: ".(12*$scale)."px; font-weight: 800; text-transform: uppercase; color: #334155;
            border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 16px; margin-top: 24px;
            letter-spacing: 1px;
        }
        
        .item { margin-bottom: 18px; page-break-inside: avoid; }
        .item-header { display: block; margin-bottom: 4px; }
        .role { font-size: {$sComp}px; color: #1e293b; font-weight: 800; display: block; margin-bottom: 1px; }
        .company-row { display: block; font-size: {$sRole}px; color: {$primaryColor}; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
        .date { color: #94a3b8; font-weight: normal; text-transform: none; font-size: 11px; }
        
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: center; color: #cbd5e1; font-size: 9px; border-top: 1px solid #f1f5f9; padding-top: 10px; }
    ";
} elseif ($template === 'sidebar') {
    $css .= "
        @page { margin: 0px; }
        body { margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .layout-tbl { width: 100%; border-collapse: collapse; }
        .col-left { width: 32%; background-color: {$primaryColor}; vertical-align: top; color: white; padding: 40px 25px; height: 100%; position: fixed; left: 0; top: 0; bottom: 0; }
        .col-right { width: 68%; vertical-align: top; padding: 50px 40px 40px 35%; } /* Padding left allows space for fixed sidebar */
        
        h1 { font-size: 38px; color: {$primaryColor}; text-transform: uppercase; font-weight: 800; margin-bottom: 5px; line-height: 0.9; }
        h2 { font-size: 16px; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 45px; letter-spacing: 2px; }
        
        h3.section-title {
            font-size: 14px; font-weight: 800; text-transform: uppercase; color: {$primaryColor};
            margin-bottom: 15px; letter-spacing: 1px; border-bottom: none;
        }
        
        .contact-row { margin-bottom: 12px; font-size: 11px; color: rgba(255,255,255,0.9); display: block; }
        .summary-text { font-size: 12px; color: #475569; text-align: justify; line-height: 1.5; padding-left: 15px; border-left: 2px solid #f1f5f9; }
        
        .exp-item { margin-bottom: 25px; padding-left: 15px; border-left: 2px solid #f1f5f9; position: relative; }
        .exp-role { font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.2; margin-bottom: 2px; }
        .exp-meta { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
        .exp-desc { font-size: 11px; color: #475569; text-align: justify; line-height: 1.4; }
        
        .profile-container { width: 140px; height: 140px; border-radius: 50%; overflow: hidden; margin: 0 auto 40px auto; border: 4px solid rgba(255,255,255,0.2); }
        .profile-pic { width: 100%; height: 100%; object-fit: cover; }
    ";
} else {
    $css .= "
        .header { border-bottom: 1px solid {$primaryColor}; padding-bottom: 20px; margin-bottom: 30px; text-align: center; }
        
        h1 { font-size: {$sH1}px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; font-weight: bold; color: {$primaryColor}; }
        h2 { font-size: {$sH2}px; text-transform: uppercase; font-weight: normal; margin-bottom: 10px; letter-spacing: 1px; color: #333; }
        
        .contact-info { font-size: ".(11*$scale)."px; font-style: italic; color: #333; }
        .profile-pic { width: 90px; height: 90px; border: 1px solid {$primaryColor}; margin-bottom: 15px; display: block; margin-left: auto; margin-right: auto; padding: 2px; }
        
        h3.section-title {
            font-size: ".(12*$scale)."px; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid {$primaryColor}; color: {$primaryColor};
            padding-bottom: 2px; margin-bottom: 15px; margin-top: 25px; letter-spacing: 1px;
        }
        
        .item { margin-bottom: 20px; page-break-inside: avoid; }
        .item-header-classic { border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 6px; }
        .company { font-weight: 700; font-size: {$sComp}px; text-transform: uppercase; color: #000; letter-spacing: 0.5px; }
        .role-classic { font-style: italic; font-weight: 600; font-size: {$sRole}px; margin-top: 2px; }

        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: center; color: #94a3b8; font-size: 9px; font-style: italic; }
    ";
}

// 5. HTML Assembly
$htmlBody = "";
if ($template === 'modern') {
    $htmlBody = '
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="left" style="text-align: ' . $textAlign . '">
                    <div style="margin-left: ' . $textX . 'px; margin-top: ' . $textY . 'px; position: relative;">
                        <h1>' . htmlspecialchars($displayName) . '</h1>
                        <h2>' . htmlspecialchars($data['job_title'] ?? '') . '</h2>
                        <div class="contact-info">
                            ' . $contactLine . '
                        </div>
                        ' . (!empty($data['is_pcd']) ? '<div class="pcd-badge">Pessoa com Deficiência ' . (!empty($data['pcd_details']) ? '('.safeText($data['pcd_details']).')' : '') . '</div>' : '') . '
                    </div>
                </td>
                <td class="right">
                    ' . $photoHtml . '
                </td>
            </tr>
        </table>
    </div>';
} elseif ($template === 'sidebar') {
    // Sidebar specific Contact construction
    $contactHtml = '';
    if (!empty($data['phone'])) $contactHtml .= '<div class="contact-row">PHONE: '.htmlspecialchars($data['phone']).'</div>';
    if (!empty($data['contact_email'])) $contactHtml .= '<div class="contact-row">EMAIL: '.htmlspecialchars($data['contact_email']).'</div>';
    if (!empty($data['links'])) $contactHtml .= '<div class="contact-row">LINK: '.htmlspecialchars($data['links']).'</div>';
    
    $htmlBody = '
    <div class="col-left">
        <!-- Photo -->
        <div class="profile-container">
            '. ($photoHtml ? str_replace('width: 100px', '', $photoHtml) : '<div style="width:100%; height:100%; background: rgba(255,255,255,0.1);"></div>') .'
        </div>
        
        <div style="font-weight: 800; font-size: 12px; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 5px;">CONTATO</div>
        '.$contactHtml.'
    </div>
    
    <div class="col-right">
        <h1>' . htmlspecialchars($displayName) . '</h1>
        <h2>' . htmlspecialchars($data['job_title'] ?? '') . '</h2>
        
        ' . ($data['summary'] ? '
        <h3 class="section-title">Perfil Profissional</h3>
        <div class="summary-text">' . safeText($data['summary']) . '</div>
        <div style="margin-bottom: 40px;"></div>
        ' : '') . '
        
        <h3 class="section-title">Experiência Profissional</h3>
        ' . $experiencesHtml . '
    </div>';
} else {
     $htmlBody = '
    <div class="header" style="text-align: ' . $textAlign . '; margin-left: ' . $textX . 'px; margin-top: ' . $textY . 'px; position: relative;">
        ' . $photoHtml . '
        <h1>' . htmlspecialchars($displayName) . '</h1>
        <h2>' . htmlspecialchars($data['job_title'] ?? '') . '</h2>
        <div class="contact-info">
            ' . $contactLine . '
        </div>
        ' . (!empty($data['is_pcd']) ? '<div style="font-weight:bold; font-size:10px; margin-top:5px; color:'.$primaryColor.'">PESSOA COM DEFICIÊNCIA</div>' : '') . '
    </div>';
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>' . $css . '</style>
</head>
<body>
    ' . $htmlBody . '

    ' . (!empty($data['summary']) ? '
    <div class="section" style="margin-left: ' . $summaryX . 'px; margin-top: ' . $summaryY . 'px; position: relative;">
        <h3 class="section-title">Resumo Profissional</h3>
        <div class="item-content">' . safeText($data['summary']) . '</div>
    </div>' : '') . '

    ' . ($experiencesHtml ? '
    <div class="section" style="margin-left: ' . $expX . 'px; margin-top: ' . $expY . 'px; position: relative;">
        <h3 class="section-title">Experiência Profissional</h3>
        ' . $experiencesHtml . '
    </div>' : '') . '
    
    <div class="footer">
        Documento gerado automaticamente pela plataforma Currículo Vitae Pro • ' . date('Y') . '
    </div>
</body>
</html>
';

// 6. PDF Rendering
$options = new Options();
$options->set('isRemoteEnabled', false); // DESABILITADO PARA SEGURANÇA (exceto se whitelist explícita)
// Usamos caminhos locais ($systemPath) para imagens, então Remote não é necessário para uploads locais, apenas para avatares externos.
// Se quisermos suportar avatares externos (Gravatar), podemos habilitar, mas com cautela.
// Por padrão, deixo false para segurança máxima. O script acima resolve URLs locais para caminhos de arquivo.
$options->set('defaultFont', 'Helvetica');
$options->set('chroot', __DIR__); // JAIL: Impede ler arquivos fora de src/

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

// Metadados
$dompdf->addInfo('Title', $displayName . ' - CV');
$dompdf->addInfo('Author', 'Currículo Vitae Pro');
$dompdf->addInfo('Creator', 'Vitae Pro Engine v2.0');

$dompdf->render();

$filename = "Curriculo_" . preg_replace('/[^a-zA-Z0-9]/', '_', explode(' ', $displayName)[0]) . "_" . date('Y') . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
?>
