<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบการเข้าถึงและ Session
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['u_id'])) {
    $u_id = $_SESSION['u_id'];
    
    try {
        // 1. ดึง student_id จาก u_id
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE u_id = :u_id");
        $stmt->execute(['u_id' => $u_id]);
        $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student_data) {
            die("ไม่พบข้อมูลนิสิตในระบบ");
        }
        $student_id = $student_data['student_id'];

        // 2. รับค่าจากฟอร์ม (ข้อมูลติดต่อ และ รายละเอียดบริษัท)
        $student_phone = $_POST['student_phone'] ?? '';
        $student_address = $_POST['student_address'] ?? '';
        
        $company_name = trim($_POST['company_name']);
        $company_address = $_POST['company_address'] ?? '';
        $company_phone = $_POST['company_phone'] ?? '';
        $company_email = $_POST['company_email'] ?? '';
        $position = $_POST['position'] ?? '';
        
        // 3. จัดการข้อมูลบริษัท (ถ้าไม่มีให้เพิ่มใหม่)
        $stmt_check = $conn->prepare("SELECT company_id FROM companies WHERE name = :name LIMIT 1");
        $stmt_check->execute(['name' => $company_name]);
        $existing_company = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_company) {
            $company_id = $existing_company['company_id'];
        } else {
            // สร้างบริษัทใหม่ พร้อมข้อมูลที่อยู่/เบอร์โทร/อีเมล ที่กรอกมา
            $company_id = "CORP" . time(); 
            $stmt_ins_com = $conn->prepare("INSERT INTO companies (company_id, name, address, phone, email) 
                                            VALUES (:id, :name, :addr, :phone, :email)");
            $stmt_ins_com->execute([
                'id' => $company_id,
                'name' => $company_name,
                'addr' => $company_address,
                'phone' => $company_phone,
                'email' => $company_email
            ]);
        }

        // 4. จัดการอัปโหลดไฟล์ PDF
        $pdf_path = ""; 
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
            $target_dir = __DIR__ . "/../uploads/";
            
            // สร้างโฟลเดอร์ uploads ถ้ายังไม่มี
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES["pdf_file"]["name"], PATHINFO_EXTENSION);
            $file_name = time() . "_internship_" . $student_id . "." . $file_extension;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $target_file)) {
                $pdf_path = $file_name; 
            }
        }

        // 5. บันทึกลงตาราง internship_requests
        // เช็คก่อนว่านิสิตเคยส่งแล้วหรือยัง
        $check = $conn->prepare("SELECT request_id FROM internship_requests WHERE student_id = :sid LIMIT 1");
        $check->execute(['sid' => $student_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // UPDATE ถ้าเคยส่งแล้ว
            $sql = "UPDATE internship_requests SET company_id=:company_id, position=:position, status='รับเรื่องเข้าระบบ', remark=NULL";
            if ($pdf_path) $sql .= ", pdf_path=:pdf_path";
            $sql .= " WHERE student_id=:student_id";
            $params = ['company_id'=>$company_id, 'position'=>$position, 'student_id'=>$student_id];
            if ($pdf_path) $params['pdf_path'] = $pdf_path;
            $conn->prepare($sql)->execute($params);
        } else {
            // INSERT ครั้งแรก
            $request_id = 'REQ' . strtoupper(uniqid());
            $sql = "INSERT INTO internship_requests (request_id, student_id, company_id, position, status, pdf_path, request_date) 
                    VALUES (:request_id, :student_id, :company_id, :position, 'รับเรื่องเข้าระบบ', :pdf_path, NOW())";
            $conn->prepare($sql)->execute([
                ':request_id' => $request_id,
                ':student_id' => $student_id,
                ':company_id' => $company_id,
                ':position'   => $position,
                ':pdf_path'   => $pdf_path
            ]);
        }

        // 6. อัปเดตเบอร์โทรนิสิตในตาราง students (ถ้ามีการกรอกมาใหม่)
        if (!empty($student_phone)) {
            $update_stmt = $conn->prepare("UPDATE students SET phone = :phone WHERE student_id = :sid");
            $update_stmt->execute(['phone' => $student_phone, 'sid' => $student_id]);
        }

        // สำเร็จ! ส่งกลับไปหน้าสถานะ
        header("Location: view_status.php?success=1");
        exit();

    } catch (PDOException $e) {
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
} else {
    echo "ไม่มีสิทธิ์เข้าถึงหน้านี้";
}
?>