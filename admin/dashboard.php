<?php
session_start(); 
require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { 
    header('Location: ../login.php'); 
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
    <title>TokoBook - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
        .sidebar { min-height: 100vh; background-color: #f8f9fa; border-right: 1px solid #dee2e6; }
        .sidebar .nav-link { color: #333; padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover { background-color: #e9ecef; }
        .sidebar .nav-link.active { background-color: #007bff; color: white !important; }
        .content { padding: 2rem; }
        @media (max-width: 991.98px) { .sidebar { min-height: auto; } }
        .toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 1055; }
    </style>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/TokoBook/index.php">TokoBook</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="../contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="../cart.php">Cart</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Admin</a></li>
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
            <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="book_add.php">Add Book</a></li>
            <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="report.php">Generate Report (CSV)</a></li>
            <li class="nav-item"><a class="nav-link" href="about_edit.php">Edit About</a></li>
            <li class="nav-item">
                <a class="nav-link" href="contacts.php">
                    Contact Messages 
                    <span id="contact-badge" class="badge bg-danger ms-1" style="display:none;"></span>
                </a>
            </li>
        </ul>
    </nav>

    <main class="content flex-grow-1">
        <div class="container">
            <h2 class="mb-4">Admin Dashboard</h2>
            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
            <?php endif; ?>
            <div class="card shadow-sm p-4">
                <p>Welcome to the Admin Dashboard. Use the sidebar to manage books, users, orders, reports, about content, and contact messages.</p>
            </div>
        </div>
    </main>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<footer class="bg-light py-3 text-center">
    <p>&copy; <?php echo date('Y'); ?> TokoBook</p>
</footer>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let lastMessageId = 0;

function showToast(message) {
    let toastHTML = `
    <div class="toast align-items-center text-bg-primary border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>`;
    let toastElement = $(toastHTML);
    $('#toast-container').append(toastElement);
    let toast = new bootstrap.Toast(toastElement[0], { delay: 5000 });
    toast.show();
}

// Fungsi cek pesan baru
function checkMessages() {
    $.getJSON('check_messages.php', function(data) {
        let count = data.unread;
        let badge = $('#contact-badge');
        if(count > 0) {
            badge.text(count).show();
        } else {
            badge.hide();
        }

        // Tampilkan popup untuk pesan terbaru jika ada pesan baru
        if(data.latest_id > lastMessageId) {
            lastMessageId = data.latest_id;
            if(data.latest_message) {
                showToast(data.latest_message);
            }
        }
    });
}

// Cek setiap 10 detik
setInterval(checkMessages, 10000);
checkMessages();
</script>
</body>
</html>
