<?php
session_start(); 
require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { 
    header('Location: ../login.php'); 
    exit; 
}
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $name = trim($_POST['name']);
    $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:n)');
    $stmt->execute([':n'=>$name]);
    header('Location: categories.php'); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="../assets/logo.jpg" type="image/jpeg">
    <title>TokoBook - Add Category</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
        }
        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white !important;
        }
        .content {
            padding: 2rem;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/TokoBook/index.php">TokoBook</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="../contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="../cart.php">Cart</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="dashboard.php">Admin</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user']['username']); ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div class="d-flex">
    <nav class="sidebar d-flex flex-column p-3">
        <h4 class="mb-3">Admin Menu</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="book_add.php">Add Book</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="report.php">Generate Report (CSV)</a></li>
            <li class="nav-item"><a class="nav-link" href="about_edit.php">Edit About</a></li>
            <li class="nav-item"><a class="nav-link" href="contacts.php">Contact Messages</a></li>
            <li class="nav-item"><a class="nav-link active" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="../index.php">Back to Home</a></li>
        </ul>
    </nav>

    <main class="content flex-grow-1">
        <div class="container">
            <h2 class="mb-4">Add Category</h2>
            <div class="card shadow-sm p-4">
                <form method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                    <a href="categories.php" class="btn btn-outline-secondary ms-2">Back to Categories</a>
                </form>
            </div>
        </div>
    </main>
</div>

<footer class="bg-light py-3 text-center">
    <p>&copy; <?php echo date('Y'); ?> TokoBook</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>