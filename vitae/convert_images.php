<?php
// Script para converter imagens para AVIF

$sourceDir = __DIR__ . '/public/images';
$files = scandir($sourceDir);
$supportedExtensions = ['png', 'jpg', 'jpeg'];

echo "Iniciando conversão de imagens para AVIF...\n";

if (!function_exists('imageavif')) {
    die("ERRO: Sua versão do PHP/GD não suporta AVIF. Impossível converter.\n");
}

foreach ($files as $file) {
    $path = $sourceDir . '/' . $file;
    if (!is_file($path)) continue;

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, $supportedExtensions)) continue;

    $filename = pathinfo($path, PATHINFO_FILENAME);
    $targetPath = $sourceDir . '/' . $filename . '.avif';

    echo "Convertendo: $file -> {$filename}.avif\n";

    $image = null;
    if ($ext === 'png') {
        $image = imagecreatefrompng($path);
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    } elseif ($ext === 'jpg' || $ext === 'jpeg') {
        $image = imagecreatefromjpeg($path);
    }

    if ($image) {
        // Converter para AVIF com qualidade 80 (balanço bom entre tamanho/qualidade)
        $result = imageavif($image, $targetPath, 80);
        imagedestroy($image);
        
        if ($result) {
            echo "SUCCESS: $targetPath criado.\n";
        } else {
            echo "FAIL: Não foi possível salvar $targetPath.\n";
        }
    } else {
        echo "FAIL: Não foi possível carregar $file.\n";
    }
}

echo "Concluído!\n";
