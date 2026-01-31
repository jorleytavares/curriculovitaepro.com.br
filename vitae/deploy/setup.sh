#!/bin/bash

# Script de Provisionamento Automático para ResumeAI
# Sistema: Ubuntu 22.04 / 24.04 LTS

# 1. Atualizar o sistema
echo "🔄 A atualizar repositórios..."
apt update && apt upgrade -y

# 2. Instalar Nginx, MySQL, PHP e Extensões necessárias
echo "📦 A instalar Nginx, MySQL, PHP 8.2 e ferramentas..."
apt install -y nginx mysql-server php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl unzip git composer zip

# 3. Iniciar serviços
systemctl enable nginx
systemctl start nginx

echo "✅ Ambiente Base Instalado!"
echo "-----------------------------------------------------"
echo "⚠️  Passos Manuais Necessários:"
echo "1. Execute 'mysql_secure_installation' para proteger o banco."
echo "2. Crie o banco 'resume_saas' e o usuário no MySQL."
echo "3. Clone seu repositório em /var/www/resume-saas."
echo "4. Copie o arquivo deploy/nginx.conf para /etc/nginx/sites-available/resume-saas."
echo "-----------------------------------------------------"
