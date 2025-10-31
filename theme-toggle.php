<?php
// ===============================
//  THEME TOGGLE + GLOBAL STYLE
// ===============================

// Toggle mode (ubah cookie)
if (isset($_GET['toggle']) && $_GET['toggle'] === '1') {
    if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') {
        setcookie('theme', 'light', time() + (86400 * 30), '/');
    } else {
        setcookie('theme', 'dark', time() + (86400 * 30), '/');
    }

    // Redirect kembali ke halaman sebelumnya
    $previous_page = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $previous_page");
    exit;
}

// Ambil status tema
$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dark Mode Global</title>

  <style>
    /* ======== Style Umum ======== */
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: white;
      color: black;
      transition: background-color 0.3s, color 0.3s;
    }

    .navbar {
      background-color: #f5f5f5;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    a {
      color: inherit;
      text-decoration: none;
      margin: 0 10px;
    }

    /* ======== Dark Mode ======== */
    body.dark-mode {
      background-color: #121212;
      color: white;
    }

    body.dark-mode .navbar {
      background-color: #1f1f1f;
    }

    /* Tombol toggle */
    .toggle-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      background-color: #4f46e5;
      color: white;
      font-weight: bold;
      transition: 0.3s;
    }

    .toggle-btn:hover {
      background-color: #4338ca;
    }

    body.dark-mode .toggle-btn {
      background-color: #eab308;
      color: black;
    }

    body.dark-mode .toggle-btn:hover {
      background-color: #ca8a04;
    }
  </style>
</head>

<body class="<?= $theme === 'dark' ? 'dark-mode' : '' ?>">
  <div class="navbar">
    <div>
      <a href="index.php">🏠 Beranda</a>
      <a href="about.php">ℹ️ Tentang</a>
    </div>
    <a href="?toggle=1" class="toggle-btn">
      <?= $theme === 'dark' ? '☀️ Light Mode' : '🌙 Dark Mode' ?>
    </a>
  </div>

  <div style="padding: 20px;">
    <h1><?= $theme === 'dark' ? '🌙 Mode Gelap Aktif' : '☀️ Mode Terang Aktif' ?></h1>
    <p>Mode ini akan diterapkan di semua halaman karena disimpan di cookie.</p>
  </div>
</body>
</html>
