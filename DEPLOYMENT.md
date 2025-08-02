# Guia de Deploy - Doe Me API

Este documento fornece instruções detalhadas para fazer o deploy da API Doe Me em diferentes ambientes.

## 🐳 Deploy com Docker (Recomendado)

### Pré-requisitos

- Docker 20.10+
- Docker Compose 2.0+
- Domínio configurado (para produção)

### 1. Preparação do Ambiente

```bash
# Clone o repositório
git clone <repository-url>
cd doe-me-api

# Configure as variáveis de ambiente
cp .env.docker .env
```

### 2. Configuração das Variáveis de Ambiente

Edite o arquivo `.env` com as configurações de produção:

```env
APP_NAME="Doe Me API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.doeme.com

# Gere uma nova chave
APP_KEY=base64:NOVA_CHAVE_AQUI

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=doe_me
DB_USERNAME=doe_me_user
DB_PASSWORD=SENHA_SEGURA_AQUI

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# OAuth - Configure com suas credenciais reais
GOOGLE_CLIENT_ID=seu_google_client_id
GOOGLE_CLIENT_SECRET=seu_google_client_secret
GOOGLE_REDIRECT_URL=https://api.doeme.com/auth/google/callback

FACEBOOK_CLIENT_ID=seu_facebook_client_id
FACEBOOK_CLIENT_SECRET=seu_facebook_client_secret
FACEBOOK_REDIRECT_URL=https://api.doeme.com/auth/facebook/callback
```

### 3. Deploy

```bash
# Construir e iniciar os containers
docker-compose up -d --build

# Executar migrações
docker-compose exec app php artisan migrate --force

# Executar seeders (opcional)
docker-compose exec app php artisan db:seed --force

# Otimizar para produção
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Gerar documentação Swagger
docker-compose exec app php artisan l5-swagger:generate
```

### 4. Configuração do Nginx (Proxy Reverso)

Crie um arquivo de configuração do Nginx para proxy reverso:

```nginx
# /etc/nginx/sites-available/doeme-api
server {
    listen 80;
    server_name api.doeme.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.doeme.com;

    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## 🚀 Deploy Manual (VPS/Servidor Dedicado)

### 1. Preparação do Servidor

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependências
sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-common \
    php8.1-mysql php8.1-pgsql php8.1-zip php8.1-gd php8.1-mbstring \
    php8.1-curl php8.1-xml php8.1-bcmath php8.1-redis \
    nginx postgresql postgresql-contrib redis-server \
    git curl unzip

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Configuração do PostgreSQL

```bash
# Acessar PostgreSQL
sudo -u postgres psql

# Criar banco e usuário
CREATE DATABASE doe_me;
CREATE USER doe_me_user WITH PASSWORD 'senha_segura';
GRANT ALL PRIVILEGES ON DATABASE doe_me TO doe_me_user;
\q
```

### 3. Deploy da Aplicação

```bash
# Clonar repositório
cd /var/www
sudo git clone <repository-url> doe-me-api
sudo chown -R www-data:www-data doe-me-api
cd doe-me-api

# Instalar dependências
sudo -u www-data composer install --no-dev --optimize-autoloader

# Configurar ambiente
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate

# Configurar permissões
sudo chmod -R 755 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# Executar migrações
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force

# Otimizar
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### 4. Configuração do Nginx

```nginx
# /etc/nginx/sites-available/doeme-api
server {
    listen 80;
    server_name api.doeme.com;
    root /var/www/doe-me-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Ativar site
sudo ln -s /etc/nginx/sites-available/doeme-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## ☁️ Deploy na AWS

### 1. EC2 + RDS + ElastiCache

#### EC2 (Aplicação)
```bash
# Usar Amazon Linux 2 ou Ubuntu 20.04
# Seguir passos do deploy manual
# Configurar Security Groups para portas 80, 443, 22
```

#### RDS (PostgreSQL)
```bash
# Criar instância RDS PostgreSQL
# Configurar Security Group para porta 5432
# Atualizar .env com endpoint RDS
DB_HOST=seu-rds-endpoint.amazonaws.com
```

#### ElastiCache (Redis)
```bash
# Criar cluster Redis
# Atualizar .env com endpoint
REDIS_HOST=seu-redis-endpoint.cache.amazonaws.com
```

### 2. Elastic Beanstalk

```bash
# Instalar EB CLI
pip install awsebcli

# Inicializar
eb init

# Criar ambiente
eb create production

# Deploy
eb deploy
```

## 🔧 Configurações de Produção

### 1. Otimizações de Performance

```bash
# Configurar OPcache
echo "opcache.enable=1" >> /etc/php/8.1/fpm/php.ini
echo "opcache.memory_consumption=256" >> /etc/php/8.1/fpm/php.ini
echo "opcache.max_accelerated_files=20000" >> /etc/php/8.1/fpm/php.ini

# Configurar PHP-FPM
echo "pm.max_children = 50" >> /etc/php/8.1/fpm/pool.d/www.conf
echo "pm.start_servers = 5" >> /etc/php/8.1/fpm/pool.d/www.conf
echo "pm.min_spare_servers = 5" >> /etc/php/8.1/fpm/pool.d/www.conf
echo "pm.max_spare_servers = 35" >> /etc/php/8.1/fpm/pool.d/www.conf
```

### 2. Monitoramento

```bash
# Instalar supervisor para queue workers
sudo apt install supervisor

# Configurar worker
sudo tee /etc/supervisor/conf.d/laravel-worker.conf > /dev/null <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/doe-me-api/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/doe-me-api/storage/logs/worker.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 3. Backup

```bash
# Script de backup do banco
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
pg_dump -h localhost -U doe_me_user doe_me > /backups/doe_me_$DATE.sql
find /backups -name "doe_me_*.sql" -mtime +7 -delete
```

### 4. SSL/TLS

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obter certificado
sudo certbot --nginx -d api.doeme.com

# Renovação automática
sudo crontab -e
# Adicionar: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 🔍 Troubleshooting

### Problemas Comuns

1. **Erro de permissão**:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

2. **Erro de conexão com banco**:
```bash
# Verificar se PostgreSQL está rodando
sudo systemctl status postgresql

# Verificar configurações no .env
```

3. **Erro 500**:
```bash
# Verificar logs
tail -f storage/logs/laravel.log

# Limpar cache
php artisan cache:clear
php artisan config:clear
```

## 📊 Monitoramento

### Logs Importantes

- **Aplicação**: `storage/logs/laravel.log`
- **Nginx**: `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
- **PHP-FPM**: `/var/log/php8.1-fpm.log`
- **PostgreSQL**: `/var/log/postgresql/postgresql-13-main.log`

### Métricas para Monitorar

- CPU e memória do servidor
- Conexões de banco de dados
- Tempo de resposta da API
- Taxa de erro (4xx, 5xx)
- Uso do Redis

## 🔄 Atualizações

```bash
# Backup antes da atualização
pg_dump doe_me > backup_pre_update.sql

# Atualizar código
git pull origin main

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Executar migrações
php artisan migrate --force

# Limpar e recriar cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reiniciar serviços
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx
```

---

Para suporte durante o deploy, consulte a documentação ou entre em contato com a equipe de desenvolvimento.

