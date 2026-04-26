<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['u_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// ===== HANDLE UPDATE STUDENT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $sid = $_POST['student_id'];
    $conn->prepare("UPDATE students SET full_name=:n, year_level=:y, major=:m, gpa=:g, advisor=:a, email=:e, phone=:p WHERE student_id=:sid")
         ->execute(['n'=>$_POST['full_name'],'y'=>$_POST['year_level'],'m'=>$_POST['major'],'g'=>$_POST['gpa'],'a'=>$_POST['advisor'],'e'=>$_POST['email'],'p'=>$_POST['phone'],'sid'=>$sid]);
    $conn->prepare("UPDATE users SET username=:u, password=:pw WHERE u_id=(SELECT u_id FROM students WHERE student_id=:sid)")
         ->execute(['u'=>$_POST['username'],'pw'=>$_POST['password'],'sid'=>$sid]);
    header("Location: s_view_all.php?tab=student&msg=saved"); exit();
}

// ===== HANDLE UPDATE TEACHER =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
    $tid = $_POST['teacher_id'];
    $conn->prepare("UPDATE teachers SET first_name=:fn, last_name=:ln, email=:e, phone=:p WHERE teacher_id=:tid")
         ->execute(['fn'=>$_POST['first_name'],'ln'=>$_POST['last_name'],'e'=>$_POST['email'],'p'=>$_POST['phone'],'tid'=>$tid]);
    $conn->prepare("UPDATE users SET username=:u, password=:pw WHERE u_id=(SELECT u_id FROM teachers WHERE teacher_id=:tid)")
         ->execute(['u'=>$_POST['username'],'pw'=>$_POST['password'],'tid'=>$tid]);
    header("Location: s_view_all.php?tab=teacher&msg=saved"); exit();
}

// ===== HANDLE UPDATE STAFF =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $stid = $_POST['staff_id'];
    $conn->prepare("UPDATE staffs SET first_name=:fn, last_name=:ln, department=:d, email=:e, phone=:p WHERE staff_id=:stid")
         ->execute(['fn'=>$_POST['first_name'],'ln'=>$_POST['last_name'],'d'=>$_POST['department'],'e'=>$_POST['email'],'p'=>$_POST['phone'],'stid'=>$stid]);
    $conn->prepare("UPDATE users SET username=:u, password=:pw WHERE u_id=:uid")
         ->execute(['u'=>$_POST['username'],'pw'=>$_POST['password'],'uid'=>$_POST['u_id']]);
    header("Location: s_view_all.php?tab=staff&msg=saved"); exit();
}

// ===== HANDLE APPROVE INTERNSHIP =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_request'])) {
    $conn->prepare("UPDATE internship_requests SET status=:s, remark=:r WHERE request_id=:rid")
         ->execute(['s'=>$_POST['status'],'r'=>$_POST['remark'],'rid'=>$_POST['request_id']]);
    header("Location: s_view_all.php?msg=approved"); exit();
}

