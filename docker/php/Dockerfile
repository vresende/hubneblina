# Use a imagem oficial do PHP como base
FROM php:8.2-fpm

# Atualize o repositório de pacotes e instale dependências necessárias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libmcrypt-dev \
    libxml2-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    supervisor \
    curl \
    tzdata \
    && docker-php-ext-install zip pdo pdo_mysql mysqli mbstring exif pcntl bcmath gd \
    && pecl install swoole && docker-php-ext-enable swoole

ENV TZ=America/Sao_Paulo
# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js (v20.15.11)
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# Limpar o cache do apt-get
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configurar o diretório de trabalho
WORKDIR /var/www


# Expor a porta que o Swoole usará
EXPOSE 1215
