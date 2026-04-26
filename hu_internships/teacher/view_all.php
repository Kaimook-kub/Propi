<?php
// teacher_dashboard.php
// Database connection
$host = "localhost";
$db   = "internship_system";
$user = "root";
$pass = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- Session / Auth (assume teacher is logged in) ---
session_start();
// For demo, hardcode teacher. In real system: $_SESSION['u_id']
$teacher_uid = $_SESSION['u_id'] ?? 'tl94258546742';

// Get teacher info
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE u_id = ?");
$stmt->execute([$teacher_uid]);
$teacher = $stmt->fetch();

if (!$teacher) {
    die("Teacher not found.");
}

$teacher_full_name = $teacher['first_name'] . ' ' . $teacher['last_name'];

// Get students advised by this teacher
// advisor column may store teacher_id OR first_name — try both
$stmt = $pdo->prepare("SELECT * FROM students WHERE advisor = ? OR advisor = ?");
$stmt->execute([$teacher['teacher_id'], $teacher['first_name']]);
$advisees = $stmt->fetchAll();

// If still empty, fallback: get all students (for demo/dev when advisor value doesn't match)
if (empty($advisees)) {
    $stmt = $pdo->query("SELECT * FROM students");
    $advisees = $stmt->fetchAll();
}

$advisee_ids = array_column($advisees, 'student_id');

// Count internship requests for advised students
$wait_count = 0;
$incomplete_count = 0;
$approved_count = 0;

// Build internship request lookup keyed by student_id
$request_map = [];

