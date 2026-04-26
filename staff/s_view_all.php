<?php
session_start();
require_once '../includes/db_connect.php';

// 1. ตรวจสอบการ Login และสิทธิ์ (Role ต้องเป็น admin)
if (!isset($_SESSION['u_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// 2. ดึงข้อมูลเจ้าหน้าที่จากตาราง staffs
$stmt = $conn->prepare("SELECT * FROM staffs WHERE u_id = :u_id LIMIT 1");
$stmt->execute(['u_id' => $u_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. ดึงรายชื่อนิสิตทั้งหมดเพื่อมาแสดงในตาราง (อ้างอิงจากรูป Admin page.jpg)
$stmt_students = $conn->prepare("SELECT student_id, full_name FROM students ORDER BY student_id ASC");
$stmt_students->execute();
$student_list = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard - IS.SWU</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #fff; margin: 0; padding: 20px; }
        .header-nav { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .logout-btn { background-color: #FF3D3D; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }

        /* การ์ดสีส้มด้านบนตามรูป Admin page.jpg */
        .admin-card {
            background-color: #FFCC80; 
            border-radius: 20px;
            padding: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .admin-info h1 { margin: 0 0 20px 0; font-size: 32px; }
        .info-row { display: flex; align-items: center; margin-bottom: 15px; gap: 10px; }
        .label { font-weight: bold; font-size: 20px; min-width: 120px; }
        .data-box { background-color: #D9D9D9; padding: 10px 25px; border-radius: 15px; flex-grow: 1; min-width: 250px; }
        .cat-group-img { width: 250px; }

        /* ส่วนจัดการรายชื่อ */
        .management-section { max-width: 900px; margin: 40px auto; text-align: center; }
        .search-bar { display: flex; justify-content: center; gap: 10px; margin-bottom: 20px; }
        .input-search { padding: 10px; width: 300px; border-radius: 5px; border: 1px solid #ccc; }
        
        /* ตารางรายชื่อบุคลากร/นิสิต */
        .data-table { width: 100%; border-collapse: collapse; background-color: #F2E3E3; border-radius: 10px; overflow: hidden; }
        .data-table th { background-color: #E1BEE7; padding: 15px; border: 1px solid #ccc; }
        .data-table td { padding: 15px; border: 1px solid #ccc; text-align: center; height: 50px; }

        .btn-edit-doc {
            background-color: #FFC107;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <div class="header-nav">
        <a href="../logout.php"><button class="logout-btn">LOG OUT</button></a>
    </div>

    <div class="admin-card">
        <div class="admin-info">
            <h1>ยินดีต้อนรับค่ะ</h1>
            <div class="info-row">
                <span class="label">ชื่อ :</span>
                <span class="data-box"><?php echo htmlspecialchars($staff['first_name'] . " " . $staff['last_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">ตำแหน่งงาน :</span>
                <span class="data-box"><?php echo htmlspecialchars($staff['department']); ?></span>
            </div>
        </div>
        <img src="../picturc/cats_group.png" class="cat-group-img" alt="cats group">
    </div>

    <div class="management-section">
        <h2>รายชื่อบุคลากร</h2>
        <div class="search-bar">
            <input type="text" class="input-search" placeholder="ค้นหา">
            <button style="padding: 10px 20px;">All</button>
            <button style="padding: 10px 20px;">กรอง</button>
            <button style="padding: 10px 20px; background-color: #ddd;">+ เพิ่มรายชื่อใหม่</button>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%;">✍️ จัดการ</th>
                    <th style="width: 40%;">ชื่อ - สกุล</th>
                    <th style="width: 30%;">รหัสนิสิต</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($student_list as $row): ?>
                <tr>
                    <td><button>แก้ไข</button> <button>ลบ</button></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button class="btn-edit-doc">แก้ไข เอกสารฝึกงาน</button>
    </div>

</body>
</html>