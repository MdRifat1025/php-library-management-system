<?php
// config.php - Database Connection
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_system');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

session_start();

// Helper Functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($location) {
    header("Location: " . $location);
    exit;
}

// ===== AUTHENTICATION FUNCTIONS =====

function register_user($username, $email, $password, $full_name) {
    global $conn;
    
    if ($conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'") ->num_rows > 0) {
        return array('success' => false, 'message' => 'Username or email already exists');
    }
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    if ($conn->query("INSERT INTO users (username, email, password, full_name) VALUES ('$username', '$email', '$hashed_password', '$full_name')")) {
        return array('success' => true, 'message' => 'Registration successful');
    } else {
        return array('success' => false, 'message' => 'Registration failed');
    }
}

function login_user($username, $password) {
    global $conn;
    
    $result = $conn->query("SELECT id, username, email, role, password FROM users WHERE username='$username'");
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return array('success' => true, 'message' => 'Login successful');
        }
    }
    return array('success' => false, 'message' => 'Invalid username or password');
}

function logout_user() {
    session_destroy();
    redirect('index.php');
}

// ===== USER MANAGEMENT FUNCTIONS (ADMIN) =====

function get_all_users() {
    global $conn;
    return $conn->query("SELECT id, username, email, full_name, role, created_at FROM users ORDER BY created_at DESC");
}

function get_user_by_id($id) {
    global $conn;
    return $conn->query("SELECT id, username, email, full_name, role FROM users WHERE id=$id")->fetch_assoc();
}

function create_user_by_admin($username, $email, $password, $full_name, $role) {
    global $conn;
    
    if ($conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'") ->num_rows > 0) {
        return array('success' => false, 'message' => 'Username or email already exists');
    }
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $role = $conn->real_escape_string($role);
    
    if ($conn->query("INSERT INTO users (username, email, password, full_name, role) VALUES ('$username', '$email', '$hashed_password', '$full_name', '$role')")) {
        return array('success' => true, 'message' => 'User created successfully');
    } else {
        return array('success' => false, 'message' => 'Failed to create user');
    }
}

function update_user($id, $username, $email, $full_name, $role, $password = null) {
    global $conn;
    
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);
    $full_name = $conn->real_escape_string($full_name);
    $role = $conn->real_escape_string($role);
    
    // Check if username/email exists for other users
    $check = $conn->query("SELECT id FROM users WHERE (username='$username' OR email='$email') AND id != $id");
    if ($check->num_rows > 0) {
        return array('success' => false, 'message' => 'Username or email already exists');
    }
    
    if ($password) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $query = "UPDATE users SET username='$username', email='$email', full_name='$full_name', role='$role', password='$hashed_password' WHERE id=$id";
    } else {
        $query = "UPDATE users SET username='$username', email='$email', full_name='$full_name', role='$role' WHERE id=$id";
    }
    
    if ($conn->query($query)) {
        return array('success' => true, 'message' => 'User updated successfully');
    } else {
        return array('success' => false, 'message' => 'Failed to update user');
    }
}

function delete_user($id) {
    global $conn;
    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        return array('success' => false, 'message' => 'Cannot delete your own account');
    }
    
    if ($conn->query("DELETE FROM users WHERE id=$id")) {
        return array('success' => true, 'message' => 'User deleted successfully');
    } else {
        return array('success' => false, 'message' => 'Failed to delete user');
    }
}

// ===== BOOK FUNCTIONS =====

function add_book($title, $author, $isbn, $genre, $publisher, $year, $copies, $description) {
    global $conn;
    
    $title = $conn->real_escape_string($title);
    $author = $conn->real_escape_string($author);
    $isbn = $conn->real_escape_string($isbn);
    $genre = $conn->real_escape_string($genre);
    $publisher = $conn->real_escape_string($publisher);
    $description = $conn->real_escape_string($description);
    
    $query = "INSERT INTO books (title, author, isbn, genre, publisher, publication_year, total_copies, available_copies, description) 
              VALUES ('$title', '$author', '$isbn', '$genre', '$publisher', $year, $copies, $copies, '$description')";
    
    return $conn->query($query);
}

function get_all_books() {
    global $conn;
    return $conn->query("SELECT * FROM books ORDER BY title ASC");
}

function get_book_by_id($id) {
    global $conn;
    return $conn->query("SELECT * FROM books WHERE id=$id")->fetch_assoc();
}

function update_book($id, $title, $author, $isbn, $genre, $publisher, $year, $description) {
    global $conn;
    
    $title = $conn->real_escape_string($title);
    $author = $conn->real_escape_string($author);
    $isbn = $conn->real_escape_string($isbn);
    $genre = $conn->real_escape_string($genre);
    $publisher = $conn->real_escape_string($publisher);
    $description = $conn->real_escape_string($description);
    
    $query = "UPDATE books SET title='$title', author='$author', isbn='$isbn', genre='$genre', 
              publisher='$publisher', publication_year=$year, description='$description' WHERE id=$id";
    
    return $conn->query($query);
}

function delete_book($id) {
    global $conn;
    return $conn->query("DELETE FROM books WHERE id=$id");
}

// ===== BORROWING FUNCTIONS =====

function borrow_book($user_id, $book_id, $days = 14) {
    global $conn;
    
    $book = get_book_by_id($book_id);
    
    if ($book['available_copies'] <= 0) {
        return array('success' => false, 'message' => 'Book not available');
    }
    
    $due_date = date('Y-m-d', strtotime("+$days days"));
    $query = "INSERT INTO borrowing (user_id, book_id, due_date) VALUES ($user_id, $book_id, '$due_date')";
    
    if ($conn->query($query)) {
        $conn->query("UPDATE books SET available_copies = available_copies - 1 WHERE id=$book_id");
        return array('success' => true, 'message' => 'Book borrowed successfully');
    }
    return array('success' => false, 'message' => 'Failed to borrow book');
}

function return_book($borrow_id) {
    global $conn;
    
    $borrow = $conn->query("SELECT * FROM borrowing WHERE id=$borrow_id")->fetch_assoc();
    
    if (!$borrow) return array('success' => false, 'message' => 'Record not found');
    
    $today = date('Y-m-d');
    $status = ($today > $borrow['due_date']) ? 'overdue' : 'returned';
    
    $query = "UPDATE borrowing SET return_date='$today', status='$status' WHERE id=$borrow_id";
    
    if ($conn->query($query)) {
        $conn->query("UPDATE books SET available_copies = available_copies + 1 WHERE id=" . $borrow['book_id']);
        return array('success' => true, 'message' => 'Book returned successfully');
    }
    return array('success' => false, 'message' => 'Failed to return book');
}

function get_user_borrowing($user_id) {
    global $conn;
    $query = "SELECT b.*, bk.title, bk.author FROM borrowing b 
              JOIN books bk ON b.book_id = bk.id 
              WHERE b.user_id=$user_id AND b.status='active' 
              ORDER BY b.due_date ASC";
    return $conn->query($query);
}

function get_all_borrowing() {
    global $conn;
    $query = "SELECT b.*, u.full_name, bk.title, bk.author FROM borrowing b 
              JOIN users u ON b.user_id = u.id 
              JOIN books bk ON b.book_id = bk.id 
              WHERE b.status='active' 
              ORDER BY b.due_date ASC";
    return $conn->query($query);
}
?>