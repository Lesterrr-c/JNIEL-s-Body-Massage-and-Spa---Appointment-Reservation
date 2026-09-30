<?php
session_start();
include "../db.php";
require_once "../includes/send_otp.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    // Check passwords
    if ($password !== $confirmPassword) {

        $message = "Passwords do not match!";
        $message_type = "error";

    } else {

        // Check email
        $emailCheck = mysqli_prepare(
            $conn,
            "SELECT customer_id FROM customers WHERE email = ?"
        );

        mysqli_stmt_bind_param(
            $emailCheck,
            "s",
            $email
        );

        mysqli_stmt_execute($emailCheck);
        mysqli_stmt_store_result($emailCheck);

        // Check phone number
        $phoneCheck = mysqli_prepare(
            $conn,
            "SELECT customer_id FROM customers WHERE phone_number = ?"
        );

        mysqli_stmt_bind_param(
            $phoneCheck,
            "s",
            $phone
        );

        mysqli_stmt_execute($phoneCheck);
        mysqli_stmt_store_result($phoneCheck);

        if (mysqli_stmt_num_rows($emailCheck) > 0) {

            $message = "This email address is already registered.";
            $message_type = "error";

        } elseif (mysqli_stmt_num_rows($phoneCheck) > 0) {

            $message = "This phone number is already registered.";
            $message_type = "error";

        } else {

            // Hash password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert customer
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO customers
                (
                    full_name,
                    email,
                    phone_number,
                    password,
                    is_verified,
                    otp_code,
                    otp_expiry
                )
                VALUES (?, ?, ?, ?, 0, NULL, NULL)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $fullname,
                $email,
                $phone,
                $hashedPassword
            );

            if (mysqli_stmt_execute($stmt)) {

                // Send OTP
                if (sendOTP($email)) {

                    $_SESSION['verify_email'] = $email;

                    header("Location: verify_otp.php");
                    exit();

                } else {

                    // Remove account if OTP email failed
                    mysqli_query(
                        $conn,
                        "DELETE FROM customers WHERE email='$email'"
                    );

                    $message = "We couldn't send the verification email. Please try again.";
                    $message_type = "error";
                }

            } else {

                $message = "Registration failed. Please try again.";
                $message_type = "error";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($emailCheck);
        mysqli_stmt_close($phoneCheck);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | JNIEL'S Body Massage & Spa</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="register-page">

<div class="register-wrapper">

    <div class="register-card">

        <!-- LEFT SIDE -->
        <div class="register-brand">

            <div class="brand-logo">
                <img src="../images/logo/logo.png" alt="JNIEL'S Logo">
            </div>

            <h1>JNIEL'S</h1>

            <span>Body Massage & Spa</span>

            <div class="brand-line"></div>

            <h3>Relax. Refresh. Rejuvenate.</h3>

            <p>
                Create your account and experience
                a more convenient way to book your
                massage and spa appointments.
            </p>

            <div class="brand-info">
                <div>
                    <strong>✦</strong>
                    Professional Services
                </div>

                <div>
                    <strong>✦</strong>
                    Easy Appointment Booking
                </div>

                <div>
                    <strong>✦</strong>
                    Secure Customer Account
                </div>
            </div>

        </div>


        <!-- RIGHT SIDE -->
        <div class="register-form-area">

        <a href="../index.php" class="back-home"> ← Back to Home </a>
        
            <div class="form-heading">

                <span class="form-small-title">
                    WELCOME TO JNIEL'S
                </span>

                <h2>Create an Account</h2>

                <p>
                    Register to book your spa appointments.
                </p>

            </div>


            <!-- MESSAGE -->

            <?php if ($message != ""): ?>

                <div class="register-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <form action="" method="POST">

                <!-- FULL NAME -->

                <div class="input-group">

                    <label for="fullname">
                        Full Name
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">♙</span>

                        <input
                            type="text"
                            id="fullname"
                            name="fullname"
                            placeholder="Enter your full name"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="input-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">✉</span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                        >

                    </div>

                </div>


                <!-- PHONE -->

                <div class="input-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">☎</span>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            placeholder="09XXXXXXXXX"
                            maxlength="11"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="input-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">🔒</span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Create a password"
                            required
                        >

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="input-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">🔒</span>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            required
                        >

                    </div>

                </div>


                <!-- REGISTER BUTTON -->

                <button type="submit" class="register-submit">
                    Create My Account
                    <span>→</span>
                </button>

            </form>


            <div class="login-link">

                Already have an account?

                <a href="login.php">
                    Login
                </a>

            </div>

            <div class="secure-note">
                🔒 Your information is securely protected.
            </div>

        </div>

    </div>

</div>

</body>
</html>