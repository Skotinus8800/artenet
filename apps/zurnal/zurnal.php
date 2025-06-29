<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>ARTEnet | Электронный журнал</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #e9ecef 0%, #f8fafc 100%); }
    .main-card { box-shadow: 0 4px 32px #0d6efd22; border-radius: 1.5rem; }
    .subject-card { cursor:pointer; transition:box-shadow .2s; border-radius:1rem; }
    .subject-card:hover { box-shadow:0 4px 24px #0d6efd22; }
    .event-icon { font-size:1.5rem; }
    .logout-btn { position: absolute; top: 1rem; right: 1rem; }
  </style>
</head>
<body>
<nav class="navbar navbar-light bg-body-tertiary border-bottom mb-4">
  <div class="container d-flex justify-content-center position-relative">
    <a class="navbar-brand mx-auto" href="#">
      <img src="../photos/logo-color-2.png" alt="ARTEnet" height="36" class="d-inline-block align-text-top">
      Электронный журнал
    </a>
    <?php if ($user['role'] === 'student'): ?>
    <div class="dropdown position-absolute end-0 me-3">
      <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
        Меню
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#grades" data-bs-toggle="tab">Мои оценки</a></li>
        <li><a class="dropdown-item" href="#homework" data-bs-toggle="tab">Домашние задания</a></li>
        <li><a class="dropdown-item" href="#notes" data-bs-toggle="tab">Похвала/замечание</a></li>
        <li><a class="dropdown-item" href="#account" data-bs-toggle="tab">Мой аккаунт</a></li>
      </ul>
    </div>
    <?php endif; ?>
    <form method="post" action="logout.php" class="logout-btn">
      <button class="btn btn-outline-secondary btn-sm">Выйти</button>
    </form>
  </div>
</nav>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card main-card p-4 mb-4">
        <div class="mb-4">
          <h3 class="fw-bold mb-1">Здравствуйте, <?=htmlspecialchars($user['username'])?>!</h3>
          <div class="text-muted">Роль: <?=($user['role']=='teacher'?'Учитель':'Ученик')?></div>
        </div>
        <a href="edit_account.php" class="btn btn-outline-secondary btn-sm mb-2">Редактировать аккаунт</a>
        <?php if ($user['role'] === 'teacher'): ?>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Мои предметы</h4>
            <a href="add_subject.php" class="btn btn-success">+ Новый предмет</a>
          </div>
          <div class="row">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE teacher_id=?");
            $stmt->execute([$user['id']]);
            foreach ($stmt as $row): ?>
              <div class="col-md-4 mb-3">
                <a href="subject.php?id=<?=$row['id']?>" class="text-decoration-none">
                  <div class="card subject-card p-3 h-100">
                    <div class="fs-4 fw-bold mb-2"><?=$row['name']?></div>
                    <div class="text-secondary">Открыть журнал</div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
          <a href="accounts.php" class="btn btn-outline-secondary btn-sm ms-2">Все аккаунты</a>
        <?php else: ?>
          <ul class="nav nav-tabs mb-3" id="studentTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="grades-tab" data-bs-toggle="tab" data-bs-target="#grades" type="button" role="tab">Мои оценки</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="homework-tab" data-bs-toggle="tab" data-bs-target="#homework" type="button" role="tab">Домашние задания</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">Похвала/замечание</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button" role="tab">Мой аккаунт</button>
            </li>
          </ul>
          <div class="tab-content" id="studentTabsContent">
            <!-- Мои оценки (строка — предмет, столбец — дата) -->
            <div class="tab-pane fade show active" id="grades" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                  <thead>
                    <tr>
                      <th>Предмет</th>
                      <?php
                      // Получить все даты с оценками
                      $stmt = $pdo->prepare("SELECT DISTINCT l.date FROM grades g
                        JOIN lessons l ON g.lesson_id=l.id
                        WHERE g.student_id=? ORDER BY l.date DESC");
                      $stmt->execute([$user['id']]);
                      $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
                      foreach ($dates as $date) {
                        echo "<th>".htmlspecialchars($date)."</th>";
                      }
                      ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    // Получить все предметы ученика
                    $stmt = $pdo->prepare("SELECT DISTINCT s.id, s.name FROM subjects s
                      JOIN students_subjects ss ON ss.subject_id=s.id
                      WHERE ss.student_id=?");
                    $stmt->execute([$user['id']]);
                    $subjects = $stmt->fetchAll();
                    foreach ($subjects as $subj) {
                      echo "<tr><td>".htmlspecialchars($subj['name'])."</td>";
                      foreach ($dates as $date) {
                        // Получить оценку и детали
                        $stmt2 = $pdo->prepare("SELECT g.grade, l.classwork, l.homework, l.homework_due, l.date
                          FROM grades g
                          JOIN lessons l ON g.lesson_id=l.id
                          WHERE g.student_id=? AND l.subject_id=? AND l.date=?");
                        $stmt2->execute([$user['id'], $subj['id'], $date]);
                        $info = $stmt2->fetch();
                        if ($info) {
                          $grade = (int)$info['grade'];
                          // Цвет рамки
                          if ($grade >= 10) $color = "#198754"; // зелёный
                          elseif ($grade <= 4) $color = "#dc3545"; // красный
                          else $color = "#ffc107"; // жёлтый
                          echo "<td>
                            <span 
                              class='badge'
                              style='border:2px solid $color; color:$color; font-size:1.1em; background: #fff; cursor:pointer;'
                              data-bs-toggle='popover'
                              data-bs-trigger='hover focus'
                              data-bs-placement='top'
                              data-bs-html='true'
                              title='Детали оценки'
                              data-bs-content=\"<b>Оценка:</b> {$grade}<br><b>Дата:</b> {$info['date']}<br><b>Классная работа:</b> ".htmlspecialchars($info['classwork'])."<br><b>Домашка:</b> ".htmlspecialchars($info['homework'])."<br><b>Срок:</b> ".htmlspecialchars($info['homework_due'])."\"
                            >{$grade}</span>
                          </td>";
                        } else {
                          echo "<td>-</td>";
                        }
                      }
                      echo "</tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
            <!-- Домашние задания -->
            <div class="tab-pane fade" id="homework" role="tabpanel">
              <ul class="list-group mb-3">
                <?php
                $stmt = $pdo->prepare("SELECT s.name as subject, l.homework, l.homework_due FROM lessons l
                    JOIN subjects s ON l.subject_id=s.id
                    JOIN students_subjects ss ON ss.subject_id=s.id
                    WHERE ss.student_id=? AND l.homework IS NOT NULL AND l.homework_due IS NOT NULL
                    ORDER BY l.homework_due ASC");
                $stmt->execute([$user['id']]);
                foreach ($stmt as $row) {
                    echo "<li class='list-group-item'><span class='event-icon text-info me-2'>📚</span>Домашнее задание по <b>{$row['subject']}</b>: {$row['homework']} (до {$row['homework_due']})</li>";
                }
                if ($stmt->rowCount() == 0) echo "<li class='list-group-item text-muted'>Нет домашних заданий</li>";
                ?>
              </ul>
            </div>
            <!-- Похвала/замечание -->
            <div class="tab-pane fade" id="notes" role="tabpanel">
              <ul class="list-group mb-3">
                <?php
                $stmt = $pdo->prepare("SELECT s.name as subject, n.date, n.type, n.text FROM notes n
                    JOIN subjects s ON n.subject_id=s.id
                    WHERE n.student_id=? ORDER BY n.date DESC");
                $stmt->execute([$user['id']]);
                foreach ($stmt as $row) {
                    $type = $row['type'] === 'praise' ? "<span class='text-success'>Похвала</span>" : "<span class='text-danger'>Замечание</span>";
                    echo "<li class='list-group-item'><span class='event-icon text-warning me-2'>!</span>{$type} по <b>{$row['subject']}</b> ({$row['date']}): {$row['text']}</li>";
                }
                if ($stmt->rowCount() == 0) echo "<li class='list-group-item text-muted'>Нет замечаний и похвал</li>";
                ?>
              </ul>
            </div>
            <!-- Мой аккаунт -->
            <div class="tab-pane fade" id="account" role="tabpanel">
              <div class="card p-3" style="max-width:400px;">
                <div class="mb-2"><b>Логин:</b> <?=htmlspecialchars($user['username'])?></div>
                <div class="mb-2"><b>Роль:</b> Ученик</div>
                <a href="edit_account.php" class="btn btn-outline-primary btn-sm mt-2">Редактировать аккаунт</a>
              </div>
            </div>
          </div>
          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
          <script>
            document.addEventListener("DOMContentLoaded", function() {
              var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
              popoverTriggerList.forEach(function (popoverTriggerEl) {
                new bootstrap.Popover(popoverTriggerEl);
              });
            });
          </script>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>