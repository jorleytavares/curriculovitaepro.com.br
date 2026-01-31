<?php
/**
 * Email Service Provider (Enterprise Edition)
 * Suporte a Templates HTML e Logs detalhados.
 */

class EmailService {
    
    // Configurações
    private const FROM_NAME = "Currículo Vitae Pro";
    private const FROM_EMAIL = "no-reply@vitaepro.com";
    private const TEMPLATE_DIR = __DIR__ . '/../templates/emails/';

    /**
     * Envia um email de recuperação de senha usando template.
     */
    public static function sendPasswordReset($toEmail, $token) {
        $resetLink = self::generateResetLink($toEmail, $token);
        
        $placeholders = [
            'action_url' => $resetLink,
            'email' => $toEmail
        ];

        $htmlContent = self::renderTemplate('reset_password.html', $placeholders);
        $subject = "Recuperar Acesso - " . self::FROM_NAME;

        return self::send($toEmail, $subject, $htmlContent);
    }

    /**
     * Envia um Magic Link para login sem senha.
     */
    public static function sendMagicLink($toEmail, $token) {
        $magicLink = self::generateMagicLink($toEmail, $token);
        
        $placeholders = [
            'action_url' => $magicLink,
            'email' => $toEmail
        ];

        $htmlContent = self::renderTemplate('magic_link.html', $placeholders);
        $subject = "Seu Link de Acesso Mágico - " . self::FROM_NAME;

        return self::send($toEmail, $subject, $htmlContent);
    }

    /**
     * Motor de Renderização de Templates
     * 1. Carrega o Layout Base
     * 2. Carrega o Conteúdo Específico
     * 3. Substitui Variáveis
     */
    private static function renderTemplate($templateName, $data = []) {
        $layoutPath = self::TEMPLATE_DIR . 'layout.html';
        $contentPath = self::TEMPLATE_DIR . $templateName;

        if (!file_exists($layoutPath) || !file_exists($contentPath)) {
            error_log("[EmailService] Template não encontrado: $templateName");
            return "Erro ao carregar template de email.";
        }

        // Carrega arquivos
        $layout = file_get_contents($layoutPath);
        $content = file_get_contents($contentPath);

        // Mescla conteúdo no layout
        $fullHtml = str_replace('{{content}}', $content, $layout);

        // Adiciona variáveis globais
        $data['year'] = date('Y');
        $data['app_name'] = self::FROM_NAME;

        // Substituição de variáveis {{chave}}
        foreach ($data as $key => $value) {
            $fullHtml = str_replace('{{' . $key . '}}', $value, $fullHtml);
        }

        return $fullHtml;
    }

    /**
     * Disparo de Email (Nativo PHP + Log)
     */
    private static function send($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . self::FROM_NAME . ' <' . self::FROM_EMAIL . '>' . "\r\n";
        $headers .= 'Reply-To: ' . self::FROM_EMAIL . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();

        // Tenta envio
        $sent = @mail($to, $subject, $message, $headers);

        // LOG DE DESENVOLVIMENTO (Sempre ativo para debug fácil)
        self::logEmail($to, $subject, $message);

        return $sent;
    }

    /**
     * Utilitário para gerar link absoluto
     */
    private static function generateResetLink($email, $token) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        // Ajuste inteligente do HOST e PATH para funcionar em subpastas ou raiz
        $host = $_SERVER['HTTP_HOST'];
        
        // Detecta se está em subpasta 'vitae/src' ou similar e corrige
        $scriptDir = dirname($_SERVER['PHP_SELF']);
        // Limpa barras duplicadas
        $baseUrl = $protocol . '://' . $host . $scriptDir;
        
        // Remove 'includes' se por acaso foi chamado de lá, o que não devia acontecer via web direto
        // O ideal é apontar para a raiz de src
        $baseUrl = str_replace('/includes', '', $baseUrl);
        $baseUrl = rtrim($baseUrl, '/');

        return "{$baseUrl}/reset_password.php?token={$token}&email=" . urlencode($email);
    }

    /**
     * Gera URL para o Magic Link
     */
    private static function generateMagicLink($email, $token) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $scriptDir = dirname($_SERVER['PHP_SELF']);
        
        $baseUrl = $protocol . '://' . $host . $scriptDir;
        $baseUrl = str_replace('/includes', '', $baseUrl);
        $baseUrl = rtrim($baseUrl, '/');

        // Aponta para magic_login.php
        return "{$baseUrl}/magic_login.php?token={$token}&email=" . urlencode($email);
    }

    /**
     * Grava log em arquivo local (email_log.txt na raiz do projeto)
     */
    private static function logEmail($to, $subject, $message) {
        // Tenta achar a raiz do projeto subindo níveis
        $logFile = __DIR__ . '/../../email_log.txt'; 
        
        // Extrai link se houver para facilitar o clique no log
        preg_match('/href=["\'](http[^"\']+)["\']/', $message, $matches);
        $link = $matches[1] ?? 'N/A';

        $logEntry = str_repeat("=", 50) . PHP_EOL;
        $logEntry .= "DATA: " . date('Y-m-d H:i:s') . PHP_EOL;
        $logEntry .= "PARA: $to" . PHP_EOL;
        $logEntry .= "ASSUNTO: $subject" . PHP_EOL;
        $logEntry .= "LINK RÁPIDO: $link" . PHP_EOL;
        $logEntry .= str_repeat("-", 50) . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
?>
