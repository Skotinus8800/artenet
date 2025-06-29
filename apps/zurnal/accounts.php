<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') exit;

// Добавление нового аккаунта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    if ($username && $password && in_array($role, ['teacher','student'])) {
        $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)")
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
    }
}

// Редактирование аккаунта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = (int)$_POST['id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    if ($username && in_array($role, ['teacher','student'])) {
        if (!empty($_POST['password'])) {
            $pdo->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?")
                ->execute([$username, password_hash($_POST['password'], PASSWORD_DEFAULT), $role, $id]);
        } else {
            $pdo->prepare("UPDATE users SET username=?, role=? WHERE id=?")
                ->execute([$username, $role, $id]);
        }
    }
}

// Удаление аккаунта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
}

$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Все аккаунты</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4" style="max-width:900px;">
  <h2 class="mb-4">Все аккаунты</h2>
  <form method="post" class="row g-2 mb-4 align-items-end">
    <input type="hidden" name="add_user" value="1">
    <div class="col-md-3">
      <input type="text" name="username" class="form-control" placeholder="Имя пользователя" required>
    </div>
    <div class="col-md-3">
      <input type="password" name="password" class="form-control" placeholder="Пароль" required>
    </div>
    <div class="col-md-3">
      <select name="role" class="form-select" required>
        <option value="student">Ученик</option>
        <option value="teacher">Учитель</option>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-success w-100">Добавить аккаунт</button>
    </div>
  </form>
  <table class="table table-bordered align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Имя пользователя</th>
        <th>Роль</th>
        <th>Действия</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <form method="post" class="align-middle">
            <input type="hidden" name="id" value="<?=$u['id']?>">
            <td><?=$u['id']?></td>
            <td><input type="text" name="username" value="<?=htmlspecialchars($u['username'])?>" class="form-control form-control-sm" required></td>
            <td>
              <select name="role" class="form-select form-select-sm">
                <option value="student" <?=$u['role']=='student'?'selected':''?>>Ученик</option>
                <option value="teacher" <?=$u['role']=='teacher'?'selected':''?>>Учитель</option>
              </select>
            </td>
            <td>
              <input type="password" name="password" class="form-control form-control-sm mb-1" placeholder="Новый пароль">
              <button class="btn btn-primary btn-sm" name="edit_user" value="1">Сохранить</button>
              <button class="btn btn-danger btn-sm" name="delete_user" value="1" onclick="return confirm('Удалить аккаунт?')">Удалить</button>
            </td>
          </form>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <a href="zurnal.php" class="btn btn-link mt-3">&larr; Назад</a>
</div>
</body>
</html>