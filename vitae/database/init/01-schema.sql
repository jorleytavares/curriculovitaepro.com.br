-- ============================================
-- VITAE - Resume SaaS Database Schema
-- Fase 1: Estrutura Relacional Normalizada
-- ============================================

CREATE DATABASE IF NOT EXISTS resume_saas;
USE resume_saas;

-- ============================================
-- Tabela de Usuários
-- Armazena credenciais seguras dos usuários
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user', -- 'admin' or 'user'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de Currículos
-- Mantém referência ao proprietário (user_id)
-- Campo content em JSON para flexibilidade
-- ============================================
CREATE TABLE IF NOT EXISTS resumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL DEFAULT 'Meu Currículo',
    content JSON, -- Flexibilidade para estrutura do CV
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Índices para Performance
-- ============================================
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_resume_user ON resumes(user_id);
