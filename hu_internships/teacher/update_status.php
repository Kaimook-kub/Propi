<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $remark = $_POST['remark'];

    try {
        $sql = "UPDATE internship_requests SET status = :status, remark = :remark WHERE request_id = :request_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'status' => $status,
            'remark' => $remark,
            'request_id' => $request_id
        ]);
        
        // บันทึกเสร็จแล้วเด้งกลับหน้าเดิม
        header("Location: view_all.php?update=success");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>