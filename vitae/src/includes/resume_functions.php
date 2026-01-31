<?php
/**
 * Resume Functions Wrapper
 * Refactored to use Services\ResumeService
 */

use Services\ResumeService;
use Repositories\ResumeRepository;
use Repositories\UserRepository;

// Global instance helper
function getResumeService() {
    global $pdo;
    static $service;
    if (!$service) {
        $resumeRepo = new ResumeRepository($pdo);
        $userRepo = new UserRepository($pdo);
        $service = new ResumeService($resumeRepo, $userRepo);
    }
    return $service;
}

function getResumeById($pdo, $resume_id, $user_id) {
    return getResumeService()->getResume($resume_id, $user_id);
}

function saveResume($pdo, $user_id, $data, $resume_id = null) {
    return getResumeService()->saveResume($user_id, $data, $resume_id);
}

function getPlanLimits($plan_type) {
    // Método privado no service, mas podemos replicar ou expor se necessário.
    // O service não expõe isso publicamente, mas o código antigo usava.
    // Vamos manter a lógica aqui por compatibilidade ou expor no service.
    // Vou reimplementar aqui simples, pois é uma função auxiliar pura.
    return match($plan_type) {
        'pro' => 9999,
        default => 1,
    };
}

function canCreateResume($pdo, $user_id) {
    return getResumeService()->canCreateResume($user_id);
}
