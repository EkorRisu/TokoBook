<?php
session_start(); 
require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { 
    header('Location: ../login.php'); 
    exit; 
}
// Ensure categories table and category_id column exist (safe for dev)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE books ADD COLUMN category_id INT DEFAULT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE books ADD COLUMN stock INT DEFAULT 0");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE books ADD CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
} catch (Exception $e) {}
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $title = $_POST['title']; 
    $author = $_POST['author']; 
    $price = $_POST['price']; 
    $desc = $_POST['description'];
    $imageName = null;
    if (!empty($_FILES['image']['name'])){
        $tmp = $_FILES['image']['tmp_name'];
        $orig = basename($_FILES['image']['name']);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $imageName = uniqid('book_') . '.' . $ext;
        move_uploaded_file($tmp, __DIR__ . '/../assets/images/' . $imageName);
    }
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $stmt = $pdo->prepare('INSERT INTO books (title,author,price,description,image,category_id,stock) VALUES (:t,:a,:p,:d,:i,:c,:s)');
    $stmt->execute([':t'=>$title,':a'=>$author,':p'=>$price,':d'=>$desc,':i'=>$imageName,':c'=>$category_id,':s'=>$stock]);
    header('Location: dashboard.php'); 
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
    <title>TokoBook - Add Book</title>
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
            <li class="nav-item"><a class="nav-link active" href="book_add.php">Add Book</a></li>
            <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="report.php">Generate Report (CSV)</a></li>
            <li class="nav-item"><a class="nav-link" href="about_edit.php">Edit About</a></li>
            <li class="nav-item"><a class="nav-link" href="contacts.php">Contact Messages</a></li>
        </ul>
    </nav>

    <main class="content flex-grow-1">
        <div class="container">
            <h2 class="mb-4">Add Book</h2>
            <div class="card shadow-sm p-4">
                <?php $categories = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC); ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" class="form-control" id="author" name="author">
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">-- None --</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" value="0" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Cover Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Book</button>
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