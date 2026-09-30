<?php
session_start();
require_once "../db.php";

$message = "";

if(isset($_POST['login'])){

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT admin_id, full_name, password FROM admins WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1){

        $admin = mysqli_fetch_assoc($result);

        if(password_verify($password, $admin['password'])){

            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];

            header("Location: dashboard.php");
            exit();

        }else{
            $message = "Invalid password.";
        }

    }else{
        $message = "Admin not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login | JNIEL's</title>

<link rel="stylesheet" href="../css/admin-login.css" >

</head>

<body class="admin-login-page">
    <div class="admin-login-wrapper">

    <!--Brand Section-->
        <div class="admin-login-brand">
            <div class="admin-login-logo">
                <img src="../images/logo/logo.png" alt="JNIEL's Body Massage and Spa">
            </div>

            <div class="admin-brand-text">
                <h1>JNIEL's</h1>
                <span>Body Massage and Spa</span>   
            </div>

            <div class="admin-brand-line"></div>

            <p class="admin-brand-tagline"> 
                Relax. Refresh. Rejuvenate.
            </p>

        </div>

<!-- LOGIN CARD -->
        <div class="admin-login-card">
            <div class="admin-login-header">
                <span class="admin-login-label">
                    ADMIN PORTAL
                </span>

                <h2>
                    WELCOME BACK
                </h2>

                <p>
                    Sign in to manage your spa.
                </p>

                <?php if ($message !=""): ?>
                 
                <div class="admin-login-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>   
                
                <?php endif; ?>

                <form method="POST" class="admin-login-form">

                <!-- EMAIL -->
                     <div class="admin-form-group">
                        <label for="email">
                            Email Address
                        </label>

                        <div class="admin-input-wrapper">
                            <span class="admin-input-icon">
                                •
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Enter you Email" required 
                            >
                        </div>
                     </div>

                     <div class="admin-form-group">
                        <label>
                            Password
                        </label>

                        <div class="admin-input-wrapper">
                            <span class="admin-input-icon">
                                •
                            </span>


                            <input
                                type="password"
                                name="password"
                                placeholder="Enter you Password" required >

                        </div>
                     </div>


                        <!-- LOGIN -->
                        <button
                            type="submit"
                            name="login"
                            class="admin-login-button">
                        
                            <span>
                                Sign in
                            </span>

                            <span class="admin-login-arrow">
                                →
                            </span>
                        </button>
                </form>

                <div class="admin-login-footer">
                    <span>
                        JNIEL's Body Massage & Spa
                    </span>

                    <spa>
                        •
                    </spa>

                    <span>
                        Administration
                    </span>
                </div>
                <p class="back-home">

                <a href="../index.php">

                    ← Back to Home

                </a>

            </p>

            </div>
        </div> 
</body>
</html>







