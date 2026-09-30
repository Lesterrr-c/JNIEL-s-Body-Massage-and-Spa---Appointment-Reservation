<?php
session_start();
include "../db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM customers WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) == 1){

        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['password'])){
            if($user['is_verified'] != 1){

            $message = "Please verify your email before logging in.";

            }else{

            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['customer_name'] = $user['full_name'];

            header("Location: ../customer/dashboard.php");
            exit();
            }
        }else{

            $message = "Incorrect password.";

        }

    }else{

        $message = "Email not found.";

    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Customer Login</title>

<link rel="stylesheet" href="../css/style.css">

</head>

<body>

<div class="login-page">

    <!-- LEFT SIDE -->
    <div class="login-left">

        <div class="overlay">

            <h1>Relax. Refresh.<br>Rejuvenate.</h1>

            <p>
                Experience premium body massage and spa services
                designed to relax your body, refresh your mind,
                and restore your well-being.
            </p>

        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="login-right">

        <div class="login-card">

           <a href="../admin/login.php"> <img src="../images/logo/logo.png" class="login-logo"> </a>

            <h2>Customer Login</h2>

            <?php if($message != ""){ ?>

                <div class="message">

                    <?php echo $message; ?>

                </div>

            <?php } ?>

            <form method="POST">

                <div class="input-group">

                    <label>Email Address</label>

                    <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required>

                </div>

                <div class="input-group">

                    <label>Password</label>

                    <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required>

                </div>

                <button type="submit" class="login-btn">

                    Login

                </button>

            </form>

            <p class="login-link">

                Don't have an account?

                <a href="register.php">

                    Register Here

                </a>

            </p>

            <p class="back-home">

                <a href="../index.php">

                    ← Back to Home

                </a>

            </p>

        </div>

    </div>

</div>

</body>
</html>
