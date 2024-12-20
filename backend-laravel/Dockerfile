# Utiliser une image PHP avec support FPM
FROM php:8.1-fpm

# Installer les dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev\
    git \
    curl\
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www/html

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