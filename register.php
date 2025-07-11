<?php
session_start();
require_once 'config.php'; 


if (!isset($conn)) {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
}


if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token tidak valid.");
  }

  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  if (empty($name) || empty($email) || empty($password)) {
    $error_message = 'Semua field wajib diisi.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = 'Format email tidak valid.';
  } elseif (strlen($password) < 6) {
    $error_message = 'Password minimal 6 karakter.';
  } elseif ($password !== $confirm_password) {
    $error_message = 'Konfirmasi password tidak cocok.';
  } else {

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $error_message = "Email sudah terdaftar.";
    } else {

      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("sss", $name, $email, $hashed_password);
      if ($stmt->execute()) {
 
        header('Location: login.php?msg=success');
        exit();
      } else {
        $error_message = "Gagal menyimpan data. Coba lagi.";
      }
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Daftar Akun</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
 <link rel="stylesheet" href="register.css">

</head>
<body>

<div class="login-container" role="main" aria-label="Form registrasi Klinik Digital">

  <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
  </svg>

  <h2>Daftar Akun</h2>

  <?php if ($error_message): ?>
    <div class="error-msg"><?= htmlspecialchars($error_message) ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php" autocomplete="off" aria-label="Form daftar akun">

<div class="input-icon-wrapper">
  <svg class="input-icon" viewBox="0 0 24 24">
    <path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4z"/>
  </svg>
  <input type="text" name="name" placeholder="Nama Lengkap" required />
</div>


    <div class="input-icon-wrapper">
      <svg class="input-icon" viewBox="0 0 24 24">
        <path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
      </svg>
      <input type="email" name="email" placeholder="Email" required />
    </div>

    <div class="input-icon-wrapper password-wrapper">
      <svg class="input-icon" viewBox="0 0 24 24">
        <path d="M12 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm6-6h-1V7a5 5 0 0 0-10 0v4H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2zm-6-4a3 3 0 0 1 3 3v4H9v-4a3 3 0 0 1 3-3z"/>
      </svg>
      <input type="password" name="password" placeholder="Password" required />
    </div>

    <div class="input-icon-wrapper password-wrapper">
      <svg class="input-icon" viewBox="0 0 24 24">
        <path d="M12 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm6-6h-1V7a5 5 0 0 0-10 0v4H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2zm-6-4a3 3 0 0 1 3 3v4H9v-4a3 3 0 0 1 3-3z"/>
      </svg>
      <input type="password" name="confirm_password" placeholder="Konfirmasi Password" required />
    </div>

    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <button type="submit">Daftar</button>
  </form>

  <div class="register-link" style="margin-top: 32px;">
    <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 14h-2v-6h2v6zm0-8h-2V6h2v2z"/></svg>
    Sudah punya akun? <a href="login.php">Login di sini</a>
  </div>
</div>

</body>
</html>
