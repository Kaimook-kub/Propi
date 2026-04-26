<?php

session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: ../login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// 1. ดึงข้อมูลส่วนตัวนิสิต
$stmt = $conn->prepare("
    SELECT s.student_id, s.full_name, s.major, s.year_level, s.advisor, s.email, s.phone, s.gpa 
    FROM users u
    JOIN students s ON u.u_id = s.u_id
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
    /* --- 1. พื้นฐานและ Header --- */
    body { 
        font-family: 'Prompt', sans-serif; 
        background-color: #fdf2f2; /* ปรับพื้นหลังหลังหน้าจอให้นวลๆ เข้ากับธีม */
        margin: 0; 
        padding: 20px; 
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
    .logo-img { height: 50px; }
    .nav-links { display: flex; gap: 20px; }
    .nav-links a { text-decoration: none; color: #333; font-weight: 600; }
    .logout-btn { background-color: #FF3D3D; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; transition: 0.3s; }
    .logout-btn:hover { background-color: #e63535; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

    /* --- 2. Profile Card --- */
    .profile-card { 
        background-color: #F2E3E3; 
        border-radius: 20px; 
        padding: 30px; 
        display: flex; 
        align-items: center; 
        gap: 40px; 
        max-width: 800px; 
        margin: 0 auto 30px; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .cat-img { width: 120px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .info-group { display: flex; flex-direction: column; gap: 15px; flex-grow: 1; }
    .form-row { display: flex; align-items: center; gap: 10px; }
    .data-box { 
        background-color: #D9D9D9; 
        padding: 10px 25px; 
        border-radius: 20px; 
        font-size: 16px; 
        flex-grow: 1;
        display: block;
        color: #333;
    }

    /* --- 3. ตารางรายวิชา --- */
    .subject-table { 
        width: 100%; 
        max-width: 800px; 
        margin: 0 auto 30px; 
        border-collapse: collapse; 
        background-color: #FCE4EC; 
        border-radius: 15px; 
        overflow: hidden; 
        border: 1px solid #e1bee7;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .subject-table th, .subject-table td { 
        padding: 18px 15px; /* เพิ่มพื้นที่แนวตั้งนิดนึง */
        border: 1px solid #e1bee7; 
        text-align: center; 
    }
    .subject-table th { background-color: #F8BBD0; font-family: 'Kanit'; font-weight: 600; color: #333; }
    .subject-table tbody tr:hover { background-color: rgba(255,255,255,0.3); }

    /* --- 4. Modal (ส่วนที่เด้งขึ้นมา - แต่งให้สวยขึ้นโดยใช้สีเดิม) --- */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 2000; 
        left: 0; top: 0;
        width: 100%; height: 100%; 
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(8px); /* เพิ่มเบลอให้ดูพรีเมียมขึ้น */
        overflow-y: auto; 
        padding: 20px 0;
    }
    .modal-content {
        background-color: #F8E4E4;
        margin: 2% auto;
        padding: 35px;
        border-radius: 25px;
        width: 90%;
        max-width: 780px; /* ขยายกว้างนิดนึง */
        position: relative;
        box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        border: 2px solid #F8BBD0;
        animation: modalFadeIn 0.4s; /* เพิ่มแอนิเมชั่นเวลาเด้งขึ้นมา */
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .modal-header {
        background-color: #F8BBD0;
        padding: 20px;
        border-radius: 18px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }
    .modal-header h2 { margin: 0; font-family: 'Kanit'; color: #333; letter-spacing: 1px; }
    .close {
        position: absolute;
        right: 25px; top: 20px;
        font-size: 32px; font-weight: bold; cursor: pointer; color: #d81b60; transition: 0.3s;
    }
    .close:hover { color: #f44336; transform: scale(1.1); }

    /* --- 5. Form ภายใน Modal --- */
    fieldset {
        border: 2px solid #F8BBD0;
        border-radius: 18px;
        padding: 25px;
        margin-bottom: 25px;
        background: rgba(255, 255, 255, 0.6); /* ทำให้ดูละมุนขึ้น */
        box-shadow: inset 0 2px 5px rgba(0,0,0,0.02);
    }
    legend { 
        font-weight: bold; 
        color: #D81B60; 
        padding: 0 15px; 
        font-size: 18px; 
        background: #F8E4E4; /* ให้ Legend ลอยเหนือเส้นขอบสวยๆ */
        border-radius: 10px;
    }
    .info-grid { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 18px 35px; /* ปรับ gap ให้ดูสบายตา */
    }
    .info-grid p { margin: 0; font-size: 16px; color: #333; }
    .info-grid b { color: #555; }

    .modal .form-row { 
        display: flex;
        flex-direction: column; 
        align-items: flex-start; 
        margin-bottom: 18px; 
        width: 100%;
    }
    .modal label { font-weight: 600; margin-bottom: 7px; color: #444; font-size: 15px; }
    .modal input, .modal textarea {
        width: 100%; 
        padding: 12px 15px; 
        border-radius: 12px; 
        border: 1px solid #ddd;
        box-sizing: border-box;
        transition: 0.3s;
        background-color: #fff;
        font-size: 15px;
    }
    .modal input:focus, .modal textarea:focus {
        border-color: #F8BBD0;
        box-shadow: 0 0 8px rgba(248, 187, 208, 0.5);
        outline: none;
    }
    .modal input[readonly] { background-color: #f1f1f1; color: #777; cursor: not-allowed; }

    /* แต่งส่วนอัปโหลดไฟล์ */
    .modal input[type="file"] {
        padding: 8px;
        background: #fff;
        border: 2px dashed #ddd;
        cursor: pointer;
    }
    .modal input[type="file"]:hover { border-color: #F8BBD0; background-color: #fdf2f2; }

    .btn-submit {
        width: 100%; 
        padding: 16px; 
        background: #28a745; 
        color: white; 
        border: none; 
        border-radius: 15px; 
        cursor: pointer; 
        font-size: 19px;
        font-weight: bold;
        margin-top: 15px;
        transition: 0.3s;
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
        font-family: 'Kanit';
    }
    .btn-submit:hover { 
        background: #218838; 
        transform: translateY(-2px); 
        box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
    }
    .btn-submit:active { transform: translateY(0); box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3); }
</style>
</head>

     <header>
        <div class="logo">
            <a href="https://swu.ac.th/" target="_blank">
                <img src="../homepage_pic/SWU_Logo_TH_Color.png" alt="SWU" class="logo-img">
            </a>
        </div>
        <div class="nav-links">
            <a href="#home">หน้าแรก</a>
            <a href="#news">ประชาสัมพันธ์</a>
            <a href="#teacher">บุคลากร</a>
        </div>
        <a href="../index.html"><button class="logout-btn">LOG OUT</button></a>

    </header>

    <div class="profile-card">
        <div style="text-align: center;">
            <img src="../homepage_pic/student_pic.png" class="cat-img" alt="profile">
            <p><b>ยินดีต้อนรับนะคะ</b></p>
        </div>
        <div class="info-group" style="flex-grow: 1;">
            <div class="form-row"><span style="min-width:120px;">สวัสดีน้อง:</span> <span class="data-box"><?= htmlspecialchars($student['full_name'] ?? 'ไม่พบข้อมูล') ?></span></div>
            <div class="form-row"><span style="min-width:120px;">ชั้นปี:</span> <span class="data-box"><?= htmlspecialchars($student['year_level'] ?? '-') ?></span></div>
            <div class="form-row"><span style="min-width:120px;">สาขา:</span> <span class="data-box"><?= htmlspecialchars($student['major'] ?? '-') ?></span></div>
            <div class="form-row"><span style="min-width:120px;">อาจารย์ที่ปรึกษา:</span> <span class="data-box"><?= htmlspecialchars($student['advisor'] ?? '-') ?></span></div>
        </div>
    </div>

    <table class="subject-table">
        <thead>
            <tr style="background:#F8BBD0;"><th>รหัสวิชา</th><th>ชื่อวิชา</th><th>หน่วยกิต</th></tr>
        </thead>
        <tbody>
            <?php if ($courses): foreach ($courses as $row): ?>
                <tr><td><?= htmlspecialchars($row['course_id']) ?></td><td><?= htmlspecialchars($row['course_name']) ?></td><td><?= htmlspecialchars($row['credits']) ?></td></tr>
            <?php endforeach; else: ?>
                <tr><td colspan="3">ยังไม่มีข้อมูลการลงทะเบียน</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="container text-center" style="margin-bottom: 50px;">
        <h2 style="font-family: 'Kanit'; letter-spacing: 2px;">ระบบฝึกงาน (INTERNSHIP)</h2>
        
        <?php if ($student['year_level'] < 4): ?>
            <div class="alert alert-warning d-inline-block p-4 mt-3" style="border-radius: 15px;">
                <h4>สถานะนิสิตชั้นปีที่ <?= $student['year_level'] ?></h4>
                <p>ระบบจะเปิดให้ใช้งานสำหรับนิสิตชั้นปีที่ 4 เท่านั้น</p>
            </div>
        <?php else: ?>
            <?php if (!$intern_request): ?>
                <div class="alert alert-warning d-inline-block p-4 mt-3" style="border-radius: 15px;">
                <h4>สถานะนิสิตชั้นปีที่ <?= $student['year_level'] ?></h4>
                <p>ระบบฝึกงานสำหรับนิสิตชั้นปีที่ 4 </p>
                <button id="btnOpenModal" class="btn btn-danger btn-lg mt-3" id="btnOpenModal" style="padding: 15px 40px; border-radius: 12px; font-weight: bold;">
                    ลงทะเบียนฝึกงาน
                </button>
            <?php else: ?>
                <div class="alert alert-success d-inline-block p-4 mt-3" style="border-radius: 15px; text-align: left;">
                    <h4 class="text-success">✅ ลงทะเบียนเรียบร้อยแล้ว</h4>
                    <p><b>บริษัท:</b> <?= htmlspecialchars($intern_request['company_name'] ?? $intern_request['company_id']) ?></p>
                    <p><b>สถานะ:</b> <span class="badge bg-warning text-dark"><?= htmlspecialchars($intern_request['status']) ?></span></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

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
                    <p><b>คณะ:</b> วิศวกรรมศาสตร์</p>
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