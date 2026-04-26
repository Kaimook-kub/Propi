<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['u_id'])) {
    $u_id = $_SESSION['u_id'];

    try {
        // 1. ดึง student_id ของนิสิตที่กำลังล็อกอินอยู่
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE u_id = :u_id LIMIT 1");
        $stmt->execute(['u_id' => $u_id]);
        $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student_data) {
            die("ไม่พบข้อมูลนิสิตในระบบ");
        }
        $student_id = $student_data['student_id'];

        // 2. รับค่าที่ส่งมาจากฟอร์ม
        $company_name = trim($_POST['company_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        
        // 3. จัดการข้อมูลบริษัท (เช็คว่ามีในระบบหรือยัง ถ้าไม่มีให้เพิ่มบริษัทใหม่)
        $stmt_comp = $conn->prepare("SELECT company_id FROM companies WHERE name = :name LIMIT 1");
        $stmt_comp->execute(['name' => $company_name]);
        $comp = $stmt_comp->fetch(PDO::FETCH_ASSOC);
        
        if ($comp) {
            // ถ้ามีบริษัทนี้แล้ว ดึง ID มาใช้
            $final_company_id = $comp['company_id'];
        } else {
            // ถ้าเป็นบริษัทใหม่ที่นิสิตพิมพ์เอง ให้สร้าง ID ใหม่และบันทึก
            $final_company_id = 'COMP' . rand(1000, 9999);
            $stmt_new_comp = $conn->prepare("INSERT INTO companies (company_id, name) VALUES (:cid, :cname)");
            $stmt_new_comp->execute(['cid' => $final_company_id, 'cname' => $company_name]);
        }

        // 4. จัดการอัปโหลดไฟล์ PDF
        $pdf_path = null;
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
            $file_ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
            if ($file_ext === 'pdf') {
                $new_filename = $student_id . '_' . time() . '.pdf';
                $upload_dir = '../uploads/';
                
                // ตรวจสอบว่ามีโฟลเดอร์ uploads หรือยัง ถ้ายังให้สร้าง
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // ย้ายไฟล์ไปยังโฟลเดอร์ uploads
                if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_dir . $new_filename)) {
                    $pdf_path = $new_filename;
                } else {
                    die("ข้อผิดพลาด: อัปโหลดไฟล์ไม่สำเร็จ กรุณาตรวจสอบว่ามีโฟลเดอร์ uploads อยู่จริง");
                }
            } else {
                die("ข้อผิดพลาด: ระบบรองรับเฉพาะไฟล์ .pdf เท่านั้น");
            }
        }

        // 5. บันทึกข้อมูลใบคำขอลงตาราง internship_requests
        // เช็คก่อนว่านิสิตคนนี้เคยส่งคำขอแล้วหรือยัง?
        $check_stmt = $conn->prepare("SELECT request_id FROM internship_requests WHERE student_id = :student_id LIMIT 1");
        $check_stmt->execute(['student_id' => $student_id]);
        
        if ($check_stmt->rowCount() > 0) {
            // กรณีเคยส่งแล้ว (เช่น อาจารย์ตีกลับให้แก้เอกสาร) -> ให้ UPDATE ข้อมูลเดิม
            $sql = "UPDATE internship_requests SET company_id = :company_id, position = :position, status = 'รับคำขอ', remark = NULL";
            // ถ้ามีการอัปโหลด PDF ใหม่ ให้บันทึกทับด้วย
            if ($pdf_path) {
                $sql .= ", pdf_path = :pdf_path";
            }
            $sql .= " WHERE student_id = :student_id";
            
            $update_stmt = $conn->prepare($sql);
            $params = [
                'company_id' => $final_company_id, 
                'position' => $position, 
                'student_id' => $student_id
            ];
            if ($pdf_path) {
                $params['pdf_path'] = $pdf_path;
            }
            $update_stmt->execute($params);
        } else {
            // กรณีส่งครั้งแรก -> ให้ INSERT สร้างข้อมูลใหม่
            $request_id = 'REQ' . rand(10000, 99999); // สุ่มรหัสคำขอเพื่อป้องกัน Error รหัสว่าง
            
            $sql = "INSERT INTO internship_requests (request_id, student_id, company_id, position, status, pdf_path) 
                    VALUES (:request_id, :student_id, :company_id, :position, 'รับคำขอ', :pdf_path)";
            $insert_stmt = $conn->prepare($sql);
            $insert_stmt->execute([
                'request_id' => $request_id,
                'student_id' => $student_id,
                'company_id' => $final_company_id,
                'position'   => $position,
                'pdf_path'   => $pdf_path
            ]);
        }

        // 6. เมื่อบันทึกเสร็จสมบูรณ์ ให้เด้งกลับไปที่หน้านิสิต
        header("Location: view_status.php?success=1");
        exit();

    } catch (PDOException $e) {
        die("เกิดข้อผิดพลาดฐานข้อมูล: " . $e->getMessage()); 
    }
} else {
    // ถ้าเข้ามาหน้านี้โดยไม่ได้กดปุ่ม submit ให้เด้งกลับ
    header("Location: view_status.php");
    exit();
}
?>