<?php
// config.php - Database Connection
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'library_system');

define('DB_HOST', getenv('localhost'));  // Use Railway's MySQL host
define('DB_USER', getenv('root'));  // Use Railway's MySQL username
define('DB_PASS', getenv(''));  // Use Railway's MySQL password
define('DB_NAME', getenv('library_system'));  // Use Railway's MySQL database name

/**
 * IMPORTANT SECURITY:
 * If you allow "admin" registration publicly, anyone can become admin.
 * So I added an Admin Registration Code system.
 * If role=admin, user must enter this code.
 * Change it before using!
 */
define('ADMIN_REG_CODE', 'CHANGE_ME_123');

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

function register_user($username, $email, $password, $full_name, $role = 'user', $admin_code = '') {
    global $conn;

    $role = strtolower(trim($role));
    if (!in_array($role, ['user', 'admin'], true)) {
        $role = 'user';
    }

    // If registering as admin, require admin code
    if ($role === 'admin') {
        if (trim($admin_code) !== ADMIN_REG_CODE) {
            return array('success' => false, 'message' => 'Invalid admin registration code');
        }
    }

    // Check duplicate username/email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $stmt->close();
        return array('success' => false, 'message' => 'Username or email already exists');
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);

    if ($stmt->execute()) {
        $stmt->close();
        return array('success' => true, 'message' => 'Registration successful');
    } else {
        $stmt->close();
        return array('success' => false, 'message' => 'Registration failed');
    }
}

function login_user($username, $password) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, username, email, role, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $stmt->close();
            return array('success' => true, 'message' => 'Login successful');
        }
    }
    $stmt->close();
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
    $id = (int)$id;
    $result = $conn->query("SELECT id, username, email, full_name, role FROM users WHERE id=$id");
    return $result ? $result->fetch_assoc() : null;
}

function create_user_by_admin($username, $email, $password, $full_name, $role) {
    global $conn;

    $role = strtolower(trim($role));
    if (!in_array($role, ['user', 'admin'], true)) $role = 'user';

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $stmt->close();
        return array('success' => false, 'message' => 'Username or email already exists');
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);

    if ($stmt->execute()) {
        $stmt->close();
        return array('success' => true, 'message' => 'User created successfully');
    } else {
        $stmt->close();
        return array('success' => false, 'message' => 'Failed to create user');
    }
}

function update_user($id, $username, $email, $full_name, $role, $password = null) {
    global $conn;

    $id = (int)$id;
    $role = strtolower(trim($role));
    if (!in_array($role, ['user', 'admin'], true)) $role = 'user';

    // Check if username/email exists for other users
    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $username, $email, $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $stmt->close();
        return array('success' => false, 'message' => 'Username or email already exists');
    }
    $stmt->close();

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $username, $email, $full_name, $role, $hashed_password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $email, $full_name, $role, $id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        return array('success' => true, 'message' => 'User updated successfully');
    } else {
        $stmt->close();
        return array('success' => false, 'message' => 'Failed to update user');
    }
}

function delete_user($id) {
    global $conn;

    $id = (int)$id;
    if ($id == (int)$_SESSION['user_id']) {
        return array('success' => false, 'message' => 'Cannot delete your own account');
    }

    if ($conn->query("DELETE FROM users WHERE id=$id")) {
        return array('success' => true, 'message' => 'User deleted successfully');
    }
    return array('success' => false, 'message' => 'Failed to delete user');
}

// ===== BOOK FUNCTIONS =====

function add_book($title, $author, $isbn, $genre, $publisher, $year, $copies, $description) {
    global $conn;

    $year = (int)$year;
    $copies = (int)$copies;

    $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, genre, publisher, publication_year, total_copies, available_copies, description)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiiis", $title, $author, $isbn, $genre, $publisher, $year, $copies, $copies, $description);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function get_all_books() {
    global $conn;
    return $conn->query("SELECT * FROM books ORDER BY title ASC");
}

function get_book_by_id($id) {
    global $conn;
    $id = (int)$id;
    $result = $conn->query("SELECT * FROM books WHERE id=$id");
    return $result ? $result->fetch_assoc() : null;
}

function update_book($id, $title, $author, $isbn, $genre, $publisher, $year, $description) {
    global $conn;

    $id = (int)$id;
    $year = (int)$year;

    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, genre=?, publisher=?, publication_year=?, description=? WHERE id=?");
    $stmt->bind_param("sssssisi", $title, $author, $isbn, $genre, $publisher, $year, $description, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function delete_book($id) {
    global $conn;
    $id = (int)$id;
    return $conn->query("DELETE FROM books WHERE id=$id");
}

// ===== BORROWING FUNCTIONS =====

function borrow_book($user_id, $book_id, $days = 14) {
    global $conn;

    $user_id = (int)$user_id;
    $book_id = (int)$book_id;
    $days = (int)$days;

    $book = get_book_by_id($book_id);
    if (!$book) return array('success' => false, 'message' => 'Book not found');

    if ((int)$book['available_copies'] <= 0) {
        return array('success' => false, 'message' => 'Book not available');
    }

    $due_date = date('Y-m-d', strtotime("+$days days"));

    $stmt = $conn->prepare("INSERT INTO borrowing (user_id, book_id, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $book_id, $due_date);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->query("UPDATE books SET available_copies = available_copies - 1 WHERE id=$book_id");
        return array('success' => true, 'message' => 'Book borrowed successfully');
    }

    $stmt->close();
    return array('success' => false, 'message' => 'Failed to borrow book');
}

function return_book($borrow_id) {
    global $conn;

    $borrow_id = (int)$borrow_id;
    $borrow = $conn->query("SELECT * FROM borrowing WHERE id=$borrow_id")->fetch_assoc();
    if (!$borrow) return array('success' => false, 'message' => 'Record not found');

    $today = date('Y-m-d');
    $status = ($today > $borrow['due_date']) ? 'overdue' : 'returned';

    $stmt = $conn->prepare("UPDATE borrowing SET return_date=?, status=? WHERE id=?");
    $stmt->bind_param("ssi", $today, $status, $borrow_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->query("UPDATE books SET available_copies = available_copies + 1 WHERE id=" . (int)$borrow['book_id']);
        return array('success' => true, 'message' => 'Book returned successfully');
    }

    $stmt->close();
    return array('success' => false, 'message' => 'Failed to return book');
}

function get_user_borrowing($user_id) {
    global $conn;
    $user_id = (int)$user_id;

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
