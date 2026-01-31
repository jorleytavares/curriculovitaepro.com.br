-- ============================================
-- Script de Limpeza: Remover Dados de Teste
-- Execute no phpMyAdmin ou MySQL CLI
-- Data: 12/01/2026
-- ============================================

-- ⚠️ ATENÇÃO: Execute com cuidado! Ações são irreversíveis.
-- Faça backup antes: mysqldump -u user -p resume_saas > backup.sql

-- ============================================
-- 1. REMOVER CURRÍCULOS DE USUÁRIOS DE TESTE
-- ============================================
DELETE FROM resumes 
WHERE user_id IN (
    SELECT id FROM users 
    WHERE email LIKE '%teste%' 
       OR email LIKE '%test%'
       OR email LIKE '%@exemplo.com'
       OR email LIKE '%@example.com'
       OR name LIKE '%Teste%'
       OR name LIKE '%Test%'
       OR name LIKE '%Debug%'
);

-- ============================================
-- 2. REMOVER USUÁRIOS DE TESTE
-- ============================================
DELETE FROM users 
WHERE email LIKE '%teste%' 
   OR email LIKE '%test%'
   OR email LIKE '%@exemplo.com'
   OR email LIKE '%@example.com'
   OR name LIKE '%Teste%'
   OR name LIKE '%Test%'
   OR name LIKE '%Debug%';

-- ============================================
-- 3. LIMPAR NOTIFICAÇÕES ÓRFÃS (se tabela existir)
-- ============================================
-- DELETE FROM notifications WHERE user_id NOT IN (SELECT id FROM users);

-- ============================================
-- 4. LIMPAR LOGS DE BUSCA ANTIGOS (opcional)
-- ============================================
-- DELETE FROM search_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ============================================
-- 5. VERIFICAR LIMPEZA
-- ============================================
SELECT 'Usuários restantes:' AS Info, COUNT(*) AS Total FROM users;
SELECT 'Currículos restantes:' AS Info, COUNT(*) AS Total FROM resumes;

-- ============================================
-- 6. RESETAR AUTO_INCREMENT (opcional, após limpeza total)
-- ============================================
-- ALTER TABLE users AUTO_INCREMENT = 1;
-- ALTER TABLE resumes AUTO_INCREMENT = 1;

-- ============================================
-- FIM DO SCRIPT
-- ============================================
