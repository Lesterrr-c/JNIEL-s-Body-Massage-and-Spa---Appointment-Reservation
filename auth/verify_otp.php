<?php
session_start();
include "../db.php";

$message = "";

if (!isset($_SESSION['verify_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['verify_email'];

if (isset($_POST['verify'])) {

    $otp = trim($_POST['otp']);

    $query = mysqli_query($conn,
        "SELECT * FROM customers
        WHERE email='$email'");

    if(mysqli_num_rows($query) == 1){

        $customer = mysqli_fetch_assoc($query);

        // Check if OTP expired
        if(strtotime($customer['otp_expiry']) < time()){

            $message = "OTP has expired. Please request a new one.";

        }
        // Check OTP
        elseif($customer['otp_code'] == $otp){

            mysqli_query($conn,"
                UPDATE customers
                SET
                    is_verified = 1,
                    otp_code = NULL,
                    otp_expiry = NULL
                WHERE email='$email'
            ");

            unset($_SESSION['verify_email']);

            header("Location: login.php?verified=1");
            exit();

        }else{

            $message = "Invalid OTP.";

        }

    }else{

        $message = "Customer not found.";

    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Verification</title>

<link rel="stylesheet" href="../css/style.css">

</head>
<body>

<div class="container">

    <div class="register-box">

        <h2>Email Verification</h2>

        <p>
            We sent a 6-digit verification code to:
        </p>

        <strong><?php echo htmlspecialchars($email); ?></strong>

        <br><br>

        <?php if($message!=""){ ?>

            <p style="color:red; margin-bottom:15px;">
                <?php echo $message; ?>
            </p>

        <?php } ?>

        <form method="POST">

            <div class="input-group">

                <label>Verification Code</label>

                <input
                    type="text"
                    name="otp"
                    maxlength="6"
                    placeholder="Enter 6-digit OTP"
                    required>

            </div>

            <button
                type="submit"
                name="verify"
                class="btn">

                Verify Email

            </button>

        </form>

        <p class="login-link">

            Didn't receive the code?

            <a href="resend_otp.php">

                Resend OTP

            </a>

        </p>

    </div>

</div>

</body>
</html>