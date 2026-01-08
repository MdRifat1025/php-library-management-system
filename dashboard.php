<?php
// dashboard.php
include 'config.php';

if (!is_logged_in()) {
    redirect('index.php');
}

$message = '';
$alert_type = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'books';

// Handle all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Book actions
    if (isset($_POST['add_book'])) {
        if (add_book($_POST['title'], $_POST['author'], $_POST['isbn'], $_POST['genre'], 
                     $_POST['publisher'], $_POST['year'], $_POST['copies'], $_POST['description'])) {
            $message = 'Book added successfully!';
            $alert_type = 'success';
        } else {
            $message = 'Error adding book';
            $alert_type = 'danger';
        }
    }
    
    if (isset($_POST['update_book'])) {
        if (update_book($_POST['book_id'], $_POST['title'], $_POST['author'], $_POST['isbn'], 
                        $_POST['genre'], $_POST['publisher'], $_POST['year'], $_POST['description'])) {
            $message = 'Book updated successfully!';
            $alert_type = 'success';
        }
    }
    
    if (isset($_POST['borrow_book'])) {
        $result = borrow_book($_SESSION['user_id'], $_POST['book_id']);
        $message = $result['message'];
        $alert_type = $result['success'] ? 'success' : 'danger';
    }
    
    if (isset($_POST['return_book'])) {
        $result = return_book($_POST['borrow_id']);
        $message = $result['message'];
        $alert_type = $result['success'] ? 'success' : 'danger';
    }
    
    // User management actions (Admin only)
    if (isset($_POST['create_user']) && is_admin()) {
        $result = create_user_by_admin($_POST['username'], $_POST['email'], $_POST['password'], 
                                       $_POST['full_name'], $_POST['role']);
        $message = $result['message'];
        $alert_type = $result['success'] ? 'success' : 'danger';
    }
    
    if (isset($_POST['update_user']) && is_admin()) {
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        $result = update_user($_POST['user_id'], $_POST['username'], $_POST['email'], 
                             $_POST['full_name'], $_POST['role'], $password);
        $message = $result['message'];
        $alert_type = $result['success'] ? 'success' : 'danger';
    }
}

// Handle GET actions
if (isset($_GET['delete_book']) && is_admin()) {
    delete_book($_GET['delete_book']);
    redirect('dashboard.php?action=manage_books');
}

if (isset($_GET['delete_user']) && is_admin()) {
    $result = delete_user($_GET['delete_user']);
    $message = $result['message'];
    $alert_type = $result['success'] ? 'success' : 'danger';
}

