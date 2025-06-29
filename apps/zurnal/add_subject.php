<?php
// filepath: c:\Users\Artiom\Desktop\ARTEnet shop\apps\zurnal\add_subject.php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// Получить список учеников
$students = $pdo->query("SELECT id, username FROM users WHERE role='student'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $student_ids = $_POST['students'] ?? [];
    if ($name && count($student_ids)) {
        $pdo->prepare("INSERT INTO subjects (name, teacher_id) VALUES (?, ?)")
            ->execute([$name, $_SESSION['user']['id']]);
        $subject_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO students_subjects (student_id, subject_id) VALUES (?, ?)");
        foreach ($student_ids as $sid) {
            $stmt->execute([$sid, $subject_id]);
        }
        header("Location: zurnal.php");
        exit;
    } else {
        $error = "Заполните все поля!";
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Добавить предмет</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width:500px;">
  <h2 class="mb-4">Добавить предмет</h2>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?=$error?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Название предмета</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Ученики</label>
      <select name="students[]" class="form-select" multiple required size="6">
        <?php foreach ($students as $st): ?>
          <option value="<?=$st['id']?>"><?=htmlspecialchars($st['username'])?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Выберите одного или нескольких учеников (Ctrl+клик)</div>
    </div>
    <button class="btn btn-success w-100">Добавить</button>
  </form>
</div>
</body>
</html>