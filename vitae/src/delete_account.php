<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // 1. Buscar a foto de perfil antes de deletar para remover o arquivo
    // Precisamos buscar isso nos currículos, já que a foto fica salva no JSON do currículo ou em uma pasta de uploads
    // Mas o sistema salva o caminho da foto no JSON do currículo.
    // Se a foto for única por usuário ou múltipla, precisamos varrer.
    
    // Vamos buscar todos os currículos do usuário
    $stmt = $pdo->prepare("SELECT data FROM resumes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resumes as $resume) {
        $data = json_decode($resume['data'], true);
        if (!empty($data['photo_url'])) {
            $photoPath = __DIR__ . '/uploads/photos/' . basename($data['photo_url']);
            if (file_exists($photoPath)) {
                @unlink($photoPath); // Deleta o arquivo físico da foto
            }
        }
    }

    try {
        $pdo->beginTransaction();

        // 2. Deletar Currículos (O banco pode ter CASCADE, mas garantimos aqui)
        $stmt = $pdo->prepare("DELETE FROM resumes WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // 3. Deletar Usuário
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();

        // 4. Logout e Destruição da Sessão
        session_destroy();
        
        // Redireciona com feedback
        header("Location: index.php?msg=account_deleted");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao excluir conta: " . $e->getMessage());
    }
}
?>
