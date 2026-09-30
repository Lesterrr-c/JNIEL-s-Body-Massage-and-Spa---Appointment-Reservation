<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../db.php";

$customer_id = $_SESSION['customer_id'];
$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| GET CUSTOMER INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        customer_id,
        full_name,
        email,
        phone_number,
        created_at,
        is_verified
     FROM customers
     WHERE customer_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $customer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    session_destroy();

    header("Location: ../auth/login.php");
    exit();
}

$customer = mysqli_fetch_assoc($result);


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_profile'])) {

    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);

    if ($full_name === "") {

        $message = "Please enter your full name.";
        $message_type = "error";

    } else {

        $update = mysqli_prepare(
            $conn,
            "UPDATE customers
             SET full_name = ?,
                 phone_number = ?
             WHERE customer_id = ?"
        );

        mysqli_stmt_bind_param(
            $update,
            "ssi",
            $full_name,
            $phone_number,
            $customer_id
        );

        if (mysqli_stmt_execute($update)) {

            $message = "Your profile has been updated successfully.";
            $message_type = "success";

            // Update session name as well
            $_SESSION['customer_name'] = $full_name;

            // Update displayed values
            $customer['full_name'] = $full_name;
            $customer['phone_number'] = $phone_number;

        } else {

            $message = "Unable to update your profile.";
            $message_type = "error";
        }
    }
}


/*
|--------------------------------------------------------------------------
| INITIALS
|--------------------------------------------------------------------------
*/

$name_parts = explode(" ", trim($customer['full_name']));

$initials = "";

foreach ($name_parts as $part) {

    if ($part !== "") {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    if (strlen($initials) >= 2) {
        break;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">


<link rel="stylesheet"href="../css/customer.css">


</head>

<body>


<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">

    <div class="brand">

        <div class="brand-logo">

            <img
                src="../images/logo/logo.png"
                alt="JNIEL'S Logo"
            >

        </div>

        <div class="brand-text">

            <h2>JNIEL'S</h2>

            <span>
                Body Massage & Spa
            </span>

        </div>

    </div>


    <ul class="nav-links">

        <li>

            <a href="dashboard.php" class="active">
                <span>⌂</span>
                Dashboard
            </a>

        </li>

        <li>

            <a href="book_appointment.php" class="active">
                <span>＋</span>
                Book Appointment
            </a>

        </li>

        <li>

            <a href="my_appointments.php" class ="active">
                <span>▣</span>
                My Appointments
            </a>

        </li>

        <li>

            <a href="profile.php" class="active">
                <span>♙</span>
                Profile
            </a>

        </li>

        <li>

            <a
                href="../auth/logout.php"
                class="logout"
            >
                Logout
            </a>

        </li>

    </ul>

</nav>


<!-- =========================
     PROFILE PAGE
========================= -->

<div class="profile-page">


    <!-- PAGE HEADER -->

    <div class="profile-header">

        <div>

            <span class="profile-eyebrow">
                MY ACCOUNT
            </span>

            <h1>
                Profile
            </h1>

            <p>
                Manage your personal information and account details.
            </p>

        </div>

    </div>


    <?php if ($message !== ""): ?>

        <div class="profile-alert <?php echo $message_type; ?>">

            <span>
                <?php
                echo $message_type === "success"
                    ? "✓"
                    : "⚠";
                ?>
            </span>

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <div class="profile-layout">


        <!-- =========================
             PROFILE SUMMARY
        ========================== -->

        <section class="profile-summary">

            <div class="profile-avatar">

                <?php
                echo htmlspecialchars($initials);
                ?>

            </div>


            <h2>

                <?php
                echo htmlspecialchars(
                    $customer['full_name']
                );
                ?>

            </h2>


            <p class="profile-role">
                JNIEL'S Customer
            </p>


            <div class="verification-badge">

                <?php if ($customer['is_verified']): ?>

                    <span>✓</span>
                    Verified Account

                <?php else: ?>

                    <span>!</span>
                    Email Not Verified

                <?php endif; ?>

            </div>


            <div class="member-info">

                <span>
                    MEMBER SINCE
                </span>

                <strong>

                    <?php
                    echo date(
                        "F Y",
                        strtotime(
                            $customer['created_at']
                        )
                    );
                    ?>

                </strong>

            </div>

        </section>


        <!-- =========================
             PERSONAL INFORMATION
        ========================== -->

        <section class="profile-card">

            <div class="profile-card-header">

                <div>

                    <span>
                        PERSONAL INFORMATION
                    </span>

                    <h2>
                        Account Details
                    </h2>

                </div>

            </div>


            <form method="POST">


                <!-- FULL NAME -->

                <div class="profile-form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php
                        echo htmlspecialchars(
                            $customer['full_name']
                        );
                        ?>"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="profile-form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        value="<?php
                        echo htmlspecialchars(
                            $customer['email']
                        );
                        ?>"
                        disabled
                    >

                    <small>
                        Email address cannot be changed here.
                    </small>

                </div>


                <!-- PHONE -->

                <div class="profile-form-group">

                    <label for="phone_number">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        id="phone_number"
                        name="phone_number"
                        value="<?php
                        echo htmlspecialchars(
                            $customer['phone_number']
                        );
                        ?>"
                        placeholder="Enter your phone number"
                    >

                </div>


                <button
                    type="submit"
                    name="update_profile"
                    class="profile-save-button"
                >
                    Save Changes
                </button>


            </form>

        </section>


    </div>


    <!-- =========================
         SECURITY
    ========================== -->

    <section class="profile-security">

        <div>

            <span class="security-icon">
                🔒
            </span>

            <div>

                <span class="security-label">
                    ACCOUNT SECURITY
                </span>

                <h2>
                    Password
                </h2>

                <p>
                    Keep your account secure by using a strong password.
                </p>

            </div>

        </div>


        <a
            href="change_password.php"
            class="change-password-button"
        >
            Change Password →
        </a>

    </section>


</div>


</body>

</html>