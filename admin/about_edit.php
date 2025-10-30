<?php
session_start(); 
require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { 
    header('Location: ../login.php'); 
    exit; 
}

// Path file data
$about_text_file = __DIR__ . '/../data/about.txt';
$about_title_file = __DIR__ . '/../data/about_title.txt';
$about_image_file = __DIR__ . '/../data/about_image.txt';
$upload_dir = __DIR__ . '/../assets/';

// Pesan notifikasi
$msg = '';

// Ambil data awal
$content = file_exists($about_text_file) ? file_get_contents($about_text_file) : '';
$title = file_exists($about_title_file) ? file_get_contents($about_title_file) : 'Tentang TokoBook 📖';
$image_name = file_exists($about_image_file) ? trim(file_get_contents($about_image_file)) : 'toko.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan teks dan judul
    $content = $_POST['content'] ?? '';
    $title = $_POST['title'] ?? 'Tentang TokoBook 📖';
    file_put_contents($about_text_file, $content);
    file_put_contents($about_title_file, $title);

    // --- Upload gambar baru jika ada ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg','jpeg','png','gif','webp'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_ext)) {
            $new_name = 'toko_' . time() . '.' . $ext;
            $target_path = $upload_dir . $new_name;

            if (move_uploaded_file($file_tmp, $target_path)) {
                // Simpan nama file ke about_image.txt
                file_put_contents($about_image_file, $new_name);
                $image_name = $new_name;
                $msg = 'Changes and image saved successfully.';
            } else {
                $msg = 'Error uploading image.';
            }
        } else {
            $msg = 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.';
        }
    } else {
        $msg = 'Changes saved successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="../assets/logo.jpg" type="image/jpeg">
    <title>TokoBook - Edit About</title>
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
        .content { padding: 2rem; }
        .preview-img {
            width: 100%;
            max-width: 500px;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
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
            <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="report.php">Generate Report (CSV)</a></li>
            <li class="nav-item"><a class="nav-link active" href="about_edit.php">Edit About</a></li>
            <li class="nav-item"><a class="nav-link" href="contacts.php">Contact Messages</a></li>
        </ul>
    </nav>

    <main class="content flex-grow-1">
        <div class="container">
            <h2 class="mb-4">Edit About Page</h2>
            <?php if($msg): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm p-4">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Page Title</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">About Content</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($content); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Gambar </label><br>
                        <img src="../assets/<?php echo htmlspecialchars($image_name); ?>" class="preview-img mb-3" alt="Current About Image">
                        <input type="file" name="image" id="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <small class="text-muted">Leave empty if you don't want to change the image.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
