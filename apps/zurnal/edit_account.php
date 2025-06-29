<?php
<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$user = $_SESSION['user'];
$id = isset($_GET['id']) && $user['role']=='teacher' ? (int)$_GET['id'] : $user['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$acc = $stmt->fetch();
if (!$acc) exit('Пользователь не найден');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $pass = $_POST['password'];
    if ($username) {
        if ($pass) {
            $pdo->prepare("UPDATE users SET username=?, password=? WHERE id=?")
                ->execute([$username, password_hash($pass, PASSWORD_DEFAULT), $id]);
        } else {
            $pdo->prepare("UPDATE users SET username=? WHERE id=?")
                ->execute([$username, $id]);
        }
        if ($id == $user['id']) $_SESSION['user']['username'] = $username;
        header("Location: zurnal.php");
        exit;
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Редактировать аккаунт</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width:400px;">
  <h2 class="mb-4">Редактировать аккаунт</h2>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Имя пользователя</label>
      <input type="text" name="username" class="form-control" value="<?=htmlspecialchars($acc['username'])?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Новый пароль (если нужно сменить)</label>
      <input type="password" name="password" class="form-control">
    </div>
    <button class="btn btn-primary w-100">Сохранить</button>
  </form>
</div>
</body>
</html>