if (!empty($advisee_ids)) {
    $placeholders = implode(',', array_fill(0, count($advisee_ids), '?'));

    $stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM internship_requests WHERE student_id IN ($placeholders) GROUP BY status");
    $stmt->execute($advisee_ids);
    $status_counts = $stmt->fetchAll();

    foreach ($status_counts as $row) {
        if ($row['status'] === 'รับเรื่องเข้าระบบ')        $wait_count = $row['cnt'];
        elseif ($row['status'] === 'อาจารย์ที่ปรึกษาอนุมัติ') $incomplete_count = $row['cnt'];
        elseif ($row['status'] === 'ฝึกงานเสร็จสิ้น')        $approved_count = $row['cnt'];
    }

    // Get internship requests mapped by student_id
    $stmt = $pdo->prepare("
        SELECT ir.*, c.name as company_name
        FROM internship_requests ir
        JOIN companies c ON ir.company_id = c.company_id
        WHERE ir.student_id IN ($placeholders)
        ORDER BY ir.request_date DESC
    ");
    $stmt->execute($advisee_ids);
    foreach ($stmt->fetchAll() as $req) {
        $request_map[$req['student_id']] = $req;
    }
}

// Determine year levels for display
$year_levels = array_unique(array_column($advisees, 'year_level'));
sort($year_levels);
$max_year = !empty($year_levels) ? implode(', ', $year_levels) : '-';

// Handle status update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request'])) {
    $req_id  = $_POST['request_id'];
    $new_status = $_POST['status'];
    $remark  = $_POST['remark'] ?? '';

    $upd = $pdo->prepare("UPDATE internship_requests SET status = ?, remark = ? WHERE request_id = ?");
    $upd->execute([$new_status, $remark, $req_id]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>หน้าหลักอาจารย์</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f5ede8;
    --card-green: #c8e6c9;
    --card-green-dark: #a5d6a7;
    --orange: #ffb74d;
    --blue: #90caf9;
    --teal: #4dd0a0;
    --teal-dark: #2ecc8a;
    --purple-btn: #7986cb;
    --purple-btn-dark: #5c6bc0;
    --text: #333;
    --muted: #777;
    --white: #fff;
    --radius: 16px;
    --shadow: 0 4px 16px rgba(0,0,0,0.08);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Prompt', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    padding: 30px 20px 60px;
  }

  header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 5%;
        background-color: #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        border-radius: 10px;
    }
    .logo-img { 
        height: 50px; 
    }
    
    .nav-links { display: flex; gap: 20px; }
    .nav-links a { text-decoration: none; color: #333; font-weight: 600; transition: color 0.2s; padding-bottom: 4px; border-bottom: 2px solid transparent; }
    .nav-links a:hover { color: #FF3D3D; }
    .nav-links a.active { color: #FF3D3D; border-bottom: 2px solid #FF3D3D; }
    .logout-btn { background-color: #FF3D3D; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; transition: 0.3s; }
    .logout-btn:hover { background-color: #e63535; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

  .container {
    max-width: 960px;
    margin: 0 auto;
  }

  /* Profile Card */
  .profile-card {
    background: #E8F5E9;
    border-radius: 24px;
    padding: 35px 40px;
    margin-bottom: 28px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.07);
    display: flex;
    align-items: center;
    gap: 40px;
  }

  .profile-card h2 {
    font-family: 'Kanit', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 6px;
    color: #1B5E20;
    text-align: center;
  }

  .profile-img-wrap {
    text-align: center;
    min-width: 130px;
    flex-shrink: 0;
  }

  .profile-img-wrap img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    background: #c8e6c9;
  }

  .profile-img-placeholder {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: linear-gradient(135deg, #c8e6c9, #a5d6a7);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.8rem;
    border: 4px solid #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin: 0 auto;
  }

  .profile-welcome {
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    color: #388E3C;
    font-weight: 600;
    margin-top: 10px;
  }

  .profile-fields {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 13px;
  }

  .field-row {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .field-row label {
    font-weight: 600;
    min-width: 180px;
    font-size: 14px;
    color: #555;
  }

  .field-value {
    background: #ffffff;
    border-radius: 20px;
    padding: 10px 22px;
    flex: 1;
    font-size: 15px;
    color: #333;
  }

  /* Stats */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
  }

  .stat-card {
    border-radius: var(--radius);
    padding: 28px 16px;
    text-align: center;
    box-shadow: var(--shadow);
    transition: transform 0.15s;
  }
  .stat-card:hover { transform: translateY(-2px); }

  .stat-card.orange { background: var(--orange); }
  .stat-card.blue   { background: var(--blue); }
  .stat-card.teal   { background: var(--teal); }

  .stat-card .stat-label {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 14px;
  }

  .stat-card .stat-number {
    font-size: 2.8rem;
    font-weight: 700;
    line-height: 1;
  }

  /* Table Section */
  .table-section h3 {
    text-align: center;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 16px;
  }

  .table-wrapper {
    background: var(--white);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
  }

  thead tr {
    background: #f0f0f0;
  }

  th, td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid #eee;
  }

  th {
    font-weight: 600;
    color: #555;
    font-size: 0.85rem;
  }

  td.center { text-align: center; }

  .status-badge {
    font-weight: 600;
    font-size: 0.88rem;
  }
  .status-badge.pending  { color: #e67e22; }
  .status-badge.process  { color: #3498db; }
  .status-badge.approved { color: #27ae60; }
  .status-badge.cancelled { color: #e74c3c; }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    font-family: 'Prompt', sans-serif;
    font-size: 0.83rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: background 0.15s, transform 0.1s;
  }
  .btn:active { transform: scale(0.97); }

  .btn-blue {
    background: var(--purple-btn);
    color: var(--white);
  }
  .btn-blue:hover { background: var(--purple-btn-dark); }

  .btn-green {
    background: var(--teal);
    color: var(--white);
  }
  .btn-green:hover { background: var(--teal-dark); }

  .btn-gray {
    background: #b0bec5;
    color: var(--white);
  }
  .btn-gray:hover { background: #90a4ae; }

  /* Navigation */
  .nav-row {
    display: flex;
    justify-content: space-between;
    margin-top: 28px;
  }

  /* Modal */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.35);
    z-index: 100;
    align-items: center;
    justify-content: center;
  }
  .modal-overlay.active { display: flex; }

  .modal {
    background: #fff0f0;
    border-radius: 16px;
    padding: 36px 32px;
    max-width: 540px;
    width: 95%;
    position: relative;
    box-shadow: 0 8px 40px rgba(0,0,0,0.18);
    animation: fadeIn 0.2s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.97); }
    to   { opacity: 1; transform: scale(1); }
  }

  .modal-close {
    position: absolute;
    top: 14px;
    right: 18px;
    font-size: 1.3rem;
    cursor: pointer;
    color: #e74c3c;
    background: none;
    border: none;
    font-weight: 700;
  }

  .modal h3 {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e0c0c0;
  }

  .modal-info p {
    font-size: 0.93rem;
    margin-bottom: 10px;
    line-height: 1.6;
  }

  .modal-info p strong { font-weight: 700; }

  .pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #00bcd4;
    color: #fff;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 14px;
    transition: background 0.15s;
  }
  .pdf-btn:hover { background: #0097a7; }

  .modal label {
    font-size: 0.9rem;
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
    margin-top: 12px;
  }

  .modal select,
  .modal textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: 'Prompt', sans-serif;
    font-size: 0.9rem;
    background: #fff;
    margin-top: 4px;
  }

  .modal textarea {
    height: 110px;
    resize: vertical;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
  }

  .no-data {
    text-align: center;
    color: var(--muted);
    padding: 40px;
    font-size: 0.95rem;
  }

  @media (max-width: 600px) {
    .stats-grid { grid-template-columns: 1fr; }
    .profile-row { flex-direction: column; align-items: center; }
    .field-row { flex-direction: column; align-items: flex-start; }
    .field-row label { min-width: unset; }
  }
</style>
</head>
<body>
         <header>
        <div class="logo">
            <a href="https://swu.ac.th/" target="_blank">
                <img src="../homepage_pic/SWU_Logo_TH_Color.png" alt="SWU" class="logo-img">
            </a>
        </div>
        <div class="nav-links">
            <a href="#home" class="nav-item">หน้าแรก</a>
            <a href="#news" class="nav-item">ประชาสัมพันธ์</a>
            <a href="#teacher" class="nav-item">บุคลากร</a>
        </div>
        <a href="../index.html"><button class="logout-btn">LOG OUT</button></a>

    </header>

<div class="container">

  <!-- Profile Card -->
  <div class="profile-card">
    <div class="profile-img-wrap">
      <img src="../homepage_pic/teacher_pic.png" alt="teacher photo">
      <p class="profile-welcome">ยินดีต้อนรับนะคะ</p>
    </div>

    <div class="profile-fields">
      <div class="field-row">
        <label>สวัสดี อาจารย์ :</label>
        <div class="field-value" style="font-family:'Kanit'; font-size:16px; font-weight:600; color:#1B5E20;"><?= htmlspecialchars($teacher_full_name) ?></div>
      </div>
      <div class="field-row">
        <label>อีเมล :</label>
        <div class="field-value"><?= htmlspecialchars($teacher['email'] ?? '-') ?></div>
      </div>
      <div class="field-row">
        <label>เบอร์โทร :</label>
        <div class="field-value"><?= htmlspecialchars($teacher['phone'] ?? '-') ?></div>
      </div>
      <div class="field-row">
        <label>อาจารย์ที่ปรึกษาชั้นปี :</label>
        <div class="field-value" style="color:#388E3C; font-weight:600;">ชั้นปีที่ <?= htmlspecialchars($max_year) ?></div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card orange">
      <div class="stat-label">รับเรื่องเข้าระบบ</div>
      <div class="stat-number"><?= $wait_count ?></div>
    </div>
    <div class="stat-card blue">
      <div class="stat-label">อาจารย์อนุมัติแล้ว</div>
      <div class="stat-number"><?= $incomplete_count ?></div>
    </div>
    <div class="stat-card teal">
      <div class="stat-label">ฝึกงานเสร็จสิ้น</div>
      <div class="stat-number"><?= $approved_count ?></div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-section">
    <h3>รายชื่อนิสิตที่ปรึกษา</h3>
    <div class="table-wrapper">
      <?php if (empty($advisees)): ?>
        <div class="no-data">ยังไม่มีนิสิตในที่ปรึกษา</div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>รหัสนิสิต</th>
            <th>ชื่อ – สกุล</th>
            <th>✅ สถานะฝึกงาน</th>
            <th>📄 เอกสารที่ส่ง</th>
            <th>🔥 หมายเหตุ</th>
            <th>ตรวจสอบ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($advisees as $student):
            $req = $request_map[$student['student_id']] ?? null;
            $status_class = 'no-request';
            $status_text  = '-';
            $company_name = '-';
            if ($req) {
              $company_name = $req['company_name'];
              if ($req['status'] === 'รับเรื่องเข้าระบบ') {
                $status_class = 'pending'; $status_text = 'รับเรื่องเข้าระบบ';
              } elseif ($req['status'] === 'อาจารย์ที่ปรึกษาอนุมัติ') {
                $status_class = 'process'; $status_text = 'อาจารย์อนุมัติ';
              } elseif ($req['status'] === 'ออกใบส่งตัวแล้ว') {
                $status_class = 'approved'; $status_text = 'ออกใบส่งตัวแล้ว';
              } elseif ($req['status'] === 'ฝึกงานเสร็จสิ้น') {
                $status_class = 'approved'; $status_text = 'ฝึกงานเสร็จสิ้น';
              } elseif ($req['status'] === 'ยกเลิก') {
                $status_class = 'cancelled'; $status_text = '9 ยกเลิก';
              }
            }
            // Build modal data merging student + request
            $modal_data = [
              'request_id'   => $req['request_id'] ?? '',
              'student_id'   => $student['student_id'],
              'full_name'    => $student['full_name'],
              'company_name' => $company_name,
              'position'     => $req['position'] ?? '',
              'pdf_path'     => $req['pdf_path'] ?? '',
              'status'       => $req['status'] ?? '',
              'remark'       => $req['remark'] ?? '',
            ];
          ?>
          <tr>
            <td><?= htmlspecialchars($student['student_id']) ?></td>
            <td><?= htmlspecialchars($student['full_name']) ?></td>
            <td><?= htmlspecialchars($company_name) ?></td>
            <td class="center">
              <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
            </td>
            <td class="center"><?= ($req && $req['remark']) ? htmlspecialchars($req['remark']) : '-' ?></td>
            <td class="center">
              <?php if ($req): ?>
              <button class="btn btn-blue"
                onclick='openModal(<?= htmlspecialchars(json_encode($modal_data), ENT_QUOTES) ?>)'>
                🔵 ตรวจสอบ / อนุมัติ
              </button>
              <?php else: ?>
              <span style="color:#aaa; font-size:0.82rem;">ยังไม่ยื่นคำขอ</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Nav -->
  <div class="nav-row">
    <button class="btn btn-blue" onclick="history.back()">← หน้าก่อนหน้า</button>
    <button class="btn btn-blue" onclick="location.reload()">หน้าถัดไป →</button>
  </div>

</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <h3>🔍 ตรวจสอบใบคำขอฝึกงาน</h3>

    <div class="modal-info">
      <p><strong>ชื่อ-นามสกุล:</strong> <span id="m_name"></span></p>
      <p><strong>รหัสนิสิต:</strong> <span id="m_sid"></span></p>
      <p><strong>บริษัท:</strong> <span id="m_company"></span></p>
      <p><strong>ตำแหน่ง:</strong> <span id="m_position"></span></p>
      <p>
        <strong>เอกสาร PDF:</strong>
        <a href="#" id="m_pdf_link" class="pdf-btn" target="_blank">📁 เปิดในแท็บใหม่</a>
      </p>
      <!-- PDF Embed Viewer -->
      <div id="m_pdf_viewer" style="display:none; margin-top:10px;">
        <iframe id="m_pdf_iframe"
          src=""
          width="100%"
          height="250px"
          style="border: 1px solid #ddd; border-radius: 8px;">
        </iframe>
      </div>
      <div id="m_pdf_none" style="display:none; color:#aaa; font-size:0.85rem; margin-top:6px;">
        ⚠️ นิสิตยังไม่ได้แนบไฟล์ PDF
      </div>
    </div>

    <form method="POST">
      <input type="hidden" name="update_request" value="1">
      <input type="hidden" name="request_id" id="m_req_id">

      <label>สถานะการอนุมัติ:</label>
      <select name="status" id="m_status">
        <option value="รับเรื่องเข้าระบบ">รับเรื่องเข้าระบบ</option>
        <option value="อาจารย์ที่ปรึกษาอนุมัติ">อาจารย์ที่ปรึกษาอนุมัติ</option>
        <option value="ออกใบส่งตัวแล้ว">ออกใบส่งตัวแล้ว</option>
        <option value="ฝึกงานเสร็จสิ้น">ฝึกงานเสร็จสิ้น</option>
        <option value="ยกเลิก">ยกเลิก</option>
      </select>

      <label>หมายเหตุ (พิมพ์ได้ที่นี่):</label>
      <textarea name="remark" id="m_remark" placeholder="ใส่เหตุผลกรณีไม่อนุมัติ หรือคำแนะนำเพิ่มเติม ..."></textarea>

      <div class="modal-footer">
        <button type="button" class="btn btn-gray" onclick="closeModal()">ยกเลิก</button>
        <button type="submit" class="btn btn-green">💾 บันทึกและอนุมัติ</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(req) {
  document.getElementById('m_name').textContent     = req.full_name || '-';
  document.getElementById('m_sid').textContent      = req.student_id || req.sid || '-';
  document.getElementById('m_company').textContent  = req.company_name || '-';
  document.getElementById('m_position').textContent = req.position || '-';
  document.getElementById('m_req_id').value         = req.request_id;
  document.getElementById('m_remark').value         = req.remark || '';

  const pdfLink   = document.getElementById('m_pdf_link');
  const pdfViewer = document.getElementById('m_pdf_viewer');
  const pdfIframe = document.getElementById('m_pdf_iframe');
  const pdfNone   = document.getElementById('m_pdf_none');

  if (req.pdf_path) {
    // ปรับ path ให้เป็น URL ที่ browser เปิดได้
    // pdf_path ใน DB เก็บแค่ชื่อไฟล์ (เช่น 63123477_1234567890.pdf)
    // save_internship.php อัปโหลดไปที่ student/uploads/ (__DIR__/uploads/)
    const filename = req.pdf_path.split('/').pop();
    const pdfUrl = req.pdf_path.startsWith('http') ? req.pdf_path : '../uploads/' + filename;
    pdfLink.href = pdfUrl;
    pdfLink.style.display = 'inline-flex';
    pdfIframe.src = pdfUrl;
    pdfViewer.style.display = 'block';
    pdfNone.style.display = 'none';
  } else {
    pdfLink.style.display = 'none';
    pdfViewer.style.display = 'none';
    pdfNone.style.display = 'block';
    pdfIframe.src = '';
  }

  // Set status select
  const sel = document.getElementById('m_status');
  sel.value = req.status || 'รับเรื่องเข้าระบบ';

  document.getElementById('modalOverlay').classList.add('active');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('active');
}

// Close on overlay click
document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>