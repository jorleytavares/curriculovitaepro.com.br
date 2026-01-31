<?php
namespace Repositories;

use PDO;
use PDOException;

class ResumeRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findByIdAndUser(int $resumeId, int $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM resumes WHERE id = ? AND user_id = ?");
            $stmt->execute([$resumeId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function create(int $userId, string $title, string $jsonContent) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO resumes (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
            // Se created_at não existir, pode falhar, mas vamos assumir padrão moderno. Se falhar, ajustamos.
            // Para segurança contra erro de coluna, melhor omitir created_at se for timestamp auto ou usar fallback
            // Vou usar a versão segura baseada no código antigo que só usava user_id, title, content
             $stmt = $this->pdo->prepare("INSERT INTO resumes (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $title, $jsonContent]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function update(int $resumeId, int $userId, string $title, string $jsonContent) {
        try {
            $stmt = $this->pdo->prepare("UPDATE resumes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $jsonContent, $resumeId, $userId]);
            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function countByUserId(int $userId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM resumes WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    
    public function findAllByUserId(int $userId) {
        $stmt = $this->pdo->prepare("SELECT id, title, created_at, updated_at FROM resumes WHERE user_id = ? ORDER BY id DESC");
        try {
             $stmt->execute([$userId]);
        } catch (PDOException $e) {
             // Fallback se updated_at não existir
             $stmt = $this->pdo->prepare("SELECT id, title FROM resumes WHERE user_id = ? ORDER BY id DESC");
             $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function delete(int $resumeId, int $userId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM resumes WHERE id = ? AND user_id = ?");
        $stmt->execute([$resumeId, $userId]);
        return $stmt->rowCount() > 0;
    }
}
