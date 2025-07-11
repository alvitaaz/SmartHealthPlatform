<?php
session_start();
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$user_id = $_SESSION['user_id'];

$host = 'localhost';
$dbname = 'clinic';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function getDoctorIcon($doctor_id) {
    switch ($doctor_id) {
        case 1:
            return "cowok.jpeg"; 
        case 2:
            return "cewek.jpeg"; 

        default:
            return "default-doctor.png"; 
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$delete_id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $doctor_id = $_POST['doctor'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $complaint = $_POST['complaint'];

        if (empty($doctor_id) || empty($date) || empty($time) || empty($complaint)) {
            $error = 'Mohon isi semua field dengan benar.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO appointments (doctor_id, patient_name, date, time, description, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $doctor_id,
                'Pasien #' . $user_id,
                $date,
                $time,
                $complaint,
                'pending'
            ]);
            $success = 'Janji temu berhasil dibuat! Terima kasih.';
        }
    }
}

$doctors = $pdo->query("SELECT * FROM doctors")->fetchAll(PDO::FETCH_ASSOC);
$appointments = $pdo->query("SELECT appointments.*, doctors.name AS doctor_name FROM appointments JOIN doctors ON appointments.doctor_id = doctors.id")->fetchAll(PDO::FETCH_ASSOC);
$feedbacks = $pdo->query("SELECT appointment_id FROM feedback")->fetchAll(PDO::FETCH_COLUMN);

$disabled_dates = [];
foreach ($appointments as $appt) {
    $disabled_dates[] = $appt['date'];
}

$error = $error ?? '';
$success = $success ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Daftar Janji Temu + Booking Modal</title>
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="app.css">
  <style>
    .dashboard-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      width: 48px;
      height: 48px;
      background-color: #3E9C95;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      text-decoration: none;
      z-index: 999;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .dashboard-btn:hover {
      background-color: #50BEB0;
      transform: scale(1.1);
    }
    .btn-delete {
      margin-top: 6px;
      background-color: #dc3545;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 4px 10px;
      cursor: pointer;
    }
    .status-badge.cancelled {
      background-color: #6c757d;
      color: white;
    }
  </style>
</head>
<body>

<a href="dashboard.php" class="dashboard-btn" aria-label="Kembali ke Home" title="Kembali ke Home">
  <i class="bi bi-house-door"></i>
</a>

<div class="container">
  <div class="left-side">
    <header>
      <div>
        <h1>Daftar Janji Temu</h1>
        <p>Lihat dan kelola jadwal konsultasimu di klinik</p>
        <button class="btn-new" id="btnOpenModal" aria-haspopup="dialog" aria-controls="modalBooking" aria-expanded="false">+ Buat Janji Baru</button>
      </div>
      <img src="app8.jpg" alt="Ilustrasi konsultasi kesehatan" />
    </header>

    <div class="filter-tabs" role="tablist" aria-label="Filter janji temu">
      <button class="active" data-filter="all" role="tab" aria-selected="true">Semua</button>
      <button data-filter="active" role="tab" aria-selected="false">Aktif</button>
      <button data-filter="pending" role="tab" aria-selected="false">Menunggu</button>
      <button data-filter="finished" role="tab" aria-selected="false">Selesai</button>
      <button data-filter="cancelled" role="tab" aria-selected="false">Dibatalkan</button>
    </div>

    <div id="appointment-list">
      <?php foreach ($appointments as $appt): ?>
        <div class="card" data-kategori="<?= htmlspecialchars($appt['status']) ?>">
          <div class="card-info">
            <img src="<?= htmlspecialchars(getDoctorIcon($appt['doctor_id'])) ?>" alt="Ikon Dokter <?= htmlspecialchars($appt['doctor_name']) ?>" style="width:48px; height:48px; object-fit:cover; border-radius:50%;" />
            <div class="card-details">
              <strong>Janji Temu Baru</strong>
              <small>Dokter: <?= htmlspecialchars($appt['doctor_name']) ?></small>
              <small><?= htmlspecialchars($appt['description']) ?></small>
              <span class="status-badge <?= htmlspecialchars($appt['status']) ?>">
                <?= ucfirst($appt['status']) ?>
              </span>

              <?php if ($appt['status'] === 'finished'): ?>
                <?php if (!in_array($appt['id'], $feedbacks)): ?>
                  <form action="feedback.php" method="POST" style="margin-top: 6px;">
                    <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                    <button type="submit" class="btn-feedback" style="margin-top: 4px; background-color: #3E9C95; color: white; border: none; border-radius: 4px; padding: 4px 10px;">Beri Feedback</button>
                  </form>
                <?php else: ?>
                  <small style="color: green; display: block; margin-top: 6px;">Feedback sudah diberikan</small>
                <?php endif; ?>
              <?php endif; ?>

              <?php if ($appt['status'] === 'pending'): ?>
                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus janji temu ini?');">
                  <input type="hidden" name="delete_id" value="<?= $appt['id'] ?>">
                  <button type="submit" class="btn-delete">Hapus</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-date <?= $appt['status'] ?>">
            <?= date("d M Y", strtotime($appt['date'])) ?><br /><?= $appt['time'] ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Modal Booking -->
<div class="modal-overlay" id="modalBooking" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal-content" role="document" tabindex="-1">
    <button class="modal-close" aria-label="Tutup Form Booking" id="btnCloseModal">&times;</button>
    <h2 class="modal-header" id="modalTitle">Buat Janji Baru</h2>

    <?php if ($error): ?>
      <div class="form-message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="form-message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form id="bookingForm" method="POST" novalidate>
      <label for="doctor">Pilih Dokter</label>
      <select id="doctor" name="doctor" required>
        <option value="" disabled selected>-- Pilih Dokter --</option>
        <?php foreach($doctors as $doc): ?>
          <option value="<?= htmlspecialchars($doc['id']) ?>">
            <?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['specialization']) ?>)
          </option>
        <?php endforeach; ?>
      </select>

      <label for="date">Tanggal Janji</label>
      <input type="text" id="date" name="date" required placeholder="Pilih Tanggal" />

      <label for="time">Jam Janji</label>
      <select id="time" name="time" required>
        <option value="" disabled selected>-- Pilih Jam --</option>
        <option value="08:00">08:00</option>
        <option value="09:00">09:00</option>
        <option value="10:00">10:00</option>
        <option value="11:00">11:00</option>
        <option value="13:00">13:00</option>
        <option value="14:00">14:00</option>
        <option value="15:00">15:00</option>
      </select>

      <label for="complaint">Keluhan</label>
      <textarea id="complaint" name="complaint" required></textarea>

      <button type="submit">Booking Sekarang</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  flatpickr("#date", {
    altInput: true,
    altFormat: "d F Y",
    dateFormat: "Y-m-d",
    disable: <?= json_encode(array_values(array_unique($disabled_dates))) ?>,
    minDate: "today",
  });

  const modal = document.getElementById('modalBooking');
  const btnOpenModal = document.getElementById('btnOpenModal');
  const btnCloseModal = document.getElementById('btnCloseModal');

  btnOpenModal.addEventListener('click', () => {
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    btnOpenModal.setAttribute('aria-expanded', 'true');
  });

  btnCloseModal.addEventListener('click', () => {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    btnOpenModal.setAttribute('aria-expanded', 'false');
  });

  const tabs = document.querySelectorAll('.filter-tabs button');
  const cards = document.querySelectorAll('#appointment-list .card');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');

      const filter = tab.getAttribute('data-filter');
      cards.forEach(card => {
        if(filter === 'all' || card.getAttribute('data-kategori') === filter) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
</script>

</body>
</html>
