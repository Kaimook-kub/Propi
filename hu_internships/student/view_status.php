<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: ../login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// 1. ดึงข้อมูลส่วนตัวนิสิต พร้อมชื่ออาจารย์ที่ปรึกษา
$stmt = $conn->prepare("
    SELECT s.student_id, s.full_name, s.major, s.year_level, s.advisor, s.email, s.phone, s.gpa,
           CONCAT(t.first_name, ' ', t.last_name) AS advisor_name
    FROM users u
    JOIN students s ON u.u_id = s.u_id
    LEFT JOIN teachers t ON s.advisor = t.teacher_id OR s.advisor = t.first_name
    WHERE u.u_id = :u_id 
    LIMIT 1
");
$stmt->execute(['u_id' => $u_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. ดึงข้อมูลรายวิชา
$stmt_course = $conn->prepare("
    SELECT c.course_id, c.course_name, c.credits 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE e.student_id = :student_id
");
$stmt_course->execute(['student_id' => $student['student_id']]);
$courses = $stmt_course->fetchAll(PDO::FETCH_ASSOC);

// 3. ดึงข้อมูลการสมัคร (ถ้ามี)
$stmt_request = $conn->prepare("SELECT * FROM internship_requests WHERE student_id = :student_id LIMIT 1");
$stmt_request->execute(['student_id' => $student['student_id']]);
$intern_request = $stmt_request->fetch(PDO::FETCH_ASSOC);

// 4. ดึงรายชื่อบริษัท (สำหรับ Dropdown)
$stmt_companies = $conn->prepare("SELECT * FROM companies ORDER BY name ASC");
$stmt_companies->execute();
$all_companies = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Student Dashboard - IS.SWU</title>

    <style>
    :root {
        --bg: #fdf2f2;
        --pink-light: #FCE4EC;
        --pink-mid: #F8BBD0;
        --pink-card: #F2E3E3;
        --red-accent: #FF3D3D;
        --text: #333;
        --muted: #777;
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

    /* HEADER */
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 5%;
        background: #fff;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
        border-radius: 10px;
    }
    .logo-img { height: 50px; }
    .nav-links { display: flex; gap: 20px; }
    .nav-links a { text-decoration: none; color: #333; font-weight: 600; transition: color 0.2s; padding-bottom: 4px; border-bottom: 2px solid transparent; }
    .nav-links a:hover { color: var(--red-accent); }
    .nav-links a.active { color: var(--red-accent); border-bottom: 2px solid var(--red-accent); }
    .logout-btn { background: var(--red-accent); color: #fff; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-family: 'Prompt', sans-serif; font-weight: 600; transition: 0.3s; }
    .logout-btn:hover { background: #e63535; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

    /* WRAPPER */
    .page-wrapper { max-width: 860px; margin: 0 auto; }

    /* PROFILE CARD */
    .profile-card {
        background: var(--pink-card);
        border-radius: 24px;
        padding: 35px 40px;
        display: flex;
        align-items: center;
        gap: 40px;
        margin-bottom: 28px;
        box-shadow: var(--shadow);
    }
    .profile-img-wrap { text-align: center; min-width: 120px; flex-shrink: 0; }
    .cat-img { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .profile-welcome { font-family: 'Kanit', sans-serif; font-size: 13px; color: #c2185b; font-weight: 600; margin-top: 10px; }
    .info-group { display: flex; flex-direction: column; gap: 13px; flex-grow: 1; }
    .form-row { display: flex; align-items: center; gap: 12px; }
    .form-row span:first-child { font-weight: 600; color: #666; font-size: 14px; min-width: 140px; }
    .data-box {
        background: #D9D9D9;
        padding: 10px 22px;
        border-radius: 20px;
        font-size: 15px;
        flex-grow: 1;
        color: #333;
    }

    /* COURSE SECTION */
    .course-section { margin-bottom: 28px; }
    .course-section-title {
        font-family: 'Kanit', sans-serif;
        font-size: 17px;
        font-weight: 600;
        color: #c2185b;
        margin-bottom: 14px;
        padding-left: 12px;
        border-left: 4px solid var(--pink-mid);
    }
    .subject-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--pink-light);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: var(--shadow);
    }
    .subject-table th {
        background: var(--pink-mid);
        padding: 14px 18px;
        font-family: 'Kanit', sans-serif;
        font-weight: 600;
        color: #333;
        text-align: center;
        border: 1px solid #f5c6d8;
        letter-spacing: 0.5px;
    }
    .subject-table td {
        padding: 13px 18px;
        border: 1px solid #f5c6d8;
        text-align: center;
        font-size: 14px;
    }
    .subject-table tbody tr { transition: background 0.2s; }
    .subject-table tbody tr:hover { background: rgba(255,255,255,0.5); }
    .course-id-badge {
        display: inline-block;
        background: #fff;
        border: 1px solid var(--pink-mid);
        border-radius: 20px;
        padding: 3px 14px;
        font-size: 13px;
        font-weight: 600;
        color: #c2185b;
    }
    .credit-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--pink-mid);
        border-radius: 50%;
        width: 32px; height: 32px;
        font-weight: 700;
        font-size: 14px;
        color: #880e4f;
    }

    /* MODAL */
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.55);
        backdrop-filter: blur(6px);
        overflow-y: auto;
        padding: 20px 0;
    }
    .modal-content {
        background: #F8E4E4;
        margin: 2% auto;
        padding: 35px;
        border-radius: 24px;
        width: 90%;
        max-width: 760px;
        position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        border: 2px solid var(--pink-mid);
        animation: modalFadeIn 0.35s ease;
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-16px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .modal-header {
        background: var(--pink-mid);
        padding: 18px 24px;
        border-radius: 16px;
        text-align: center;
        margin-bottom: 28px;
    }
    .modal-header h2 { margin: 0; font-family: 'Kanit', sans-serif; color: #333; letter-spacing: 1px; font-size: 1.3rem; }
    .close {
        position: absolute;
        right: 22px; top: 18px;
        font-size: 28px; font-weight: bold;
        cursor: pointer; color: #d81b60; transition: 0.2s;
        background: none; border: none;
    }
    .close:hover { color: #f44336; transform: scale(1.1); }

    /* FORM INSIDE MODAL */
    fieldset {
        border: 2px solid var(--pink-mid);
        border-radius: 16px;
        padding: 22px 24px;
        margin-bottom: 22px;
        background: rgba(255,255,255,0.6);
    }
    legend { font-weight: bold; color: #D81B60; padding: 0 12px; font-size: 16px; background: #F8E4E4; border-radius: 8px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 30px; }
    .info-grid p { margin: 0; font-size: 15px; color: #333; }
    .info-grid b { color: #555; }

    .modal .form-row { display: flex; flex-direction: column; align-items: flex-start; margin-bottom: 16px; width: 100%; }
    .modal label { font-weight: 600; margin-bottom: 6px; color: #444; font-size: 14px; }
    .modal input, .modal textarea, .modal select {
        width: 100%;
        padding: 11px 15px;
        border-radius: 12px;
        border: 1px solid #ddd;
        font-family: 'Prompt', sans-serif;
        font-size: 14px;
        background: #fff;
        transition: 0.2s;
        outline: none;
    }
    .modal input:focus, .modal textarea:focus, .modal select:focus {
        border-color: var(--pink-mid);
        box-shadow: 0 0 8px rgba(248,187,208,0.5);
    }
    .modal input[readonly] { background: #f5f5f5; color: #999; cursor: not-allowed; }
    .modal input[type="file"] { padding: 8px; background: #fff; border: 2px dashed #ddd; cursor: pointer; }
    .modal input[type="file"]:hover { border-color: var(--pink-mid); background: #fdf2f2; }

    .btn-submit {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #4CAF50, #388E3C);
        color: #fff;
        border: none;
        border-radius: 14px;
        cursor: pointer;
        font-size: 17px;
        font-weight: 700;
        font-family: 'Kanit', sans-serif;
        margin-top: 12px;
        transition: 0.3s;
        box-shadow: 0 4px 12px rgba(76,175,80,0.35);
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(76,175,80,0.4); }
    .btn-submit:active { transform: translateY(0); }
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

    <div class="page-wrapper">

    <div class="profile-card">
        <div class="profile-img-wrap">
            <img src="../homepage_pic/student_pic.png" class="cat-img" alt="profile">
            <p class="profile-welcome">ยินดีต้อนรับนะคะ</p>
        </div>
        <div class="info-group">
            <div class="form-row">
                <span>สวัสดีน้อง :</span>
                <span class="data-box" style="font-family:'Kanit';font-size:16px;font-weight:600;color:#333;"><?= htmlspecialchars($student['full_name'] ?? 'ไม่พบข้อมูล') ?></span>
            </div>
            <div class="form-row">
                <span>ชั้นปี :</span>
                <span class="data-box"><?= htmlspecialchars($student['year_level'] ?? '-') ?></span>
            </div>
            <div class="form-row">
                <span>สาขา :</span>
                <span class="data-box"><?= htmlspecialchars($student['major'] ?? '-') ?></span>
            </div>
            <div class="form-row">
                <span>อาจารย์ที่ปรึกษา :</span>
                <span class="data-box" style="color:#c2185b;font-weight:600;"><?= htmlspecialchars($student['advisor_name'] ?? $student['advisor'] ?? '-') ?></span>
            </div>
        </div>
    </div>

    <div class="course-section">
        <div class="course-section-title">📚 รายวิชาที่ลงทะเบียน</div>
        <table class="subject-table">
            <thead>
                <tr><th>รหัสวิชา</th><th>ชื่อวิชา</th><th>หน่วยกิต</th></tr>
            </thead>
            <tbody>
                <?php if ($courses): foreach ($courses as $row): ?>
                <tr>
                    <td><span class="course-id-badge"><?= htmlspecialchars($row['course_id']) ?></span></td>
                    <td style="text-align:left;padding-left:24px;"><?= htmlspecialchars($row['course_name']) ?></td>
                    <td><span class="credit-badge"><?= htmlspecialchars($row['credits']) ?></span></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3" style="color:#bbb;padding:24px;">ยังไม่มีข้อมูลการลงทะเบียน</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-bottom: 50px;">
        <h2 style="font-family:'Kanit';letter-spacing:2px;text-align:center;margin-bottom:24px;">ระบบฝึกงาน (INTERNSHIP)</h2>
        
        <?php if ($student['year_level'] < 4): ?>
        <div style="background:linear-gradient(135deg,#FFF9C4,#FFF3E0);border-radius:20px;padding:35px;text-align:center;box-shadow:0 5px 15px rgba(0,0,0,0.07);border-left:5px solid #FFB74D;">
            <div style="font-size:48px;margin-bottom:12px;">🔒</div>
            <h3 style="font-family:'Kanit';color:#E65100;margin-bottom:8px;">ระบบยังไม่เปิดใช้งาน</h3>
            <p style="color:#888;font-size:15px;">ระบบฝึกงานสำหรับนิสิตชั้นปีที่ 4 เท่านั้น</p>
            <p style="color:#bbb;font-size:13px;margin-top:8px;">คุณอยู่ชั้นปีที่ <?= $student['year_level'] ?></p>
        </div>

        <?php elseif (!$intern_request): ?>
        <div style="background:linear-gradient(135deg,#FFF0F5,#FCE4EC);border-radius:20px;padding:35px;text-align:center;box-shadow:0 5px 15px rgba(255,182,193,0.2);border-left:5px solid #F48FB1;">
            <div style="font-size:48px;margin-bottom:12px;">📋</div>
            <h3 style="font-family:'Kanit';color:#c2185b;margin-bottom:8px;">ยังไม่ได้ลงทะเบียนฝึกงาน</h3>
            <p style="color:#888;font-size:15px;margin-bottom:24px;">กรุณากรอกข้อมูลและแนบเอกสารเพื่อยื่นคำขอ</p>
            <button id="btnOpenModal" style="background:linear-gradient(135deg,#FF6B9D,#FF3D7F);color:white;border:none;padding:14px 45px;border-radius:30px;font-family:'Kanit';font-size:16px;font-weight:600;cursor:pointer;box-shadow:0 6px 20px rgba(255,61,127,0.35);">
                📝 ลงทะเบียนฝึกงาน
            </button>
        </div>

        <?php else:
            $s = $intern_request['status'];
            if ($s == 'ฝึกงานเสร็จสิ้น') {
                $icon='🎉'; $color='#1B5E20'; $bg='linear-gradient(135deg,#F1F8E9,#E8F5E9)'; $border='#66BB6A'; $bb='#A5D6A7'; $bc='#1B5E20'; $label='ฝึกงานเสร็จสิ้น';
            } elseif ($s == 'ออกใบส่งตัวแล้ว') {
                $icon='📄'; $color='#1565C0'; $bg='linear-gradient(135deg,#E8F4FD,#E3F2FD)'; $border='#64B5F6'; $bb='#90CAF9'; $bc='#1a237e'; $label='ออกใบส่งตัวแล้ว';
            } elseif ($s == 'อาจารย์ที่ปรึกษาอนุมัติ') {
                $icon='✅'; $color='#2E7D32'; $bg='linear-gradient(135deg,#F9FBE7,#F0F4C3)'; $border='#AED581'; $bb='#C5E1A5'; $bc='#33691E'; $label='อาจารย์ที่ปรึกษาอนุมัติ';
            } elseif ($s == 'ยกเลิก') {
                $icon='❌'; $color='#b71c1c'; $bg='linear-gradient(135deg,#FFEBEE,#FFCDD2)'; $border='#EF9A9A'; $bb='#FFCDD2'; $bc='#b71c1c'; $label='ยกเลิก';
            } else {
                $icon='🕐'; $color='#E65100'; $bg='linear-gradient(135deg,#FFF8E1,#FFF3E0)'; $border='#FFB74D'; $bb='#FFE082'; $bc='#E65100'; $label='รับเรื่องเข้าระบบ';
            }
        ?>
        <div style="background:<?= $bg ?>;border-radius:20px;padding:35px;box-shadow:0 5px 20px rgba(0,0,0,0.08);border-left:5px solid <?= $border ?>;">
            <div style="display:flex;align-items:center;gap:15px;margin-bottom:24px;">
                <span style="font-size:40px;"><?= $icon ?></span>
                <div>
                    <h3 style="font-family:'Kanit';color:<?= $color ?>;margin:0 0 4px;">ลงทะเบียนเรียบร้อยแล้ว</h3>
                    <span style="background:<?= $bb ?>;color:<?= $bc ?>;padding:4px 16px;border-radius:20px;font-size:13px;font-weight:600;"><?= $label ?></span>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div style="background:rgba(255,255,255,0.7);border-radius:14px;padding:16px 20px;">
                    <div style="font-size:12px;color:#999;margin-bottom:4px;">บริษัท</div>
                    <div style="font-family:'Kanit';font-size:16px;font-weight:600;color:#333;"><?= htmlspecialchars($intern_request['company_name'] ?? $intern_request['company_id']) ?></div>
                </div>
                <div style="background:rgba(255,255,255,0.7);border-radius:14px;padding:16px 20px;">
                    <div style="font-size:12px;color:#999;margin-bottom:4px;">ตำแหน่ง</div>
                    <div style="font-family:'Kanit';font-size:16px;font-weight:600;color:#333;"><?= htmlspecialchars($intern_request['position'] ?? '-') ?></div>
                </div>
                <?php if (!empty($intern_request['remark'])): ?>
                <div style="background:rgba(255,255,255,0.7);border-radius:14px;padding:16px 20px;grid-column:1/-1;">
                    <div style="font-size:12px;color:#999;margin-bottom:4px;">หมายเหตุจากอาจารย์</div>
                    <div style="color:#555;font-size:14px;"><?= htmlspecialchars($intern_request['remark']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($intern_request['pdf_path'])): ?>
                <div style="grid-column:1/-1;text-align:center;margin-top:8px;">
                    <a href="../uploads/<?= htmlspecialchars(basename($intern_request['pdf_path'])) ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:8px;background:#00BCD4;color:#fff;padding:10px 28px;border-radius:20px;text-decoration:none;font-size:14px;font-weight:600;">
                        📁 ดูเอกสาร PDF ที่แนบ
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($s != 'ฝึกงานเสร็จสิ้น' && $s != 'ยกเลิก'): ?>
            <div style="text-align:center;margin-top:20px;">
                <button id="btnOpenModal" style="background:rgba(255,255,255,0.8);color:#c2185b;border:2px solid #F48FB1;padding:10px 30px;border-radius:20px;font-family:'Prompt';font-size:14px;font-weight:600;cursor:pointer;">
                    ✏️ แก้ไขข้อมูล / เปลี่ยนเอกสาร
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    </div><!-- end page-wrapper -->

    <div id="internshipModal" class="modal">
    <div class="modal-content">
        <span class="close" id="btnCloseModal">&times;</span>
        <div class="modal-header">
            <h2>แบบฟอร์มลงทะเบียนฝึกงาน</h2>
        </div>    
        <form action="save_internship.php" method="POST" enctype="multipart/form-data">
            
            <fieldset>
                <legend>ข้อมูลส่วนตัวนิสิต</legend>
                <div class="info-grid">
                    <p><b>ชื่อ-นามสกุล:</b> <?php echo $student['full_name']; ?></p>
                    <p><b>ชั้นปี:</b> <?php echo $student['year_level']; ?></p>
                    <p><b>สาขา:</b> <?php echo $student['major']; ?></p>
                    <p><b>คณะ:</b> มนุษย</p>
                    <p><b>GPA:</b> <?php echo $student['gpa']; ?></p>
                    <p><b>เทอม/ปีการศึกษา:</b> 2 / 2566</p>
                </div>
            </fieldset>

            <fieldset>
                <legend>ช่องทางติดต่อ</legend>
                <div class="form-row">
                    <label>อีเมล:</label>
                    <input type="text" value="<?php echo $student['email']; ?>" readonly style="background:#eee;">
                </div>
                <div class="form-row">
                    <label>เบอร์โทรนิสิต:</label>
                    <input type="text" name="student_phone" value="<?php echo $student['phone']; ?>">
                </div>
                <div class="form-row">
                    <label>ที่อยู่ติดต่อ:</label>
                    <textarea name="student_address" rows="2" placeholder="กรอกที่อยู่ของคุณ"></textarea>
                </div>
            </fieldset>

            <fieldset>
                <legend>รายละเอียดของการฝึกงาน</legend>
                <div class="form-row">
                    <label>ชื่อบริษัท (เลือกหรือกรอกใหม่):</label>
                    <input type="text" name="company_name" id="company_name" list="company_list" onchange="autoFill(this.value)" required>
                    <datalist id="company_list">
                        <?php foreach ($all_companies as $com): ?>
                            <option value="<?php echo htmlspecialchars($com['name']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-row">
                    <label>ที่อยู่บริษัท:</label>
                    <textarea name="company_address" id="company_address" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <label>เบอร์บริษัท:</label>
                    <input type="text" name="company_phone" id="company_phone">
                </div>
                <div class="form-row">
                    <label>อีเมลบริษัท:</label>
                    <input type="email" name="company_email" id="company_email">
                </div>
                <div class="form-row">
                    <label>ตำแหน่งที่สมัคร:</label>
                    <input type="text" name="position" required>
                </div>
            </fieldset>

            <div class="form-row">
                <label>อัปโหลด PDF:</label>
                <input type="file" name="pdf_file" accept=".pdf" required>
            </div>

            <button type="submit" style="width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">ส่งข้อมูล</button>
        </form>
    </div>
</div>

<script>
// ข้อมูลบริษัทสำหรับ Auto-fill
const companyData = <?php echo json_encode($all_companies); ?>;

function autoFill(val) {
    const com = companyData.find(c => c.name === val);
    if (com) {
        document.getElementById('company_address').value = com.address || '';
        document.getElementById('company_phone').value = com.phone || '';
        document.getElementById('company_email').value = com.email || '';
    }
}

// ควบคุม Modal
const modal = document.getElementById("internshipModal");
const btnOpen = document.getElementById("btnOpenModal");
const btnClose = document.getElementById("btnCloseModal");

btnOpen.onclick = function() { modal.style.display = "block"; }
btnClose.onclick = function() { modal.style.display = "none"; }
window.onclick = function(event) {
    if (event.target == modal) { modal.style.display = "none"; }
}
</script>
</body>
</html>