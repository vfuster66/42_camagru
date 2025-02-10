-- Création de la base de données avec encodage UTF-8
CREATE DATABASE IF NOT EXISTS camagru
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE camagru;

-- Suppression des tables existantes dans l'ordre pour respecter les contraintes de clés étrangères
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS images;
DROP TABLE IF EXISTS users;

-- Table utilisateurs avec contraintes de sécurité renforcées
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_notifications BOOLEAN DEFAULT TRUE,
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_expires TIMESTAMP DEFAULT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires TIMESTAMP DEFAULT NULL,
    last_login TIMESTAMP DEFAULT NULL,
    failed_login_attempts INT UNSIGNED DEFAULT 0,
    account_locked BOOLEAN DEFAULT FALSE,
    account_locked_until TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_username CHECK (LENGTH(username) >= 3),
    CONSTRAINT chk_email CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
) ENGINE=InnoDB;

-- Table images avec gestion des métadonnées
CREATE TABLE images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    width INT UNSIGNED,
    height INT UNSIGNED,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_mime_type CHECK (mime_type IN ('image/jpeg', 'image/png', 'image/gif'))
) ENGINE=InnoDB;

-- Table commentaires avec modération
CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    is_inappropriate BOOLEAN DEFAULT FALSE,
    reported_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_content CHECK (LENGTH(content) >= 1 AND LENGTH(content) <= 1000)
) ENGINE=InnoDB;

-- Table likes avec horodatage
CREATE TABLE likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (image_id, user_id),
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Index pour optimiser les requêtes fréquentes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_images_user_id ON images(user_id);
CREATE INDEX idx_comments_image_id ON comments(image_id);
CREATE INDEX idx_likes_image_id ON likes(image_id);