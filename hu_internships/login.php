<?php
session_start();
require_once 'includes/db_connect.php'; // ✅ ไม่มี ../ เพราะ login.php อยู่ root
 
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
 
    $username = isset($_POST['u_id'])     ? trim($_POST['u_id']) : '';
    $password = isset($_POST['password']) ? $_POST['password']   : '';
    $role     = isset($_POST['role'])     ? $_POST['role']        : '';
 
    $stmt = $conn->prepare("SELECT * FROM users WHERE u_id = :u_id AND role = :role LIMIT 1");
    $stmt->execute(['u_id' => $username, 'role' => $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($user && $password === $user['password']) {
        $_SESSION['u_id']  = $user['u_id'];
        $_SESSION['role']  = $user['role'];
 
        // ✅ ไม่มี ../ เพราะ login.php อยู่ root
        if ($user['role'] == 'student') {
            header("Location: student/view_status.php");
        } elseif ($user['role'] == 'teacher') {
            header("Location: teacher/view_all.php");
        } elseif ($user['role'] == 'admin') {
            header("Location: staff/s_view_all.php");
        }
        exit();
 
    } else {
        $error = "User ID หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Login - IS.SWU</title>
    <style>
    body {
        background: linear-gradient(135deg, #FFF0F5 0%, #FDE7EF 50%, #FFD1DC 100%);
        font-family: 'Prompt', sans-serif;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        overflow: hidden;
    }
    .uni-header {
        position: absolute;
        top: 30px;
        right: 50px;
        display: flex;
        align-items: center;
        text-align: right;
        gap: 15px;
    }
    .uni-text { font-size: 14px; color: #333; font-weight: 500; }
    .main-container {
        display: flex;
        align-items: center;
        gap: 60px;
        width: 100%;
        max-width: 1100px;
        padding: 20px;
    }
    .login-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(8px);
        padding: 45px;
        border-radius: 50px;
        box-shadow: 0 20px 50px rgba(255, 182, 193, 0.4);
        width: 400px;
        z-index: 2;
        transition: transform 0.3s ease;
    }
    .login-header-flex {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 15px;
    }
    .login-header-flex h2 {
        font-family: 'Kanit', sans-serif;
        margin: 0; font-size: 28px; color: #333;
        letter-spacing: 1px;
    }
    .form-group { margin-bottom: 20px; }
    label { display: block; margin-bottom: 8px; margin-left: 15px; color: #C1A7A7; font-size: 14px; }
    select, input {
        box-sizing: border-box;
        width: 100%;
        padding: 14px 22px;
        border: 2px solid transparent;
        border-radius: 30px;
        background-color: #E9E2E4;
        font-size: 14px;
        font-family: 'Prompt', sans-serif;
        outline: none;
        transition: all 0.3s ease;
    }
    select:focus, input:focus {
        background-color: #fff;
        border-color: #F8BBD0;
        box-shadow: 0 0 15px rgba(248, 187, 208, 0.3);
    }
    .btn-login {
        width: 140px;
        padding: 10px;
        border: none;
        border-radius: 12px;
        background-color: #79F1B2;
        color: #333;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        display: block;
        margin: 30px auto 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .btn-login:hover {
        background-color: #58D68D;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(121, 241, 178, 0.4);
    }
    @keyframes float {
        0%   { transform: translateY(0px); }
        50%  { transform: translateY(-20px); }
        100% { transform: translateY(0px); }
    }
    .image-section { flex: 1; display: flex; justify-content: center; }
    .image-section img {
        width: 100%;
        max-width: 500px;
        height: auto;
        animation: float 4s ease-in-out infinite;
    }
    .forgot {
        display: block;
        text-align: center;
        color: #FF5252;
        text-decoration: none;
        font-size: 13px;
        font-weight: bold;
        transition: opacity 0.3s;
    }
    .forgot:hover { opacity: 0.7; }
    .error-msg { color: red; text-align: center; font-size: 13px; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="uni-header">
        <div class="uni-text">
            มหาวิทยาลัยศรีนครินทรวิโรฒ<br>
            (Srinakharinwirot University)
        </div>
        <img src="homepage_pic/Srinakharinwirot_Logo_EN_Black.png" alt="SWU Logo" style="width: 50px;">
    </div>

    <div class="main-container">

        <div class="login-card">
            <div class="login-header-flex">
                <img src="homepage_pic/signin.png" alt="Icon" style="width: 45px;">
                <div>
                    <h2>LOG IN</h2>
                    <p style="margin:0; color:#888; font-size:12px;">WELCOME TO IS.SWU</p>
                </div>
            </div>

            <?php if ($error) echo "<p class='error-msg'>" . htmlspecialchars($error) . "</p>"; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <select name="role" required>
                        <option value="" disabled selected>-- สถานะผู้ใช้งาน --</option>
                        <option value="student" <?php echo (($_POST['role'] ?? '') == 'student') ? 'selected' : ''; ?>>นิสิต (Student)</option>
                        <option value="teacher" <?php echo (($_POST['role'] ?? '') == 'teacher') ? 'selected' : ''; ?>>อาจารย์ (Teacher)</option>
                        <option value="admin"   <?php echo (($_POST['role'] ?? '') == 'admin')   ? 'selected' : ''; ?>>เจ้าหน้าที่ (Admin)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>User ID :</label>
                    <input type="text" name="u_id"
                           value="<?php echo htmlspecialchars($_POST['u_id'] ?? ''); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Password :</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="btn-login">LOG IN</button>
                <a href="#" class="forgot">Forget Password?</a>
            </form>
        </div>

        <div class="image-section">
            <img src="homepage_pic/picturc/login_pic.png" alt="3D Computer Illustration">
        </div>
    </div>

</body>
</html>