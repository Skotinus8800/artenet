<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') exit;

// Удаление предмета
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $subject_id = (int)$_POST['subject_id'];
    // Удаляем все оценки, уроки, связи, заметки
    $pdo->prepare("DELETE FROM grades WHERE lesson_id IN (SELECT id FROM lessons WHERE subject_id=?)")->execute([$subject_id]);
    $pdo->prepare("DELETE FROM lessons WHERE subject_id=?")->execute([$subject_id]);
    $pdo->prepare("DELETE FROM students_subjects WHERE subject_id=?")->execute([$subject_id]);
    $pdo->prepare("DELETE FROM notes WHERE subject_id=?")->execute([$subject_id]);
    $pdo->prepare("DELETE FROM subjects WHERE id=?")->execute([$subject_id]);
    header("Location: zurnal.php");
    exit;
}
$subject_id = (int)($_GET['id'] ?? $_POST['subject_id'] ?? 0);

// Добавление урока
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lesson'])) {
    $date = $_POST['date'];
    $classwork = $_POST['classwork'];
    $homework = $_POST['homework'];
    $homework_due = $_POST['homework_due'];
    if ($date && $classwork) {
        $pdo->prepare("INSERT INTO lessons (subject_id, date, classwork, homework, homework_due) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE date=VALUES(date), classwork=VALUES(classwork), homework=VALUES(homework), homework_due=VALUES(homework_due)")
            ->execute([$subject_id, $date, $classwork, $homework, $homework_due]);
    }
}

// Добавление/редактирование оценки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    $student_id = (int)$_POST['student_id'];
    $grade = (int)$_POST['grade'];
    // Проверяем, есть ли уже оценка
    $stmt = $pdo->prepare("SELECT id FROM grades WHERE lesson_id=? AND student_id=?");
    $stmt->execute([$lesson_id, $student_id]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE grades SET grade=? WHERE lesson_id=? AND student_id=?")
            ->execute([$grade, $lesson_id, $student_id]);
    } else {
        $pdo->prepare("INSERT INTO grades (lesson_id, student_id, grade) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE grade=VALUES(grade)")
            ->execute([$lesson_id, $student_id, $grade]);
    }
}

// Добавление замечания/похвалы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $student_id = (int)$_POST['student_id'];
    $type = $_POST['type'];
    $text = trim($_POST['text']);
    $date = date('Y-m-d');
    if ($student_id && $type && $text) {
        $pdo->prepare("INSERT INTO notes (student_id, subject_id, date, type, text) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE date=VALUES(date), type=VALUES(type), text=VALUES(text)")
            ->execute([$student_id, $subject_id, $date, $type, $text]);
    }
}

// Удаление урока
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_lesson'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    // Сначала удаляем оценки, связанные с этим уроком
    $pdo->prepare("DELETE FROM grades WHERE lesson_id=?")->execute([$lesson_id]);
    // Затем сам урок
    $pdo->prepare("DELETE FROM lessons WHERE id=? AND subject_id=?")->execute([$lesson_id, $subject_id]);
}

// Удаление заметки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note'])) {
    $note_id = (int)$_POST['note_id'];
    $pdo->prepare("DELETE FROM notes WHERE id=?")->execute([$note_id]);
}

