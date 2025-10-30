-- Database schema for TokoBook (aligned with application code)
-- The application expects database name 'tokobook' (see db.php)
CREATE DATABASE IF NOT EXISTS tokobook DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE tokobook;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    author VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    stock INT DEFAULT 0,
    category_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add FK from books.category_id to categories.id
ALTER TABLE books ADD CONSTRAINT IF NOT EXISTS fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped') DEFAULT 'pending',
    payment_method VARCHAR(50),
    shipping_address TEXT,
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional seed/example data. Adjust or remove in production.
INSERT INTO categories (name) VALUES ('Fiksi'), ('Non-Fiksi'), ('Teknologi') ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO books (title, author, description, price, image, category_id, stock)
VALUES
('Belajar PHP', 'Penulis A', 'Buku tentang PHP.', 85000.00, 'belajar-php.jpg', (SELECT id FROM categories WHERE name='Teknologi' LIMIT 1), 10),
('Novel Contoh', 'Penulis B', 'Contoh novel.', 65000.00, 'novel-contoh.jpg', (SELECT id FROM categories WHERE name='Fiksi' LIMIT 1), 5)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- Example admin account placeholder. Replace PASSWORD_HASH_PLACEHOLDER with PHP password_hash output.
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '$2y$10$PASSWORD_HASH_PLACEHOLDER', 'admin')
ON DUPLICATE KEY UPDATE username = VALUES(username);
