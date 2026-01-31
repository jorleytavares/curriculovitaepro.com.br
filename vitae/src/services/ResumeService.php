<?php
namespace Services;

use Repositories\ResumeRepository;
use Repositories\UserRepository;
use Exception;

class ResumeService {
    private $resumeRepo;
    private $userRepo;

    public function __construct(ResumeRepository $resumeRepo, UserRepository $userRepo) {
        $this->resumeRepo = $resumeRepo;
        $this->userRepo = $userRepo;
    }

    public function getResume(int $resumeId, int $userId) {
        $resume = $this->resumeRepo->findByIdAndUser($resumeId, $userId);
        if ($resume) {
            $resume['data'] = json_decode($resume['content'], true) ?? [];
        }
        return $resume;
    }

    public function listResumes(int $userId) {
        return $this->resumeRepo->findAllByUserId($userId);
    }

    public function saveResume(int $userId, array $data, ?int $resumeId = null) {
        $title = $data['title'] ?? 'Meu Currículo';
        unset($data['title']);
        
        $jsonContent = json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($resumeId) {
            // Update - Não precisa checar limite de criação
            if ($this->resumeRepo->update($resumeId, $userId, $title, $jsonContent)) {
                return ['success' => true, 'id' => $resumeId];
            }
            return ['success' => false, 'message' => 'Erro ao atualizar ou currículo não encontrado.'];
        } else {
            // Create - Precisa checar limite
            if (!$this->canCreateResume($userId)) {
                return ['success' => false, 'message' => 'Limite de currículos atingido para seu plano.'];
            }

            try {
                $id = $this->resumeRepo->create($userId, $title, $jsonContent);
                return ['success' => true, 'id' => $id];
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erro ao criar currículo: ' . $e->getMessage()];
            }
        }
    }

    public function deleteResume(int $resumeId, int $userId) {
        return $this->resumeRepo->delete($resumeId, $userId);
    }

    public function canCreateResume(int $userId): bool {
        $user = $this->userRepo->findById($userId);
        if (!$user) return false;

        $userPlan = $user['plan'] ?? 'free';
        $userRole = $user['role'] ?? 'user';

        // Admins always allow
        if ($userRole === 'admin') return true;

        $currentCount = $this->resumeRepo->countByUserId($userId);
        $limit = $this->getPlanLimits($userPlan);

        return $currentCount < $limit;
    }

    private function getPlanLimits(string $planType): int {
        return match($planType) {
            'pro' => 9999,
            default => 1,
        };
    }
}
