-- DROP DATABASE bd_hijinx;
CREATE DATABASE bd_hijinx; -- cambiar nombre al de la página final
use bd_hijinx;

-- Tabla de Usuarios
CREATE TABLE users (
    id_name VARCHAR(15) PRIMARY KEY, -- nombre que servirá como id
    username VARCHAR(50) UNIQUE NOT NULL, -- nombre visible para todo
    email VARCHAR(100) UNIQUE NOT NULL,
    pass VARCHAR(255) NOT NULL,
    birth DATE NOT NULL,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Amistades
CREATE TABLE friends (
    user_id VARCHAR(15),
    friend_id VARCHAR(15),
    stat ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    PRIMARY KEY (user_id, friend_id),
    FOREIGN KEY (user_id) REFERENCES users(id_name) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id_name) ON DELETE CASCADE
);

-- Tabla de Publicaciones
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(15),
    content TEXT,
    likes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id_name) ON DELETE CASCADE
);

-- Tabla de Multimedia
CREATE TABLE multimedia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
	media VARCHAR(255),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Tabla de Comentarios
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
    user_id VARCHAR(15),
    content TEXT,
    likes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id_name) ON DELETE CASCADE
);

-- Tabla de Comunidades
CREATE TABLE communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_comm VARCHAR(100) NOT NULL,
    descrip TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Miembros de Comunidad
CREATE TABLE community_members (
    community_id INT,
    user_id VARCHAR(15),
    role_type ENUM('member', 'admin') DEFAULT 'member',
    PRIMARY KEY (community_id, user_id),
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id_name) ON DELETE CASCADE
);

-- Tabla de Mensajes Privados (Chat)
CREATE TABLE chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id VARCHAR(15),
    user2_id VARCHAR(15),
    FOREIGN KEY (user1_id) REFERENCES users(id_name) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id_name) ON DELETE CASCADE
);

-- Tabla de Mensajes
CREATE TABLE message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT,
    author_id VARCHAR(15),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES chat(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id_name) ON DELETE CASCADE
);

-- Tabla de Reportes
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reported_user_id VARCHAR(15),
    reporting_user_id VARCHAR(15),
    reporting_post_id INT,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_user_id) REFERENCES users(id_name) ON DELETE CASCADE,
    FOREIGN KEY (reporting_user_id) REFERENCES users(id_name) ON DELETE CASCADE,
    FOREIGN KEY (reporting_post_id) REFERENCES posts(id) ON DELETE CASCADE
);
