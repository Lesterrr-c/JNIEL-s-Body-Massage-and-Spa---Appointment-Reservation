<?php
session_start();
include "../db.php";

$message = "";

if (!isset($_SESSION['register_data'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['register_data']['email'];

if (isset($_POST['verify'])) {

    $otp = trim($_POST['otp']);

    $check = mysqli_query($conn,
        "SELECT * FROM email_verification
        WHERE email='$email'
        AND otp='$otp'");

    if (mysqli_num_rows($check) == 1) {

        $data = $_SESSION['register_data'];

        $fullname = mysqli_real_escape_string($conn, $data['fullname']);
        $email = mysqli_real_escape_string($conn, $data['email']);
        $phone = mysqli_real_escape_string($conn, $data['phone']);
        $password = $data['password'];

        $sql = "INSERT INTO customers
                (full_name,email,phone_number,password)
                VALUES
                ('$fullname','$email','$phone','$password')";

        if(mysqli_query($conn,$sql)){

            mysqli_query($conn,
                "DELETE FROM email_verification
                WHERE email='$email'");

            unset($_SESSION['register_data']);

            header("Location: login.php?verified=1");
            exit();

        }else{

            $message = "Failed to create account.";

        }

    }else{

        $message = "Invalid OTP.";

    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Email</title>

<link rel="stylesheet" href="../css/style.css">

</head>
<body>

<div class="container">

    <div class="register-box">

        <h2>Email Verification</h2>

        <p>Enter the 6-digit OTP sent to</p>

        <strong><?php echo htmlspecialchars($email); ?></strong>

        <br><br>

        <?php
        if($message!=""){
            echo "<p class='message'>$message</p>";
        }
        ?>

        <form method="POST">

            <div class="input-group">

                <label>OTP Code</label>

                <input
                type="text"
                name="otp"
                maxlength="6"
                placeholder="Enter OTP"
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