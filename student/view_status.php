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
        /* CSS เดิมของคุณที่ปรับแต่งแล้ว */
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #fff; 
            margin: 0; 
            padding: 20px; 
        }

        input, select, textarea, button, label {
            font-family: 'Prompt', sans-serif; 
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        h1, h2, h3, .logo {
            font-family: 'Kanit', sans-serif;
        }

        .logo {
            font-weight: bold;
            font-size: 24px;
            color: #333;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            font-size: 14px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-size: 16px;
            font-weight: 600;
            padding: 5px 0;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary-red);
            border-bottom: 2px solid var(--primary-red);
        }

        .login-btn {
            background-color: var(--primary-red);
            color: white;
            padding: 8px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .logo-img {
            height: 45px;
            width: auto;
            display: block;
        }

        .profile-card { 
            background-color: #F2E3E3; 
            border-radius: 20px; 
            padding: 30px; 
            display: flex; 
            align-items: center; 
            gap: 40px; 
            max-width: 800px; 
            margin: 0 auto; 
        }
        .cat-img { 
            width: 150px; 
        }
        .info-group { 
            display: flex; 
            flex-direction: column; 
            gap: 15px; 
            flex-grow: 1; 
        }
        .info-row { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .label { 
            font-weight: bold; 
            font-size: 18px; 
            min-width: 120px; 
        }
        .data-box { 
            background-color: #D9D9D9; 
            padding: 8px 20px; 
            border-radius: 15px; 
            flex-grow: 1; 
            font-size: 16px; 
            min-height: 20px; 
        }
        .subject-table { 
            width: 100%; 
            max-width: 800px; 
            margin: 30px auto; 
            border-collapse: collapse; 
            background-color: #FCE4EC; 
            border-radius: 15px; 
            overflow: hidden; 
        }
        .subject-table th, .subject-table td { 
            padding: 15px; 
            border: 1px solid #e1bee7; 
            text-align: center; 
        }
        .subject-table th { 
            background-color: #fce4ec; 
        }
        .btn-register { 
            background-color: #FFC107; 
            border: none; 
            padding: 15px 40px; 
            border-radius: 10px; 
            font-weight: bold; 
            font-size: 18px; 
            cursor: pointer; 
            display: block; 
            margin: 20px auto; 
        }
        .logout-btn { 
            background-color: #FF3D3D; 
            color: white; 
            border: none; 
            padding: 5px 15px; 
            border-radius: 5px; 
            float: right; 
            cursor: pointer; 
        }
    /* สไตล์ของ Modal */
        .modal {
            display: none;
            position: fixed; z-index: 1000; left: 0; top: 0;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
            height: 100%;
            background-color: rgba(0,0,0,0.6); /* ปรับให้เข้มขึ้นดูแพง */
            backdrop-filter: blur(3px); /* ทำพื้นหลังเบลอนิดๆ */
            overflow-y: auto;
        }

        /* ตัวกล่อง Popup */
        .modal-content {
            background-color: #F8E4E4; /* สีชมพูอ่อนตามรูป */
            margin: 5% auto; padding: 20px;
            padding: 30px;
            border-radius: 15px; width: 50%; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 850px; /* ขยายให้กว้างขึ้นตามรูป */
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            font-family: 'crossorigin', 'Kanit'; /* แนะนำให้ใช้ font สวยๆ */
        }

        .modal-header {
            background-color: #F8BBD0; 
            text-align: center; font-weight: bold; border-radius: 10px; margin-bottom: 10px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            justify-content: center; /* ดันเนื้อหาหลัก (h2) ไปไว้ตรงกลาง */
            position: relative;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            text-align: center; /* บังคับให้อยู่กลาง */
            flex-grow: 1;
        }

        /* หัวข้อ Popup */
        .modal-content h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }


        /* การจัดวาง Fieldset (กรอบข้อมูล) */
        fieldset {
            border: 1px solid #dee2e6;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            background-color: #f8f9fa;
        }

        legend {
            font-weight: bold;
            padding: 0 15px;
            color: #404245;
            font-size: 1.1rem;
            width: auto;
            float: none;
        }

        /* การจัดวางข้อมูลข้างในแบบ 2 คอลัมน์ */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* แบ่งครึ่งซ้ายขวา */
            gap: 15px;
        }

        .info-grid p {
            margin: 5px 0;
            font-size: 15px;
            color: #444;
        }

        .info-grid b {
            color: #333;
        }

        /* การจัดการ Row ของฟอร์ม */
        .form-row {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .form-row label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }

        .form-row input { 
            flex-grow: 1; 
            padding: 8px; 
            border-radius: 5px; 
            border: 1px 
            solid #ccc; 
        }

        .form-row textarea {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-row input:focus {
            border-color: #ced4da;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        /* ปุ่มกด */
        .save-btn {
            width: 100%;
            padding: 15px;
            background-color: #AED581; width: 100%; padding: 12px;
            border: none; border-radius: 10px; font-weight: bold; cursor: pointer;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }

        .save-btn:hover {
            background-color: #218838;
        }

        /* ปุ่มปิด (X) */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            line-height: 20px;
        }

        .close:hover {
            color: #000;
            text-decoration: none;
        }

        .close-btn { 
            float: right; 
            font-size: 28px; 
            cursor: pointer; 
        }
        .register-btn {
            background-color: #ff2a2a; /* สีแดงแบบ index */
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }
        .register-btn:hover { 
            background-color: #d42222; 
            transform: scale(1.05); 
        }
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