<?php
session_start();
require_once '../includes/db_connect.php';

// 1. ตรวจสอบว่ามีการ Login หรือยัง และต้องเป็นบทบาท teacher (อาจารย์)
// แก้ไข: เช็ค u_id แทน username เพื่อให้ Error สีดำหายไป
if (!isset($_SESSION['u_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// 2. ดึงข้อมูลอาจารย์จากตาราง teachers โดยใช้ u_id
$stmt = $conn->prepare("SELECT * FROM teachers WHERE u_id = :u_id LIMIT 1");
$stmt->execute(['u_id' => $u_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงชื่ออาจารย์ไปใช้กรองนิสิตในที่ปรึกษา
$teacher_name = $teacher['first_name'];

// 3. ดึงสถิติตัวเลข (ดึงข้อมูลจริงจาก DB)
// รออนุมัติ
$stmt_p = $conn->prepare("SELECT COUNT(*) FROM internship_requests ir JOIN students s ON ir.student_id = s.student_id WHERE s.advisor = :name AND ir.status = 'รับคำขอ'");
$stmt_p->execute(['name' => $teacher_name]);
$count_pending = $stmt_p->fetchColumn();

// อนุมัติแล้ว
$stmt_a = $conn->prepare("SELECT COUNT(*) FROM internship_requests ir JOIN students s ON ir.student_id = s.student_id WHERE s.advisor = :name AND ir.status = 'อนุมัติ'");
$stmt_a->execute(['name' => $teacher_name]);
$count_approved = $stmt_a->fetchColumn();

// 4. ดึงรายชื่อนิสิตที่ปรึกษาทั้งหมด
$stmt_list = $conn->prepare("
    SELECT s.student_id, s.full_name, ir.status, ir.remark 
    FROM students s
    LEFT JOIN internship_requests ir ON s.student_id = ir.student_id
    WHERE s.advisor = :name
");
$stmt_list->execute(['name' => $teacher_name]);
$students = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - IS.SWU</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #fff; margin: 0; padding: 20px; }
        .header-bar { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .logout-btn { background-color: #FF3D3D; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }

        /* การ์ดสีเขียวด้านบน */
        .teacher-card {
            background-color: #C5E1A5; 
            border-radius: 20px;
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 40px;
            max-width: 850px;
            margin: 0 auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .cat-box { text-align: center; }
        .cat-img { width: 150px; }
        
        .info-group { flex-grow: 1; }
        .info-row { display: flex; align-items: center; margin-bottom: 15px; gap: 10px; }
        .label { font-weight: bold; font-size: 18px; min-width: 150px; }
        .data-box { background-color: #D9D9D9; padding: 8px 20px; border-radius: 10px; flex-grow: 1; min-height: 20px; }

        /* กล่องสถิติ */
        .stats-row { display: flex; justify-content: center; gap: 20px; margin: 40px 0; }
        .stat-card { width: 180px; padding: 20px; border-radius: 15px; text-align: center; font-weight: bold; }
        .bg-orange { background-color: #FFCC80; }
        .bg-blue { background-color: #90CAF9; }
        .bg-green { background-color: #A5D6A7; }
        .stat-num { font-size: 45px; display: block; margin-top: 10px; }

        /* ตารางนิสิต */
        .student-table { width: 100%; max-width: 900px; margin: 20px auto; border-collapse: collapse; background-color: #F3E5F5; border-radius: 10px; overflow: hidden; }
        .student-table th { background-color: #E1BEE7; padding: 15px; border: 1px solid #BDBDBD; }
        .student-table td { padding: 12px; border: 1px solid #BDBDBD; text-align: center; }

        .btn-page { background-color: #7986CB; color: white; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="header-bar">
        <a href="../index.html"><button class="logout-btn">LOG OUT</button></a>
    </div>

    <div class="teacher-card">
        <div class="cat-box">
            <img src="../picturc/pp.png" class="cat-img" alt="teacher-icon">
            <p><b>ยินดีต้อนรับนะคะ</b></p>
        </div>
        <div class="info-group">
            <h2 style="margin-top: 0;">สวัสดีนะคะ อาจารย์</h2>
            <div class="info-row">
                <span class="label">ชื่อ :</span>
                <span class="data-box"><?php echo htmlspecialchars($teacher['first_name'] . " " . $teacher['last_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">อาจารย์ที่ปรึกษาชั้นปี :</span>
                <span class="data-box">นิสิตในความดูแล (<?php echo count($students); ?> คน)</span>
            </div>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card bg-orange">
            รออนุมัติฝึกงาน
            <span class="stat-num"><?php echo $count_pending; ?></span>
        </div>
        <div class="stat-card bg-blue">
            เอกสารไม่ครบ
            <span class="stat-num">0</span>
        </div>
        <div class="stat-card bg-green">
            อนุมัติแล้ว
            <span class="stat-num"><?php echo $count_approved; ?></span>
        </div>
    </div>

    <h3 style="text-align: center;">รายชื่อนิสิตที่ปรึกษา</h3>
    <table class="student-table">
        <thead>
            <tr>
                <th>รหัสนิสิต</th>
                <th>ชื่อ - สกุล</th>
                <th>✅ สถานะฝึกงาน</th>
                <th>📄 เอกสารที่ส่ง</th>
                <th>✍️ หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['status'] ?? 'ยังไม่ดำเนินการ'); ?></td>
                <td><button>ดูไฟล์</button></td>
                <td><?php echo htmlspecialchars($row['remark'] ?? '-'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="display: flex; justify-content: space-between; max-width: 900px; margin: 30px auto;">
        <button class="btn-page">หน้าก่อนหน้า</button>
        <button class="btn-page">หน้าถัดไป</button>
    </div>

</body>
</html>