// Получить учеников
$stmt = $pdo->prepare("SELECT u.id, u.username FROM users u
    JOIN students_subjects ss ON ss.student_id=u.id
    WHERE ss.subject_id=?");
$stmt->execute([$subject_id]);
$students = $stmt->fetchAll();
// Получить даты/уроки
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE subject_id=? ORDER BY date");
$stmt->execute([$subject_id]);
$lessons = $stmt->fetchAll();
// Получить оценки
$grades = [];
$stmt = $pdo->prepare("SELECT * FROM grades WHERE lesson_id IN (SELECT id FROM lessons WHERE subject_id=?)");
$stmt->execute([$subject_id]);
foreach ($stmt as $g) $grades[$g['lesson_id']][$g['student_id']] = $g['grade'];
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Журнал предмета</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .work-title { font-size: 0.97em; color: #555; }
    .grade-cell { min-width: 48px; text-align: center; font-weight: 500; }
  </style>
</head>
<body>
<div class="container mt-4">
  <a href="zurnal.php" class="btn btn-link mb-3">&larr; К журналу</a>
  <h2 class="mb-3">Журнал предмета</h2>
  <!-- Добавить урок -->
  <form class="row g-2 mb-4" method="post">
    <input type="hidden" name="add_lesson" value="1">
    <div class="col-md-2">
      <input type="date" name="date" class="form-control" required>
    </div>
    <div class="col-md-3">
      <input type="text" name="classwork" class="form-control" placeholder="Классная работа" required>
    </div>
    <div class="col-md-3">
      <input type="text" name="homework" class="form-control" placeholder="Домашняя работа">
    </div>
    <div class="col-md-2">
      <input type="date" name="homework_due" class="form-control" placeholder="Срок сдачи">
    </div>
    <div class="col-md-2">
      <button class="btn btn-success w-100">Добавить урок</button>
    </div>
  </form>
  <!-- Кнопка удалить предмет -->
  <form method="post" onsubmit="return confirm('Удалить предмет и все связанные данные?');" class="mb-3">
    <input type="hidden" name="delete_subject" value="1">
    <input type="hidden" name="subject_id" value="<?=$subject_id?>">
    <button class="btn btn-danger">Удалить предмет</button>
  </form>
  <!-- Таблица -->
  <div class="table-responsive">
  <table class="table table-bordered align-middle text-center" style="min-width:900px;">
    <thead>
      <tr>
        <th style="min-width:120px;">№<br>Ученик</th>
        <?php foreach ($lessons as $l): ?>
          <th>
            <div class="fw-bold"><?=date('d.m', strtotime($l['date']))?></div>
            <div class="small text-muted"><?=htmlspecialchars($l['classwork'])?></div>
            <div class="small text-info"><?=htmlspecialchars($l['homework'])?></div>
            <div class="small text-secondary"><?=htmlspecialchars($l['homework_due'])?></div>
            <!-- Кнопка редактирования урока -->
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editLessonModal<?=$l['id']?>" title="Редактировать урок">✎</button>
            <!-- Кнопка удаления урока -->
            <form method="post" style="display:inline;" onsubmit="return confirm('Удалить этот урок? Все оценки по нему тоже будут удалены!');">
              <input type="hidden" name="delete_lesson" value="1">
              <input type="hidden" name="lesson_id" value="<?=$l['id']?>">
              <button class="btn btn-sm btn-outline-danger" title="Удалить урок">&times;</button>
            </form>
          </th>
        <?php endforeach; ?>
        <th>Средняя</th>
        <th>Комментарий</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $idx => $st): ?>
        <tr>
          <td class="text-start"><?=$idx+1?>. <?=htmlspecialchars($st['username'])?></td>
          <?php
          $sum = 0; $cnt = 0;
          foreach ($lessons as $l):
            $g = $grades[$l['id']][$st['id']] ?? '';
            if ($g) { $sum += $g; $cnt++; }
          ?>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="save_grade" value="1">
                <input type="hidden" name="lesson_id" value="<?=$l['id']?>">
                <input type="hidden" name="student_id" value="<?=$st['id']?>">
                <input type="number" name="grade" value="<?=$g?>" min="1" max="10" class="form-control form-control-sm d-inline" style="width:60px;display:inline-block;" required>
                <button class="btn btn-sm btn-outline-primary" title="Сохранить">&#10003;</button>
              </form>
            </td>
          <?php endforeach; ?>
          <td class="fw-bold"><?=($cnt ? round($sum/$cnt,2) : '-')?></td>
          <td>
            <!-- Кнопка для комментария/замечания -->
            <form method="post" class="d-flex align-items-center" style="gap:4px;">
              <input type="hidden" name="add_note" value="1">
              <input type="hidden" name="student_id" value="<?=$st['id']?>">
              <select name="type" class="form-select form-select-sm" style="width:auto;">
                <option value="praise">Похвала</option>
                <option value="remark">Замечание</option>
              </select>
              <input type="text" name="text" class="form-control form-control-sm" placeholder="Текст..." required>
              <button class="btn btn-sm btn-outline-secondary" title="Добавить">+</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<h4 class="mt-4">Похвалы и замечания по предмету</h4>
<?php foreach ($students as $st): ?>
  <div class="mb-3">
    <b><?=htmlspecialchars($st['username'])?>:</b>
    <ul class="list-group mb-2">
      <?php
      $stmt = $pdo->prepare("SELECT * FROM notes WHERE student_id=? AND subject_id=? ORDER BY date DESC");
      $stmt->execute([$st['id'], $subject_id]);
      foreach ($stmt as $note): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span>
            <span class="<?=$note['type']=='praise'?'text-success':'text-danger'?>">
              <?=$note['type']=='praise'?'Похвала':'Замечание'?>
            </span>
            (<?=htmlspecialchars($note['date'])?>): <?=htmlspecialchars($note['text'])?>
          </span>
          <form method="post" style="margin:0;">
            <input type="hidden" name="delete_note" value="1">
            <input type="hidden" name="note_id" value="<?=$note['id']?>">
            <button class="btn btn-sm btn-outline-danger" title="Удалить">&times;</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
    <!-- Форма добавления -->
    <form method="post" class="d-flex align-items-center gap-2">
      <input type="hidden" name="add_note" value="1">
      <input type="hidden" name="student_id" value="<?=$st['id']?>">
      <select name="type" class="form-select form-select-sm" style="width:auto;">
        <option value="praise">Похвала</option>
        <option value="remark">Замечание</option>
      </select>
      <input type="text" name="text" class="form-control form-control-sm" placeholder="Текст..." required>
      <button class="btn btn-sm btn-outline-secondary" title="Добавить">+</button>
    </form>
  </div>
<?php endforeach; ?>

<?php if ($user['role'] === 'teacher'): ?>
  <a href="accounts.php" class="btn btn-outline-secondary btn-sm ms-2">Все аккаунты</a>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Инициализация popover для всех оценок
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
    new bootstrap.Popover(el);
  });
</script>
</div>

<!-- Модальные окна редактирования уроков -->
<?php foreach ($lessons as $l): ?>
<div class="modal fade" id="editLessonModal<?=$l['id']?>" tabindex="-1" aria-labelledby="editLessonModalLabel<?=$l['id']?>" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <input type="hidden" name="edit_lesson" value="1">
      <input type="hidden" name="lesson_id" value="<?=$l['id']?>">
      <div class="modal-header">
        <h5 class="modal-title" id="editLessonModalLabel<?=$l['id']?>">Редактировать урок</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Дата</label>
          <input type="date" name="date" class="form-control" value="<?=htmlspecialchars($l['date'])?>" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Классная работа</label>
          <input type="text" name="classwork" class="form-control" value="<?=htmlspecialchars($l['classwork'])?>" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Домашняя работа</label>
          <input type="text" name="homework" class="form-control" value="<?=htmlspecialchars($l['homework'])?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Срок сдачи</label>
          <input type="date" name="homework_due" class="form-control" value="<?=htmlspecialchars($l['homework_due'])?>">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">Сохранить</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>