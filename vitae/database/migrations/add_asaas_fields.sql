-- ============================================
-- Migration: Add Asaas Payment Fields to Users
-- Run this script to add payment-related columns
-- ============================================

-- Adiciona campos de pagamento na tabela users
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS asaas_id VARCHAR(100) DEFAULT NULL COMMENT 'ID do cliente no Asaas',
    ADD COLUMN IF NOT EXISTS plan VARCHAR(20) DEFAULT 'free' COMMENT 'Plano atual: free ou pro',
    ADD COLUMN IF NOT EXISTS plan_expires_at DATETIME DEFAULT NULL COMMENT 'Data de expiração do plano',
    ADD COLUMN IF NOT EXISTS asaas_subscription_id VARCHAR(100) DEFAULT NULL COMMENT 'ID da assinatura ativa no Asaas';

-- Índice para buscar por asaas_id
CREATE INDEX IF NOT EXISTS idx_user_asaas ON users(asaas_id);
