<?php
namespace Services;

use PDO;
use PDOException;

class AuthService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->initializeSession();
    }

    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);
            
            $isSecure = false;
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $isSecure = true;
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
                $isSecure = true;
            }

            if ($isSecure) {
                ini_set('session.cookie_secure', 1);
            }

            session_start();
        }
    }

    public function getClientIp(): string {
        if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '0.0.0.0';
    }

    public function isDisposableEmail(string $email): bool {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        
        // Caminho relativo para config
        $configPath = __DIR__ . '/../../config/disposable_emails.php';
        if (file_exists($configPath)) {
            $disposableDomains = require $configPath;
            return is_array($disposableDomains) && in_array($domain, $disposableDomains, true);
        }
        return false;
    }

    public function register(string $name, string $email, string $password): array {
        $name = trim(strip_tags($name));
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'E-mail inválido.'];
        }

        if ($this->isDisposableEmail($email)) {
            return ['success' => false, 'message' => 'Não aceitamos e-mails temporários. Por favor, use um e-mail permanente.'];
        }

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Este e-mail já está em uso.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
            if ($stmt->execute([$name, $email, $hash])) {
                return ['success' => true, 'message' => 'Conta criada com sucesso!'];
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '42S22') { // Column not found fallback
                 $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                 if ($stmt->execute([$name, $email, $hash])) {
                     return ['success' => true, 'message' => 'Conta criada com sucesso!'];
                 }
            }
            error_log("Register Error: " . $e->getMessage());
        }
        
        return ['success' => false, 'message' => 'Erro interno ao criar conta.'];
    }

    public function login(string $email, string $password): array {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
        } catch (PDOException $e) {
            $stmt = $this->pdo->prepare("SELECT id, name, password_hash FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            $_SESSION['logged_in'] = true;
            
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Credenciais inválidas.'];
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function isAdmin(): bool {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header("Location: /login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public function requireAdmin(): void {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header("Location: /dashboard.php");
            exit;
        }
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: /login.php");
        exit;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['role']
        ];
    }
}
