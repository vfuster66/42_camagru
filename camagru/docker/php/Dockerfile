FROM php:8.2-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libgd-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev

# Nettoyer le cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configurer et installer GD avec le support des formats d'image
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd

# Installer les autres extensions PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers

# Définir le répertoire racine d'Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Modifier la configuration Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Définir la configuration Apache avec les bons paramètres pour upload
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot ${APACHE_DOCUMENT_ROOT}\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    <Directory ${APACHE_DOCUMENT_ROOT}>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php\n\
        php_value upload_max_filesize 10M\n\
        php_value post_max_size 10M\n\
        php_value max_execution_time 300\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Créer le dossier uploads avec les bonnes permissions
RUN mkdir -p public/uploads && \
    chown -R www-data:www-data public/uploads && \
    chmod -R 755 public/uploads

# Copier composer.json et installer les dépendances
COPY composer.json .

# Installer les dépendances
RUN composer update --no-interaction --no-progress

# Copier le reste du projet
COPY . .

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type f -exec chmod 644 {} \; && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    chmod -R 777 public/uploads

# Configurer les limites PHP pour les uploads
RUN echo "upload_max_filesize = 10M\n\
post_max_size = 10M\n\
memory_limit = 256M\n\
max_execution_time = 300" > /usr/local/etc/php/conf.d/uploads.ini

# Exposer le port 80
EXPOSE 80

# Démarrer Apache
CMD ["apache2-foreground"]