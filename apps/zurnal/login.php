<?php
session_start();
require 'db.php'; // подключение к MySQL

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $pass = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        header('Location: zurnal.php');
        exit;
    } else {
        $error = "Неверный логин или пароль";
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Вход в журнал</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #e9ecef 0%, #f8fafc 100%); }
    .login-card { margin-top: 8vh; box-shadow: 0 4px 32px #0d6efd22; border-radius: 1.5rem; }
    .login-logo { width: 60px; }
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card login-card p-4">
        <div class="text-center mb-3">
          <img src="../../photos/logo-color-2.png" class="login-logo mb-2" alt="ARTEnet">
          <h2 class="fw-bold mb-0">ARTEnet</h2>
          <div class="text-muted mb-2">Электронный журнал</div>
        </div>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?=$error?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
          <div class="mb-3">
            <label class="form-label">Логин</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Войти</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>