// Get edit data if needed
$edit_book = null;
$edit_user = null;
if (isset($_GET['edit']) && is_admin()) {
    if ($action === 'manage_books') {
        $edit_book = get_book_by_id($_GET['edit']);
    } elseif ($action === 'manage_users') {
        $edit_user = get_user_by_id($_GET['edit']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            color: white !important;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            margin: 0 10px;
            transition: all 0.3s;
        }
        .navbar .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        .navbar .nav-link.active {
            color: white !important;
            border-bottom: 2px solid white;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: none;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .book-card {
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-5px);
        }
        .badge-available {
            background-color: #28a745;
        }
        .badge-unavailable {
            background-color: #dc3545;
        }
        .content-wrapper {
            padding: 30px;
            min-height: calc(100vh - 56px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book-reader"></i> Library System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'books' ? 'active' : ''; ?>" href="?action=books">
                            <i class="fas fa-book"></i> Browse Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'my_borrowing' ? 'active' : ''; ?>" href="?action=my_borrowing">
                            <i class="fas fa-hand-holding-heart"></i> My Books
                        </a>
                    </li>
                    <?php if (is_admin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($action, ['manage_books', 'manage_users', 'all_borrowing']) ? 'active' : ''; ?>" 
                           href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cogs"></i> Admin Panel
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?action=manage_books">
                                <i class="fas fa-book-medical"></i> Manage Books
                            </a></li>
                            <li><a class="dropdown-item" href="?action=manage_users">
                                <i class="fas fa-users-cog"></i> Manage Users
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?action=all_borrowing">
                                <i class="fas fa-list"></i> All Borrowing Records
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                            <?php if(is_admin()) echo '<span class="badge bg-warning text-dark ms-1">Admin</span>'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container-fluid">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Browse Books -->
            <?php if ($action === 'books'): ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-book-open"></i> Available Books</h5>
                </div>
                <div class="card-body">
                    <?php
                    $books = get_all_books();
                    echo '<div class="row">';
                    while ($book = $books->fetch_assoc()):
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card book-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h6>
                                    <p class="card-text text-muted small">
                                        <strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?><br>
                                        <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?><br>
                                        <strong>Genre:</strong> <?php echo htmlspecialchars($book['genre']); ?><br>
                                        <strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher']); ?>
                                    </p>
                                    <p class="text-muted small"><?php echo htmlspecialchars(substr($book['description'], 0, 80)) . '...'; ?></p>
                                    <div class="mb-3">
                                        <?php if ($book['available_copies'] > 0): ?>
                                            <span class="badge badge-available">Available: <?php echo $book['available_copies']; ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-unavailable">Not Available</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($book['available_copies'] > 0): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" name="borrow_book" class="btn btn-sm btn-primary">
                                                <i class="fas fa-hand-holding"></i> Borrow
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <!-- My Borrowing -->
            <?php elseif ($action === 'my_borrowing'): ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bookmark"></i> My Borrowed Books</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Borrowed Date</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $borrowing = get_user_borrowing($_SESSION['user_id']);
                                while ($record = $borrowing->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                                        <td><?php echo htmlspecialchars($record['author']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($record['borrow_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="borrow_id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" name="return_book" class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo"></i> Return
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Manage Books (Admin) -->
            <?php elseif ($action === 'manage_books' && is_admin()): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> <?php echo $edit_book ? 'Edit Book' : 'Add New Book'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_book): ?>
                            <input type="hidden" name="book_id" value="<?php echo $edit_book['id']; ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" value="<?php echo $edit_book ? htmlspecialchars($edit_book['title']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author</label>
                                <input type="text" class="form-control" name="author" value="<?php echo $edit_book ? htmlspecialchars($edit_book['author']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ISBN</label>
                                <input type="text" class="form-control" name="isbn" value="<?php echo $edit_book ? htmlspecialchars($edit_book['isbn']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Genre</label>
                                <input type="text" class="form-control" name="genre" value="<?php echo $edit_book ? htmlspecialchars($edit_book['genre']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Publisher</label>
                                <input type="text" class="form-control" name="publisher" value="<?php echo $edit_book ? htmlspecialchars($edit_book['publisher']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Publication Year</label>
                                <input type="number" class="form-control" name="year" value="<?php echo $edit_book ? $edit_book['publication_year'] : ''; ?>">
                            </div>
                            <?php if (!$edit_book): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Copies</label>
                                <input type="number" class="form-control" name="copies" value="1" required>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo $edit_book ? htmlspecialchars($edit_book['description']) : ''; ?></textarea>
                            </div>
                        </div>
                        <button type="submit" name="<?php echo $edit_book ? 'update_book' : 'add_book'; ?>" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $edit_book ? 'Update Book' : 'Add Book'; ?>
                        </button>
                        <?php if ($edit_book): ?>
                            <a href="?action=manage_books" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-books"></i> All Books</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Genre</th>
                                    <th>Total/Available</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $books = get_all_books();
                                while ($book = $books->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                        <td><?php echo htmlspecialchars($book['genre']); ?></td>
                                        <td><?php echo $book['total_copies'] . '/' . $book['available_copies']; ?></td>
                                        <td>
                                            <a href="?action=manage_books&edit=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="?action=manage_books&delete_book=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this book?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Manage Users (Admin) -->
            <?php elseif ($action === 'manage_users' && is_admin()): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> <?php echo $edit_user ? 'Edit User' : 'Create New User'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_user): ?>
                            <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo $edit_user ? htmlspecialchars($edit_user['full_name']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <?php echo $edit_user ? '(leave blank to keep current)' : ''; ?></label>
                                <input type="password" class="form-control" name="password" <?php echo !$edit_user ? 'required' : ''; ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="user" <?php echo ($edit_user && $edit_user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo ($edit_user && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="<?php echo $edit_user ? 'update_user' : 'create_user'; ?>" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $edit_user ? 'Update User' : 'Create User'; ?>
                        </button>
                        <?php if ($edit_user): ?>
                            <a href="?action=manage_users" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-users"></i> All Users</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $users = get_all_users();
                                while ($user = $users->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-warning text-dark">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">User</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="?action=manage_users&edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="?action=manage_users&delete_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- All Borrowing (Admin) -->
            <?php elseif ($action === 'all_borrowing' && is_admin()): ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> All Borrowing Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Book</th>
                                    <th>Author</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $borrowing = get_all_borrowing();
                                while ($record = $borrowing->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                                        <td><?php echo htmlspecialchars($record['author']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($record['borrow_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                                        <td>
                                            <?php
                                            if (date('Y-m-d') > $record['due_date']) {
                                                echo '<span class="badge bg-danger">Overdue</span>';
                                            } else {
                                                echo '<span class="badge bg-success">Active</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>