<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Segurança: Apenas ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Check duplo no banco para garantir
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    $u = $stmt->fetch();
    if (!$u || $u['role'] !== 'admin') {
        die("⛔ Acesso Negado.");
    }
}

$action = $_REQUEST['action'] ?? '';

// ============================================
// 1. EXPORTAR CSV
// ============================================
if ($action === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users_vitae_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    
    // Header do CSV
    fputcsv($output, ['ID', 'Nome', 'Email', 'Role', 'Plano', 'Data Cadastro']);

    // Dados
    $stmt = $pdo->query("SELECT id, name, email, role, plan, created_at FROM users ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// ============================================
// 2. BANIR (DELETAR) USUÁRIO
// ============================================
if ($action === 'delete_user') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($id && $id != $_SESSION['user_id']) { // Não pode se banir
        // Deleta currículos primeiro (embora o CASCADE do banco deva cuidar disso)
        $pdo->prepare("DELETE FROM resumes WHERE user_id = ?")->execute([$id]);
        
        // Deleta usuário
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        header("Location: admin_dashboard.php?msg=user_deleted");
    } else {
        header("Location: admin_dashboard.php?msg=error_delete");
    }
    exit;
}

// ============================================
// 3. EDITAR USUÁRIO
// ============================================
if ($action === 'edit_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);
    $role = $_POST['edit_role'];
    $plan = $_POST['edit_plan'];
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);

    if ($id) {
        $password = $_POST['edit_password'] ?? '';
        
        if (!empty($password)) {
            // Se forneceu senha, atualiza TUDO
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = ?, email = ?, role = ?, plan = ?, password_hash = ? WHERE id = ?";
            $params = [$name, $email, $role, $plan, $hash, $id];
        } else {
            // Se NÃO forneceu senha, mantém a antiga
            $sql = "UPDATE users SET name = ?, email = ?, role = ?, plan = ? WHERE id = ?";
            $params = [$name, $email, $role, $plan, $id];
        }

        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute($params);
            header("Location: admin_dashboard.php?msg=user_updated");
        } catch (PDOException $e) {
            header("Location: admin_dashboard.php?msg=error_update&debug=" . urlencode($e->getMessage()));
        }
    }
    exit;
}
// ============================================
// 4. CRIAR USUÁRIO (MANUALMENTE)
// ============================================
if ($action === 'create_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['new_name']);
    $email = trim($_POST['new_email']);
    $role = $_POST['new_role'];
    $plan = $_POST['new_plan'];
    $password = $_POST['new_password'];

    // Validação básica
    if (empty($name) || empty($email) || empty($password)) {
        header("Location: admin_dashboard.php?msg=error_empty_fields");
        exit;
    }

    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password_hash, role, plan, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $hash, $role, $plan]);
        
        header("Location: admin_dashboard.php?msg=user_created");
    } catch (PDOException $e) {
        // Erro comum: Email duplicado
        if ($e->getCode() == '23000') {
             header("Location: admin_dashboard.php?msg=error_email_exists");
        } else {
             header("Location: admin_dashboard.php?msg=error_create&debug=" . urlencode($e->getMessage()));
        }
    }
    exit;
}
// ============================================
// 5. WIPE DATABASE (RESET TOTAL)
// ============================================
if ($action === 'wipe_database') {
    try {
        // Preserva o Admin atual
        $currentAdminId = $_SESSION['user_id'];
        
        // 1. Limpar Tabelas Auxiliares
        $pdo->exec("DELETE FROM blog_posts");
        // Verifica se tabela existe antes de deletar (segurança)
        try { $pdo->exec("DELETE FROM search_logs"); } catch(Exception $e) {}
        $pdo->exec("DELETE FROM resumes");
        
        // 2. Limpar todos os usuários EXCETO o atual
        $stmt = $pdo->prepare("DELETE FROM users WHERE id != ?");
        $stmt->execute([$currentAdminId]);
        
        // 3. Resetar Auto increments (Opcional)
        try {
            $pdo->exec("ALTER TABLE resumes AUTO_INCREMENT = 1");
             // users reset pode ser arriscado se mantermos IDs altos
        } catch (Exception $e) {}

        // Redireciona com sucesso
        header("Location: admin_dashboard.php?msg=system_wiped");
        
    } catch (PDOException $e) {
        header("Location: admin_dashboard.php?msg=error_wipe&debug=" . urlencode($e->getMessage()));
    }
    exit;
}
?>
