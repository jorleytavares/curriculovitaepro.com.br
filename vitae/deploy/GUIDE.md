# Guia de Deploy (Ubuntu + Nginx)

Este guia assume que tem um servidor VPS limpo (DigitalOcean, Linode, AWS) a correr Ubuntu 22.04 ou 24.04.

## 1. Configuração Inicial do Servidor

1.  Aceda ao servidor via SSH: `ssh root@seu-ip`.
2.  Copie o ficheiro `setup.sh` para o servidor (ou crie-o lá com `nano setup.sh`).
3.  Dê permissão de execução e corra:
    ```bash
    chmod +x setup.sh
    ./setup.sh
    ```

## 2. Configuração do Banco de Dados

Crie o banco e o usuário para o sistema:

```bash
sudo mysql -u root -p
```

Dentro do MySQL console:
```sql
CREATE DATABASE resume_saas;
CREATE USER 'vitae_user'@'localhost' IDENTIFIED BY 'SUA_SENHA_FORTE';
GRANT ALL PRIVILEGES ON resume_saas.* TO 'vitae_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Instalação da Aplicação

1.  Crie a pasta e clone o repositório (ou faça upload dos arquivos):
    ```bash
    mkdir -p /var/www/resume-saas
    # Upload via SFTP ou Git Clone aqui...
    ```

2.  Ajuste as permissões (Crucial):
    ```bash
    sudo chown -R www-data:www-data /var/www/resume-saas
    sudo chmod -R 755 /var/www/resume-saas
    ```

3.  Instale as dependências do PHP:
    ```bash
    cd /var/www/resume-saas/src
    sudo -u www-data composer install --no-dev --optimize-autoloader
    ```

4.  Importe o banco de dados:
    ```bash
    mysql -u vitae_user -p resume_saas < ../database/init/01-schema.sql
    ```

## 4. Configuração do Nginx

1.  Copie a configuração:
    ```bash
    sudo cp /var/www/resume-saas/deploy/nginx.conf /etc/nginx/sites-available/resume-saas
    ```

2.  Edite para colocar seu domínio:
    ```bash
    sudo nano /etc/nginx/sites-available/resume-saas
    # Altere server_name e root se necessário
    ```

3.  Ative o site:
    ```bash
    sudo ln -s /etc/nginx/sites-available/resume-saas /etc/nginx/sites-enabled/
    sudo rm /etc/nginx/sites-enabled/default
    sudo nginx -t # Teste a configuração
    sudo systemctl reload nginx
    ```

## 5. SSL (HTTPS)

Para finalizar e ter o cadeado verde:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com
```

✅ **Pronto! Seu SaaS está no ar.**
