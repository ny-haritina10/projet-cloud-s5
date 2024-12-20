# Utiliser une image PHP avec support FPM
FROM php:8.2-fpm

# Installer les dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuration PHP pour la production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Définir le dossier de travail
WORKDIR /var/www/backend

# Copier les fichiers nécessaires pour installer les dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-dev

# Copier le reste du projet dans l'image
COPY . .

# Définir les permissions pour le cache et les logs
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Finaliser l'installation Composer
RUN composer dump-autoload --optimize

# Exposer le port Laravel
EXPOSE 8000

# Commande par défaut pour démarrer Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]