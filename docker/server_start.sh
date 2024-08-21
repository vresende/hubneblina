#!/bin/bash

# Ajustar permissões do diretório de cache do Composer
mkdir -p /var/www/.composer/cache
mkdir -p /var/www/storage/app/documents/padrao
chown -R www-data:www-data /var/www/.composer

# shellcheck disable=SC2164
cd /var/www
# Instalar dependências do Composer
composer install --no-dev --optimize-autoloader

# Instalar dependências do NPM
npm install

# Geração da chave de aplicação
if [ ! -f /var/www/.env ]; then
    cp /var/www/.env.example /var/www/.env
fi
#chmod -R 777 /var/www/storage
#chown -R www-data:www-data /var/www/storage
php artisan key:generate
# Iniciar o Laravel Octane com Swoole em segundo plano
php artisan octane:start --watch --server=swoole --host=0.0.0.0 --port=8081 &

# Manter o contêiner ativo
tail -f /dev/null
