CREATE DATABASE spacebackendersdb;
USE spacebackendersdb;

-- Skapar tabell för användare
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Unikt ID för varje användare
    username VARCHAR(30) NOT NULL UNIQUE, -- Användarnamn (kan inte vara tomt)
    email VARCHAR(40) NOT NULL UNIQUE, -- E-postadress (unik och kan inte vara tom)
    password VARCHAR(255) NOT NULL, -- Lösenord, satt denna till max längd för att kunna använda kryptering som kan ge längre strängar (Kan inte vara tomt)
    role ENUM('user', 'admin', 'owner') DEFAULT 'user' NOT NULL, -- Användar roller, default är 'user'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Registreringsdatum (default är nuvarande tid)
) ENGINE=InnoDB;

-- Skapar tabell för blogginlägg
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Unikt ID för varje inlägg
    user_id INT NOT NULL, -- Referens till användaren som skapade inlägget (kan inte vara tomt)
    title VARCHAR(70) NOT NULL, -- Titel på inlägget (kan inte vara tomt)
    content TEXT NOT NULL, -- Innehållet i inlägget (kan inte vara tomt)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Tidsstämpel för skapandet av inlägget (default är nuvarande tid)
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE  -- Om användaren tas bort, tas även inlägget bort
) ENGINE=InnoDB;

-- Skapar tabell för kommentarer
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Unikt ID för varje kommentar
    post_id INT NOT NULL, -- Referens till inlägget kommentaren tillhör (kan inte vara tomt)
    user_id INT NOT NULL, -- Referens till användaren som skrev kommentaren (kan inte vara tomt)
    content TEXT NOT NULL, -- Kommentarens innehåll (kan inte vara tomt)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Tidsstämpel för skapandet av kommentaren (default är nuvarande tid)
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE, -- Om inlägget tas bort, tas även kommentaren bort
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE -- Om användaren tas bort, tas även kommentaren bort
) ENGINE=InnoDB;

-- Skapar tabell för gillningar av inlägg
CREATE TABLE post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Unikt ID för varje gillning
    user_id INT NOT NULL, -- Referens till användaren som gillar inlägget (kan inte vara tomt)
    post_id INT NOT NULL, -- Referens till det gillade inlägget (kan inte vara tomt)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Tidsstämpel för när gillningen skapades
    UNIQUE (user_id, post_id), -- Förhindrar att en användare gillar samma inlägg flera gånger
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, -- Om användaren tas bort, tas även gillningen bort
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE -- Om inlägget tas bort, tas även gillningen bort
) ENGINE=InnoDB;

-- Skapar tabell för gillningar av kommentarer
CREATE TABLE comment_likes (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Unikt ID för varje gillning
    user_id INT NOT NULL, -- Referens till användaren som gillar kommentaren (kan inte vara tomt)
    comment_id INT NOT NULL, -- Referens till den gillade kommentaren (kan inte vara tomt)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Tidsstämpel för när gillningen skapades
    UNIQUE (user_id, comment_id), -- Förhindrar att en användare gillar samma kommentar flera gånger
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, -- Om användaren tas bort, tas även gillningen bort
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE -- Om kommentaren tas bort, tas även gillningen bort
) ENGINE=InnoDB;

-- Skapar tabell för Profilbilder
CREATE TABLE images (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT UNIQUE NOT NULL,
    image_data LONGBLOB NOT NULL,
    mime_type VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;