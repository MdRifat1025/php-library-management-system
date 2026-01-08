-- Create Database
CREATE DATABASE IF NOT EXISTS library_system;
USE library_system;

-- Users Table
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('user', 'admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books Table
CREATE TABLE books (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(100) NOT NULL,
  isbn VARCHAR(20) UNIQUE NOT NULL,
  genre VARCHAR(50),
  publisher VARCHAR(100),
  publication_year INT,
  total_copies INT DEFAULT 1,
  available_copies INT DEFAULT 1,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Borrowing Records Table
CREATE TABLE borrowing (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  due_date DATE NOT NULL,
  return_date DATE,
  status ENUM('active', 'returned', 'overdue') DEFAULT 'active',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_book (book_id),
  INDEX idx_status (status)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample books
INSERT INTO books (title, author, isbn, genre, publisher, publication_year, total_copies, available_copies, description) VALUES
('To Kill a Mockingbird', 'Harper Lee', '978-0-06-112008-4', 'Fiction', 'J.B. Lippincott & Co.', 1960, 3, 3, 'A gripping tale of racial injustice and childhood innocence in the American South.'),
('1984', 'George Orwell', '978-0-452-28423-4', 'Dystopian', 'Secker & Warburg', 1949, 2, 2, 'A dystopian social science fiction novel and cautionary tale about totalitarianism.'),
('Pride and Prejudice', 'Jane Austen', '978-0-14-143951-8', 'Romance', 'T. Egerton', 1813, 2, 2, 'A romantic novel of manners that critiques the British landed gentry at the end of the 18th century.'),
('The Great Gatsby', 'F. Scott Fitzgerald', '978-0-7432-7356-5', 'Fiction', 'Charles Scribner\'s Sons', 1925, 3, 3, 'A critique of the American Dream during the Roaring Twenties.'),
('Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', '978-0-439-70818-8', 'Fantasy', 'Bloomsbury', 1997, 5, 5, 'The first novel in the Harry Potter series about a young wizard\'s journey.');