// ===== FETCH DATA =====
$stmt = $conn->prepare("SELECT * FROM staffs WHERE u_id=:u"); $stmt->execute(['u'=>$u_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$students = $conn->query("SELECT s.*, u.username, u.password as u_password FROM students s LEFT JOIN users u ON s.u_id=u.u_id ORDER BY s.student_id ASC")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $conn->query("SELECT t.*, u.username, u.password as u_password FROM teachers t LEFT JOIN users u ON t.u_id=u.u_id ORDER BY t.teacher_id ASC")->fetchAll(PDO::FETCH_ASSOC);
$staffs   = $conn->query("SELECT s.*, u.username, u.password as u_password FROM staffs s LEFT JOIN users u ON s.u_id=u.u_id ORDER BY s.staff_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// ดึงคำขอฝึกงานทั้งหมดพร้อมข้อมูลนิสิตและบริษัท
$internships = $conn->query("
    SELECT ir.*, s.full_name, s.major, c.name as company_name
    FROM internship_requests ir
    JOIN students s ON ir.student_id = s.student_id
    JOIN companies c ON ir.company_id = c.company_id
    ORDER BY ir.request_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$active_tab = $_GET['tab'] ?? 'student';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - IS.SWU</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --bg: #fdf6ee;
        --orange: #FFCC80;
        --orange-dark: #FFB74D;
        --orange-shadow: rgba(255,180,0,0.2);
        --red: #FF3D3D;
        --text: #333;
        --muted: #777;
        --radius: 16px;
        --shadow: 0 4px 16px rgba(0,0,0,0.08);
        --white: #fff;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Prompt', sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        padding: 30px 20px 60px;
    }

    /* HEADER */
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 5%;
        background: var(--white);
        box-shadow: var(--shadow);
        margin-bottom: 30px;
        border-radius: 10px;
    }
    .logo-img { height: 48px; }
    .nav-links { display: flex; gap: 20px; }
    .nav-links a { text-decoration: none; color: #333; font-weight: 600; transition: color 0.2s; padding-bottom: 4px; border-bottom: 2px solid transparent; font-size: 15px; }
    .nav-links a:hover { color: var(--red); }
    .nav-links a.active { color: var(--red); border-bottom: 2px solid var(--red); }
    .logout-btn { background: var(--red); color: #fff; border: none; padding: 8px 22px; border-radius: 6px; font-family: 'Prompt', sans-serif; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
    .logout-btn:hover { background: #e63535; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

    /* PAGE WRAPPER */
    .page-wrapper { max-width: 960px; margin: 0 auto; }

    /* WELCOME CARD */
    .welcome-card {
        background: var(--orange);
        border-radius: 24px;
        padding: 35px 45px;
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 40px;
        margin-bottom: 28px;
        box-shadow: 0 8px 25px var(--orange-shadow);
    }
    .welcome-card h1 { font-family: 'Kanit', sans-serif; font-size: 28px; font-weight: 700; margin-bottom: 18px; color: #333; }
    .info-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .info-label { font-family: 'Kanit', sans-serif; font-weight: 700; font-size: 16px; min-width: 130px; color: #555; }
    .info-value { background: rgba(255,255,255,0.7); padding: 10px 20px; border-radius: 20px; min-width: 220px; font-size: 15px; color: #333; }
    .cat-img { width: 200px; opacity: 0.9; }

    /* SUCCESS */
    .success-banner { background: #E8F5E9; color: #2E7D32; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }

    /* SECTION */
    .section { }
    .section-title {
        font-family: 'Kanit', sans-serif;
        font-size: 20px; font-weight: 700;
        color: #333;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 2px dashed #e0c8a0;
        text-align: center;
    }

    /* TABS */
    .tabs { display: flex; gap: 10px; margin-bottom: 20px; justify-content: center; }
    .tab-btn {
        padding: 9px 28px;
        border: 2px solid #ddd;
        background: var(--white);
        border-radius: 20px;
        font-family: 'Prompt', sans-serif;
        font-size: 14px; font-weight: 600;
        cursor: pointer; color: #666; transition: 0.2s;
    }
    .tab-btn.active { background: var(--orange); border-color: var(--orange-dark); color: #333; }
    .tab-btn:hover { border-color: var(--orange-dark); }

    /* TOOLBAR */
    .toolbar { display: flex; gap: 10px; margin-bottom: 16px; align-items: center; }
    .search-wrap {
        display: flex; align-items: center;
        background: var(--white); border: 1px solid #ddd;
        border-radius: 10px; padding: 0 14px; gap: 8px;
        flex: 1; max-width: 320px;
        transition: border 0.2s;
    }
    .search-wrap:focus-within { border-color: var(--orange-dark); }
    .search-wrap input { border: none; outline: none; padding: 10px 0; font-family: 'Prompt', sans-serif; font-size: 14px; width: 100%; background: transparent; }
    .btn-all { padding: 9px 20px; border: 1px solid #ddd; background: var(--white); border-radius: 10px; font-family: 'Prompt', sans-serif; font-size: 14px; cursor: pointer; transition: 0.2s; }
    .btn-all:hover { background: #fafafa; border-color: var(--orange-dark); }
    .btn-filter { padding: 9px 14px; border: 1px solid #ddd; background: var(--white); border-radius: 10px; cursor: pointer; font-size: 15px; transition: 0.2s; }
    .btn-add { padding: 9px 18px; background: var(--white); border: 1px solid #ccc; border-radius: 10px; font-family: 'Prompt', sans-serif; font-size: 14px; cursor: pointer; color: #555; margin-left: auto; transition: 0.2s; }
    .btn-add:hover { background: #f5f5f5; }

    /* TABLE */
    .data-table { width: 100%; border-collapse: collapse; border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); }
    .data-table th { background: #FFE0B2; padding: 14px 16px; font-family: 'Kanit', sans-serif; font-weight: 600; font-size: 15px; color: #333; text-align: center; border-bottom: 2px solid #FFCC80; }
    .data-table td { padding: 13px 16px; border-top: 1px solid #FFF3E0; text-align: center; font-size: 14px; background: var(--white); }
    .data-table tr:hover td { background: #FFFDE7; }
    .btn-edit { background: #FFCC80; border: none; padding: 7px 18px; border-radius: 20px; font-family: 'Prompt', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; color: #5D4037; transition: 0.2s; }
    .btn-edit:hover { background: var(--orange-dark); }

    /* BOTTOM BTN */
    .bottom-btn { display: block; margin: 30px auto 0; background: linear-gradient(135deg, #FFC107, #FF9800); border: none; padding: 15px 60px; border-radius: 30px; font-family: 'Kanit', sans-serif; font-size: 17px; font-weight: 700; cursor: pointer; color: #333; transition: 0.3s; box-shadow: 0 6px 20px rgba(255,152,0,0.35); }
    .bottom-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(255,152,0,0.45); }

    /* MODAL OVERLAY */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
    .modal-overlay.active { display: flex; }

    /* POPUP CARD */
    .popup-card {
        background: #FFF8F0;
        border-radius: 24px;
        padding: 35px 40px;
        width: 90%; max-width: 520px;
        max-height: 88vh; overflow-y: auto;
        position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        animation: popIn 0.3s ease;
    }
    @keyframes popIn {
        from { opacity: 0; transform: translateY(-16px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .popup-close { position: absolute; top: 14px; right: 18px; background: none; border: none; font-size: 1.4rem; color: #e74c3c; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .popup-close:hover { transform: scale(1.1); }
    .popup-title { background: var(--orange); border-radius: 14px; padding: 14px 24px; text-align: center; font-family: 'Kanit', sans-serif; font-size: 19px; font-weight: 700; color: #333; margin-bottom: 22px; }
    .popup-section-label { font-family: 'Kanit', sans-serif; font-size: 16px; font-weight: 700; text-align: center; margin: 16px 0 12px; color: #555; border-top: 1px solid #FFE0B2; padding-top: 14px; }
    .popup-field { display: flex; align-items: center; gap: 12px; margin-bottom: 11px; }
    .popup-field label { font-family: 'Kanit', sans-serif; font-weight: 600; font-size: 14px; min-width: 140px; color: #555; }
    .popup-field input {
        background: #F5F5F5; border: 1px solid #ddd;
        border-radius: 10px; padding: 9px 14px;
        font-family: 'Prompt', sans-serif; font-size: 14px;
        flex: 1; outline: none; transition: 0.2s; color: #333;
    }
    .popup-field input:focus { background: var(--white); border-color: var(--orange-dark); box-shadow: 0 0 0 3px rgba(255,183,77,0.15); }
    .popup-cat-row { display: flex; gap: 16px; align-items: flex-start; }
    .popup-cat-row .popup-fields { flex: 1; }
    .popup-cat-img { width: 110px; margin-top: 8px; border-radius: 12px; }
    .btn-save { display: block; width: 100%; margin-top: 22px; background: linear-gradient(135deg, #66BB6A, #43A047); border: none; padding: 13px; border-radius: 12px; font-family: 'Kanit', sans-serif; font-size: 16px; font-weight: 700; color: #fff; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 12px rgba(76,175,80,0.3); }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(76,175,80,0.4); }

    /* INTERNSHIP MODAL */
    .intern-modal { max-width: 860px; background: var(--white); }
    .intern-table { width: 100%; border-collapse: collapse; margin-top: 10px; border-radius: 10px; overflow: hidden; }
    .intern-table th { background: #FFE0B2; padding: 12px 14px; font-family: 'Kanit', sans-serif; font-size: 14px; font-weight: 600; text-align: center; border-bottom: 2px solid var(--orange); }
    .intern-table td { padding: 11px 14px; border-top: 1px solid #FFF3E0; font-size: 13px; text-align: center; background: var(--white); }
    .intern-table tr:hover td { background: #FFFDE7; }
    .badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-pending    { background: #FFF3E0; color: #E65100; }
    .badge-processing { background: #E3F2FD; color: #1565C0; }
    .badge-approved   { background: #E8F5E9; color: #2E7D32; }
    .approve-form { display: flex; gap: 8px; align-items: center; justify-content: center; flex-wrap: wrap; }
    .approve-select { padding: 6px 10px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Prompt', sans-serif; font-size: 12px; outline: none; }
    .approve-select:focus { border-color: var(--orange-dark); }
    .approve-remark { padding: 6px 10px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Prompt', sans-serif; font-size: 12px; width: 130px; outline: none; }
    .btn-approve { background: #A5D6A7; border: none; padding: 6px 14px; border-radius: 8px; font-family: 'Prompt', sans-serif; font-size: 12px; font-weight: 600; color: #1B5E20; cursor: pointer; transition: 0.2s; }
    .btn-approve:hover { background: #81C784; }
    .btn-pdf { background: #00BCD4; color: #fff; border: none; padding: 5px 12px; border-radius: 8px; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-block; }

    .tab-content { display: none; }
    .tab-content.active { display: block; }
    </style>
</head>
<body>

<header>
    <div><a href="https://swu.ac.th/" target="_blank"><img src="../homepage_pic/SWU_Logo_TH_Color.png" alt="SWU" class="logo-img"></a></div>
    <div class="nav-links">
        <a href="#welcome" class="nav-item">หน้าแรก</a>
        <a href="#" onclick="switchTab('student')" class="nav-item">รายชื่อนิสิต</a>
        <a href="#" onclick="switchTab('staff')" class="nav-item">บุคลากร</a>
    </div>
    <a href="../index.html"><button class="logout-btn">LOG OUT</button></a>
</header>

<div class="page-wrapper">

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
<div class="success-banner">✅ บันทึกข้อมูลเรียบร้อยแล้ว</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'approved'): ?>
<div class="success-banner">✅ อนุมัติคำขอเรียบร้อยแล้ว — นิสิตและอาจารย์จะเห็นสถานะที่อัปเดตทันที</div>
<?php endif; ?>

<div class="welcome-card" id="welcome">
    <div style="text-align:center; min-width:130px; flex-shrink:0;">
        <img src="../homepage_pic/admin_pic.png" alt="staff photo"
             style="width:110px; height:110px; border-radius:50%; object-fit:cover; border:4px solid rgba(255,255,255,0.8); box-shadow:0 4px 12px rgba(0,0,0,0.12);"
             onerror="this.style.display='none'">
        <p style="font-family:'Kanit',sans-serif; font-size:13px; color:#5D4037; font-weight:600; margin-top:10px;">ยินดีต้อนรับค่ะ</p>
    </div>
    <div>
        <div class="info-row">
            <span class="info-label">ชื่อ :</span>
            <span class="info-value"><?= htmlspecialchars(($staff['first_name']??'').(' ').($staff['last_name']??'')) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">ตำแหน่งงาน :</span>
            <span class="info-value"><?= htmlspecialchars($staff['department']??'-') ?></span>
        </div>
    </div>
    <img src="../homepage_pic/picturc/cats_group.png" class="cat-img" alt="cats" onerror="this.style.display='none'">
</div>

<div class="section">
    <div class="section-title">รายชื่อบุคลากร</div>

    <div class="tabs">
        <button class="tab-btn <?= $active_tab=='student'?'active':'' ?>" onclick="switchTab('student')">🎓 นิสิต</button>
        <button class="tab-btn <?= $active_tab=='teacher'?'active':'' ?>" onclick="switchTab('teacher')">👩‍🏫 อาจารย์</button>
        <button class="tab-btn <?= $active_tab=='staff'  ?'active':'' ?>" onclick="switchTab('staff')">🏢 เจ้าหน้าที่</button>
    </div>

    <!-- STUDENT TAB -->
    <div id="tab-student" class="tab-content <?= $active_tab=='student'?'active':'' ?>">
        <div class="toolbar">
            <div class="search-wrap"><span>🔍</span><input type="text" placeholder="ค้นหา" oninput="filterTable('student-table', this.value)"></div>
            <button class="btn-all" onclick="filterTable('student-table','')">All</button>
            <button class="btn-filter">⏷</button>
            <button class="btn-add">+ เพิ่มรายชื่อใหม่</button>
        </div>
        <table class="data-table" id="student-table">
            <thead><tr><th style="width:28%">✍️ จัดการ</th><th style="width:42%">ชื่อ - สกุล</th><th style="width:30%">รหัสนิสิต</th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): $d = htmlspecialchars(json_encode(['student_id'=>$s['student_id'],'full_name'=>$s['full_name'],'year_level'=>$s['year_level'],'major'=>$s['major'],'gpa'=>$s['gpa'],'advisor'=>$s['advisor'],'email'=>$s['email'],'phone'=>$s['phone'],'username'=>$s['username'],'u_password'=>$s['u_password']]),ENT_QUOTES); ?>
            <tr>
                <td><button class="btn-edit" onclick="openStudentModal(<?= $d ?>)">✏️ แก้ไข</button></td>
                <td style="text-align:left"><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['student_id']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- TEACHER TAB -->
    <div id="tab-teacher" class="tab-content <?= $active_tab=='teacher'?'active':'' ?>">
        <div class="toolbar">
            <div class="search-wrap"><span>🔍</span><input type="text" placeholder="ค้นหา" oninput="filterTable('teacher-table', this.value)"></div>
            <button class="btn-all" onclick="filterTable('teacher-table','')">All</button>
            <button class="btn-filter">⏷</button>
            <button class="btn-add">+ เพิ่มรายชื่อใหม่</button>
        </div>
        <table class="data-table" id="teacher-table">
            <thead><tr><th style="width:28%">✍️ จัดการ</th><th style="width:42%">ชื่อ - สกุล</th><th style="width:30%">รหัสอาจารย์</th></tr></thead>
            <tbody>
            <?php foreach ($teachers as $t): $d = htmlspecialchars(json_encode(['teacher_id'=>$t['teacher_id'],'first_name'=>$t['first_name'],'last_name'=>$t['last_name'],'email'=>$t['email'],'phone'=>$t['phone'],'username'=>$t['username'],'u_password'=>$t['u_password']]),ENT_QUOTES); ?>
            <tr>
                <td><button class="btn-edit" onclick="openTeacherModal(<?= $d ?>)">✏️ แก้ไข</button></td>
                <td style="text-align:left"><?= htmlspecialchars($t['first_name'].' '.$t['last_name']) ?></td>
                <td><?= htmlspecialchars($t['teacher_id']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- STAFF TAB -->
    <div id="tab-staff" class="tab-content <?= $active_tab=='staff'?'active':'' ?>">
        <div class="toolbar">
            <div class="search-wrap"><span>🔍</span><input type="text" placeholder="ค้นหา" oninput="filterTable('staff-table', this.value)"></div>
            <button class="btn-all" onclick="filterTable('staff-table','')">All</button>
            <button class="btn-filter">⏷</button>
            <button class="btn-add">+ เพิ่มรายชื่อใหม่</button>
        </div>
        <table class="data-table" id="staff-table">
            <thead><tr><th style="width:28%">✍️ จัดการ</th><th style="width:42%">ชื่อ - สกุล</th><th style="width:30%">รหัสเจ้าหน้าที่</th></tr></thead>
            <tbody>
            <?php foreach ($staffs as $sf): $d = htmlspecialchars(json_encode(['staff_id'=>$sf['staff_id'],'u_id'=>$sf['u_id'],'first_name'=>$sf['first_name'],'last_name'=>$sf['last_name'],'department'=>$sf['department'],'email'=>$sf['email'],'phone'=>$sf['phone'],'username'=>$sf['username'],'u_password'=>$sf['u_password']]),ENT_QUOTES); ?>
            <tr>
                <td><button class="btn-edit" onclick="openStaffModal(<?= $d ?>)">✏️ แก้ไข</button></td>
                <td style="text-align:left"><?= htmlspecialchars($sf['first_name'].' '.$sf['last_name']) ?></td>
                <td><?= htmlspecialchars($sf['staff_id']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button class="bottom-btn" onclick="document.getElementById('modal-internship').classList.add('active')">แก้ไข เอกสารฝึกงาน</button>
</div>

</div><!-- end page-wrapper -->

<!-- POPUP: STUDENT -->
<div class="modal-overlay" id="modal-student">
    <div class="popup-card">
        <button class="popup-close" onclick="closeModal('modal-student')">✕</button>
        <div class="popup-title">ข้อมูลส่วนตัวของนิสิต</div>
        <form method="POST">
            <input type="hidden" name="update_student" value="1">
            <input type="hidden" name="student_id" id="s_student_id">
            <div class="popup-section-label">ข้อมูลส่วนตัว</div>
            <div class="popup-cat-row">
                <div class="popup-fields">
                    <div class="popup-field"><label>ชื่อ :</label><input name="full_name" id="s_full_name"></div>
                    <div class="popup-field"><label>ชั้น ปี :</label><input name="year_level" id="s_year_level"></div>
                    <div class="popup-field"><label>สาขา :</label><input name="major" id="s_major"></div>
                    <div class="popup-field"><label>GPA :</label><input name="gpa" id="s_gpa"></div>
                    <div class="popup-field"><label>อาจารย์ที่ปรึกษา :</label><input name="advisor" id="s_advisor"></div>
                </div>
                <img src="../homepage_pic/picturc/cat_writing.png" class="popup-cat-img" onerror="this.style.display='none'" alt="">
            </div>
            <div class="popup-section-label">USERS</div>
            <div class="popup-field"><label>User ID :</label><input name="username" id="s_username"></div>
            <div class="popup-field"><label>Password :</label><input name="password" id="s_password"></div>
            <div class="popup-section-label">ช่องทางการติดต่อ</div>
            <div class="popup-field"><label>Email ติดต่อ :</label><input name="email" id="s_email" type="email"></div>
            <div class="popup-field"><label>เบอร์โทรติดต่อ :</label><input name="phone" id="s_phone"></div>
            <button type="submit" class="btn-save">บันทึกข้อมูล</button>
        </form>
    </div>
</div>

<!-- POPUP: TEACHER -->
<div class="modal-overlay" id="modal-teacher">
    <div class="popup-card">
        <button class="popup-close" onclick="closeModal('modal-teacher')">✕</button>
        <div class="popup-title">ข้อมูลส่วนตัวของอาจารย์</div>
        <form method="POST">
            <input type="hidden" name="update_teacher" value="1">
            <input type="hidden" name="teacher_id" id="t_teacher_id">
            <div class="popup-section-label">ข้อมูลส่วนตัว</div>
            <div class="popup-field"><label>ชื่อ :</label><input name="first_name" id="t_first_name"></div>
            <div class="popup-field"><label>นามสกุล :</label><input name="last_name" id="t_last_name"></div>
            <div class="popup-section-label">USERS</div>
            <div class="popup-field"><label>User ID :</label><input name="username" id="t_username"></div>
            <div class="popup-field"><label>Password :</label><input name="password" id="t_password"></div>
            <div class="popup-section-label">ช่องทางการติดต่อ</div>
            <div class="popup-field"><label>Email ติดต่อ :</label><input name="email" id="t_email" type="email"></div>
            <div class="popup-field"><label>เบอร์โทรติดต่อ :</label><input name="phone" id="t_phone"></div>
            <button type="submit" class="btn-save">บันทึกข้อมูล</button>
        </form>
    </div>
</div>

<!-- POPUP: STAFF -->
<div class="modal-overlay" id="modal-staff">
    <div class="popup-card">
        <button class="popup-close" onclick="closeModal('modal-staff')">✕</button>
        <div class="popup-title">ข้อมูลส่วนตัวของเจ้าหน้าที่</div>
        <form method="POST">
            <input type="hidden" name="update_staff" value="1">
            <input type="hidden" name="staff_id" id="sf_staff_id">
            <input type="hidden" name="u_id" id="sf_u_id">
            <div class="popup-section-label">ข้อมูลส่วนตัว</div>
            <div class="popup-field"><label>ชื่อ :</label><input name="first_name" id="sf_first_name"></div>
            <div class="popup-field"><label>นามสกุล :</label><input name="last_name" id="sf_last_name"></div>
            <div class="popup-field"><label>ฝ่ายที่สังกัด :</label><input name="department" id="sf_department"></div>
            <div class="popup-section-label">USERS</div>
            <div class="popup-field"><label>User ID :</label><input name="username" id="sf_username"></div>
            <div class="popup-field"><label>Password :</label><input name="password" id="sf_password"></div>
            <div class="popup-section-label">ช่องทางการติดต่อ</div>
            <div class="popup-field"><label>Email ติดต่อ :</label><input name="email" id="sf_email" type="email"></div>
            <div class="popup-field"><label>เบอร์โทรติดต่อ :</label><input name="phone" id="sf_phone"></div>
            <button type="submit" class="btn-save">บันทึกข้อมูล</button>
        </form>
    </div>
</div>

<!-- POPUP: INTERNSHIP REQUESTS -->
<div class="modal-overlay" id="modal-internship">
    <div class="popup-card intern-modal" style="background:#fff; max-width:860px; padding:30px 35px;">
        <button class="popup-close" onclick="closeModal('modal-internship')">✕</button>
        <div class="popup-title" style="background:#FFCC80; color:#333; margin-bottom:20px;">📋 คำขอฝึกงานทั้งหมด</div>
        <p style="font-size:13px; color:#888; margin-bottom:14px;">เมื่ออนุมัติ สถานะจะอัปเดตให้นิสิตและอาจารย์เห็นทันที</p>

        <?php if (empty($internships)): ?>
        <p style="text-align:center; color:#aaa; padding:30px;">ยังไม่มีคำขอฝึกงาน</p>
        <?php else: ?>
        <table class="intern-table">
            <thead>
                <tr>
                    <th>นิสิต</th>
                    <th>รหัส</th>
                    <th>บริษัท</th>
                    <th>ตำแหน่ง</th>
                    <th>PDF</th>
                    <th>สถานะปัจจุบัน</th>
                    <th>อนุมัติ / แก้ไข</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($internships as $ir):
                if ($ir['status'] == 'รับเรื่องเข้าระบบ')          $badge = '<span class="badge badge-pending">รับเรื่องเข้าระบบ</span>';
                elseif ($ir['status'] == 'อาจารย์ที่ปรึกษาอนุมัติ') $badge = '<span class="badge badge-processing">อาจารย์อนุมัติ</span>';
                elseif ($ir['status'] == 'ออกใบส่งตัวแล้ว')          $badge = '<span class="badge badge-approved">ออกใบส่งตัวแล้ว</span>';
                elseif ($ir['status'] == 'ฝึกงานเสร็จสิ้น')          $badge = '<span class="badge badge-approved">ฝึกงานเสร็จสิ้น</span>';
                elseif ($ir['status'] == 'ยกเลิก')                    $badge = '<span class="badge" style="background:#FFCDD2;color:#b71c1c;">9 ยกเลิก</span>';
                else                                                   $badge = '<span class="badge badge-pending">รับเรื่องเข้าระบบ</span>';
                $pdf_url = $ir['pdf_path'] ? '../uploads/' . basename($ir['pdf_path']) : '';
            ?>
            <tr>
                <td style="text-align:left"><?= htmlspecialchars($ir['full_name']) ?></td>
                <td><?= htmlspecialchars($ir['student_id']) ?></td>
                <td style="text-align:left"><?= htmlspecialchars($ir['company_name']) ?></td>
                <td><?= htmlspecialchars($ir['position'] ?? '-') ?></td>
                <td>
                    <?php if ($pdf_url): ?>
                    <a href="<?= htmlspecialchars($pdf_url) ?>" target="_blank" class="btn-pdf">📁 ดู</a>
                    <?php else: ?><span style="color:#ccc">-</span><?php endif; ?>
                </td>
                <td><?= $badge ?></td>
                <td>
                    <form method="POST" class="approve-form">
                        <input type="hidden" name="approve_request" value="1">
                        <input type="hidden" name="request_id" value="<?= htmlspecialchars($ir['request_id']) ?>">
                        <select name="status" class="approve-select">
                            <option value="รับเรื่องเข้าระบบ"        <?= $ir['status']=='รับเรื่องเข้าระบบ'?'selected':'' ?>>รับเรื่องเข้าระบบ</option>
                            <option value="อาจารย์ที่ปรึกษาอนุมัติ"  <?= $ir['status']=='อาจารย์ที่ปรึกษาอนุมัติ'?'selected':'' ?>>อาจารย์ที่ปรึกษาอนุมัติ</option>
                            <option value="ออกใบส่งตัวแล้ว"           <?= $ir['status']=='ออกใบส่งตัวแล้ว'?'selected':'' ?>>ออกใบส่งตัวแล้ว</option>
                            <option value="ฝึกงานเสร็จสิ้น"           <?= $ir['status']=='ฝึกงานเสร็จสิ้น'?'selected':'' ?>>ฝึกงานเสร็จสิ้น</option>
                            <option value="ยกเลิก"                    <?= $ir['status']=='ยกเลิก'?'selected':'' ?>>ยกเลิก</option>
                        </select>
                        <input type="text" name="remark" class="approve-remark" placeholder="หมายเหตุ" value="<?= htmlspecialchars($ir['remark']??'') ?>">
                        <button type="submit" class="btn-approve">💾 บันทึก</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelectorAll('.tab-btn')[['student','teacher','staff'].indexOf(tab)].classList.add('active');
}
function filterTable(id, q) {
    q = q.toLowerCase();
    document.querySelectorAll('#' + id + ' tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});
function openStudentModal(d) {
    document.getElementById('s_student_id').value = d.student_id;
    document.getElementById('s_full_name').value  = d.full_name   || '';
    document.getElementById('s_year_level').value = d.year_level  || '';
    document.getElementById('s_major').value      = d.major       || '';
    document.getElementById('s_gpa').value        = d.gpa         || '';
    document.getElementById('s_advisor').value    = d.advisor     || '';
    document.getElementById('s_username').value   = d.username    || '';
    document.getElementById('s_password').value   = d.u_password  || '';
    document.getElementById('s_email').value      = d.email       || '';
    document.getElementById('s_phone').value      = d.phone       || '';
    document.getElementById('modal-student').classList.add('active');
}
function openTeacherModal(d) {
    document.getElementById('t_teacher_id').value = d.teacher_id;
    document.getElementById('t_first_name').value = d.first_name  || '';
    document.getElementById('t_last_name').value  = d.last_name   || '';
    document.getElementById('t_username').value   = d.username    || '';
    document.getElementById('t_password').value   = d.u_password  || '';
    document.getElementById('t_email').value      = d.email       || '';
    document.getElementById('t_phone').value      = d.phone       || '';
    document.getElementById('modal-teacher').classList.add('active');
}
function openStaffModal(d) {
    document.getElementById('sf_staff_id').value   = d.staff_id;
    document.getElementById('sf_u_id').value       = d.u_id;
    document.getElementById('sf_first_name').value = d.first_name  || '';
    document.getElementById('sf_last_name').value  = d.last_name   || '';
    document.getElementById('sf_department').value = d.department  || '';
    document.getElementById('sf_username').value   = d.username    || '';
    document.getElementById('sf_password').value   = d.u_password  || '';
    document.getElementById('sf_email').value      = d.email       || '';
    document.getElementById('sf_phone').value      = d.phone       || '';
    document.getElementById('modal-staff').classList.add('active');
}
const urlTab = new URLSearchParams(window.location.search).get('tab');
if (urlTab) switchTab(urlTab);
</script>
</body>
</html>