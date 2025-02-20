# Utilisation de l'image officielle PHP avec Apache
FROM php:8.2-apache

# Mise à jour et installation des dépendances nécessaires
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libgd-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    msmtp \
    msmtp-mta \
    ca-certificates \
    gettext && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql

# Création d'un fichier modèle pour msmtp avec variables d'environnement
RUN echo "defaults" > /etc/msmtprc.template && \
    echo "auth on" >> /etc/msmtprc.template && \
    echo "tls on" >> /etc/msmtprc.template && \
    echo "tls_trust_file /etc/ssl/certs/ca-certificates.crt" >> /etc/msmtprc.template && \
    echo "account default" >> /etc/msmtprc.template && \
    echo "host smtp.gmail.com" >> /etc/msmtprc.template && \
    echo "port 587" >> /etc/msmtprc.template && \
    echo "user \$GMAIL_ADDRESS" >> /etc/msmtprc.template && \
    echo "password \$GMAIL_PASSWORD" >> /etc/msmtprc.template && \
    echo "from \$GMAIL_ADDRESS" >> /etc/msmtprc.template && \
    echo "logfile /var/log/msmtp.log" >> /etc/msmtprc.template

# Définition de msmtp comme agent de messagerie par défaut pour PHP
RUN echo "sendmail_path = /usr/bin/msmtp -t" > /usr/local/etc/php/conf.d/msmtp.ini

# Activation des modules Apache nécessaires
RUN a2enmod rewrite headers

# Définition du DocumentRoot d'Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Configuration d'Apache pour refléter le nouveau DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configuration PHP pour optimiser les téléchargements et l'exécution
RUN echo "upload_max_filesize = 10M\n\
post_max_size = 10M\n\
memory_limit = 256M\n\
max_execution_time = 300" > /usr/local/etc/php/conf.d/uploads.ini

# Définition du répertoire de travail
WORKDIR /var/www/html

# Création des dossiers nécessaires et attribution des permissions
RUN mkdir -p public/uploads && \
    chown -R www-data:www-data public/uploads && \
    chmod -R 755 public/uploads

# Copie du code source dans le conteneur
COPY . .

# Gestion des permissions sur les fichiers et dossiers
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type f -exec chmod 644 {} \; && \
    find /var/www/html -type d -exec chmod 755 {} \;

# Ajout d'un script de démarrage pour remplacer les variables d'environnement dans /etc/msmtprc
CMD sh -c "envsubst < /etc/msmtprc.template > /etc/msmtprc && \
    chown www-data:www-data /etc/msmtprc && \
    chmod 600 /etc/msmtprc && \
    apache2-foreground"

# Exposition du port 80
EXPOSE 80
